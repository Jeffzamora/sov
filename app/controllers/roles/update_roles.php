<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

ensure_session();

// Requiere sesión válida
if (empty($_SESSION['sesion_id_usuario'])) {
  if (is_ajax_request()) json_response(['ok' => false, 'error' => 'Sesión no válida.'], 401);
  if (function_exists('redirect')) redirect($URL . '/login', 'Debe iniciar sesión.', 'danger');
  header('Location: ' . $URL . '/login');
  exit;
}

// Seguridad: solo ADMIN o permiso roles.actualizar
if (function_exists('require_admin')) {
  require_admin($pdo, $URL . '/index.php');
} elseif (function_exists('require_perm')) {
  require_perm($pdo, 'roles.actualizar', $URL . '/index.php');
}

try {
  $id_rol = input_int('id_rol', true);
  $rol = input_str('rol', 50, true);

  // Normalización defensiva
  $rol = trim($rol);
  $rol = preg_replace('/\s+/', ' ', $rol);
  $rol = mb_strtoupper($rol, 'UTF-8');

  if (mb_strlen($rol) < 2) {
    throw new RuntimeException('El nombre del rol es demasiado corto.');
  }

  // Verifica que el rol exista
  $stmt = $pdo->prepare("SELECT id_rol FROM tb_roles WHERE id_rol = ? LIMIT 1");
  $stmt->execute([$id_rol]);
  if (!$stmt->fetchColumn()) {
    throw new RuntimeException('Rol no encontrado.');
  }

  // Evitar duplicado: otro rol con el mismo nombre
  $stmt = $pdo->prepare("SELECT id_rol FROM tb_roles WHERE UPPER(rol) = UPPER(?) AND id_rol <> ? LIMIT 1");
  $stmt->execute([$rol, $id_rol]);
  if ($stmt->fetchColumn()) {
    throw new RuntimeException('Ya existe otro rol con ese nombre.');
  }

  // Update
  $stmt = $pdo->prepare("
    UPDATE tb_roles
       SET rol = :rol,
           fyh_actualizacion = :fyh
     WHERE id_rol = :id
     LIMIT 1
  ");
  $stmt->execute([
    ':rol' => $rol,
    ':fyh' => date('Y-m-d H:i:s'),
    ':id'  => $id_rol,
  ]);

  // Si no afectó filas, puede ser porque no hubo cambios (no es error)
  // pero si quieres detectar “nada cambió” puedes hacerlo aquí.
  if ($stmt->rowCount() === 0) {
    // No es necesariamente error; lo tratamos como OK con mensaje neutral
    if (is_ajax_request()) json_response(['ok' => true, 'message' => 'Sin cambios.']);
    if (function_exists('redirect')) redirect($URL . '/roles/', 'Sin cambios (rol ya estaba actualizado).', 'info');
    $_SESSION['mensaje'] = 'Sin cambios (rol ya estaba actualizado).';
    $_SESSION['icono'] = 'info';
    header('Location: ' . $URL . '/roles/');
    exit;
  }

  if (is_ajax_request()) {
    json_response(['ok' => true, 'rol' => $rol]);
  }

  if (function_exists('redirect')) redirect($URL . '/roles/', 'Rol actualizado correctamente.', 'success');

  $_SESSION['mensaje'] = 'Rol actualizado correctamente.';
  $_SESSION['icono'] = 'success';
  header('Location: ' . $URL . '/roles/');
  exit;
} catch (Throwable $e) {
  // Manejo amigable de UNIQUE / duplicados
  if ($e instanceof PDOException) {
    $code = (string)($e->getCode() ?? '');
    if ($code === '23000') {
      $e = new RuntimeException('Ya existe otro rol con ese nombre.');
    }
  }

  if (is_ajax_request()) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 422);
  }

  if (function_exists('redirect')) redirect($URL . '/roles/', $e->getMessage(), 'danger');

  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono'] = 'error';
  header('Location: ' . $URL . '/roles/');
  exit;
}
