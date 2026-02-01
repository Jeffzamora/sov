<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

// Solo ADMINISTRADOR (según tu política)
require_admin($pdo, $URL . '/index.php');

// Requiere sesión válida (por si alguien pega directo el endpoint)
ensure_session();
if (!isset($_SESSION['sesion_id_usuario'])) {
  if (is_ajax_request()) json_response(['ok' => false, 'error' => 'Sesión no válida.'], 401);
  redirect($URL . '/login', 'Sesión no válida.', 'danger');
}

try {
  $id_usuario = input_int('id_usuario', true);
  if ($id_usuario <= 0) {
    throw new RuntimeException('ID de usuario inválido.');
  }

  $nombres = input_str('nombres', 120, true);
  $email   = input_email('email', true);
  $id_rol  = input_int('id_rol', true); // en tu UI este select manda el id_rol

  // Contraseña (opcional)
  $password_user   = trim((string)($_POST['password_user'] ?? ''));
  $password_repeat = trim((string)($_POST['password_repeat'] ?? ''));
  $willUpdatePassword = ($password_user !== '' || $password_repeat !== '');

  // Normalización
  $nombres = trim($nombres);
  $email   = strtolower(trim($email));

  // Verifica que exista usuario
  $st = $pdo->prepare("SELECT id_usuario FROM tb_usuarios WHERE id_usuario = ? LIMIT 1");
  $st->execute([$id_usuario]);
  if (!$st->fetchColumn()) {
    throw new RuntimeException('Usuario no encontrado.');
  }

  // Validar email duplicado (excluyendo el mismo usuario)
  $st = $pdo->prepare("SELECT id_usuario FROM tb_usuarios WHERE email = ? AND id_usuario <> ? LIMIT 1");
  $st->execute([$email, $id_usuario]);
  if ($st->fetchColumn()) {
    throw new RuntimeException('El correo ya está registrado. Use otro correo.');
  }

  // (Opcional) validar que el rol exista y esté activo, si tu tabla tiene estado
  $st = $pdo->prepare("SELECT id_rol FROM tb_roles WHERE id_rol = ? LIMIT 1");
  $st->execute([$id_rol]);
  if (!$st->fetchColumn()) {
    throw new RuntimeException('Rol inválido.');
  }

  $params = [
    ':n'   => $nombres,
    ':e'   => $email,
    ':r'   => $id_rol,
    ':fyh' => date('Y-m-d H:i:s'),
    ':id'  => $id_usuario,
  ];

  $setExtra = '';
  if ($willUpdatePassword) {
    require_password_min($password_user, 8);
    if (!hash_equals($password_user, $password_repeat)) {
      throw new RuntimeException('Las contraseñas no coinciden.');
    }

    $hash = password_hash($password_user, PASSWORD_DEFAULT);
    if (!$hash) throw new RuntimeException('No se pudo procesar la contraseña.');

    // Rotar token para invalidar sesiones anteriores (recomendado)
    $newTokenRaw  = bin2hex(random_bytes(32));
    $newTokenHash = hash('sha256', $newTokenRaw);

    $setExtra = ", password_user = :p, token = :t";
    $params[':p'] = $hash;
    $params[':t'] = $newTokenHash;

    // Si el admin cambió su propia clave, actualiza su token en sesión
    $idSesion = (int)($_SESSION['sesion_id_usuario'] ?? 0);
    if ($idSesion === (int)$id_usuario) {
      $_SESSION['sesion_token'] = $newTokenRaw;
    }
  }

  $stmt = $pdo->prepare("
    UPDATE tb_usuarios
       SET nombres = :n,
           email   = :e,
           id_rol  = :r,
           fyh_actualizacion = :fyh
           {$setExtra}
     WHERE id_usuario = :id
     LIMIT 1
  ");

  $stmt->execute($params);

  if ($stmt->rowCount() < 1) {
    // Puede ser “sin cambios”; no es necesariamente error
    // Decide tu UX: yo lo trato como OK.
  }

  $okMsg = $willUpdatePassword ? 'Usuario y contraseña actualizados.' : 'Usuario actualizado.';

  if (is_ajax_request()) {
    json_response(['ok' => true, 'message' => $okMsg], 200);
  }

  redirect($URL . '/usuarios/', $okMsg, 'success');
} catch (Throwable $e) {
  $msg = $e->getMessage() ?: 'No se pudo actualizar el usuario.';

  if ($e instanceof PDOException) {
    error_log('update_usuario error: ' . $e->getMessage());
    $msg = 'No se pudo actualizar el usuario.';
  }

  if (is_ajax_request()) json_response(['ok' => false, 'error' => $msg], 422);

  redirect($URL . '/usuarios/', $msg, 'danger');
}
