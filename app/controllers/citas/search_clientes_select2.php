<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'clientes.ver', $URL . '/');


function sov_json(int $code, array $payload): void
{
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('PDO no inicializado. Revisa config.php');
  }

  $q = trim((string)($_GET['q'] ?? ''));
  $page = (int)($_GET['page'] ?? 1);
  if ($page < 1) $page = 1;

  $perPage = 10;
  $offset = ($page - 1) * $perPage;

  $len = function_exists('mb_strlen') ? mb_strlen($q) : strlen($q);
  if ($q === '' || $len < 2) {
    sov_json(200, ['results' => [], 'pagination' => ['more' => false]]);
  }

  $needle = '%' . $q . '%';

  // Columnas opcionales reales (usa tus helpers)
  $hasCelular = function_exists('db_column_exists') ? db_column_exists($pdo, 'tb_clientes', 'celular') : false;
  $hasEmail   = function_exists('db_column_exists') ? db_column_exists($pdo, 'tb_clientes', 'email') : false;

  // Construir condiciones con placeholders únicos para evitar HY093
  $conds = [];
  $params = [];

  $conds[] = "nombre LIKE :q1";
  $params[':q1'] = $needle;
  $conds[] = "apellido LIKE :q2";
  $params[':q2'] = $needle;
  $conds[] = "numero_documento LIKE :q3";
  $params[':q3'] = $needle;

  $i = 4;
  if ($hasCelular) {
    $conds[] = "celular LIKE :q{$i}";
    $params[":q{$i}"] = $needle;
    $i++;
  }
  if ($hasEmail) {
    $conds[] = "email LIKE :q{$i}";
    $params[":q{$i}"] = $needle;
    $i++;
  }

  $where = '(' . implode(' OR ', $conds) . ')';

  $lim = (int)$perPage;
  $off = (int)$offset;

  $sql = "
    SELECT id_cliente,
           nombre,
           apellido,
           tipo_documento,
           numero_documento
      FROM tb_clientes
     WHERE $where
     ORDER BY nombre ASC, apellido ASC
     LIMIT $lim OFFSET $off
  ";

  $stmt = $pdo->prepare($sql);
  foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
  }
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  $sqlCount = "
    SELECT COUNT(*)
      FROM tb_clientes
     WHERE $where
  ";
  $c = $pdo->prepare($sqlCount);
  foreach ($params as $k => $v) {
    $c->bindValue($k, $v, PDO::PARAM_STR);
  }
  $c->execute();
  $total = (int)$c->fetchColumn();

  $results = [];
  foreach ($rows as $r) {
    $id = (int)($r['id_cliente'] ?? 0);
    $nombre = trim((string)($r['nombre'] ?? '') . ' ' . (string)($r['apellido'] ?? ''));
    $doc = trim((string)($r['tipo_documento'] ?? '') . ' ' . (string)($r['numero_documento'] ?? ''));

    $results[] = [
      'id'   => $id,
      'text' => ($nombre !== '' ? $nombre : ('Cliente #' . $id)),
      'doc'  => $doc,
    ];
  }

  $more = ($offset + $perPage) < $total;

  sov_json(200, [
    'results' => $results,
    'pagination' => ['more' => $more],
  ]);
} catch (Throwable $e) {
  error_log('[citas.search_clientes_select2] ' . $e->getMessage());
  sov_json(500, [
    'results' => [],
    'pagination' => ['more' => false],
    'error' => 'Error buscando clientes: ' . $e->getMessage(),
  ]);
}
