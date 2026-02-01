<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';
require_once __DIR__ . '/_caja_lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// sesion.php ya asegura sesión y token
$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

function out($code, $payload)
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $id_caja = isset($_GET['id_caja']) ? (int)$_GET['id_caja'] : 0;
    $desde   = trim((string)($_GET['desde'] ?? ''));
    $hasta   = trim((string)($_GET['hasta'] ?? ''));
    $tipo    = trim((string)($_GET['tipo'] ?? ''));
    $metodo  = trim((string)($_GET['metodo'] ?? ''));
    $limit   = (int)($_GET['limit'] ?? 200);
    $ver_anulados = (string)($_GET['ver_anulados'] ?? '0'); // '1' para incluir anulados


    if ($limit < 1) $limit = 200;
    if ($limit > 1000) $limit = 1000;

    $params = [];
    $where = [];

    // Estado (si existe columna)
    $hasEstado = db_column_exists($pdo, 'tb_caja_movimientos', 'estado');
    if ($hasEstado && $ver_anulados !== '1') {
        $where[] = "m.estado = 'activo'";
    }


    if ($id_caja > 0) {
        $where[] = "m.id_caja = :id_caja";
        $params[':id_caja'] = $id_caja;
    }

    if ($tipo !== '') {
        if (!in_array($tipo, ['ingreso', 'egreso'], true)) out(400, ['ok' => false, 'error' => 'Tipo inválido']);
        $where[] = "m.tipo = :tipo";
        $params[':tipo'] = $tipo;
    }

    if ($metodo !== '') {
        if (!in_array($metodo, ['efectivo', 'deposito'], true)) out(400, ['ok' => false, 'error' => 'Método inválido']);
        $where[] = "m.metodo_pago = :metodo";
        $params[':metodo'] = $metodo;
    }

    // fechas: si tu campo es DATETIME 'fecha' (según tu índice), filtramos por DATE(fecha)
    if ($desde !== '') {
        $where[] = "DATE(m.fecha) >= :desde";
        $params[':desde'] = $desde;
    }
    if ($hasta !== '') {
        $where[] = "DATE(m.fecha) <= :hasta";
        $params[':hasta'] = $hasta;
    }

    // Selección dinámica según columnas (compatibilidad)
    $cols = [
        'm.id_movimiento', 'm.id_caja', 'm.tipo', 'm.metodo_pago', 'm.monto',
        'm.concepto', 'm.referencia', 'm.id_usuario',
        "DATE_FORMAT(m.fecha, '%Y-%m-%d %H:%i') AS fecha",
    ];
    if ($hasEstado) {
        $cols[] = 'm.estado';
        if (db_column_exists($pdo, 'tb_caja_movimientos', 'anulado_por')) $cols[] = 'm.anulado_por';
        if (db_column_exists($pdo, 'tb_caja_movimientos', 'anulado_at')) $cols[] = 'm.anulado_at';
        if (db_column_exists($pdo, 'tb_caja_movimientos', 'motivo_anulacion')) $cols[] = 'm.motivo_anulacion';
        if (db_column_exists($pdo, 'tb_caja_movimientos', 'id_movimiento_ajuste')) $cols[] = 'm.id_movimiento_ajuste';
    } else {
        $cols[] = "'activo' AS estado";
    }

    $sql = "SELECT " . implode(', ', $cols) . " FROM tb_caja_movimientos m";

    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY m.fecha DESC, m.id_movimiento DESC LIMIT " . (int)$limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Sanitiza strings (evita XSS si el frontend imprime HTML)
    foreach ($rows as &$r) {
        foreach (['tipo', 'metodo_pago', 'concepto', 'referencia', 'fecha'] as $k) {
            if (isset($r[$k])) $r[$k] = (string)$r[$k];
        }
        $r['monto'] = (float)($r['monto'] ?? 0);
        $r['id_caja'] = (int)($r['id_caja'] ?? 0);
        $r['id_usuario'] = (int)($r['id_usuario'] ?? 0);
    }

    out(200, ['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
    $rid = bin2hex(random_bytes(6));
    error_log("Movimientos JSON error [$rid]: " . $e->getMessage());
    out(500, ['ok' => false, 'error' => 'Error interno', 'request_id' => $rid]);
}
