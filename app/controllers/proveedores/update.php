<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

try {
  $id_proveedor     = input_int('id_proveedor', true);
  $nombre_proveedor = input_str('nombre_proveedor', 150, true);
  $celular          = input_str('celular', 30, false);
  $telefono         = input_str('telefono', 30, false);
  $empresa          = input_str('empresa', 150, false);
  $email            = input_email('email', false);
  $direccion        = input_str('direccion', 255, false);

  // No permitir editar si está inactivo? (opcional)
  // $estado = input_int('estado', false) ?? null;

  $fyh = isset($fechaHora) ? $fechaHora : date('Y-m-d H:i:s');

  $stmt = $pdo->prepare(
    "UPDATE tb_proveedores
        SET nombre_proveedor = :nombre,
            celular          = :celular,
            telefono         = :telefono,
            empresa          = :empresa,
            email            = :email,
            direccion        = :direccion,
            fyh_actualizacion = :fyh
      WHERE id_proveedor = :id"
  );

  $ok = $stmt->execute([
    ':nombre' => $nombre_proveedor,
    ':celular' => $celular,
    ':telefono' => $telefono,
    ':empresa' => $empresa,
    ':email' => $email,
    ':direccion' => $direccion,
    ':fyh' => $fyh,
    ':id' => $id_proveedor,
  ]);

  if (!$ok || $stmt->rowCount() < 1) {
    // rowCount puede ser 0 si no cambió nada; no lo tratamos como error.
    // Pero si el ID no existe, sí es error.
    $chk = $pdo->prepare("SELECT COUNT(*) c FROM tb_proveedores WHERE id_proveedor=:id");
    $chk->execute([':id'=>$id_proveedor]);
    $exists = (int)($chk->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    if ($exists < 1) throw new RuntimeException('Proveedor no encontrado.');
  }

  if (is_ajax_request()) {
    json_response(['ok'=>true]);
  }

  ensure_session();
  $_SESSION['mensaje'] = 'Proveedor actualizado correctamente';
  $_SESSION['icono']   = 'success';
  header('Location: ' . $URL . '/proveedores');
  exit;

} catch (Throwable $e) {
  if (is_ajax_request()) {
    json_response(['ok'=>false,'error'=>$e->getMessage()], 422);
  }
  ensure_session();
  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono']   = 'error';
  header('Location: ' . $URL . '/proveedores');
  exit;
}
