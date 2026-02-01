<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

try {
  $st = $pdo->prepare("SELECT id_permiso, clave, descripcion FROM tb_permisos ORDER BY clave ASC");
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  foreach ($rows as &$r) {
    $r['id_permiso'] = (int)($r['id_permiso'] ?? 0);
    $r['clave'] = (string)($r['clave'] ?? '');
    $r['descripcion'] = (string)($r['descripcion'] ?? '');
  }
  unset($r);
  echo json_encode(['ok'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
  error_log('Permisos list error: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Error'], JSON_UNESCAPED_UNICODE);
}
