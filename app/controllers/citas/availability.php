<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Helpers/db_schema.php';

header('Content-Type: application/json; charset=utf-8');

function time_to_minutes(string $t): int {
  // t: HH:MM(:SS)
  $parts = explode(':', $t);
  $h = (int)($parts[0] ?? 0);
  $m = (int)($parts[1] ?? 0);
  return $h*60 + $m;
}

function minutes_to_time(int $m): string {
  $h = floor($m/60);
  $min = $m%60;
  return sprintf('%02d:%02d', $h, $min);
}

function dow_from_date(string $date): int {
  // Retorna 1..7 (Domingo..Sábado) como MySQL DAYOFWEEK()
  $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
  if (!$dt) return 0;
  // PHP: w => 0 (Dom) .. 6 (Sáb)
  return ((int)$dt->format('w')) + 1;
}

try {
  if (!sov_table_exists($pdo,'tb_horario_laboral') || !sov_table_exists($pdo,'tb_citas')) {
    echo json_encode(['ok'=>false,'error'=>'Faltan tablas del módulo de citas.']);
    exit;
  }
  $date = input_date('date', true, false, 'GET');
  $duration = (int)($_GET['duration'] ?? 30);
  $excludeId = (int)($_GET['exclude_id'] ?? 0);
  if ($duration <= 0) $duration = 30;
  if ($duration > 240) $duration = 240;

  // Día de semana 1..7 (evita SQL dinámico)
  $dow = dow_from_date($date);
  if ($dow < 1 || $dow > 7) {
    echo json_encode(['ok'=>false,'error'=>'Fecha inválida.']);
    exit;
  }
  $h = $pdo->prepare("SELECT activo, hora_inicio, hora_fin FROM tb_horario_laboral WHERE dia_semana = :d LIMIT 1");
  $h->execute([':d'=>$dow]);
  $hor = $h->fetch();
  if (!$hor || (int)($hor['activo'] ?? 0) !== 1) {
    echo json_encode(['ok'=>false,'error'=>'Día no laborable.']);
    exit;
  }
  $hi = substr((string)($hor['hora_inicio'] ?? '08:00:00'),0,5);
  $hf = substr((string)($hor['hora_fin'] ?? '17:00:00'),0,5);
  $startMin = time_to_minutes($hi);
  $endMin = time_to_minutes($hf);
  if ($endMin <= $startMin) {
    echo json_encode(['ok'=>false,'error'=>'Horario inválido.']);
    exit;
  }

  // Citas ya registradas (programadas)
  $busy = [];
  if ($excludeId > 0) {
    $q = $pdo->prepare("SELECT hora_inicio, hora_fin FROM tb_citas WHERE fecha=:f AND estado <> 'cancelada' AND id_cita <> :id");
    $q->execute([':f'=>$date, ':id'=>$excludeId]);
  } else {
    $q = $pdo->prepare("SELECT hora_inicio, hora_fin FROM tb_citas WHERE fecha=:f AND estado <> 'cancelada'");
    $q->execute([':f'=>$date]);
  }
  foreach (($q->fetchAll() ?: []) as $r) {
    $busy[] = [time_to_minutes((string)$r['hora_inicio']), time_to_minutes((string)$r['hora_fin'])];
  }

  // Bloqueos
  if (sov_table_exists($pdo,'tb_citas_bloqueos')) {
    $b = $pdo->prepare("SELECT hora_inicio, hora_fin FROM tb_citas_bloqueos WHERE activo=1 AND fecha=:f");
    $b->execute([':f'=>$date]);
    foreach (($b->fetchAll() ?: []) as $r) {
      if (empty($r['hora_inicio']) || empty($r['hora_fin'])) {
        // todo el día
        $busy[] = [$startMin, $endMin];
      } else {
        $busy[] = [time_to_minutes((string)$r['hora_inicio']), time_to_minutes((string)$r['hora_fin'])];
      }
    }
  }

  // Generar slots cada 30 min
  $slots = [];
  for ($m = $startMin; $m + $duration <= $endMin; $m += 30) {
    $slotStart = $m;
    $slotEnd = $m + $duration;
    $overlap = false;
    foreach ($busy as $iv) {
      $a = $iv[0]; $b2 = $iv[1];
      if ($slotStart < $b2 && $slotEnd > $a) { $overlap = true; break; }
    }
    if (!$overlap) $slots[] = minutes_to_time($slotStart);
  }

  echo json_encode(['ok'=>true,'slots'=>$slots]);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
