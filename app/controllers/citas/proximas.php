<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Helpers/db_schema.php';

header('Content-Type: application/json; charset=utf-8');

try {
  if (!sov_table_exists($pdo, 'tb_citas')) {
    echo json_encode(['ok'=>true,'items'=>[]]);
    exit;
  }

  $days = (int)($_GET['days'] ?? 7);
  if ($days <= 0) $days = 7;
  if ($days > 31) $days = 31;
  $limit = (int)($_GET['limit'] ?? 15);
  if ($limit <= 0) $limit = 15;
  if ($limit > 50) $limit = 50;

  $today = date('Y-m-d');
  $to = date('Y-m-d', strtotime($today . ' +'.($days-1).' day'));

  $q = $pdo->prepare(
    "SELECT c.id_cita, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado,
            cli.nombre, cli.apellido
     FROM tb_citas c
     INNER JOIN tb_clientes cli ON cli.id_cliente = c.id_cliente
     WHERE c.fecha >= :s AND c.fecha <= :e
       AND c.estado = 'programada'
     ORDER BY c.fecha ASC, c.hora_inicio ASC
     LIMIT {$limit}"
  );
  $q->execute([':s'=>$today, ':e'=>$to]);
  $items = [];
  foreach (($q->fetchAll(PDO::FETCH_ASSOC) ?: []) as $r) {
    $items[] = [
      'id' => (int)$r['id_cita'],
      'fecha' => (string)($r['fecha'] ?? ''),
      'hora_inicio' => substr((string)($r['hora_inicio'] ?? ''),0,5),
      'hora_fin' => substr((string)($r['hora_fin'] ?? ''),0,5),
      'cliente' => trim(($r['nombre'] ?? '') . ' ' . ($r['apellido'] ?? '')),
      'motivo' => (string)($r['motivo'] ?? ''),
    ];
  }

  echo json_encode(['ok'=>true,'items'=>$items, 'range'=>['from'=>$today,'to'=>$to]]);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
