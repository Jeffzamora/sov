<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

require_post();
csrf_verify();

// Solo ADMINISTRADOR (como pediste: “solo administrador usuario”)
if (function_exists('require_admin')) {
  require_admin($pdo, $URL . '/index.php');
} else {
  if (function_exists('require_perm')) {
    require_perm($pdo, 'usuarios.actualizar', $URL . '/index.php');
  }
}

try {
  ensure_session();

  $idSesion = (int)($_SESSION['sesion_id_usuario'] ?? 0);
  if ($idSesion <= 0) {
    redirect($URL . '/login', 'Sesión no válida.', 'danger');
  }

  $id_usuario      = input_int('id_usuario', true);
  $password_user   = input_str('password_user', 255, true);
  $password_repeat = input_str('password_repeat', 255, true);

  require_password_min($password_user, 8);
  if (!hash_equals($password_user, $password_repeat)) {
    throw new RuntimeException('Las contraseñas no coinciden.');
  }

  // Verifica existencia del usuario
  $st = $pdo->prepare("SELECT id_usuario FROM tb_usuarios WHERE id_usuario = ? LIMIT 1");
  $st->execute([$id_usuario]);
  if (!$st->fetchColumn()) {
    throw new RuntimeException('Usuario no encontrado.');
  }

  $hash = password_hash($password_user, PASSWORD_DEFAULT);
  if (!$hash) throw new RuntimeException('No se pudo procesar la contraseña.');

  // Rotar token para invalidar sesiones anteriores del usuario (recomendado)
  $newTokenRaw  = bin2hex(random_bytes(32));
  $newTokenHash = hash('sha256', $newTokenRaw);

  $stmt = $pdo->prepare("
    UPDATE tb_usuarios
       SET password_user = :p,
           token = :t,
           fyh_actualizacion = NOW()
     WHERE id_usuario = :id
     LIMIT 1
  ");
  $stmt->execute([
    ':p'  => $hash,
    ':t'  => $newTokenHash,
    ':id' => $id_usuario,
  ]);

  // Si el admin cambió su propia clave, actualiza su token en sesión para no “botarlo”
  if ($idSesion === (int)$id_usuario) {
    $_SESSION['sesion_token'] = $newTokenRaw;
  }

  if (function_exists('is_ajax_request') && is_ajax_request()) {
    json_response(['ok' => true, 'message' => 'Contraseña actualizada.'], 200);
  }

  $_SESSION['mensaje'] = "Contraseña actualizada";
  $_SESSION['icono']   = "success";
  header('Location: ' . $URL . '/usuarios/');
  exit;
} catch (Throwable $e) {
  $msg = $e->getMessage() ?: 'No se pudo actualizar la contraseña.';
  if ($e instanceof PDOException) {
    error_log('update password error: ' . $e->getMessage());
    $msg = 'No se pudo actualizar la contraseña.';
  }

  if (function_exists('is_ajax_request') && is_ajax_request()) {
    json_response(['ok' => false, 'error' => $msg], 422);
  }

  ensure_session();
  $_SESSION['mensaje'] = $msg;
  $_SESSION['icono']   = "error";
  header('Location: ' . $URL . '/usuarios/');
  exit;
}
