<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

try {
  $st = $pdo->prepare("SELECT id_rol, rol FROM tb_roles ORDER BY rol ASC");
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  foreach ($rows as &$r) {
    $r['id_rol'] = (int)($r['id_rol'] ?? 0);
    $r['rol'] = (string)($r['rol'] ?? '');
  }
  unset($r);
  echo json_encode(['ok'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
  error_log('roles list_json error: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Error'], JSON_UNESCAPED_UNICODE);
}
