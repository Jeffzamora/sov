<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Helpers/db_schema.php';

require_post();
csrf_verify();

header('Content-Type: application/json; charset=utf-8');

try {
  if (!sov_table_exists($pdo,'tb_citas_bloqueos')) {
    throw new RuntimeException('Falta la tabla tb_citas_bloqueos. Ejecute db/migrations/040_citas.sql');
  }
  $fecha = input_date('fecha', true, false);
  $hora_inicio = input_str('hora_inicio', 8, false);
  $hora_fin = input_str('hora_fin', 8, false);
  $motivo = input_str('motivo', 255, false);

  $hi = trim($hora_inicio);
  $hf = trim($hora_fin);
  if (($hi !== '' || $hf !== '') && (!preg_match('/^\d{2}:\d{2}$/', $hi) || !preg_match('/^\d{2}:\d{2}$/', $hf))) {
    throw new RuntimeException('Horas inválidas.');
  }
  if (($hi !== '' && $hf !== '') && ($hi >= $hf)) {
    throw new RuntimeException('La hora fin debe ser mayor a la hora inicio.');
  }

  $ins = $pdo->prepare("INSERT INTO tb_citas_bloqueos (fecha, hora_inicio, hora_fin, motivo, activo, fyh_creacion, fyh_actualizacion)
                        VALUES (:f,:hi,:hf,:m,1,NOW(),NOW())");
  $ok = $ins->execute([
    ':f'=>$fecha,
    ':hi'=>($hi===''?null:($hi.':00')),
    ':hf'=>($hf===''?null:($hf.':00')),
    ':m'=>($motivo===''?null:$motivo),
  ]);
  if(!$ok) throw new RuntimeException('No se pudo guardar el bloqueo.');

  echo json_encode(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
