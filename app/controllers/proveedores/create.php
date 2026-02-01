<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

try {
  $nombre_proveedor = input_str('nombre_proveedor', 150, true);
  $celular          = input_str('celular', 30, false);
  $telefono         = input_str('telefono', 30, false);
  $empresa          = input_str('empresa', 150, false);
  $email            = input_email('email', false);
  $direccion        = input_str('direccion', 255, false);

  // Estado por defecto: 1 = activo
  $estado = 1;

  $fyh = isset($fechaHora) ? (string)$fechaHora : date('Y-m-d H:i:s');

  $sql = "INSERT INTO tb_proveedores (nombre_proveedor, celular, telefono, empresa, email, direccion, estado, fyh_creacion)
          VALUES (:nombre, :celular, :telefono, :empresa, :email, :direccion, :estado, :fyh)";
  $stmt = $pdo->prepare($sql);
  $ok = $stmt->execute([
    ':nombre'   => $nombre_proveedor,
    ':celular'  => $celular,
    ':telefono' => $telefono,
    ':empresa'  => $empresa,
    ':email'    => $email,
    ':direccion'=> $direccion,
    ':estado'   => $estado,
    ':fyh'      => $fyh,
  ]);
  if (!$ok) throw new RuntimeException('No se pudo registrar en la base de datos');

  if (is_ajax_request()) {
    json_response(['ok' => true, 'id_proveedor' => (int)$pdo->lastInsertId()]);
  }

  ensure_session();
  $_SESSION['mensaje'] = 'Proveedor registrado correctamente';
  $_SESSION['icono'] = 'success';
  header('Location: ' . $URL . '/proveedores');
  exit;

} catch (Throwable $e) {
  if (is_ajax_request()) json_response(['ok' => false, 'error' => $e->getMessage()], 422);

  ensure_session();
  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono'] = 'error';
  header('Location: ' . $URL . '/proveedores');
  exit;
}
