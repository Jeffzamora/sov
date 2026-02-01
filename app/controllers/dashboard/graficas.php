<?php
declare(strict_types=1);

// Datos para gráficas del Dashboard (Chart.js).
// Se incluye desde index.php (no imprime HTML).

$ventas_7d_labels = [];
$ventas_7d_values = [];

$ventas_metodo_labels = [];
$ventas_metodo_values = [];

$top5_labels = [];
$top5_values = [];

$low5_labels = [];
$low5_values = [];

try {
  if (isset($pdo) && $pdo instanceof PDO) {
    // Ventas últimos 7 días
    $sql = "
      SELECT DATE(v.fecha_venta) AS dia, SUM(v.total) AS monto
        FROM tb_ventas v
       WHERE v.estado='activa'
         AND DATE(v.fecha_venta) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
       GROUP BY dia
       ORDER BY dia ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute();
    $map = [];
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      $d = (string)($r['dia'] ?? '');
      if ($d !== '') $map[$d] = (float)($r['monto'] ?? 0);
    }
    // Completar días faltantes
    for ($i = 6; $i >= 0; $i--) {
      $d = date('Y-m-d', strtotime('-' . $i . ' day'));
      $ventas_7d_labels[] = $d;
      $ventas_7d_values[] = (float)($map[$d] ?? 0);
    }

    // Ventas por método de pago (últimos 30 días)
    $sql = "
      SELECT COALESCE(NULLIF(TRIM(v.metodo_pago),''),'N/D') AS metodo,
             SUM(v.total) AS monto
        FROM tb_ventas v
       WHERE v.estado='activa'
         AND DATE(v.fecha_venta) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
       GROUP BY metodo
       ORDER BY monto DESC
       LIMIT 12
    ";
    $st = $pdo->prepare($sql);
    $st->execute();
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      $ventas_metodo_labels[] = (string)($r['metodo'] ?? 'N/D');
      $ventas_metodo_values[] = (float)($r['monto'] ?? 0);
    }

    // Top 5 productos por cantidad (últimos 30 días)
    $sql = "
      SELECT a.nombre,
             SUM(d.cantidad) AS qty
        FROM tb_ventas_detalle d
        INNER JOIN tb_ventas v ON v.id_venta = d.id_venta
        INNER JOIN tb_almacen a ON a.id_producto = d.id_producto
       WHERE v.estado='activa'
         AND DATE(v.fecha_venta) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
       GROUP BY d.id_producto
       ORDER BY qty DESC
       LIMIT 5
    ";
    $st = $pdo->prepare($sql);
    $st->execute();
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      $top5_labels[] = (string)($r['nombre'] ?? '');
      $top5_values[] = (int)($r['qty'] ?? 0);
    }

    // Stock bajo (Top 5 más críticos)
    // Detecta columnas comunes (stock / stock_minimo) por compatibilidad
    $stockCol = 'stock';
    $minCol = 'stock_minimo';
    if (function_exists('db_column_exists')) {
      if (!db_column_exists($pdo, 'tb_almacen', $stockCol) && db_column_exists($pdo, 'tb_almacen', 'existencias')) {
        $stockCol = 'existencias';
      }
      if (!db_column_exists($pdo, 'tb_almacen', $minCol) && db_column_exists($pdo, 'tb_almacen', 'stock_min')) {
        $minCol = 'stock_min';
      }
    }

    $sql = "
      SELECT a.nombre,
             a.$stockCol AS stock,
             a.$minCol AS minimo,
             (a.$stockCol - a.$minCol) AS delta
        FROM tb_almacen a
       WHERE a.$minCol IS NOT NULL
         AND a.$stockCol <= a.$minCol
       ORDER BY delta ASC
       LIMIT 5
    ";
    $st = $pdo->prepare($sql);
    $st->execute();
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      $low5_labels[] = (string)($r['nombre'] ?? '');
      $low5_values[] = (int)($r['stock'] ?? 0);
    }
  }
} catch (Throwable $e) {
  error_log('[dashboard.graficas] ' . $e->getMessage());
}
