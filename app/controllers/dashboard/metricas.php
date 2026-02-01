<?php
declare(strict_types=1);

// Métricas del dashboard (ventas + inventario)
// Este archivo asume que $pdo ya está disponible desde config.php

if (!isset($pdo) || !($pdo instanceof PDO)) {
  return;
}

// Helpers defensivos (evitar romper si el módulo aún no está desplegado)
$ventas_total_monto = 0.0;
$ventas_hoy_monto = 0.0;
$ventas_hoy_count = 0;
$top_producto_nombre = '';
$top_producto_cant = 0;
$low_stock_count = 0;
$low_stock_min_nombre = '';
$low_stock_min_stock = 0;

try {
  if (function_exists('db_table_exists') && db_table_exists($pdo, 'tb_ventas')) {
    // Ventas totales (solo activas)
    $st = $pdo->query("SELECT COALESCE(SUM(total),0) FROM tb_ventas WHERE estado='activa'");
    $ventas_total_monto = (float)$st->fetchColumn();

    // Ventas del día
    $st = $pdo->query("SELECT COALESCE(SUM(total),0) AS monto, COUNT(*) AS cnt
                         FROM tb_ventas
                        WHERE estado='activa' AND DATE(fecha_venta)=CURDATE()");
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    $ventas_hoy_monto = (float)($row['monto'] ?? 0);
    $ventas_hoy_count = (int)($row['cnt'] ?? 0);
  }

  // Producto más vendido (por cantidad)
  if (
    function_exists('db_table_exists') &&
    db_table_exists($pdo, 'tb_ventas') &&
    db_table_exists($pdo, 'tb_ventas_detalle') &&
    db_table_exists($pdo, 'tb_almacen')
  ) {
    $sqlTop = "
      SELECT a.nombre AS producto, COALESCE(SUM(d.cantidad),0) AS qty
        FROM tb_ventas_detalle d
        INNER JOIN tb_ventas v ON v.id_venta = d.id_venta
        INNER JOIN tb_almacen a ON a.id_producto = d.id_producto
       WHERE v.estado='activa'
       GROUP BY d.id_producto
       ORDER BY qty DESC
       LIMIT 1
    ";
    $st = $pdo->query($sqlTop);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if ($r) {
      $top_producto_nombre = (string)($r['producto'] ?? '');
      $top_producto_cant = (int)($r['qty'] ?? 0);
    }
  }

  // Stock bajo
  if (function_exists('db_table_exists') && db_table_exists($pdo, 'tb_almacen')) {
    // Conteo de productos con stock <= stock_minimo (cuando stock_minimo no es null)
    $sqlLow = "
      SELECT COUNT(*)
        FROM tb_almacen
       WHERE stock_minimo IS NOT NULL
         AND stock <= stock_minimo
    ";
    $st = $pdo->query($sqlLow);
    $low_stock_count = (int)$st->fetchColumn();

    // El más crítico
    $sqlMin = "
      SELECT nombre, stock
        FROM tb_almacen
       WHERE stock_minimo IS NOT NULL
         AND stock <= stock_minimo
       ORDER BY stock ASC
       LIMIT 1
    ";
    $st = $pdo->query($sqlMin);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if ($r) {
      $low_stock_min_nombre = (string)($r['nombre'] ?? '');
      $low_stock_min_stock = (int)($r['stock'] ?? 0);
    }
  }

} catch (Throwable $e) {
  // No rompemos dashboard por métricas
  error_log('[dashboard.metricas] ' . $e->getMessage());
}
