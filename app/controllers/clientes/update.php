<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

function is_ajax(): bool {
  return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

try {
  $id_cliente = input_int('id_cliente');
  $nombre = format_person_name(input_str('nombre', 120, true));
  $apellido = format_person_name(input_str('apellido', 120, true));
  $tipo_documento = input_str('tipo_documento', 30, true);
  $numero_documento = input_str('numero_documento', 60, false);
  $fecha_nacimiento = input_date('fecha_nacimiento', false);
  $celular = input_phone('celular', false);
  $email = input_email('email', false); // allow null/empty
  $direccion = input_str('direccion', 255, false);

  $tipo_documento = trim($tipo_documento);
  $numero_documento = trim($numero_documento);
  $fecha_nacimiento = trim($fecha_nacimiento);

  $isMenorByAge = false;
  if ($fecha_nacimiento !== '') {
    $edad = function_exists('age_years_from_date') ? age_years_from_date($fecha_nacimiento) : 0;
    if ($edad < 18) $isMenorByAge = true;
  }
  $tipoIsMenor = (strcasecmp($tipo_documento, 'Menor') === 0);
  if ($tipoIsMenor) {
    $tipo_documento = 'Menor';
  }

  // Normalizar documento (sin guiones/espacios) para consistencia
  if ($numero_documento !== '') {
    if (function_exists('nic_cedula_normalize') && (
      (stripos($tipo_documento, 'cédula') !== false) || (stripos($tipo_documento, 'cedula') !== false) || (strcasecmp($tipo_documento, 'CED') === 0)
    )) {
      $numero_documento = nic_cedula_normalize($numero_documento);
    } else {
      $numero_documento = doc_normalize_simple($numero_documento);
    }
  }

  $isCedulaNic = (stripos($tipo_documento, 'cédula') !== false) || (stripos($tipo_documento, 'cedula') !== false) || (strcasecmp($tipo_documento, 'CED') === 0);
  if (!$tipoIsMenor && !$isMenorByAge && $numero_documento !== '' && $isCedulaNic && function_exists('nic_cedula_parse')) {
    $p = nic_cedula_parse($numero_documento);
    if ($p['ok'] ?? false) {
      if ($fecha_nacimiento === '') {
        $fecha_nacimiento = (string)$p['fecha_nacimiento'];
      } else {
        if ((string)$p['fecha_nacimiento'] !== $fecha_nacimiento) {
          throw new RuntimeException('La fecha de nacimiento no coincide con la cédula NIC.');
        }
      }
    } else {
      throw new RuntimeException($p['error'] ?? 'Formato de cédula NIC inválido.');
    }
  }

  if ($tipoIsMenor) {
    $numero_documento = null;
  } else {
    if ($numero_documento === '') {
      if ($isMenorByAge) {
        $numero_documento = null;
      } else {
        throw new RuntimeException('El número de documento es requerido (o marca el cliente como Menor).');
      }
    }
  }

  $q = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE id_cliente = :id LIMIT 1");
  $q->execute([':id'=>$id_cliente]);
  if (!$q->fetch()) throw new RuntimeException('Cliente no encontrado.');

  // Duplicado documento (excluyendo el mismo) si aplica
  if ($numero_documento !== null && $numero_documento !== '') {
    $q2 = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE numero_documento = :nd AND id_cliente <> :id LIMIT 1");
    $q2->execute([':nd'=>$numero_documento, ':id'=>$id_cliente]);
    if ($q2->fetch()) throw new RuntimeException('Ya existe otro cliente con ese número de documento.');
  }

  $stmt = $pdo->prepare("UPDATE tb_clientes SET
    nombre=:n, apellido=:a, tipo_documento=:td, numero_documento=:nd, fecha_nacimiento=:fn, celular=:c, email=:e, direccion=:d,
    fyh_actualizacion=NOW()
    WHERE id_cliente=:id");
  $ok = $stmt->execute([
    ':n'=>$nombre, ':a'=>$apellido, ':td'=>$tipo_documento, ':nd'=>($numero_documento !== null && $numero_documento !== '' ? $numero_documento : null),
    ':fn'=>($fecha_nacimiento !== '' ? $fecha_nacimiento : null),
    ':c'=>$celular, ':e'=>($email===''?null:$email), ':d'=>$direccion, ':id'=>$id_cliente
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
  $friendly = pdo_exception_user_message($e);
  if ($friendly) {
    $e = new RuntimeException($friendly);
  }
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
