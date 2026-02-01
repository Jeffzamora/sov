<?php
/**
 * Devolución parcial/total de una venta (nota de crédito simple).
 *
 * Reglas:
 * - Requiere POST + CSRF.
 * - Solo ventas activas.
 * - Requiere caja ABIERTA.
 * - Solo el usuario que aperturó la caja puede operar.
 * - Devuelve stock (tb_almacen.stock += cantidad devuelta).
 * - Registra egreso en tb_caja_movimientos (para reflejar el reembolso).
 * - Guarda detalle en tb_devoluciones + tb_devoluciones_detalle.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../cajas/_caja_lib.php';

require_post();
csrf_verify();
ensure_session();

if (function_exists('require_perm')) {
    // Permiso granular (si no existe en roles, el admin puede asignarlo)
    require_perm($pdo, 'ventas.devolver', $URL . '/ventas');
}

$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    redirect($URL . '/ventas', 'Sesión inválida.', 'danger');
}

$id_venta = input_int('id_venta', true);
$metodo = strtolower(trim((string)($_POST['metodo_pago'] ?? 'efectivo')));
if (!in_array($metodo, ['efectivo', 'deposito'], true)) $metodo = 'efectivo';
$referencia = input_str('referencia', 100, false);
$motivo = input_str('motivo', 255, false);

// Items: producto_id => cantidad
$ids = $_POST['id_producto'] ?? [];
$qty = $_POST['cantidad'] ?? [];

if (!is_array($ids) || !is_array($qty) || count($ids) === 0) {
    redirect($URL . '/ventas/ver.php?id=' . (int)$id_venta, 'Selecciona al menos 1 producto para devolver.', 'danger');
}

// Confirmar que existan tablas
if (!db_table_exists($pdo, 'tb_devoluciones') || !db_table_exists($pdo, 'tb_devoluciones_detalle')) {
    redirect($URL . '/ventas/ver.php?id=' . (int)$id_venta, 'Falta crear tablas de devoluciones. Ejecuta la migración: db/migrations/20260131_add_devoluciones.sql', 'danger');
}

$pdo->beginTransaction();
try {
    // Bloquear venta
    $st = $pdo->prepare("SELECT * FROM tb_ventas WHERE id_venta=? FOR UPDATE");
    $st->execute([$id_venta]);
    $venta = $st->fetch(PDO::FETCH_ASSOC);
    if (!$venta) throw new RuntimeException('Venta no encontrada.');
    if (($venta['estado'] ?? '') !== 'activa') throw new RuntimeException('La venta está anulada.');

    // Caja abierta actual (global) y validación de operador
    $caja = caja_abierta_actual($pdo);
    if (!$caja) throw new RuntimeException('No hay caja abierta.');
    if (!caja_usuario_es_apertura($caja, $id_usuario)) throw new RuntimeException('La caja está abierta por otro usuario.');

    $id_caja = (int)($caja['id_caja'] ?? 0);
    if ($id_caja <= 0) throw new RuntimeException('Caja inválida.');

    // Cargar detalle venta (para precios y límites)
    $st = $pdo->prepare("SELECT id_producto, cantidad, precio_unitario, descuento_linea, total_linea FROM tb_ventas_detalle WHERE id_venta=?");
    $st->execute([$id_venta]);
    $detalle = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!$detalle) throw new RuntimeException('La venta no tiene detalle.');

    // Vendido por producto
    $vendido = [];
    foreach ($detalle as $d) {
        $pid = (int)($d['id_producto'] ?? 0);
        if ($pid <= 0) continue;
        $vendido[$pid] = [
            'cantidad' => (int)($d['cantidad'] ?? 0),
            'precio_unitario' => (float)($d['precio_unitario'] ?? 0),
            'total_linea' => (float)($d['total_linea'] ?? 0),
        ];
    }

    // Ya devuelto por producto
    $st = $pdo->prepare(
        "SELECT dd.id_producto, SUM(dd.cantidad) AS qty
           FROM tb_devoluciones_detalle dd
           INNER JOIN tb_devoluciones d ON d.id_devolucion = dd.id_devolucion
          WHERE d.id_venta = ?
          GROUP BY dd.id_producto"
    );
    $st->execute([$id_venta]);
    $devRows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $yaDevuelto = [];
    foreach ($devRows as $r) {
        $yaDevuelto[(int)($r['id_producto'] ?? 0)] = (int)($r['qty'] ?? 0);
    }

    // Construir devolución
    $toReturn = []; // pid => qty
    for ($i = 0; $i < count($ids); $i++) {
        $pid = (int)($ids[$i] ?? 0);
        $q = (int)($qty[$i] ?? 0);
        if ($pid <= 0 || $q <= 0) continue;
        $toReturn[$pid] = ($toReturn[$pid] ?? 0) + $q;
    }
    if (!$toReturn) throw new RuntimeException('Cantidades inválidas.');

    // Validar límites
    foreach ($toReturn as $pid => $q) {
        if (!isset($vendido[$pid])) throw new RuntimeException("Producto no pertenece a la venta (ID: $pid)");
        $max = (int)$vendido[$pid]['cantidad'] - (int)($yaDevuelto[$pid] ?? 0);
        if ($max <= 0) throw new RuntimeException("El producto ID $pid ya fue devuelto por completo.");
        if ($q > $max) throw new RuntimeException("Cantidad a devolver excede lo disponible para el producto ID $pid (máx $max).");
    }

    // Bloquear productos
    $prodIds = array_keys($toReturn);
    $place = implode(',', array_fill(0, count($prodIds), '?'));
    $st = $pdo->prepare("SELECT id_producto FROM tb_almacen WHERE id_producto IN ($place) FOR UPDATE");
    $st->execute($prodIds);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $found = [];
    foreach ($rows as $r) $found[(int)$r['id_producto']] = true;
    foreach ($toReturn as $pid => $_) {
        if (empty($found[$pid])) throw new RuntimeException("Producto no encontrado en almacén (ID: $pid)." );
    }

    // Crear devolución (header)
    $monto_total = 0.0;
    foreach ($toReturn as $pid => $q) {
        $pu = (float)$vendido[$pid]['precio_unitario'];
        // monto_linea proporcional a total_linea si hubo descuento
        $total_linea = (float)$vendido[$pid]['total_linea'];
        $cant_vendida = max(1, (int)$vendido[$pid]['cantidad']);
        $monto_unit = $total_linea / $cant_vendida;
        $monto_total += round($monto_unit * $q, 2);
    }
    $monto_total = round($monto_total, 2);
    if ($monto_total <= 0) throw new RuntimeException('Monto de devolución inválido.');

    $st = $pdo->prepare("INSERT INTO tb_devoluciones (id_venta, id_caja, metodo_pago, monto_total, referencia, motivo, id_usuario) VALUES (?,?,?,?,?,?,?)");
    $st->execute([$id_venta, $id_caja, $metodo, $monto_total, ($referencia ?: null), ($motivo ?: null), $id_usuario]);
    $id_dev = (int)$pdo->lastInsertId();

    // Detalle + stock
    $insDet = $pdo->prepare("INSERT INTO tb_devoluciones_detalle (id_devolucion, id_producto, cantidad, precio_unitario, monto_linea) VALUES (?,?,?,?,?)");
    $updStock = $pdo->prepare("UPDATE tb_almacen SET stock = stock + :c WHERE id_producto=:p LIMIT 1");

    foreach ($toReturn as $pid => $q) {
        $pu = (float)$vendido[$pid]['precio_unitario'];
        $total_linea = (float)$vendido[$pid]['total_linea'];
        $cant_vendida = max(1, (int)$vendido[$pid]['cantidad']);
        $monto_unit = $total_linea / $cant_vendida;
        $monto_linea = round($monto_unit * $q, 2);

        $insDet->execute([$id_dev, $pid, $q, $pu, $monto_linea]);
        $updStock->execute([':c' => $q, ':p' => $pid]);
    }

    // Registrar egreso en caja_movimientos para reflejar reembolso
    $concepto = 'Devolución venta #' . (int)($venta['nro_venta'] ?? $id_venta);
    $st = $pdo->prepare("INSERT INTO tb_caja_movimientos (id_caja, tipo, concepto, metodo_pago, monto, referencia, id_usuario) VALUES (?,?,?,?,?,?,?)");
    $st->execute([$id_caja, 'egreso', $concepto, $metodo, $monto_total, ($referencia ?: null), $id_usuario]);

    $pdo->commit();
    redirect($URL . '/ventas/ver.php?id=' . (int)$id_venta, 'Devolución registrada. Reembolso: C$ ' . number_format($monto_total, 2), 'success');
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Devolución error: ' . $e->getMessage());
    redirect($URL . '/ventas/ver.php?id=' . (int)$id_venta, $e->getMessage() ?: 'No se pudo registrar la devolución.', 'danger');
}
