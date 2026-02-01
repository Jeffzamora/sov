<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/_caja_lib.php';

require_post();
csrf_verify();
ensure_session();
require_perm($pdo, 'cajas.cerrar', $URL . '/cajas');

$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    redirect($URL . '/login', 'Sesión no válida. Inicia sesión nuevamente.', 'danger');
}

$id_caja = input_int('id_caja', true);
$monto_cierre_efectivo_str = input_decimal('monto_cierre_efectivo', true);
$observacion = input_str('observacion_cierre', 255, false);

$monto_cierre_efectivo = round((float)$monto_cierre_efectivo_str, 2);
if ($monto_cierre_efectivo < 0) {
    redirect($URL . '/cajas', 'El efectivo contado no puede ser negativo.', 'danger');
}
$observacion = trim((string)$observacion);
$observacion = $observacion === '' ? null : $observacion;

$pdo->beginTransaction();
try {
    // Bloqueo de la caja para cierre consistente
    $stmt = $pdo->prepare("SELECT * FROM tb_cajas WHERE id_caja=? LIMIT 1 FOR UPDATE");
    $stmt->execute([$id_caja]);
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$caja) throw new RuntimeException('Caja no encontrada.');
    if (($caja['estado'] ?? '') !== 'abierta') throw new RuntimeException('La caja ya está cerrada.');

    // Regla operativa: solo el usuario que aperturó puede cerrar.
    if (function_exists('caja_usuario_es_apertura') && !caja_usuario_es_apertura($caja, $id_usuario)) {
        throw new RuntimeException('No puedes cerrar: la caja fue aperturada por otro usuario.');
    }

    $tot = caja_calcular_totales($pdo, $id_caja);

    $monto_inicial = round((float)($caja['monto_inicial'] ?? 0), 2);
    $ventas_ef = round((float)($tot['ventas_efectivo'] ?? 0), 2);
    $abonos_ef = round((float)($tot['abonos_efectivo'] ?? 0), 2);
    $mov_ing_ef = round((float)($tot['mov_ingresos_efectivo'] ?? 0), 2);
    $mov_egr_ef = round((float)($tot['mov_egresos_efectivo'] ?? 0), 2);

    $efectivo_esperado = round(($monto_inicial + $ventas_ef + $abonos_ef + $mov_ing_ef) - $mov_egr_ef, 2);
    $diferencia = round($monto_cierre_efectivo - $efectivo_esperado, 2);

    $total_efectivo = round($ventas_ef + $abonos_ef + $mov_ing_ef - $mov_egr_ef, 2);
    $total_deposito = round(
        (float)($tot['ventas_deposito'] ?? 0)
        + (float)($tot['abonos_deposito'] ?? 0)
        + (float)($tot['mov_ingresos_deposito'] ?? 0)
        - (float)($tot['mov_egresos_deposito'] ?? 0),
        2
    );
    $total_credito = round((float)($tot['ventas_credito'] ?? 0), 2);

    // Totales globales para auditoría
    $total_ingresos = round($ventas_ef + (float)($tot['ventas_deposito'] ?? 0) + (float)($tot['abonos_total'] ?? 0) + (float)($tot['mov_ingresos'] ?? 0), 2);
    $total_egresos  = round((float)($tot['mov_egresos'] ?? 0), 2);

    // Construir UPDATE dinámico según columnas existentes (evita errores por columnas no migradas)
    $sets = [
        'fecha_cierre = NOW()',
        'usuario_cierre_id = :uc',
        'total_efectivo = :te',
        'total_deposito = :td',
        'total_credito = :tc',
        'total_abonos = :ta',
        'total_ingresos = :ti',
        'total_egresos = :tg',
        "estado = 'cerrada'",
    ];

    // Observación
    if (db_column_exists($pdo, 'tb_cajas', 'observacion_cierre')) {
        $sets[] = 'observacion_cierre = :obs';
    }

    // Efectivo contado
    if (db_column_exists($pdo, 'tb_cajas', 'efectivo_contado')) {
        $sets[] = 'efectivo_contado = :ec';
    } elseif (db_column_exists($pdo, 'tb_cajas', 'monto_cierre_efectivo')) {
        $sets[] = 'monto_cierre_efectivo = :ec';
    }

    // Efectivo esperado
    if (db_column_exists($pdo, 'tb_cajas', 'efectivo_esperado')) {
        $sets[] = 'efectivo_esperado = :ee';
    } elseif (db_column_exists($pdo, 'tb_cajas', 'monto_esperado_efectivo')) {
        $sets[] = 'monto_esperado_efectivo = :ee';
    }

    // Diferencia
    if (db_column_exists($pdo, 'tb_cajas', 'diferencia_cierre')) {
        $sets[] = 'diferencia_cierre = :dif';
    } elseif (db_column_exists($pdo, 'tb_cajas', 'diferencia_efectivo')) {
        $sets[] = 'diferencia_efectivo = :dif';
    } elseif (db_column_exists($pdo, 'tb_cajas', 'diferencia')) {
        $sets[] = 'diferencia = :dif';
    }

    $sqlUp = 'UPDATE tb_cajas SET ' . implode(",\n                ", $sets) . ' WHERE id_caja = :id LIMIT 1';
    $stmt = $pdo->prepare($sqlUp);
    $stmt->execute([
        ':uc' => $id_usuario,
        ':te' => $total_efectivo,
        ':td' => $total_deposito,
        ':tc' => $total_credito,
        ':ta' => (float)($tot['abonos_total'] ?? 0),
        ':ti' => $total_ingresos,
        ':tg' => $total_egresos,
        ':obs' => $observacion,
        ':ec'  => $monto_cierre_efectivo,
        ':ee'  => $efectivo_esperado,
        ':dif' => $diferencia,
        ':id'  => $id_caja,
    ]);

    $pdo->commit();
    redirect($URL . '/cajas', 'Caja cerrada correctamente.', 'success');
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Caja cierre error: ' . $e->getMessage());
    redirect($URL . '/cajas', $e->getMessage() ?: 'No se pudo cerrar la caja.', 'danger');
}
