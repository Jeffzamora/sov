<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

ensure_session();

// Sesión válida
if (empty($_SESSION['sesion_id_usuario'])) {
  if (is_ajax_request()) json_response(['ok' => false, 'error' => 'Sesión no válida.'], 401);
  if (function_exists('redirect')) redirect($URL . '/login', 'Debe iniciar sesión.', 'danger');
  header('Location: ' . $URL . '/login');
  exit;
}

// Seguridad: solo ADMIN o permiso roles.eliminar (o roles.desactivar)
if (function_exists('require_admin')) {
  require_admin($pdo, $URL . '/index.php');
} elseif (function_exists('require_perm')) {
  // ajusta la clave según tu catálogo de permisos
  require_perm($pdo, 'roles.eliminar', $URL . '/index.php');
}

try {
  $id_rol = input_int('id_rol', true);

  // 1) Existe el rol?
  $st = $pdo->prepare("SELECT id_rol, rol, UPPER(estado) AS estado FROM tb_roles WHERE id_rol = ? LIMIT 1");
  $st->execute([$id_rol]);
  $rolRow = $st->fetch(PDO::FETCH_ASSOC);

  if (!$rolRow) {
    throw new RuntimeException('Rol no encontrado.');
  }

  $rolNombre = trim((string)($rolRow['rol'] ?? ''));
  $estadoActual = strtoupper((string)($rolRow['estado'] ?? 'ACTIVO'));

  // 2) Bloquear ADMINISTRADOR (recomendado)
  if (mb_strtoupper($rolNombre, 'UTF-8') === 'ADMINISTRADOR') {
    throw new RuntimeException('No se puede desactivar el rol ADMINISTRADOR.');
  }

  // 3) Si ya está INACTIVO, OK idempotente
  if ($estadoActual === 'INACTIVO') {
    if (is_ajax_request()) json_response(['ok' => true, 'message' => 'El rol ya estaba inactivo.']);
    if (function_exists('redirect')) redirect($URL . '/roles/', 'El rol ya estaba inactivo.', 'info');
    $_SESSION['mensaje'] = 'El rol ya estaba inactivo.';
    $_SESSION['icono'] = 'info';
    header('Location: ' . $URL . '/roles/');
    exit;
  }

  // 4) Evitar desactivar roles en uso
  $st = $pdo->prepare("SELECT COUNT(*) FROM tb_usuarios WHERE id_rol = ?");
  $st->execute([$id_rol]);
  $count = (int)$st->fetchColumn();

  if ($count > 0) {
    throw new RuntimeException('No se puede desactivar: hay usuarios asignados a este rol.');
  }

  // 5) Soft delete: estado = INACTIVO
  $stmt = $pdo->prepare("
    UPDATE tb_roles
       SET estado = 'INACTIVO',
           fyh_actualizacion = :fyh
     WHERE id_rol = :id
     LIMIT 1
  ");
  $stmt->execute([
    ':fyh' => date('Y-m-d H:i:s'),
    ':id'  => $id_rol,
  ]);

  if ($stmt->rowCount() < 1) {
    throw new RuntimeException('No se pudo desactivar el rol.');
  }

  if (is_ajax_request()) json_response(['ok' => true]);

  if (function_exists('redirect')) redirect($URL . '/roles/', 'Rol desactivado.', 'success');

  $_SESSION['mensaje'] = 'Rol desactivado.';
  $_SESSION['icono'] = 'success';
  header('Location: ' . $URL . '/roles/');
  exit;
} catch (Throwable $e) {
  if (is_ajax_request()) json_response(['ok' => false, 'error' => $e->getMessage()], 422);

  if (function_exists('redirect')) redirect($URL . '/roles/', $e->getMessage(), 'danger');

  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono'] = 'error';
  header('Location: ' . $URL . '/roles/');
  exit;
}
