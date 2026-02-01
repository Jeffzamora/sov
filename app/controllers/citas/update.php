<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Helpers/db_schema.php';

require_post();
csrf_verify();

header('Content-Type: application/json; charset=utf-8');

function time_to_minutes(string $t): int {
  $p = explode(':', $t);
  return ((int)($p[0]??0))*60 + ((int)($p[1]??0));
}

function minutes_to_time(int $m): string {
  return sprintf('%02d:%02d:00', (int)floor($m/60), (int)($m%60));
}

function dow_from_date(string $date): int {
  // 1..7 (Domingo..Sábado) como MySQL DAYOFWEEK()
  $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
  if (!$dt) return 0;
  return ((int)$dt->format('w')) + 1;
}

try {
  if (!sov_table_exists($pdo,'tb_citas') || !sov_table_exists($pdo,'tb_horario_laboral')) {
    throw new RuntimeException('Faltan tablas del módulo de citas. Ejecute db/migrations/040_citas.sql');
  }

  $id_cita = input_int('id_cita', true);
  $id_cliente = input_int('id_cliente', true);
  $fecha = input_date('fecha', true, false);
  $hora_inicio = input_str('hora_inicio', 5, true);
  $dur = input_int('duracion', true);
  $motivo = input_str('motivo', 255, false);

  if (!preg_match('/^\d{2}:\d{2}$/', $hora_inicio)) {
    throw new RuntimeException('Hora inválida.');
  }
  if ($dur <= 0) $dur = 30;
  if ($dur > 240) $dur = 240;

  // Validar que existe la cita
  $qc = $pdo->prepare("SELECT id_cita FROM tb_citas WHERE id_cita=:id LIMIT 1");
  $qc->execute([':id'=>$id_cita]);
  if (!$qc->fetch()) throw new RuntimeException('Cita no encontrada.');

  // Validar cliente
  $c = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE id_cliente=:id LIMIT 1");
  $c->execute([':id'=>$id_cliente]);
  if (!$c->fetch()) throw new RuntimeException('Cliente no válido.');

  $startMin = time_to_minutes($hora_inicio);
  $endMin = $startMin + $dur;
  $hora_fin = minutes_to_time($endMin);

  // Horario del día (evita SQL dinámico)
  $dow = dow_from_date($fecha);
  if ($dow < 1 || $dow > 7) throw new RuntimeException('Fecha inválida.');
  $h = $pdo->prepare("SELECT activo, hora_inicio, hora_fin FROM tb_horario_laboral WHERE dia_semana=:d LIMIT 1");
  $h->execute([':d'=>$dow]);
  $hor = $h->fetch();
  if (!$hor || (int)($hor['activo']??0)!==1) throw new RuntimeException('Día no laborable.');
  $hi = time_to_minutes(substr((string)$hor['hora_inicio'],0,5));
  $hf = time_to_minutes(substr((string)$hor['hora_fin'],0,5));
  if ($startMin < $hi || $endMin > $hf) throw new RuntimeException('Hora fuera del horario laboral.');

  // Bloqueos
  if (sov_table_exists($pdo,'tb_citas_bloqueos')) {
    $b = $pdo->prepare("SELECT hora_inicio, hora_fin FROM tb_citas_bloqueos WHERE activo=1 AND fecha=:f");
    $b->execute([':f'=>$fecha]);
    foreach (($b->fetchAll() ?: []) as $r) {
      if (empty($r['hora_inicio']) || empty($r['hora_fin'])) {
        throw new RuntimeException('La fecha está bloqueada.');
      }
      $bs = time_to_minutes(substr((string)$r['hora_inicio'],0,5));
      $be = time_to_minutes(substr((string)$r['hora_fin'],0,5));
      if ($startMin < $be && $endMin > $bs) throw new RuntimeException('La hora seleccionada está bloqueada.');
    }
  }

  // Conflictos con otras citas
  $q = $pdo->prepare("SELECT hora_inicio, hora_fin FROM tb_citas WHERE fecha=:f AND estado <> 'cancelada' AND id_cita <> :id");
  $q->execute([':f'=>$fecha, ':id'=>$id_cita]);
  foreach (($q->fetchAll() ?: []) as $r) {
    $cs = time_to_minutes(substr((string)$r['hora_inicio'],0,5));
    $ce = time_to_minutes(substr((string)$r['hora_fin'],0,5));
    if ($startMin < $ce && $endMin > $cs) {
      throw new RuntimeException('Ya existe una cita en ese horario.');
    }
  }

  $up = $pdo->prepare("UPDATE tb_citas
                       SET id_cliente=:c, fecha=:f, hora_inicio=:hi, hora_fin=:hf, motivo=:m, fyh_actualizacion=NOW()
                       WHERE id_cita=:id");
  $ok = $up->execute([
    ':c'=>$id_cliente,
    ':f'=>$fecha,
    ':hi'=>substr($hora_inicio,0,5).':00',
    ':hf'=>$hora_fin,
    ':m'=>($motivo===''?null:$motivo),
    ':id'=>$id_cita,
  ]);
  if (!$ok) throw new RuntimeException('No se pudo actualizar la cita.');

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
