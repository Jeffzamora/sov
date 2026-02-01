<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

function is_ajax(): bool {
  return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

try {
  $id_cliente = input_int('id_cliente');
  $nombre = input_str('nombre', 120, true);
  $apellido = input_str('apellido', 120, true);
  $tipo_documento = input_str('tipo_documento', 30, true);
  $numero_documento = input_str('numero_documento', 60, true);
  $celular = input_str('celular', 30, false);
  $email = input_email('email', true);
  $direccion = input_str('direccion', 255, false);

  $q = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE id_cliente = :id LIMIT 1");
  $q->execute([':id'=>$id_cliente]);
  if (!$q->fetch()) throw new RuntimeException('Cliente no encontrado.');

  // Duplicado documento (excluyendo el mismo)
  $q2 = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE numero_documento = :nd AND id_cliente <> :id LIMIT 1");
  $q2->execute([':nd'=>$numero_documento, ':id'=>$id_cliente]);
  if ($q2->fetch()) throw new RuntimeException('Ya existe otro cliente con ese número de documento.');

  $stmt = $pdo->prepare("UPDATE tb_clientes SET
    nombre=:n, apellido=:a, tipo_documento=:td, numero_documento=:nd, celular=:c, email=:e, direccion=:d,
    fyh_actualizacion=NOW()
    WHERE id_cliente=:id");
  $ok = $stmt->execute([
    ':n'=>$nombre, ':a'=>$apellido, ':td'=>$tipo_documento, ':nd'=>$numero_documento,
    ':c'=>$celular, ':e'=>$email, ':d'=>$direccion, ':id'=>$id_cliente
  ]);
  if (!$ok) throw new RuntimeException('No se pudo actualizar el cliente.');

  if (is_ajax()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>true]);
    exit;
  }

  ensure_session();
  $_SESSION['mensaje'] = 'Cliente actualizado correctamente';
  $_SESSION['icono'] = 'success';
  header('Location: '.$URL.'/clientes');
  exit;

} catch (Throwable $e) {
  if (is_ajax()) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    exit;
  }
  ensure_session();
  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono'] = 'error';
  header('Location: '.$URL.'/clientes');
  exit;
}
