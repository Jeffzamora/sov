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
  // Puede venir vacío para menores
  $numero_documento = input_str('numero_documento', 60, false);
  $fecha_nacimiento = input_date('fecha_nacimiento', false);
  $celular = input_str('celular', 30, false);
  $email = input_email('email', false); // allow null/empty
  $direccion = input_str('direccion', 255, false);

  $tipo_documento = trim($tipo_documento);
  $numero_documento = trim($numero_documento);
  $fecha_nacimiento = trim($fecha_nacimiento);

  // Regla: si es menor por fecha, puede NO tener documento.
  // IMPORTANTE: NO forzamos tipo_documento = 'Menor' automáticamente.
  $isMenorByAge = false;
  if ($fecha_nacimiento !== '') {
    $edad = function_exists('age_years_from_date') ? age_years_from_date($fecha_nacimiento) : 0;
    if ($edad < 18) $isMenorByAge = true;
  }

  $tipoIsMenor = (strcasecmp($tipo_documento, 'Menor') === 0);
  if ($tipoIsMenor) {
    $tipo_documento = 'Menor';
  }

  // Si es cédula NIC: auto-detectar fecha de nacimiento desde el número si aplica.
  $isCedulaNic = (stripos($tipo_documento, 'cédula') !== false) || (stripos($tipo_documento, 'cedula') !== false) || (strcasecmp($tipo_documento, 'CED') === 0);
  if (!$tipoIsMenor && !$isMenorByAge && $numero_documento !== '' && $isCedulaNic && function_exists('nic_cedula_parse')) {
    $p = nic_cedula_parse($numero_documento);
    if ($p['ok'] ?? false) {
      // Si no mandaron fecha_nacimiento, la tomamos de la cédula
      if ($fecha_nacimiento === '') {
        $fecha_nacimiento = (string)$p['fecha_nacimiento'];
      } else {
        // Si mandaron DOB y no coincide, avisamos (evita inconsistencias)
        if ((string)$p['fecha_nacimiento'] !== $fecha_nacimiento) {
          throw new RuntimeException('La fecha de nacimiento no coincide con la cédula NIC.');
        }
      }
    }
  }

  // Documento requerido SOLO para mayores de edad, excepto cuando el usuario selecciona "Menor".
  if ($tipoIsMenor) {
    $numero_documento = null;
  } else {
    if ($numero_documento === '') {
      if ($isMenorByAge) {
        // Menor por fecha: permitimos guardar sin documento.
        $numero_documento = null;
      } else {
        throw new RuntimeException('El número de documento es requerido (o marca el cliente como Menor).');
      }
    }
  }

  // Evitar duplicados por documento (si aplica)
  if ($numero_documento !== null && $numero_documento !== '') {
    $q = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE numero_documento = :nd LIMIT 1");
    $q->execute([':nd' => $numero_documento]);
    if ($q->fetch()) {
      throw new RuntimeException('Ya existe un cliente con ese número de documento.');
    }
  }

  $stmt = $pdo->prepare("INSERT INTO tb_clientes
    (nombre, apellido, tipo_documento, numero_documento, fecha_nacimiento, celular, email, direccion, fyh_creacion, fyh_actualizacion)
    VALUES (:n, :a, :td, :nd, :fn, :c, :e, :d, NOW(), NOW())");
  $ok = $stmt->execute([
    ':n'=>$nombre, ':a'=>$apellido, ':td'=>$tipo_documento, ':nd'=>($numero_documento !== null && $numero_documento !== '' ? $numero_documento : null),
    ':fn'=>($fecha_nacimiento !== '' ? $fecha_nacimiento : null),
    ':c'=>$celular, ':e'=>($email===''?null:$email), ':d'=>$direccion
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
