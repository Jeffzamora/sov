<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Helpers/db_schema.php';

header('Content-Type: application/json; charset=utf-8');

try {
  if (!sov_table_exists($pdo, 'tb_citas')) {
    echo json_encode(['ok'=>true,'items'=>[]]);
    exit;
  }
  // FullCalendar puede enviar ISO con hora/zona (ej: 2026-01-24T09:00:00-08:00).
  // Normalizamos a YYYY-MM-DD.
  $raw = isset($_GET['date']) && is_string($_GET['date']) ? trim($_GET['date']) : '';
  if ($raw === '') {
    throw new RuntimeException('Parámetro requerido: date');
  }
  $date = substr($raw, 0, 10);
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    throw new RuntimeException('Fecha inválida.');
  }
  $q = $pdo->prepare(
    "SELECT c.id_cita, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado,
            cli.nombre, cli.apellido
     FROM tb_citas c
     INNER JOIN tb_clientes cli ON cli.id_cliente = c.id_cliente
     WHERE c.fecha = :f
     ORDER BY c.hora_inicio ASC"
  );
  $q->execute([':f'=>$date]);
  $items = [];
  foreach (($q->fetchAll() ?: []) as $r) {
    $items[] = [
      'id' => (int)$r['id_cita'],
      'hora_inicio' => substr((string)$r['hora_inicio'],0,5),
      'hora_fin' => substr((string)$r['hora_fin'],0,5),
      'motivo' => $r['motivo'] ?? '',
      'estado' => $r['estado'] ?? 'programada',
      'cliente' => trim(($r['nombre'] ?? '') . ' ' . ($r['apellido'] ?? '')),
    ];
  }
  echo json_encode(['ok'=>true,'items'=>$items]);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
