<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/_caja_lib.php';

require_post();
csrf_verify();
ensure_session();
require_perm($pdo, 'cajas.movimiento.crear', $URL . '/cajas');

$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    redirect($URL . '/login', 'Sesión no válida. Inicia sesión nuevamente.', 'danger');
}

$tipo        = input_str('tipo', 20, true);
$concepto    = input_str('concepto', 150, true);
$metodo_pago = input_str('metodo_pago', 20, true);
$monto       = input_decimal('monto', true);
$referencia  = input_str('referencia', 100, false);

$tipo = trim((string)$tipo);
$metodo_pago = trim((string)$metodo_pago);
$concepto = trim((string)$concepto);
$referencia = trim((string)$referencia);

if (!in_array($tipo, ['ingreso', 'egreso'], true)) {
    redirect($URL . '/cajas', 'Tipo de movimiento inválido.', 'danger');
}
if (!in_array($metodo_pago, ['efectivo', 'deposito'], true)) {
    redirect($URL . '/cajas', 'Método de pago inválido.', 'danger');
}
if ($concepto === '' || mb_strlen($concepto) < 3) {
    redirect($URL . '/cajas', 'El concepto es obligatorio (mínimo 3 caracteres).', 'danger');
}

$monto_f = round((float)$monto, 2);
if ($monto_f <= 0) {
    redirect($URL . '/cajas', 'El monto debe ser mayor que 0.', 'danger');
}

$pdo->beginTransaction();
try {
    $caja = caja_abierta_actual($pdo);
    if (!$caja) {
        throw new RuntimeException('No hay caja abierta. Primero aperture caja.');
    }

    // Regla operativa: solo el usuario que aperturó puede registrar movimientos.
    if (function_exists('caja_usuario_es_apertura') && !caja_usuario_es_apertura($caja, $id_usuario)) {
        throw new RuntimeException('No puedes registrar movimientos: la caja fue aperturada por otro usuario.');
    }

    $id_caja = (int)$caja['id_caja'];

    $hasEstado = db_column_exists($pdo, 'tb_caja_movimientos', 'estado');

    if ($hasEstado) {
        $stmt = $pdo->prepare(
            "INSERT INTO tb_caja_movimientos (id_caja, tipo, concepto, metodo_pago, monto, referencia, id_usuario, estado)
             VALUES (:caja,:tipo,:concepto,:metodo,:monto,:ref,:u,'activo')"
        );
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tb_caja_movimientos (id_caja, tipo, concepto, metodo_pago, monto, referencia, id_usuario)
             VALUES (:caja,:tipo,:concepto,:metodo,:monto,:ref,:u)"
        );
    }

    $stmt->execute([
        ':caja' => $id_caja,
        ':tipo' => $tipo,
        ':concepto' => $concepto,
        ':metodo' => $metodo_pago,
        ':monto' => $monto_f,
        ':ref' => $referencia !== '' ? $referencia : null,
        ':u' => $id_usuario,
    ]);

    $pdo->commit();
    redirect($URL . '/cajas', 'Movimiento registrado.', 'success');
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Caja movimiento error: ' . $e->getMessage());
    redirect($URL . '/cajas', $e->getMessage() ?: 'No se pudo registrar el movimiento.', 'danger');
}
