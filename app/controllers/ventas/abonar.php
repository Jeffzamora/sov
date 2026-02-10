<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/_ventas_lib.php';

require_post();
csrf_verify();

// Seguridad: id_usuario siempre desde sesión si existe.
ensure_session();
if (function_exists('require_perm')) {
    require_perm($pdo, 'ventas.abonar', $URL . '/ventas');
}

$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    // Fallback legacy
    $id_usuario = input_int('id_usuario', true);
}
$id_venta = input_int('id_venta', true);
$metodo_pago = input_str('metodo_pago', 20, true);
$monto = input_decimal('monto', true);
$referencia = input_str('referencia', 100, false);

if (!in_array($metodo_pago, ['efectivo','deposito'], true)) {
    redirect($URL . '/ventas', 'Método de pago inválido.', 'danger');
}

$pdo->beginTransaction();
try {
    $caja = caja_abierta_actual($pdo);
    if (!$caja) throw new RuntimeException('No hay caja abierta. Aperture la caja para registrar abonos.');
    // Regla operativa: solo el usuario que aperturó la caja puede cobrar/registrar abonos.
    if (function_exists('caja_usuario_es_apertura') && !caja_usuario_es_apertura($caja, (int)$id_usuario)) {
        throw new RuntimeException('La caja está abierta por otro usuario. Solo quien aperturó puede cobrar/registrar abonos.');
    }
    $id_caja = (int)$caja['id_caja'];

    $stmt = $pdo->prepare("SELECT * FROM tb_ventas WHERE id_venta=? FOR UPDATE");
    $stmt->execute([$id_venta]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$venta) throw new RuntimeException('Venta no encontrada.');
    if (($venta['estado'] ?? '') !== 'activa') throw new RuntimeException('La venta está anulada.');
    $saldo = (float)($venta['saldo_pendiente'] ?? 0);
    if ($saldo <= 0) throw new RuntimeException('La venta no tiene saldo pendiente.');

    $monto = min($monto, $saldo);
    if ($monto <= 0) throw new RuntimeException('Monto inválido.');

    $stmt = $pdo->prepare("
        INSERT INTO tb_ventas_pagos (id_venta, id_caja, metodo_pago, monto, referencia, id_usuario)
        VALUES (:v, :c, :met, :m, :ref, :u)
    ");
    $stmt->execute([
        ':v' => $id_venta,
        ':c' => $id_caja,
        ':met' => $metodo_pago,
        ':m' => $monto,
        ':ref' => $referencia ?: null,
        ':u' => $id_usuario,
    ]);

	$id_pago = (int)$pdo->lastInsertId();

	// Nunca permitir saldo negativo
	$stmt = $pdo->prepare("UPDATE tb_ventas SET saldo_pendiente = GREATEST(0, saldo_pendiente - :m) WHERE id_venta = :v LIMIT 1");
	$stmt->execute([':m' => $monto, ':v' => $id_venta]);

	// Si ya quedó cancelada la deuda, normalizamos saldo a 0 exacto
	$stmt = $pdo->prepare("SELECT saldo_pendiente FROM tb_ventas WHERE id_venta=? LIMIT 1");
	$stmt->execute([$id_venta]);
	$saldoNuevo = (float)($stmt->fetchColumn() ?? 0);
	if ($saldoNuevo <= 0) {
	    $pdo->prepare("UPDATE tb_ventas SET saldo_pendiente = 0 WHERE id_venta=? LIMIT 1")->execute([$id_venta]);
	}

    $pdo->commit();

	// Auditoría
	if (function_exists('auditoria_log')) {
		auditoria_log($pdo, 'PAGO', 'tb_ventas_pagos', $id_pago, 'Abono registrado');
	}

	// Ir directo al voucher del abono (ideal para imprimir térmica)
	redirect($URL . '/ventas/abono_voucher.php?id_pago=' . $id_pago, 'Abono registrado.', 'success');
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Abono error: ' . $e->getMessage());
    redirect($URL . '/ventas', $e->getMessage() ?: 'No se pudo registrar el abono.', 'danger');
}
