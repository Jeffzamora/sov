<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php'; // asegura sesión + RBAC cache (si aplica)

require_post();
csrf_verify();

// Permiso
require_admin($pdo, $URL . '/index.php');


try {
  $nombres = input_str('nombres', 120, true);
  $email = input_email('email', true);
  $email = strtolower(trim($email));

  // Compat: aceptar id_rol o rol
  $idRol = 0;
  if (isset($_POST['id_rol']) && $_POST['id_rol'] !== '') {
    $idRol = input_int('id_rol', true);
  } else {
    $idRol = input_int('rol', true);
  }

  $password_user   = input_str('password_user', 255, true);
  $password_repeat = input_str('password_repeat', 255, true);

  // Validaciones
  $nombres = trim($nombres);
  if ($nombres === '') {
    throw new RuntimeException('El nombre es requerido.');
  }

  require_password_min($password_user, 8);
  if (!hash_equals($password_user, $password_repeat)) {
    throw new RuntimeException('Las contraseñas no coinciden.');
  }

  // Validar rol existente (evita FK/errores silenciosos)
  $st = $pdo->prepare("SELECT 1 FROM tb_roles WHERE id_rol=? LIMIT 1");
  $st->execute([$idRol]);
  if (!$st->fetchColumn()) {
    throw new RuntimeException('Rol inválido.');
  }

  $hash = password_hash($password_user, PASSWORD_DEFAULT);
  if (!$hash) {
    throw new RuntimeException('No se pudo procesar la contraseña.');
  }

  $stmt = $pdo->prepare("
    INSERT INTO tb_usuarios (nombres, email, password_user, id_rol, estado)
    VALUES (:n, :e, :p, :r, 'ACTIVO')
  ");
  $stmt->execute([
    ':n' => $nombres,
    ':e' => $email,
    ':p' => $hash,
    ':r' => $idRol
  ]);

  $newId = (int)$pdo->lastInsertId();

  if (function_exists('is_ajax_request') && is_ajax_request()) {
    json_response([
      'ok' => true,
      'id_usuario' => $newId,
      'message' => 'Usuario registrado correctamente.'
    ], 200);
  }

  ensure_session();
  $_SESSION['mensaje'] = "Usuario registrado correctamente";
  $_SESSION['icono']   = "success";
  header('Location: ' . $URL . '/usuarios/');
  exit;
} catch (Throwable $e) {

  // Mensaje amigable para email duplicado
  if ($e instanceof PDOException) {
    $sqlState = (string)($e->getCode() ?? '');
    $errInfo  = $e->errorInfo ?? null;
    $driverCode = (is_array($errInfo) && isset($errInfo[1])) ? (int)$errInfo[1] : 0;

    // 23000 (integrity constraint) o 1062 (duplicate entry)
    if ($sqlState === '23000' || $driverCode === 1062) {
      $e = new RuntimeException('El correo ya está registrado. Use otro correo.');
    }
  }

  $msg = $e->getMessage() ?: 'No se pudo registrar el usuario.';

  if (function_exists('is_ajax_request') && is_ajax_request()) {
    json_response(['ok' => false, 'error' => $msg], 422);
  }

  ensure_session();
  $_SESSION['mensaje'] = $msg;
  $_SESSION['icono']   = "error";
  header('Location: ' . $URL . '/usuarios/');
  exit;
}
