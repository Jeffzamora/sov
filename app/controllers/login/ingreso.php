<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

if (!function_exists('is_ajax_request')) {
    function is_ajax_request(): bool {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
}

try {
    $email = input_email('email', true);
    $password_user = input_str('password_user', 255, true);

    $stmt = $pdo->prepare(
        "SELECT id_usuario, nombres, email, password_user, id_rol, estado
         FROM tb_usuarios
         WHERE email = :email
         LIMIT 1"
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $ok = false;
    if ($user && isset($user['password_user'])) {
        $ok = password_verify($password_user, (string)$user['password_user']);
    }

    if (!$ok || !$user) {
        throw new RuntimeException('Correo o contraseña incorrectos.');
    }

    if (isset($user['estado']) && strtoupper((string)$user['estado']) !== 'ACTIVO') {
        throw new RuntimeException('Usuario inactivo. Contacte al administrador.');
    }

    ensure_session();
    session_regenerate_id(true);

    // Token de sesión: se guarda HASH en BD y raw en sesión (no se guarda raw en BD)
    $tokenRaw = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $tokenRaw);

    $stmt = $pdo->prepare("UPDATE tb_usuarios SET token = :t WHERE id_usuario = :id LIMIT 1");
    $stmt->execute([
        ':t'  => $tokenHash,
        ':id' => (int)$user['id_usuario'],
    ]);

    $_SESSION['sesion_id_usuario'] = (int)$user['id_usuario'];
    $_SESSION['sesion_email'] = (string)$user['email'];
    $_SESSION['sesion_id_rol'] = (int)($user['id_rol'] ?? 0);
    $_SESSION['sesion_token'] = $tokenRaw;

    if (is_ajax_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'redirect' => $URL . '/index.php'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: ' . $URL . '/index.php');
    exit;

} catch (Throwable $e) {
    ensure_session();

    if (is_ajax_request()) {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION['mensaje'] = $e->getMessage();
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/login');
    exit;
}
