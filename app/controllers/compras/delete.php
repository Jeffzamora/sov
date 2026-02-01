<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

$isAjax = is_ajax_request();

try {
    ensure_session();

    $id_compra = input_int('id_compra', true);

    // Motivo opcional (columna motivo_anulacion)
    $motivo = trim((string)($_POST['motivo_anulacion'] ?? ''));
    if ($motivo !== '' && mb_strlen($motivo) > 255) {
        $motivo = mb_substr($motivo, 0, 255);
    }

    // Ideal: tomar usuario actual de sesión
    $anulado_por = (int)($_SESSION['id_usuario'] ?? 0);
    if ($anulado_por <= 0) $anulado_por = null;

    $pdo->beginTransaction();

    // Bloquear compra
    $st = $pdo->prepare("SELECT id_producto, cantidad, estado FROM tb_compras WHERE id_compra=:id FOR UPDATE");
    $st->execute([':id' => $id_compra]);
    $c = $st->fetch(PDO::FETCH_ASSOC);
    if (!$c) throw new RuntimeException("Compra no encontrada.");
    if (($c['estado'] ?? 'ACTIVO') === 'ANULADO') throw new RuntimeException("La compra ya está ANULADA.");

    $id_producto = (int)$c['id_producto'];
    $cantidad    = (int)$c['cantidad'];

    // Bloquear producto y revertir stock
    $stp = $pdo->prepare("SELECT stock FROM tb_almacen WHERE id_producto=:id FOR UPDATE");
    $stp->execute([':id' => $id_producto]);
    $p = $stp->fetch(PDO::FETCH_ASSOC);
    if (!$p) throw new RuntimeException("Producto no existe.");

    $stock = (int)($p['stock'] ?? 0);
    $stock_nuevo = $stock - $cantidad;
    if ($stock_nuevo < 0) throw new RuntimeException("No se puede anular: stock quedaría negativo.");

    // Marcar compra ANULADA
    $updC = $pdo->prepare("
        UPDATE tb_compras
           SET estado='ANULADO',
               fyh_anulado=:at,
               anulado_por=:por,
               motivo_anulacion=:mot,
               fyh_actualizacion=:fyh
         WHERE id_compra=:id
    ");
    $updC->execute([
        ':at'  => $fechaHora,
        ':por' => $anulado_por,
        ':mot' => ($motivo !== '' ? $motivo : null),
        ':fyh' => $fechaHora,
        ':id'  => $id_compra,
    ]);

    // Actualizar stock
    $updP = $pdo->prepare("UPDATE tb_almacen SET stock=:s, fyh_actualizacion=:fyh WHERE id_producto=:id");
    $updP->execute([':s' => $stock_nuevo, ':fyh' => $fechaHora, ':id' => $id_producto]);

    $pdo->commit();

    if ($isAjax) json_response(['ok' => true, 'message' => 'Compra anulada correctamente']);

    ensure_session();
    $_SESSION['mensaje'] = "Compra anulada correctamente";
    $_SESSION['icono'] = "success";
    header('Location: ' . $URL . '/compras');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($isAjax) json_response(['ok' => false, 'error' => $e->getMessage()], 422);

    ensure_session();
    $_SESSION['mensaje'] = $e->getMessage();
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/compras');
    exit;
}
