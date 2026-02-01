<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Solo CLI.\n");
}

$id = (int)($argv[1] ?? 0);
$new = (string)($argv[2] ?? '');

if ($id <= 0 || $new === '') {
    exit("Uso: php tools/reset_password.php <id_usuario> <nueva_password>\n");
}
if (strlen($new) < 8) {
    exit("La contraseña debe tener al menos 8 caracteres.\n");
}

$hash = password_hash($new, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE tb_usuarios SET password_hash = :h, fyh_actualizacion = NOW() WHERE id_usuario = :id LIMIT 1");
$stmt->bindValue(':h', $hash, PDO::PARAM_STR);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

echo "OK. Password actualizada para usuario ID {$id}\n";
