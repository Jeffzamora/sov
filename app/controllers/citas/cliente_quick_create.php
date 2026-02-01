<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

header('Content-Type: application/json; charset=utf-8');

try {
  $nombre = input_str('nombre', 120, true);
  $apellido = input_str('apellido', 120, true);
  $tipo_documento = input_str('tipo_documento', 30, true);
  $numero_documento = input_str('numero_documento', 60, true);
  $celular = input_str('celular', 30, false);
  $email = input_email('email', false); // allow null/empty
  $direccion = input_str('direccion', 255, false);

  // Duplicados por documento
  $q = $pdo->prepare("SELECT id_cliente, nombre, apellido, tipo_documento, numero_documento FROM tb_clientes WHERE numero_documento = :nd LIMIT 1");
  $q->execute([':nd' => $numero_documento]);
  if ($row = $q->fetch()) {
    throw new RuntimeException('Ya existe un cliente con ese número de documento.');
  }

  $stmt = $pdo->prepare("INSERT INTO tb_clientes (nombre, apellido, tipo_documento, numero_documento, celular, email, direccion, fyh_creacion, fyh_actualizacion)
                         VALUES (:n,:a,:td,:nd,:c,:e,:d,NOW(),NOW())");
  $ok = $stmt->execute([
    ':n'=>$nombre, ':a'=>$apellido, ':td'=>$tipo_documento, ':nd'=>$numero_documento,
    ':c'=>$celular, ':e'=>($email===''?null:$email), ':d'=>$direccion
  ]);
  if (!$ok) throw new RuntimeException('No se pudo crear el cliente.');

  $id = (int)$pdo->lastInsertId();

  echo json_encode([
    'ok'=>true,
    'cliente'=>[
      'id'=>$id,
      'nombre'=>trim($nombre.' '.$apellido),
      'doc'=>trim($tipo_documento.' '.$numero_documento)
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
