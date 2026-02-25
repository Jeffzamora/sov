<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'examenes.crear', $URL . '/');

require_post();
csrf_verify();

// Helpers locales para exámenes: permiten decimales con signo y nulos cuando no son requeridos,
// y lanzan excepciones (NO hacen exit()) para poder responder JSON consistente.
function exam_date(string $key, bool $required = true): string {
  $v = $_POST[$key] ?? '';
  $v = is_string($v) ? trim($v) : '';
  if ($v === '') {
    if ($required) throw new RuntimeException("Fecha requerida: {$key}");
    return '';
  }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
    throw new RuntimeException("Fecha inválida: {$key}");
  }
  return $v;
}

function exam_decimal(string $key, bool $required = true): ?string {
  $v = $_POST[$key] ?? null;
  if ($v === null || $v === '') {
    if ($required) throw new RuntimeException("Campo requerido: {$key}");
    return null;
  }
  $v = trim((string)$v);
  $v = str_replace(',', '.', $v);
  // Permite signo negativo y hasta 2 decimales
  if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $v)) {
    throw new RuntimeException("Campo inválido (decimal): {$key}");
  }
  return number_format((float)$v, 2, '.', '');
}

function exam_int(string $key, bool $required = true): ?int {
  $v = $_POST[$key] ?? null;
  if ($v === null || $v === '') {
    if ($required) throw new RuntimeException("Campo requerido: {$key}");
    return null;
  }
  $v = trim((string)$v);
  if (!preg_match('/^-?\d+$/', $v)) {
    throw new RuntimeException("Campo inválido (int): {$key}");
  }
  return (int)$v;
}

try {
  $id_cliente = input_int('id_cliente');
  // Fecha de examen: se muestra bloqueada en UI, pero se guarda (input hidden/readonly)
  $fecha_examen = exam_date('fecha_examen', true);

  // Campos opcionales (DECIMAL/INT)
  $od_esfera = exam_decimal('od_esfera', false);
  $od_cilindro = exam_decimal('od_cilindro', false);
  $od_eje = exam_int('od_eje', false);
  $od_add = exam_decimal('od_add', false);
  $od_prisma = exam_decimal('od_prisma', false);
  $od_base = input_str('od_base', 12, false);

  $oi_esfera = exam_decimal('oi_esfera', false);
  $oi_cilindro = exam_decimal('oi_cilindro', false);
  $oi_eje = exam_int('oi_eje', false);
  $oi_add = exam_decimal('oi_add', false);
  $oi_prisma = exam_decimal('oi_prisma', false);
  $oi_base = input_str('oi_base', 12, false);

  $pd_lejos = exam_decimal('pd_lejos', false);
  $pd_cerca = exam_decimal('pd_cerca', false);

  $notas = input_str('notas_optometrista', 2000, false);

  // Nota: En muchos flujos de óptica el examen puede guardarse de forma parcial
  // y completarse más tarde. Por eso NO forzamos campos mínimos excepto fecha/id_cliente.
  // Si tu operación requiere obligatorios, se pueden volver a habilitar.
  $od_base = $od_base ? strtolower(trim($od_base)) : null;
  $oi_base = $oi_base ? strtolower(trim($oi_base)) : null;
  $validBases = [null, 'in', 'out', 'up', 'down'];
  if (!in_array($od_base, $validBases, true)) { throw new RuntimeException('Base OD inválida. Use IN/OUT/UP/DOWN.'); }
  if (!in_array($oi_base, $validBases, true)) { throw new RuntimeException('Base OI inválida. Use IN/OUT/UP/DOWN.'); }

  // Validaciones mínimas
  
  if ($od_eje !== null && ($od_eje < 0 || $od_eje > 180)) {
    throw new RuntimeException('Eje OD debe estar entre 0 y 180.');
  }
  if ($oi_eje !== null && ($oi_eje < 0 || $oi_eje > 180)) {
    throw new RuntimeException('Eje OI debe estar entre 0 y 180.');
  }

  // Insert
  $stmt = $pdo->prepare("
    INSERT INTO tb_examenes_optometricos
      (id_cliente, id_usuario, fecha_examen,
       od_esfera, od_cilindro, od_eje, od_add, od_prisma, od_base,
       oi_esfera, oi_cilindro, oi_eje, oi_add, oi_prisma, oi_base,
       pd_lejos, pd_cerca, notas_optometrista)
    VALUES
      (:id_cliente, :id_usuario, :fecha_examen,
       :od_esfera, :od_cilindro, :od_eje, :od_add, :od_prisma, :od_base,
       :oi_esfera, :oi_cilindro, :oi_eje, :oi_add, :oi_prisma, :oi_base,
       :pd_lejos, :pd_cerca, :notas)
  ");
  $stmt->execute([
    ':id_cliente' => $id_cliente,
    ':id_usuario' => $id_usuario_sesion ?? null,
    ':fecha_examen' => $fecha_examen,

    ':od_esfera' => $od_esfera,
    ':od_cilindro' => $od_cilindro,
    ':od_eje' => $od_eje,
    ':od_add' => $od_add,
    ':od_prisma' => $od_prisma,
    ':od_base' => $od_base ?: null,

    ':oi_esfera' => $oi_esfera,
    ':oi_cilindro' => $oi_cilindro,
    ':oi_eje' => $oi_eje,
    ':oi_add' => $oi_add,
    ':oi_prisma' => $oi_prisma,
    ':oi_base' => $oi_base ?: null,

    ':pd_lejos' => $pd_lejos,
    ':pd_cerca' => $pd_cerca,
    ':notas' => $notas ?: null,
  ]);

  $id_examen = (int)$pdo->lastInsertId();

  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'id_examen' => $id_examen, 'redirect' => $URL . '/clientes/examenes/show.php?id_examen=' . $id_examen]);
    exit;
  }

  ensure_session();
  $_SESSION['mensaje'] = 'Examen registrado correctamente.';
  $_SESSION['icono'] = 'success';
  header('Location: ' . $URL . '/clientes/show.php?id=' . $id_cliente);
  exit;

} catch (Throwable $e) {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  if ($isAjax) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
  }
  ensure_session();
  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono'] = 'error';
  $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
  header('Location: ' . $URL . '/clientes/examenes/new.php?id=' . $id_cliente);
  exit;
}