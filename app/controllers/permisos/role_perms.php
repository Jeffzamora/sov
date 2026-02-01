<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

try {
  $idRol = input_int('id_rol', true, 'GET');

  $st = $pdo->prepare("
    SELECT p.clave
      FROM tb_roles_permisos rp
      INNER JOIN tb_permisos p ON p.id_permiso = rp.id_permiso
     WHERE rp.id_rol = ?
     ORDER BY p.clave
  ");
  $st->execute([$idRol]);

  $out = [];
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $k = (string)($r['clave'] ?? '');
    if ($k !== '') $out[] = $k;
  }

  echo json_encode(['ok'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
  error_log('role_perms error: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Error'], JSON_UNESCAPED_UNICODE);
}
