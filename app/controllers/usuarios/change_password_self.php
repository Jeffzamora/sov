<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

csrf_verify();
require_post();

// Sesión
if (empty($_SESSION['sesion_id_usuario'])) {
  redirect($URL . '/login', 'Debe iniciar sesión.', 'danger');
}

$id_usuario = (int)$_SESSION['sesion_id_usuario'];

$password_actual = input_str('password_actual', 255, true);
$password_nueva  = input_str('password_nueva', 255, true);
$password_repeat = input_str('password_repeat', 255, true);

require_password_min($password_nueva, 8);
if (!hash_equals($password_nueva, $password_repeat)) {
  redirect($URL . '/usuarios/password.php', 'Las contraseñas no coinciden.', 'warning');
}

try {
  // Traer hash actual
  $st = $pdo->prepare("SELECT password_user FROM tb_usuarios WHERE id_usuario = ? LIMIT 1");
  $st->execute([$id_usuario]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row || empty($row['password_user'])) {
    redirect($URL . '/usuarios/password.php', 'Usuario no encontrado.', 'danger');
  }

  $hashActual = (string)$row['password_user'];
  if (!password_verify($password_actual, $hashActual)) {
    redirect($URL . '/usuarios/password.php', 'La contraseña actual es incorrecta.', 'danger');
  }

  // Hash nuevo
  $hashNuevo = password_hash($password_nueva, PASSWORD_DEFAULT);

  // Rotar token: deja solo válida la sesión actual (invalida otras)
  $tokenRaw = (string)($_SESSION['sesion_token'] ?? '');
  $tokenHash = $tokenRaw !== '' ? hash('sha256', $tokenRaw) : '';

  $up = $pdo->prepare(
    "UPDATE tb_usuarios
        SET password_user = :p,
            token = :t,
            fyh_actualizacion = CURRENT_TIMESTAMP
      WHERE id_usuario = :id
      LIMIT 1"
  );
  $up->execute([
    ':p' => $hashNuevo,
    ':t' => $tokenHash,
    ':id' => $id_usuario,
  ]);

  redirect($URL . '/usuarios/password.php', 'Contraseña actualizada correctamente.', 'success');

} catch (Throwable $e) {
  error_log('[usuarios.change_password_self] ' . $e->getMessage());
  redirect($URL . '/usuarios/password.php', 'Error al actualizar contraseña.', 'danger');
}
