<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/_ventas_lib.php';

require_post();
csrf_verify();

// Seguridad: no confiar en id_usuario enviado por el cliente.
// Si hay sesión activa, usamos el usuario autenticado.
ensure_session();
if (function_exists('require_perm')) {
    // Permiso granular
    require_perm($pdo, 'ventas.crear', $URL . '/ventas');
}

$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    // Fallback para instalaciones antiguas: si no hay sesión, acepta el POST.
    $id_usuario = input_int('id_usuario', true);
}
$id_cliente = input_int('id_cliente', true);
$metodo_pago = input_str('metodo_pago', 20, true);
$descuento = input_decimal('descuento', false);
$impuesto = input_decimal('impuesto', false);
$pagado_inicial = input_decimal('pagado_inicial', false);
$nota = input_str('nota', 255, false);

if (!in_array($metodo_pago, ['efectivo','deposito','credito','mixto'], true)) {
    redirect($URL . '/ventas/create.php', 'Método de pago inválido.', 'danger');
}

$items = ventas_parse_items();
if (!$items) {
    redirect($URL . '/ventas/create.php', 'Agrega al menos 1 producto.', 'danger');
}

$pdo->beginTransaction();
try {
    $caja = caja_abierta_actual($pdo);
    if (!$caja) throw new RuntimeException('No hay caja abierta. Aperture la caja para poder vender.');
    // Regla operativa: solo el usuario que aperturó la caja puede vender.
    if (function_exists('caja_usuario_es_apertura') && !caja_usuario_es_apertura($caja, (int)$id_usuario)) {
        throw new RuntimeException('La caja está abierta por otro usuario. Solo quien aperturó puede vender/cobrar.');
    }
    $id_caja = (int)$caja['id_caja'];

    // Bloquear productos para validar stock
    $byId = [];
    foreach ($items as $it) {
        $byId[$it['id_producto']] = ($byId[$it['id_producto']] ?? 0) + $it['cantidad'];
    }
    $ids = array_keys($byId);
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id_producto, nombre, stock, precio_venta FROM tb_almacen WHERE id_producto IN ($place) FOR UPDATE");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stockMap = [];
    foreach ($rows as $r) $stockMap[(int)$r['id_producto']] = $r;

    foreach ($byId as $idp => $cant) {
        if (!isset($stockMap[$idp])) {
            throw new RuntimeException("Producto no encontrado (ID: $idp).");
        }
        $stk = (int)$stockMap[$idp]['stock'];
        if ($stk < $cant) {
            $nom = $stockMap[$idp]['nombre'] ?? 'Producto';
            throw new RuntimeException("Stock insuficiente para $nom. Disponible: $stk, requerido: $cant.");
        }
    }

    // Totales
    $t = ventas_calcular_totales($items, (float)$descuento, (float)$impuesto);

    // Reglas de pago
    $total = (float)$t['total'];
    $pagado_inicial = max(0.0, (float)$pagado_inicial);

    if ($metodo_pago === 'efectivo' || $metodo_pago === 'deposito') {
        $pagado_inicial = $total;
        $saldo = 0.0;
    } elseif ($metodo_pago === 'credito') {
        // Se permite 0 o un abono inicial
        $pagado_inicial = min($pagado_inicial, $total);
        $saldo = $total - $pagado_inicial;
    } else { // mixto
        // Mixto: el usuario define pagado inicial, lo restante queda a crédito
        $pagado_inicial = min($pagado_inicial, $total);
        $saldo = $total - $pagado_inicial;
        if ($saldo <= 0) {
            // si no hay saldo, en realidad no es mixto: lo dejamos efectivo por defecto
            $metodo_pago = 'efectivo';
            $pagado_inicial = $total;
            $saldo = 0.0;
        }
    }

    // Nro venta
    $nro = ventas_next_nro($pdo);

    $stmt = $pdo->prepare("
        INSERT INTO tb_ventas
        (nro_venta, id_cliente, id_usuario, id_caja, subtotal, descuento, impuesto, total, metodo_pago, pagado_inicial, saldo_pendiente, nota)
        VALUES
        (:nro, :cli, :usr, :caja, :sub, :des, :imp, :tot, :met, :pag, :sal, :nota)
    ");
    $stmt->execute([
        ':nro' => $nro,
        ':cli' => $id_cliente,
        ':usr' => $id_usuario,
        ':caja' => $id_caja,
        ':sub' => $t['subtotal'],
        ':des' => $t['descuento'],
        ':imp' => $t['impuesto'],
        ':tot' => $t['total'],
        ':met' => $metodo_pago,
        ':pag' => $pagado_inicial,
        ':sal' => $saldo,
        ':nota' => $nota ?: null,
    ]);
    $id_venta = (int)$pdo->lastInsertId();

    // Detalle + descontar stock
    $stmtDet = $pdo->prepare("
        INSERT INTO tb_ventas_detalle (id_venta, id_producto, cantidad, precio_unitario, descuento_linea, total_linea)
        VALUES (:v, :p, :c, :pu, 0, :tl)
    ");
    $stmtUpd = $pdo->prepare("UPDATE tb_almacen SET stock = stock - :c WHERE id_producto = :p LIMIT 1");

    foreach ($items as $it) {
        $cant = (int)$it['cantidad'];
        $pu = (float)$it['precio_unitario'];
        $tl = $cant * $pu;
        $stmtDet->execute([
            ':v' => $id_venta,
            ':p' => (int)$it['id_producto'],
            ':c' => $cant,
            ':pu' => $pu,
            ':tl' => $tl,
        ]);
        $stmtUpd->execute([
            ':c' => $cant,
            ':p' => (int)$it['id_producto'],
        ]);
    }

    // NOTA IMPORTANTE:
    // NO registrar aquí el pago inicial en tb_ventas_pagos.
    // Ese tabla está diseñada para "abonos" posteriores, y la caja ya suma
    // tb_ventas.pagado_inicial. Si insertamos aquí, se DUPLICAN los totales.

    $pdo->commit();
    redirect($URL . '/ventas/voucher.php?id=' . $id_venta, 'Venta registrada.', 'success');
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Venta create error: ' . $e->getMessage());
    redirect($URL . '/ventas/create.php', $e->getMessage() ?: 'No se pudo registrar la venta.', 'danger');
}
