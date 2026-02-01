<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Helpers/db_schema.php';

require_post();
csrf_verify();

header('Content-Type: application/json; charset=utf-8');

try {
  if (!sov_table_exists($pdo,'tb_citas')) {
    throw new RuntimeException('No existe la tabla de citas. Ejecute db/migrations/040_citas.sql');
  }
  $id_cita = input_int('id_cita', true);
  $estado = input_str('estado', 20, true);
  $allowed = ['programada','cancelada','atendida'];
  if (!in_array($estado, $allowed, true)) {
    throw new RuntimeException('Estado inválido.');
  }

  $q = $pdo->prepare("UPDATE tb_citas SET estado=:e, fyh_actualizacion=NOW() WHERE id_cita=:id");
  $ok = $q->execute([':e'=>$estado, ':id'=>$id_cita]);
  if (!$ok || $q->rowCount() < 1) {
    throw new RuntimeException('No se pudo actualizar el estado.');
  }
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
