<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

ensure_session();

// 1) Requiere sesión
if (empty($_SESSION['sesion_id_usuario'])) {
  if (is_ajax_request()) json_response(['ok' => false, 'error' => 'Sesión no válida.'], 401);
  if (function_exists('redirect')) redirect($URL . '/login', 'Debe iniciar sesión.', 'danger');
  header('Location: ' . $URL . '/login');
  exit;
}

// 2) Seguridad: solo ADMIN o permiso roles.crear
// - Si ya tienes RBAC, esto debe existir.
if (function_exists('require_admin')) {
  require_admin($pdo, $URL . '/index.php');
} elseif (function_exists('require_perm')) {
  require_perm($pdo, 'roles.crear', $URL . '/index.php');
} else {
  // Si aún no tienes middleware, al menos bloquea por nombre de rol en sesión si lo tienes.
  // Ajusta si tu sesión guarda algo como $_SESSION['sesion_rol'] o similar.
}

try {
  // 3) Validación + normalización
  $rol = input_str('rol', 50, true);
  $rol = trim($rol);
  $rol = preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/', $rol);       // colapsa espacios
  $rol = mb_strtoupper($rol, 'UTF-8');           // normaliza

  if (mb_strlen($rol) < 2) {
    throw new RuntimeException('El nombre del rol es demasiado corto.');
  }

  // 4) Evitar duplicados (pre-check)
  $stmt = $pdo->prepare("SELECT id_rol FROM tb_roles WHERE UPPER(rol) = UPPER(?) LIMIT 1");
  $stmt->execute([$rol]);
  if ($stmt->fetchColumn()) {
    throw new RuntimeException('Ese rol ya existe.');
  }

  // 5) Insert
  $stmt = $pdo->prepare("
    INSERT INTO tb_roles (rol, fyh_creacion)
    VALUES (:rol, :fyh)
  ");
  $ok = $stmt->execute([
    ':rol' => $rol,
    ':fyh' => date('Y-m-d H:i:s'),
  ]);

  if (!$ok) throw new RuntimeException('No se pudo registrar el rol.');

  $id = (int)$pdo->lastInsertId();

  if (is_ajax_request()) {
    json_response(['ok' => true, 'id_rol' => $id, 'rol' => $rol]);
  }

  $_SESSION['mensaje'] = 'Rol registrado correctamente.';
  $_SESSION['icono'] = 'success';

  if (function_exists('redirect')) redirect($URL . '/roles/', 'Rol registrado correctamente.', 'success');
  header('Location: ' . $URL . '/roles/');
  exit;
} catch (Throwable $e) {
  // Mensaje amigable si existe UNIQUE y chocó
  if ($e instanceof PDOException) {
    $code = (string)($e->getCode() ?? '');
    if ($code === '23000') {
      $e = new RuntimeException('Ese rol ya existe.');
    }
  }

  if (is_ajax_request()) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 422);
  }

  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono'] = 'error';

  if (function_exists('redirect')) redirect($URL . '/roles/', $e->getMessage(), 'danger');
  header('Location: ' . $URL . '/roles/');
  exit;
}
