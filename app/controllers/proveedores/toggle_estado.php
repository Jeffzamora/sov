<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

try {
  $id_proveedor = input_int('id_proveedor', true);
  $accion = strtolower(trim((string)($_POST['accion'] ?? '')));
  if (!in_array($accion, ['activar','desactivar'], true)) {
    throw new RuntimeException('Acción inválida.');
  }

  $estado = ($accion === 'activar') ? 1 : 0;
  $fyh = isset($fechaHora) ? $fechaHora : date('Y-m-d H:i:s');

  $stmt = $pdo->prepare(
    "UPDATE tb_proveedores
        SET estado = :estado,
            fyh_actualizacion = :fyh, CASE WHEN :estado = 0 THEN :fyh ELSE NULL END
      WHERE id_proveedor = :id"
  );
  $ok = $stmt->execute([':estado'=>$estado, ':fyh'=>$fyh, ':id'=>$id_proveedor]);
  if(!$ok) throw new RuntimeException('No se pudo actualizar el estado.');

  if (is_ajax_request()) json_response(['ok'=>true, 'estado'=>$estado]);

  ensure_session();
  $_SESSION['mensaje'] = ($estado === 1) ? 'Proveedor activado' : 'Proveedor desactivado';
  $_SESSION['icono'] = 'success';
  header('Location: '.$URL.'/proveedores');
  exit;

} catch(Throwable $e) {
  if (is_ajax_request()) json_response(['ok'=>false,'error'=>$e->getMessage()], 422);
  ensure_session();
  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono'] = 'error';
  header('Location: '.$URL.'/proveedores');
  exit;
}
