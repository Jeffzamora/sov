<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

function sov_json(int $code, array $payload): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helpers opcionales si existen en tu proyecto:
// - db_table_exists($pdo, $table)
// - db_column_exists($pdo, $table, $column)

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('PDO no inicializado. Revisa config.php');
    }

    $q = trim((string)($_GET['q'] ?? ''));
    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) $page = 1;

    $perPage = 10;
    $offset  = ($page - 1) * $perPage;

    $len = function_exists('mb_strlen') ? mb_strlen($q) : strlen($q);
    if ($q === '' || $len < 2) {
        sov_json(200, ['results' => [], 'pagination' => ['more' => false]]);
    }

    $needle = '%' . $q . '%';

    // Tabla principal: almacén
    $tA = 'tb_almacen';
    if (function_exists('db_table_exists') && !db_table_exists($pdo, $tA)) {
        throw new RuntimeException("No existe la tabla {$tA}.");
    }

    // Tabla opcional de productos para enriquecer (si existe)
    $tP = 'tb_productos';
    $hasP = function_exists('db_table_exists') ? db_table_exists($pdo, $tP) : true;

    // Detectar columnas en tb_almacen
    $aHasIdProd   = function_exists('db_column_exists') ? db_column_exists($pdo, $tA, 'id_producto') : true;
    $aHasNombre   = function_exists('db_column_exists') ? db_column_exists($pdo, $tA, 'nombre') : false;
    $aHasCodigo   = function_exists('db_column_exists') ? db_column_exists($pdo, $tA, 'codigo') : false;
    $aHasBarras   = function_exists('db_column_exists') ? db_column_exists($pdo, $tA, 'codigo_barras') : false;
    $aHasPrecioV  = function_exists('db_column_exists') ? db_column_exists($pdo, $tA, 'precio_venta') : false;
    $aHasPrecio   = function_exists('db_column_exists') ? db_column_exists($pdo, $tA, 'precio') : false;
    $aHasStock    = function_exists('db_column_exists') ? db_column_exists($pdo, $tA, 'stock') : true;
    $aHasEstado   = function_exists('db_column_exists') ? db_column_exists($pdo, $tA, 'estado') : false;

    if (!$aHasIdProd) {
        throw new RuntimeException("{$tA} no tiene columna id_producto. Dime cómo se llama el PK/relación.");
    }

    // Detectar columnas en tb_productos (si existe)
    $pHasNombre  = $hasP && (function_exists('db_column_exists') ? db_column_exists($pdo, $tP, 'nombre') : true);
    $pHasCodigo  = $hasP && (function_exists('db_column_exists') ? db_column_exists($pdo, $tP, 'codigo') : false);
    $pHasBarras  = $hasP && (function_exists('db_column_exists') ? db_column_exists($pdo, $tP, 'codigo_barras') : false);

    // Elegir “fuente” de nombre/código:
    // 1) Si tb_almacen ya trae nombre/codigo, usarlo.
    // 2) Si no, hacer LEFT JOIN tb_productos para obtenerlos.
    $useJoin = (!$aHasNombre && $hasP && $pHasNombre);

    $join = $useJoin ? "LEFT JOIN {$tP} p ON p.id_producto = a.id_producto" : "";

    $nameExpr  = $aHasNombre ? "a.nombre" : ($useJoin ? "p.nombre" : "CONCAT('Producto #', a.id_producto)");
    $codeExpr  = $aHasCodigo ? "a.codigo" : ($useJoin && $pHasCodigo ? "p.codigo" : "NULL");
    $barExpr   = $aHasBarras ? "a.codigo_barras" : ($useJoin && $pHasBarras ? "p.codigo_barras" : "NULL");

    $priceExpr = "0";
    if ($aHasPrecioV) $priceExpr = "a.precio_venta";
    else if ($aHasPrecio) $priceExpr = "a.precio";

    $stockExpr = $aHasStock ? "COALESCE(a.stock,0)" : "0";

    // WHERE: buscar por nombre/código/barras (si existen)
    $conds = [];
    $params = [];

    $conds[] = "{$nameExpr} LIKE :q1";
    $params[':q1'] = $needle;

    $i = 2;
    // código
    if ($aHasCodigo || ($useJoin && $pHasCodigo)) {
        $conds[] = "{$codeExpr} LIKE :q{$i}";
        $params[":q{$i}"] = $needle;
        $i++;
    }
    // barras
    if ($aHasBarras || ($useJoin && $pHasBarras)) {
        $conds[] = "{$barExpr} LIKE :q{$i}";
        $params[":q{$i}"] = $needle;
        $i++;
    }

    $where = '(' . implode(' OR ', $conds) . ')';

    // Estado activo (si existe)
    $estadoWhere = '';
    if ($aHasEstado) {
        // Ajusta si tu estado usa otros valores
        $estadoWhere = " AND (a.estado = 1 OR a.estado = '1' OR a.estado = 'activo' OR a.estado = 'ACTIVO')";
    }

    $lim = (int)$perPage;
    $off = (int)$offset;

    $sql = "
    SELECT
      a.id_producto,
      {$nameExpr} AS nombre,
      {$codeExpr} AS codigo,
      {$priceExpr} AS precio,
      {$stockExpr} AS stock
    FROM {$tA} a
    {$join}
    WHERE {$where}
    {$estadoWhere}
    ORDER BY nombre ASC
    LIMIT {$lim} OFFSET {$off}
  ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $sqlCount = "
    SELECT COUNT(*)
    FROM {$tA} a
    {$join}
    WHERE {$where}
    {$estadoWhere}
  ";
    $c = $pdo->prepare($sqlCount);
    foreach ($params as $k => $v) $c->bindValue($k, $v, PDO::PARAM_STR);
    $c->execute();
    $total = (int)$c->fetchColumn();

    $results = [];
    foreach ($rows as $r) {
        $id = (int)($r['id_producto'] ?? 0);
        $nombre = trim((string)($r['nombre'] ?? ''));
        $codigo = trim((string)($r['codigo'] ?? ''));
        $stock = (int)($r['stock'] ?? 0);
        $precio = (float)($r['precio'] ?? 0);

        $text = $nombre !== '' ? $nombre : ("Producto #{$id}");
        if ($codigo !== '') $text .= " ({$codigo})";

        $results[] = [
            'id' => $id,
            'text' => $text,
            'stock' => $stock,
            'precio' => number_format($precio, 2, '.', ''),
            'disabled' => ($stock <= 0),
        ];
    }

    $more = ($offset + $perPage) < $total;

    sov_json(200, [
        'results' => $results,
        'pagination' => ['more' => $more],
    ]);
} catch (Throwable $e) {
    error_log('[ventas.search_productos_select2] ' . $e->getMessage());
    sov_json(500, [
        'results' => [],
        'pagination' => ['more' => false],
        'error' => 'Error buscando productos: ' . $e->getMessage(),
    ]);
}
