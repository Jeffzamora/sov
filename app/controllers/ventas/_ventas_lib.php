<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../cajas/_caja_lib.php';

function ventas_next_nro(PDO $pdo): int
{
    // Evitar colisiones: bloquea la tabla en transacción usando MAX + 1.
    // Para producción: recomendable una tabla de series o usar SELECT ... FOR UPDATE.
    $stmt = $pdo->query("SELECT COALESCE(MAX(nro_venta), 0) AS m FROM tb_ventas");
    $m = (int)($stmt->fetchColumn() ?: 0);
    return $m + 1;
}

function ventas_parse_items(): array
{
    // Espera arrays:
    // id_producto[], cantidad[], precio_unitario[]
    $ids = $_POST['id_producto'] ?? [];
    $cants = $_POST['cantidad'] ?? [];
    $precios = $_POST['precio_unitario'] ?? [];

    if (!is_array($ids) || !is_array($cants) || !is_array($precios)) return [];

    $items = [];
    $n = min(count($ids), count($cants), count($precios));
    for ($i=0; $i<$n; $i++) {
        $idp = (int)$ids[$i];
        $cant = (int)$cants[$i];
        $precio = (float)$precios[$i];
        if ($idp <= 0 || $cant <= 0 || $precio < 0) continue;
        $items[] = ['id_producto' => $idp, 'cantidad' => $cant, 'precio_unitario' => $precio];
    }
    return $items;
}

function ventas_calcular_totales(array $items, float $descuento = 0.0, float $impuesto = 0.0): array
{
    $subtotal = 0.0;
    foreach ($items as $it) {
        $subtotal += ((float)$it['precio_unitario']) * ((int)$it['cantidad']);
    }
    $descuento = max(0.0, $descuento);
    $impuesto = max(0.0, $impuesto);
    $total = max(0.0, $subtotal - $descuento + $impuesto);
    return ['subtotal' => $subtotal, 'descuento' => $descuento, 'impuesto' => $impuesto, 'total' => $total];
}
