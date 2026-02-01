<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';
require_once __DIR__ . '/_caja_lib.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

require_post();
csrf_verify();
require_perm($pdo, 'cajas.movimiento.anular', $URL . '/cajas');

$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    json_out(['ok' => false, 'error' => 'No autorizado'], 401);
}

// Esta funcionalidad requiere columnas de anulación en tb_caja_movimientos.
$hasEstado = db_column_exists($pdo, 'tb_caja_movimientos', 'estado');
$hasAnuladoPor = db_column_exists($pdo, 'tb_caja_movimientos', 'anulado_por');
$hasAnuladoAt = db_column_exists($pdo, 'tb_caja_movimientos', 'anulado_at');
$hasMotivo = db_column_exists($pdo, 'tb_caja_movimientos', 'motivo_anulacion');
$hasAjusteId = db_column_exists($pdo, 'tb_caja_movimientos', 'id_movimiento_ajuste');
if (!$hasEstado || !$hasAnuladoPor || !$hasAnuladoAt || !$hasMotivo || !$hasAjusteId) {
    json_out(['ok' => false, 'error' => 'Para anular movimientos necesitas aplicar la migración de caja (anulación).'], 422);
}

$id_movimiento = input_int('id_movimiento', true);
$motivo = input_str('motivo', 255, false);
$motivo = trim((string)$motivo);
$motivo = $motivo === '' ? null : $motivo;

/**
 * Política de anulación:
 * - Si la caja del movimiento está ABIERTA: se anula directamente.
 * - Si la caja del movimiento está CERRADA: no se modifica el cierre histórico.
 *   Se crea un MOVIMIENTO INVERSO en la caja ABIERTA actual (ajuste) y luego se anula el movimiento original,
 *   dejando trazabilidad.
 */

$pdo->beginTransaction();
try {
    // Bloquear movimiento + estado de su caja
    $stmt = $pdo->prepare(
        "
        SELECT m.*, c.estado AS caja_estado
          FROM tb_caja_movimientos m
          INNER JOIN tb_cajas c ON c.id_caja = m.id_caja
         WHERE m.id_movimiento = ?
         LIMIT 1
         FOR UPDATE
        "
    );
    $stmt->execute([$id_movimiento]);
    $mov = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mov) throw new RuntimeException('Movimiento no encontrado.');
    if (($mov['estado'] ?? 'activo') === 'anulado') throw new RuntimeException('El movimiento ya está anulado.');

    $caja_estado = (string)($mov['caja_estado'] ?? '');
    $caja_origen = (int)($mov['id_caja'] ?? 0);

    // Si el movimiento pertenece a una caja abierta, validamos que el usuario actual sea quien aperturó.
    if ($caja_estado === 'abierta' && $caja_origen > 0) {
        $stCaja = $pdo->prepare("SELECT * FROM tb_cajas WHERE id_caja=? LIMIT 1");
        $stCaja->execute([$caja_origen]);
        $cajaRow = $stCaja->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($cajaRow && function_exists('caja_usuario_es_apertura') && !caja_usuario_es_apertura($cajaRow, $id_usuario)) {
            throw new RuntimeException('No puedes anular este movimiento: la caja fue aperturada por otro usuario.');
        }
    }

    $id_mov_ajuste = null;

    if ($caja_estado === 'cerrada') {
        $cajaAbierta = caja_abierta_actual($pdo);
        if (!$cajaAbierta) {
            throw new RuntimeException('La caja del movimiento está cerrada. Abra una caja para registrar el ajuste.');
        }
        // Regla operativa: solo el usuario que aperturó la caja abierta puede registrar el ajuste.
        if (function_exists('caja_usuario_es_apertura') && !caja_usuario_es_apertura($cajaAbierta, $id_usuario)) {
            throw new RuntimeException('No puedes anular: la caja abierta para ajuste fue aperturada por otro usuario.');
        }
        $id_caja_ajuste = (int)$cajaAbierta['id_caja'];

        $tipo = (string)($mov['tipo'] ?? '');
        $tipo_inv = $tipo === 'ingreso' ? 'egreso' : 'ingreso';

        $metodo = (string)($mov['metodo_pago'] ?? 'efectivo');
        if (!in_array($metodo, ['efectivo', 'deposito'], true)) $metodo = 'efectivo';

        $monto = round((float)($mov['monto'] ?? 0), 2);
        if ($monto <= 0) {
            throw new RuntimeException('Monto inválido del movimiento original.');
        }

        $concepto = 'Ajuste por anulación mov #' . (int)$id_movimiento . ' (caja cerrada #' . $caja_origen . ')';
        $referencia = $mov['referencia'] ?? null;

        // Insert ajuste inverso
        $stmtIns = $pdo->prepare(
            "
            INSERT INTO tb_caja_movimientos (id_caja, tipo, concepto, metodo_pago, monto, referencia, id_usuario, estado)
            VALUES (:caja,:tipo,:concepto,:metodo,:monto,:ref,:u,'activo')
            "
        );
        $stmtIns->execute([
            ':caja' => $id_caja_ajuste,
            ':tipo' => $tipo_inv,
            ':concepto' => $concepto,
            ':metodo' => $metodo,
            ':monto' => $monto,
            ':ref' => $referencia,
            ':u' => $id_usuario,
        ]);
        $id_mov_ajuste = (int)$pdo->lastInsertId();

        $suffix = ' | Ajuste generado: mov #' . $id_mov_ajuste . ' en caja abierta #' . $id_caja_ajuste;
        $motivo = ($motivo ? $motivo : 'Anulado con ajuste') . $suffix;
    } elseif ($caja_estado !== 'abierta') {
        throw new RuntimeException('Estado de caja inválido para anulación.');
    }

    // Anular original
    $stmtUp = $pdo->prepare(
        "
        UPDATE tb_caja_movimientos
           SET estado='anulado',
               anulado_por=:u,
               anulado_at=NOW(),
               motivo_anulacion=:mot,
               id_movimiento_ajuste=:aj
         WHERE id_movimiento=:id
         LIMIT 1
        "
    );
    $stmtUp->execute([
        ':u' => $id_usuario,
        ':mot' => $motivo,
        ':aj' => $id_mov_ajuste,
        ':id' => $id_movimiento,
    ]);

    $pdo->commit();

    $msg = ($caja_estado === 'cerrada')
      ? 'Movimiento anulado. Se registró un ajuste inverso en la caja abierta.'
      : 'Movimiento anulado correctamente.';
    json_out(['ok' => true, 'message' => $msg, 'id_movimiento' => $id_movimiento, 'id_movimiento_ajuste' => $id_mov_ajuste], 200);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Caja anular movimiento error: ' . $e->getMessage());
    json_out(['ok' => false, 'error' => $e->getMessage() ?: 'No se pudo anular el movimiento.'], 500);
}
