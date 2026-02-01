<?php
require_once __DIR__ . '/../config.php';
require_post();
csrf_verify();

try {
  $idRol = input_int('id_rol', true);
  $perms = $_POST['permisos'] ?? [];
  if (!is_array($perms)) $perms = [];

  $keys = [];
  foreach ($perms as $p) {
    if (!is_string($p)) continue;
    $p = strtolower(trim($p));
    if ($p === '') continue;
    if (!preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/', $p)) continue;
    $keys[$p] = true;
  }
  $keys = array_keys($keys);

  $pdo->beginTransaction();

  $pdo->prepare("DELETE FROM tb_roles_permisos WHERE id_rol=?")->execute([$idRol]);

  if ($keys) {
    $stFind = $pdo->prepare("SELECT id_permiso FROM tb_permisos WHERE clave=? LIMIT 1");
    $stIns  = $pdo->prepare("INSERT INTO tb_roles_permisos (id_rol, id_permiso) VALUES (?, ?)");
    for ($i=0; $i<count($keys); $i++) {
      $stFind->execute([$keys[$i]]);
      $idPerm = (int)$stFind->fetchColumn();
      if ($idPerm > 0) $stIns->execute([$idRol, $idPerm]);
    }
  }

  $pdo->commit();

  ensure_session();
  if ((int)($_SESSION['_perms_role'] ?? 0) === $idRol) {
    unset($_SESSION['_perms'], $_SESSION['_perms_role']);
  }

  if (is_ajax_request()) json_response(['ok'=>true]);
  redirect($URL.'/permisos', 'Permisos guardados.', 'success');
} catch(Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  if (is_ajax_request()) json_response(['ok'=>false,'error'=>$e->getMessage()], 422);
  redirect($URL.'/permisos', $e->getMessage() ?: 'No se pudo guardar.', 'danger');
}
