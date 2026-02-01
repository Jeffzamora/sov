<?php
require_once __DIR__ . '/../config.php';
require_post();
csrf_verify();

try {
  $clave = strtolower(trim(input_str('clave', 80, true)));
  $descripcion = input_str('descripcion', 150, true);

  if (!preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/', $clave)) {
    throw new RuntimeException('Clave inválida. Usa formato modulo.accion (ej: usuarios.crear)');
  }

  $st = $pdo->prepare("INSERT INTO tb_permisos (clave, descripcion) VALUES (:c,:d)");
  $st->execute([':c'=>$clave, ':d'=>$descripcion]);

  if (is_ajax_request()) json_response(['ok'=>true,'id_permiso'=>(int)$pdo->lastInsertId()]);
  redirect($URL.'/permisos', 'Permiso creado.', 'success');
} catch(Throwable $e) {
  if ($e instanceof PDOException && (string)$e->getCode()==='23000') $e = new RuntimeException('La clave ya existe.');
  if (is_ajax_request()) json_response(['ok'=>false,'error'=>$e->getMessage()], 422);
  redirect($URL.'/permisos', $e->getMessage() ?: 'No se pudo crear.', 'danger');
}
