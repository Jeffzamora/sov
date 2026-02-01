<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Helpers/db_schema.php';

header('Content-Type: application/json; charset=utf-8');

try {
  if (!sov_table_exists($pdo, 'tb_citas')) {
    echo json_encode(['ok'=>true,'events'=>[]]);
    exit;
  }

  // FullCalendar v6 envía startStr/endStr en ISO 8601 con segundos y zona horaria.
  // Nuestro validador input_date() no acepta offsets, así que aquí normalizamos a YYYY-MM-DD.
  $startRaw = isset($_GET['start']) && is_string($_GET['start']) ? trim($_GET['start']) : '';
  $endRaw   = isset($_GET['end']) && is_string($_GET['end']) ? trim($_GET['end']) : '';
  if ($startRaw === '' || $endRaw === '') {
    throw new RuntimeException('Parámetros requeridos: start, end');
  }
  $startDate = substr($startRaw, 0, 10);
  $endDate   = substr($endRaw, 0, 10);
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    throw new RuntimeException('Rango de fechas inválido.');
  }

  $events = [];

  // Citas
  $q = $pdo->prepare(
    "SELECT c.id_cita, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado,
            cli.nombre, cli.apellido
     FROM tb_citas c
     INNER JOIN tb_clientes cli ON cli.id_cliente = c.id_cliente
     WHERE c.fecha >= :s AND c.fecha <= :e"
  );
  $q->execute([':s'=>$startDate, ':e'=>$endDate]);
  foreach (($q->fetchAll() ?: []) as $r) {
    $title = 'Cita: ' . trim(($r['nombre'] ?? '') . ' ' . ($r['apellido'] ?? ''));
    if (!empty($r['motivo'])) $title .= ' - ' . $r['motivo'];
    $color = ($r['estado'] ?? '') === 'cancelada' ? '#6c757d' : (($r['estado'] ?? '') === 'atendida' ? '#28a745' : '#007bff');
    $isLocked = (($r['estado'] ?? '') === 'cancelada' || ($r['estado'] ?? '') === 'atendida');
    $events[] = [
      // FullCalendar funciona mejor con IDs numéricos para parseInt en el frontend.
      'id' => (int)$r['id_cita'],
      'title' => $title,
      'start' => ($r['fecha'] ?? '') . 'T' . substr((string)$r['hora_inicio'],0,8),
      'end' => ($r['fecha'] ?? '') . 'T' . substr((string)$r['hora_fin'],0,8),
      'allDay' => false,
      'backgroundColor' => $color,
      'borderColor' => $color,
      // Control para drag&drop/resizing
      'editable' => !$isLocked,
      'extendedProps' => [
        'kind' => 'cita',
        'estado' => (string)($r['estado'] ?? 'programada'),
      ],
    ];
  }

  // Bloqueos
  if (sov_table_exists($pdo, 'tb_citas_bloqueos')) {
    $b = $pdo->prepare("SELECT id_bloqueo, fecha, hora_inicio, hora_fin, motivo, activo FROM tb_citas_bloqueos WHERE activo=1 AND fecha >= :s AND fecha <= :e");
    $b->execute([':s'=>$startDate, ':e'=>$endDate]);
    foreach (($b->fetchAll() ?: []) as $r) {
      $allDay = empty($r['hora_inicio']) || empty($r['hora_fin']);
      $title = 'Bloqueado' . (!empty($r['motivo']) ? (': ' . $r['motivo']) : '');

      // FullCalendar trata "end" como exclusivo en eventos allDay.
      // Si end == start, el bloqueo podría no renderizarse.
      $startDateOnly = (string)($r['fecha'] ?? '');
      $endDateOnly = $startDateOnly;
      if ($allDay && $startDateOnly !== '') {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $startDateOnly);
        if ($dt) $endDateOnly = $dt->modify('+1 day')->format('Y-m-d');
      }
      $events[] = [
        'id' => 'bloq_' . (int)$r['id_bloqueo'],
        'title' => $title,
        'start' => $allDay ? $startDateOnly : ($startDateOnly . 'T' . substr((string)$r['hora_inicio'],0,8)),
        'end' => $allDay ? $endDateOnly : ($startDateOnly . 'T' . substr((string)$r['hora_fin'],0,8)),
        'allDay' => $allDay,
        'backgroundColor' => '#ffc107',
        'borderColor' => '#ffc107',
        'editable' => false,
        'extendedProps' => [
          'kind' => 'bloqueo',
        ],
      ];
    }
  }

  echo json_encode(['ok'=>true,'events'=>$events]);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
