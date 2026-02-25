<?php
declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';

header('Content-Type: application/json; charset=utf-8');

// Evita que warnings rompan el JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);

function g(string $k, string $d=''): string { return trim((string)($_GET[$k] ?? $d)); }

// Seguridad: no devolver HTML en AJAX
$uid = $_SESSION['sesion_id_usuario'] ?? null;
$uemail = $_SESSION['sesion_email'] ?? null;

if (empty($uid)) {
  http_response_code(401);
  echo json_encode(['error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Permiso
if (!function_exists('ui_can') || !ui_can('auditoria.ver')) {
  http_response_code(403);
  echo json_encode(['error' => 'Sin permiso: auditoria.ver'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $draw   = (int)($_GET['draw'] ?? 1);
  $start  = max(0, (int)($_GET['start'] ?? 0));
  $length = min(100, max(10, (int)($_GET['length'] ?? 25)));
  $search = trim((string)($_GET['search']['value'] ?? ''));

  $desde   = g('desde');
  $hasta   = g('hasta');
  $tabla   = g('tabla');
  $accion  = g('accion');
  $usuario = g('usuario');

  $where = [];
  $params = [];

  if ($desde !== '') { $where[] = "a.fecha >= :desde"; $params[':desde'] = $desde . " 00:00:00"; }
  if ($hasta !== '') { $where[] = "a.fecha <= :hasta"; $params[':hasta'] = $hasta . " 23:59:59"; }
  if ($tabla !== '') { $where[] = "a.tabla LIKE :tabla"; $params[':tabla'] = "%{$tabla}%"; }
  if ($accion !== '') { $where[] = "a.accion = :accion"; $params[':accion'] = $accion; }

  // usuario puede ser id o email
  if ($usuario !== '') {
    if (ctype_digit($usuario)) {
      $where[] = "a.usuario_id = :uid";
      $params[':uid'] = (int)$usuario;
    } else {
      $where[] = "a.usuario_email LIKE :uemail";
      $params[':uemail'] = "%{$usuario}%";
    }
  }

  if ($search !== '') {
    $where[] = "(a.tabla LIKE :s OR a.ip LIKE :s OR a.usuario_email LIKE :s OR a.pk LIKE :s)";
    $params[':s'] = "%{$search}%";
  }

  $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

  $total = (int)$pdo->query("SELECT COUNT(*) FROM tb_auditoria")->fetchColumn();

  $st = $pdo->prepare("SELECT COUNT(*) FROM tb_auditoria a {$whereSql}");
  $st->execute($params);
  $filtered = (int)$st->fetchColumn();

  $sql = "
    SELECT a.id_auditoria, a.fecha, a.tabla, a.accion, a.pk,
           a.usuario_id, a.usuario_email, a.ip, a.user_agent,
           a.antes, a.despues
    FROM tb_auditoria a
    {$whereSql}
    ORDER BY a.fecha DESC
    LIMIT :lim OFFSET :off
  ";
  $q = $pdo->prepare($sql);
  foreach ($params as $k => $v) $q->bindValue($k, $v);
  $q->bindValue(':lim', $length, PDO::PARAM_INT);
  $q->bindValue(':off', $start, PDO::PARAM_INT);
  $q->execute();

  $rows = [];
  while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
      'fecha'   => $r['fecha'],
      'tabla'   => $r['tabla'],
      'accion'  => $r['accion'],
      'pk'      => $r['pk'],
      'usuario' => ($r['usuario_email'] !== null && $r['usuario_email'] !== '' ? $r['usuario_email'] : ('ID#' . ($r['usuario_id'] ?? ''))),
      'ip'      => $r['ip'],
      'ua'      => $r['user_agent'],
      'antes'   => $r['antes'],
      'despues' => $r['despues'],
    ];
  }

  echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $filtered,
    'data' => $rows,
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'error' => 'Error servidor',
    'detail' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
  exit;
}