<?php
require_once __DIR__ . '/../config.php';
require_post();
csrf_verify();

try {
  $id = input_int('id_permiso', true);
  $clave = strtolower(trim(input_str('clave', 80, true)));
  $descripcion = input_str('descripcion', 150, true);

  if (!preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/', $clave)) {
    throw new RuntimeException('Clave inválida. Usa formato modulo.accion (ej: usuarios.crear)');
  }

  $st = $pdo->prepare("UPDATE tb_permisos SET clave=:c, descripcion=:d WHERE id_permiso=:id LIMIT 1");
  $st->execute([':c'=>$clave, ':d'=>$descripcion, ':id'=>$id]);

  if (is_ajax_request()) json_response(['ok'=>true]);
  redirect($URL.'/permisos', 'Permiso actualizado.', 'success');
} catch(Throwable $e) {
  if ($e instanceof PDOException && (string)$e->getCode()==='23000') $e = new RuntimeException('La clave ya existe.');
  if (is_ajax_request()) json_response(['ok'=>false,'error'=>$e->getMessage()], 422);
  redirect($URL.'/permisos', $e->getMessage() ?: 'No se pudo actualizar.', 'danger');
}
