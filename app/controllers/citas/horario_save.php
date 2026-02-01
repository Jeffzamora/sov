<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Helpers/db_schema.php';

require_post();
csrf_verify();

header('Content-Type: application/json; charset=utf-8');

try {
  if (!sov_table_exists($pdo,'tb_horario_laboral')) {
    throw new RuntimeException('Falta la tabla tb_horario_laboral. Ejecute db/migrations/040_citas.sql');
  }
  $json = input_str('items', 50000, true);
  $items = json_decode($json, true);
  if (!is_array($items)) throw new RuntimeException('Payload inválido.');

  $pdo->beginTransaction();
  $stmt = $pdo->prepare("INSERT INTO tb_horario_laboral (dia_semana, hora_inicio, hora_fin, activo, fyh_creacion, fyh_actualizacion)
                         VALUES (:d,:hi,:hf,:a,NOW(),NOW())
                         ON DUPLICATE KEY UPDATE hora_inicio=VALUES(hora_inicio), hora_fin=VALUES(hora_fin), activo=VALUES(activo), fyh_actualizacion=NOW()");
  foreach ($items as $it) {
    $d = (int)($it['dia_semana'] ?? 0);
    $a = (int)($it['activo'] ?? 0) ? 1 : 0;
    $hi = (string)($it['hora_inicio'] ?? '08:00');
    $hf = (string)($it['hora_fin'] ?? '17:00');
    if ($d < 1 || $d > 7) continue;
    if (!preg_match('/^\d{2}:\d{2}$/', $hi) || !preg_match('/^\d{2}:\d{2}$/', $hf)) {
      throw new RuntimeException('Formato de hora inválido.');
    }
    if ($hi >= $hf) {
      throw new RuntimeException('Hora fin debe ser mayor a inicio.');
    }
    $stmt->execute([':d'=>$d, ':hi'=>$hi.':00', ':hf'=>$hf.':00', ':a'=>$a]);
  }
  $pdo->commit();

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
