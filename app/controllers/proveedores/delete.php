<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

try {
  $id_proveedor = input_int('id_proveedor', true);
  $fyh = isset($fechaHora) ? $fechaHora : date('Y-m-d H:i:s');

  // Soft delete: estado=0
  $stmt = $pdo->prepare(
    "UPDATE tb_proveedores
        SET estado = 0,
            fyh_actualizacion = :fyh
      WHERE id_proveedor = :id"
  );
  $ok = $stmt->execute([':fyh'=>$fyh, ':id'=>$id_proveedor]);
  if(!$ok) throw new RuntimeException('No se pudo desactivar el proveedor.');

  // Validar existencia
  if($stmt->rowCount() < 1){
    $chk = $pdo->prepare("SELECT COUNT(*) c FROM tb_proveedores WHERE id_proveedor=:id");
    $chk->execute([':id'=>$id_proveedor]);
    $exists = (int)($chk->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    if ($exists < 1) throw new RuntimeException('Proveedor no encontrado.');
  }

  if (is_ajax_request()) json_response(['ok'=>true]);

  ensure_session();
  $_SESSION['mensaje'] = 'Proveedor desactivado';
  $_SESSION['icono'] = 'success';
  header('Location: ' . $URL . '/proveedores');
  exit;

} catch(Throwable $e){
  if (is_ajax_request()) json_response(['ok'=>false,'error'=>$e->getMessage()], 422);

  ensure_session();
  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono'] = 'error';
  header('Location: ' . $URL . '/proveedores');
  exit;
}
