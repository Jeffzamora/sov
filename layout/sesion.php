<?php
/**
 * Middleware de sesión (protege páginas internas).
 *
 * - Valida que exista sesión.
 * - Valida token contra BD (tb_usuarios.token) para evitar sesiones robadas.
 * - Carga usuario/rol.
 * - Carga permisos RBAC (tb_permisos + tb_roles_permisos) y los cachea en sesión.
 *
 * Importante:
 * - Para endpoints AJAX/JSON, NO hacemos redirect HTML (evita "Unexpected token '<'").
 */

// Este archivo asume que el caller ya incluyó app/config.php.
// Aun así, por seguridad, intentamos incluirlo si no existe $pdo.
if (!isset($pdo) || !($pdo instanceof PDO)) {
    require_once __DIR__ . '/../app/config.php';
    // helpers de esquema (evita redefiniciones con guards)
    $schemaHelper = __DIR__ . '/../app/Helpers/db_schema.php';
    if (is_file($schemaHelper)) {
        require_once $schemaHelper;
    }
}

ensure_session();

/**
 * Si es petición AJAX/JSON devolvemos JSON 401/403 en lugar de HTML.
 */
if (!function_exists('sov_abort_auth')) {
    function sov_abort_auth(string $message, int $code = 401): void
    {
        $isAjax = function_exists('is_ajax_request') ? is_ajax_request() : false;
        if ($isAjax) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // fallback HTML
        redirect(($GLOBALS['URL'] ?? '') . '/login', $message, 'danger');
    }
}

// 1) Sesión mínima
if (empty($_SESSION['sesion_id_usuario']) || empty($_SESSION['sesion_token'])) {
    sov_abort_auth('Debe iniciar sesión.', 401);
}

$id_usuario_sesion = (int)$_SESSION['sesion_id_usuario'];
$tokenRaw = (string)$_SESSION['sesion_token'];
$tokenHash = hash('sha256', $tokenRaw);

// 2) Validación token + estado + rol
$stmt = $pdo->prepare(
    "SELECT u.id_usuario, u.nombres, u.email, u.id_rol, u.estado, u.token, r.rol
       FROM tb_usuarios u
       LEFT JOIN tb_roles r ON r.id_rol = u.id_rol
      WHERE u.id_usuario = ?
      LIMIT 1"
);
$stmt->execute([$id_usuario_sesion]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    $_SESSION = [];
    session_destroy();
    sov_abort_auth('Sesión inválida.', 401);
}

if (strtoupper((string)($usuario['estado'] ?? '')) !== 'ACTIVO') {
    $_SESSION = [];
    session_destroy();
    sov_abort_auth('Usuario inactivo. Contacte al administrador.', 403);
}

$tokenDb = (string)($usuario['token'] ?? '');
if ($tokenDb === '' || !hash_equals($tokenDb, $tokenHash)) {
    $_SESSION = [];
    session_destroy();
    sov_abort_auth('Sesión expirada. Inicie sesión nuevamente.', 401);
}

// Variables útiles
$email_sesion   = (string)($usuario['email'] ?? '');
$nombres_sesion = (string)($usuario['nombres'] ?? '');
$rol_sesion     = (string)($usuario['rol'] ?? '');
$id_rol_sesion  = (int)($usuario['id_rol'] ?? 0);

// Cache para UI
$_SESSION['sesion_email'] = $email_sesion;
$_SESSION['sesion_nombres'] = $nombres_sesion;
$_SESSION['sesion_rol_nombre'] = $rol_sesion;
$_SESSION['sesion_id_rol'] = $id_rol_sesion;

// 3) Cargar permisos RBAC (si existen tablas). Fallback: permitir todo si aún no migraste.
if (!function_exists('sov_table_exists')) {
    function sov_table_exists(PDO $pdo, string $table): bool
    {
        try {
            $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

$hasRbac = sov_table_exists($pdo, 'tb_permisos') && sov_table_exists($pdo, 'tb_roles_permisos');

if ($hasRbac) {
    $cachedRole = (int)($_SESSION['_perms_role'] ?? 0);
    if ($cachedRole !== $id_rol_sesion || !is_array($_SESSION['_perms'] ?? null)) {
        $_SESSION['_perms'] = load_role_perms($pdo, $id_rol_sesion);
        $_SESSION['_perms_role'] = $id_rol_sesion;
    }
} else {
    // Fallback seguro: deja operar mientras migras RBAC
    $_SESSION['_perms'] = ['*' => true];
    $_SESSION['_perms_role'] = $id_rol_sesion;

    if (!function_exists('ui_can')) {
        function ui_can(string $perm): bool { return true; }
    }
}

// Helper: solo admin por nombre de rol
if (!function_exists('require_admin')) {
    function require_admin(PDO $pdo, string $redirectTo): void
    {
        global $rol_sesion;
        $rol = strtoupper(trim((string)$rol_sesion));
        if ($rol !== 'ADMINISTRADOR') {
            if (function_exists('is_ajax_request') && is_ajax_request()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Acceso denegado: solo ADMINISTRADOR.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            redirect($redirectTo, 'Acceso denegado: solo ADMINISTRADOR.', 'danger');
        }
    }
}

// después de tener $id_usuario_sesion y el email del usuario (si lo tienes)
$uid = (int)($id_usuario_sesion ?? 0);
$uemail = (string)($email_usuario_sesion ?? '');

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

// variables de sesión MySQL (valen para esta conexión)
$pdo->exec("SET @app_user_id = {$uid}");
$pdo->exec("SET @app_user_email = " . $pdo->quote($uemail));
$pdo->exec("SET @app_ip = " . $pdo->quote($ip));
$pdo->exec("SET @app_ua = " . $pdo->quote($ua));
