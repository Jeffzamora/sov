<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Helpers/db_schema.php';

header('Content-Type: application/json; charset=utf-8');

function time_to_minutes(string $t): int {
  $p = explode(':', $t);
  return ((int)($p[0] ?? 0))*60 + ((int)($p[1] ?? 0));
}

try {
  if (!sov_table_exists($pdo,'tb_citas')) {
    throw new RuntimeException('No existe la tabla de citas. Ejecute db/migrations/040_citas.sql');
  }

  $id = input_int('id', true, 'GET');
  $q = $pdo->prepare(
    "SELECT c.id_cita, c.id_cliente, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado,
            cli.nombre, cli.apellido, cli.tipo_documento, cli.numero_documento
     FROM tb_citas c
     INNER JOIN tb_clientes cli ON cli.id_cliente = c.id_cliente
     WHERE c.id_cita = :id
     LIMIT 1"
  );
  $q->execute([':id'=>$id]);
  $r = $q->fetch(PDO::FETCH_ASSOC);
  if (!$r) throw new RuntimeException('Cita no encontrada.');

  $hi = substr((string)$r['hora_inicio'],0,5);
  $hf = substr((string)$r['hora_fin'],0,5);
  $dur = time_to_minutes($hf) - time_to_minutes($hi);
  if ($dur <= 0) $dur = 30;

  $doc = trim((string)($r['tipo_documento'] ?? '') . ' ' . (string)($r['numero_documento'] ?? ''));
  $cliente = trim((string)($r['nombre'] ?? '') . ' ' . (string)($r['apellido'] ?? ''));

  echo json_encode([
    'ok' => true,
    'cita' => [
      'id_cita' => (int)$r['id_cita'],
      'id_cliente' => (int)$r['id_cliente'],
      'cliente' => $cliente,
      'doc' => $doc,
      'fecha' => (string)$r['fecha'],
      'hora_inicio' => $hi,
      'hora_fin' => $hf,
      'duracion' => (int)$dur,
      'motivo' => $r['motivo'] ?? '',
      'estado' => $r['estado'] ?? 'programada',
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
