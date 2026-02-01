<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

if (function_exists('ensure_session')) {
    ensure_session();
} else {
    session_start();
}

$id = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE tb_usuarios SET token = NULL WHERE id_usuario = ? LIMIT 1");
        $stmt->execute([$id]);
    } catch (Throwable $e) {
        // no bloquear logout
        error_log('Logout token clear error: ' . $e->getMessage());
    }
}

// Limpia sesión
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        (bool)($params['secure'] ?? false),
        (bool)($params['httponly'] ?? true)
    );
}

session_destroy();

header('Location: ' . $URL . '/login');
exit;
