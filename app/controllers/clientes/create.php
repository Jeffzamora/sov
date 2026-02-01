<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

function is_ajax(): bool {
  return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

try {
  $nombre = input_str('nombre', 120, true);
  $apellido = input_str('apellido', 120, true);
  $tipo_documento = input_str('tipo_documento', 30, true);
  $numero_documento = input_str('numero_documento', 60, true);
  $celular = input_str('celular', 30, false);
  $email = input_email('email', true); // allow null
  $direccion = input_str('direccion', 255, false);

  // Evitar duplicados por documento
  $q = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE numero_documento = :nd LIMIT 1");
  $q->execute([':nd' => $numero_documento]);
  if ($q->fetch()) {
    throw new RuntimeException('Ya existe un cliente con ese número de documento.');
  }

  $stmt = $pdo->prepare("INSERT INTO tb_clientes
    (nombre, apellido, tipo_documento, numero_documento, celular, email, direccion, fyh_creacion, fyh_actualizacion)
    VALUES (:n, :a, :td, :nd, :c, :e, :d, NOW(), NOW())");
  $ok = $stmt->execute([
    ':n'=>$nombre, ':a'=>$apellido, ':td'=>$tipo_documento, ':nd'=>$numero_documento,
    ':c'=>$celular, ':e'=>$email, ':d'=>$direccion
  ]);

  if (!$ok) throw new RuntimeException('No se pudo registrar el cliente.');

  if (is_ajax()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>true]);
    exit;
  }

  ensure_session();
  $_SESSION['mensaje'] = 'Cliente registrado correctamente';
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
