<?php
require_once __DIR__ . '/../config.php';
require_post();
csrf_verify();

try {
  $id = input_int('id_permiso', true);

  $st = $pdo->prepare("SELECT COUNT(*) FROM tb_roles_permisos WHERE id_permiso=?");
  $st->execute([$id]);
  if ((int)$st->fetchColumn() > 0) throw new RuntimeException('No se puede eliminar: el permiso está asignado a uno o más roles.');

  $st = $pdo->prepare("DELETE FROM tb_permisos WHERE id_permiso=? LIMIT 1");
  $st->execute([$id]);

  if (is_ajax_request()) json_response(['ok'=>true]);
  redirect($URL.'/permisos', 'Permiso eliminado.', 'success');
} catch(Throwable $e) {
  if (is_ajax_request()) json_response(['ok'=>false,'error'=>$e->getMessage()], 422);
  redirect($URL.'/permisos', $e->getMessage() ?: 'No se pudo eliminar.', 'danger');
}
