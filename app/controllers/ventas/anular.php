<?php
/**
 * Anular venta.
 *
 * Reglas:
 * - Solo ventas activas.
 * - La caja asociada a la venta debe estar ABIERTA.
 * - Seguridad: usa el usuario de la sesión.
 * - Reversa stock (tb_almacen.stock += cantidad vendida).
 * - Al marcar la venta como 'anulada', los totales de caja se ajustan automáticamente
 *   porque los cálculos filtran v.estado='activa'.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../cajas/_caja_lib.php';

require_post();
csrf_verify();
ensure_session();

if (function_exists('require_perm')) {
    require_perm($pdo, 'ventas.anular', $URL . '/ventas');
}

$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    redirect($URL . '/ventas', 'Sesión inválida.', 'danger');
}

$id_venta = input_int('id_venta', true);
$motivo = input_str('motivo', 255, false);

$pdo->beginTransaction();
try {
    // Bloquear venta
    $st = $pdo->prepare("SELECT * FROM tb_ventas WHERE id_venta=? FOR UPDATE");
    $st->execute([$id_venta]);
    $venta = $st->fetch(PDO::FETCH_ASSOC);
    if (!$venta) throw new RuntimeException('Venta no encontrada.');
    if (($venta['estado'] ?? '') !== 'activa') throw new RuntimeException('La venta ya está anulada.');

    $id_caja = (int)($venta['id_caja'] ?? 0);
    if ($id_caja <= 0) throw new RuntimeException('La venta no tiene caja asociada.');

    // Caja de la venta
    $st = $pdo->prepare("SELECT * FROM tb_cajas WHERE id_caja=? LIMIT 1");
    $st->execute([$id_caja]);
    $caja = $st->fetch(PDO::FETCH_ASSOC);
    if (!$caja) throw new RuntimeException('Caja no encontrada.');

    // Caja de operación: por defecto la misma de la venta.
    // Si la caja de la venta está CERRADA, requiere una caja ABIERTA actual
    // para registrar el ajuste (reembolso/egreso) y mantener contabilidad consistente.
    $caja_oper = $caja;
    if (($caja['estado'] ?? '') !== 'abierta') {
        $abierta = caja_abierta_actual($pdo);
        if (!$abierta) {
            throw new RuntimeException('La caja de esta venta está cerrada y no hay una caja abierta para registrar el ajuste.');
        }
        // Solo el usuario que aperturó puede operar la caja abierta
        if (function_exists('caja_usuario_es_apertura') && !caja_usuario_es_apertura($abierta, $id_usuario)) {
            throw new RuntimeException('Hay una caja abierta pero está aperturada por otro usuario.');
        }
        $caja_oper = $abierta;
    } else {
        // Si la caja está abierta, solo el aperturador puede operar
        if (function_exists('caja_usuario_es_apertura') && !caja_usuario_es_apertura($caja, $id_usuario)) {
            throw new RuntimeException('No puedes anular: la caja está abierta por otro usuario.');
        }
    }

    // Reversa de stock
    $st = $pdo->prepare("SELECT id_producto, cantidad FROM tb_ventas_detalle WHERE id_venta=?");
    $st->execute([$id_venta]);
    $det = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!$det) throw new RuntimeException('La venta no tiene detalle.');

    // Bloquear productos
    $byId = [];
    foreach ($det as $d) {
        $pid = (int)($d['id_producto'] ?? 0);
        $cant = (int)($d['cantidad'] ?? 0);
        if ($pid > 0 && $cant > 0) $byId[$pid] = ($byId[$pid] ?? 0) + $cant;
    }
    if (!$byId) throw new RuntimeException('Detalle inválido.');

    $ids = array_keys($byId);
    $place = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT id_producto FROM tb_almacen WHERE id_producto IN ($place) FOR UPDATE");
    $st->execute($ids);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $found = [];
    foreach ($rows as $r) $found[(int)$r['id_producto']] = true;
    foreach ($byId as $pid => $_) {
        if (!isset($found[$pid])) throw new RuntimeException("Producto no encontrado en almacén (ID: $pid).");
    }

    $upd = $pdo->prepare("UPDATE tb_almacen SET stock = stock + :c WHERE id_producto=:p LIMIT 1");
    foreach ($byId as $pid => $cant) {
        $upd->execute([':c' => (int)$cant, ':p' => (int)$pid]);
    }

    // Campos opcionales (si existen)
    $cols = [];
    try {
        $cst = $pdo->query("SHOW COLUMNS FROM tb_ventas");
        $cols = $cst ? array_map(fn($x) => $x['Field'], $cst->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (Throwable $e) {
        $cols = [];
    }

    $set = ["estado='anulada'", "saldo_pendiente=0"]; // saldo 0 para evitar créditos activos
    $params = [];

    if (in_array('fecha_anulacion', $cols, true)) {
        $set[] = "fecha_anulacion=NOW()";
    }
    if (in_array('anulada_por', $cols, true)) {
        $set[] = "anulada_por=?";
        $params[] = $id_usuario;
    }
    if (in_array('motivo_anulacion', $cols, true)) {
        $set[] = "motivo_anulacion=?";
        $params[] = ($motivo ?: null);
    }

    $params[] = $id_venta;
    $sql = "UPDATE tb_ventas SET " . implode(', ', $set) . " WHERE id_venta=? LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute($params);

    // Si se está anulando una venta de una caja ya cerrada, registra un ajuste (egreso)
    // en la caja abierta actual para reflejar el reembolso en el momento de la anulación.
    $idCajaOper = (int)($caja_oper['id_caja'] ?? 0);
    $idCajaVenta = (int)($caja['id_caja'] ?? 0);
    if ($idCajaOper > 0 && $idCajaOper !== $idCajaVenta) {
        $st = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM tb_ventas_pagos WHERE id_venta=?");
        $st->execute([$id_venta]);
        $pagos_extra = (float)$st->fetchColumn();

        $pagado_inicial = (float)($venta['pagado_inicial'] ?? 0);
        $reembolso = round(max(0, $pagado_inicial + $pagos_extra), 2);

        if ($reembolso > 0) {
            $mp = strtolower(trim((string)($venta['metodo_pago'] ?? 'efectivo')));
            $mp = ($mp === 'deposito') ? 'deposito' : 'efectivo';
            $concepto = 'Ajuste anulación venta #' . (int)($venta['nro_venta'] ?? $id_venta);
            $st = $pdo->prepare("INSERT INTO tb_caja_movimientos (id_caja, tipo, concepto, metodo_pago, monto, referencia, id_usuario)
                                 VALUES (?,?,?,?,?,?,?)");
            $st->execute([$idCajaOper, 'egreso', $concepto, $mp, $reembolso, null, $id_usuario]);
        }
    }
    $pdo->commit();

    // Auditoría
    if (function_exists('auditoria_log')) {
        auditoria_log($pdo, 'ANULAR', 'tb_ventas', $id_venta, 'Venta anulada');
    }

    redirect($URL . '/ventas/ver.php?id=' . $id_venta, 'Venta anulada correctamente.', 'success');
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Venta anular error: ' . $e->getMessage());
    redirect($URL . '/ventas/ver.php?id=' . (int)$id_venta, $e->getMessage() ?: 'No se pudo anular la venta.', 'danger');
}
