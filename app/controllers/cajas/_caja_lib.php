<?php
/**
 * Utilidades para el módulo de Caja.
 *
 * Convenciones:
 * - Solo puede existir 1 caja "abierta" a la vez (global).
 * - Los totales se calculan a partir de ventas, abonos y movimientos manuales.
 * - Los movimientos manuales soportan anulación (tb_caja_movimientos.estado='activo'|'anulado').
 */

require_once __DIR__ . '/../../config.php';

// Evitar "Cannot redeclare" si se incluye desde múltiples puntos.

if (!function_exists('caja_abierta_actual')) {
function caja_abierta_actual(PDO $pdo): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM tb_cajas WHERE estado='abierta' ORDER BY id_caja DESC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
}

if (!function_exists('caja_count_abiertas')) {
function caja_count_abiertas(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM tb_cajas WHERE estado='abierta'");
    return (int)$stmt->fetchColumn();
}
}

if (!function_exists('caja_usuario_es_apertura')) {
function caja_usuario_es_apertura(array $caja, int $idUsuario): bool
{
    return ((int)($caja['usuario_apertura_id'] ?? 0)) === $idUsuario;
}
}

if (!function_exists('caja_calcular_totales')) {
function caja_calcular_totales(PDO $pdo, int $idCaja): array
{
    // Totales por ventas (efectivo / deposito / credito)
    $stmt = $pdo->prepare(
        "
        SELECT
          SUM(CASE WHEN v.estado='activa' AND v.metodo_pago='efectivo' THEN v.pagado_inicial ELSE 0 END) AS ventas_efectivo,
          SUM(CASE WHEN v.estado='activa' AND v.metodo_pago='deposito' THEN v.pagado_inicial ELSE 0 END) AS ventas_deposito,
          SUM(CASE WHEN v.estado='activa' AND v.metodo_pago IN('credito','mixto') THEN v.pagado_inicial ELSE 0 END) AS ventas_pagado_inicial,
          SUM(CASE WHEN v.estado='activa' AND v.metodo_pago IN('credito','mixto') THEN v.saldo_pendiente ELSE 0 END) AS ventas_credito
        FROM tb_ventas v
        WHERE v.id_caja = ?
        "
    );
    $stmt->execute([$idCaja]);
    $ventas = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Abonos (pagos posteriores) registrados en caja
    $stmt = $pdo->prepare(
        "
        SELECT
          SUM(CASE WHEN p.metodo_pago='efectivo' THEN p.monto ELSE 0 END) AS abonos_efectivo,
          SUM(CASE WHEN p.metodo_pago='deposito' THEN p.monto ELSE 0 END) AS abonos_deposito,
          SUM(p.monto) AS abonos_total
        FROM tb_ventas_pagos p
        INNER JOIN tb_ventas v ON v.id_venta = p.id_venta
        WHERE p.id_caja = ? AND v.estado='activa'
        "
    );
    $stmt->execute([$idCaja]);
    $abonos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Movimientos manuales (filtra anulados si existe la columna)
    $hasEstado = false;
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM tb_caja_movimientos LIKE 'estado'");
        $hasEstado = (bool)$chk->fetchColumn();
    } catch (Throwable $e) {
        $hasEstado = false;
    }
    $whereEstado = $hasEstado ? " AND (estado='activo' OR estado IS NULL)" : '';

    $stmt = $pdo->prepare(
        "
        SELECT
          SUM(CASE WHEN tipo='ingreso' AND metodo_pago='efectivo' THEN monto ELSE 0 END) AS mov_ingresos_efectivo,
          SUM(CASE WHEN tipo='egreso'  AND metodo_pago='efectivo' THEN monto ELSE 0 END) AS mov_egresos_efectivo,
          SUM(CASE WHEN tipo='ingreso' AND metodo_pago='deposito' THEN monto ELSE 0 END) AS mov_ingresos_deposito,
          SUM(CASE WHEN tipo='egreso'  AND metodo_pago='deposito' THEN monto ELSE 0 END) AS mov_egresos_deposito,
          SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END) AS mov_ingresos,
          SUM(CASE WHEN tipo='egreso'  THEN monto ELSE 0 END) AS mov_egresos
        FROM tb_caja_movimientos
        WHERE id_caja = ? {$whereEstado}
        "
    );
    $stmt->execute([$idCaja]);
    $mov = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $ventas_ef = (float)($ventas['ventas_efectivo'] ?? 0);
    $ventas_dep = (float)($ventas['ventas_deposito'] ?? 0);
    $ab_ef = (float)($abonos['abonos_efectivo'] ?? 0);
    $ab_dep = (float)($abonos['abonos_deposito'] ?? 0);

    $mov_ing_ef = (float)($mov['mov_ingresos_efectivo'] ?? 0);
    $mov_egr_ef = (float)($mov['mov_egresos_efectivo'] ?? 0);

    // Efectivo esperado base: inicial + ventas_ef + abonos_ef + mov_ing_ef - mov_egr_ef
    // (monto_inicial se calcula en UI/controlador usando tb_cajas.monto_inicial)

    // Aliases de compatibilidad para otros controladores (ventas/cierre, etc.)
    $mov_ing_total = (float)($mov['mov_ingresos'] ?? 0);
    $mov_egr_total = (float)($mov['mov_egresos'] ?? 0);

    // Base de efectivo esperado (sin incluir monto inicial; ese vive en tb_cajas.monto_inicial)
    $efectivo_base = round(($ventas_ef + $ab_ef + $mov_ing_ef) - $mov_egr_ef, 2);

    return [
        'ventas_efectivo' => $ventas_ef,
        'ventas_deposito' => $ventas_dep,
        'ventas_pagado_inicial' => (float)($ventas['ventas_pagado_inicial'] ?? 0),
        'ventas_credito' => (float)($ventas['ventas_credito'] ?? 0),

        'abonos_efectivo' => $ab_ef,
        'abonos_deposito' => $ab_dep,
        'abonos_total' => (float)($abonos['abonos_total'] ?? 0),

        'mov_ingresos_efectivo' => $mov_ing_ef,
        'mov_egresos_efectivo' => $mov_egr_ef,
        'mov_ingresos_deposito' => (float)($mov['mov_ingresos_deposito'] ?? 0),
        'mov_egresos_deposito' => (float)($mov['mov_egresos_deposito'] ?? 0),
        'mov_ingresos' => $mov_ing_total,
        'mov_egresos' => $mov_egr_total,

        // Compatibilidad: algunos archivos esperan estos nombres
        'mov_ingresos_total' => $mov_ing_total,
        'mov_egresos_total' => $mov_egr_total,

        // Útil para UI (cierre): efectivo esperado sin monto inicial
        'efectivo_esperado_base' => $efectivo_base,
    ];
}
}
