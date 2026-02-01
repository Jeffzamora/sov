<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

$isAjax = is_ajax_request();

try {
    ensure_session();

    // 1) Leer inputs primero (IMPORTANTE: antes de usar $id_compra)
    $id_compra       = input_int('id_compra', true);
    $id_producto_new = input_int('id_producto', true);
    $nro_compra      = input_int('nro_compra', true);
    $fecha_compra    = input_date('fecha_compra', true, false);
    $id_proveedor    = input_int('id_proveedor', true);
    $comprobante     = input_str('comprobante', 255, true);
    $precio_compra   = input_decimal('precio_compra', true);
    $cantidad_new    = input_int('cantidad_compra', true);

    // Usuario en sesión (fallback a POST por compatibilidad)
    $id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
    if ($id_usuario <= 0) {
        $id_usuario = input_int('id_usuario', true);
    }

    if ($cantidad_new <= 0) {
        throw new RuntimeException("Cantidad inválida.");
    }

    // 2) Bloqueo: NO permitir editar compras anuladas (una sola vez)
    $chk = $pdo->prepare("SELECT fyh_anulado, estado FROM tb_compras WHERE id_compra = :id LIMIT 1");
    $chk->execute([':id' => $id_compra]);
    $st = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$st) {
        if ($isAjax) {
            json_response(['ok' => false, 'error' => 'Compra no encontrada'], 404);
            exit;
        }
        $_SESSION['mensaje'] = "Compra no encontrada";
        $_SESSION['icono'] = "error";
        header('Location: ' . $URL . '/compras');
        exit;
    }

    if (!empty($st['fyh_anulado']) || (($st['estado'] ?? '') === 'ANULADO')) {
        if ($isAjax) {
            json_response(['ok' => false, 'error' => 'No se puede editar: la compra está ANULADA.'], 409);
            exit;
        }
        $_SESSION['mensaje'] = "No se puede editar: la compra está ANULADA.";
        $_SESSION['icono'] = "warning";
        header('Location: ' . $URL . '/compras');
        exit;
    }

    $pdo->beginTransaction();

    // 3) Traer compra actual bloqueada
    $st2 = $pdo->prepare("SELECT id_producto, cantidad, estado FROM tb_compras WHERE id_compra = :id FOR UPDATE");
    $st2->execute([':id' => $id_compra]);
    $old = $st2->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        throw new RuntimeException("Compra no encontrada.");
    }
    if (($old['estado'] ?? 'ACTIVO') === 'ANULADO') {
        throw new RuntimeException("No se puede editar: compra ANULADA.");
    }

    $id_producto_old = (int)$old['id_producto'];
    $cantidad_old    = (int)$old['cantidad'];

    // 4) Ajuste stock (delta)
    if ($id_producto_new === $id_producto_old) {
        // mismo producto => stock += (new - old)
        $stp = $pdo->prepare("SELECT stock FROM tb_almacen WHERE id_producto = :id FOR UPDATE");
        $stp->execute([':id' => $id_producto_new]);
        $p = $stp->fetch(PDO::FETCH_ASSOC);
        if (!$p) throw new RuntimeException("Producto no existe.");

        $stock = (int)($p['stock'] ?? 0);
        $delta = $cantidad_new - $cantidad_old;
        $stock_nuevo = $stock + $delta;
        if ($stock_nuevo < 0) throw new RuntimeException("Stock negativo: revisa cantidades.");

        $updStock = $pdo->prepare("UPDATE tb_almacen SET stock=:s, fyh_actualizacion=:fyh WHERE id_producto=:id");
        $updStock->execute([':s' => $stock_nuevo, ':fyh' => $fechaHora, ':id' => $id_producto_new]);
    } else {
        // producto cambió: revertir stock en producto_old
        $stOldP = $pdo->prepare("SELECT stock FROM tb_almacen WHERE id_producto = :id FOR UPDATE");
        $stOldP->execute([':id' => $id_producto_old]);
        $pOld = $stOldP->fetch(PDO::FETCH_ASSOC);
        if (!$pOld) throw new RuntimeException("Producto anterior no existe.");

        $stockOld = (int)($pOld['stock'] ?? 0);
        $stockOldNuevo = $stockOld - $cantidad_old;
        if ($stockOldNuevo < 0) throw new RuntimeException("Stock negativo al revertir producto anterior.");

        $pdo->prepare("UPDATE tb_almacen SET stock=:s, fyh_actualizacion=:fyh WHERE id_producto=:id")
            ->execute([':s' => $stockOldNuevo, ':fyh' => $fechaHora, ':id' => $id_producto_old]);

        // aplicar stock en producto_new
        $stNewP = $pdo->prepare("SELECT stock FROM tb_almacen WHERE id_producto = :id FOR UPDATE");
        $stNewP->execute([':id' => $id_producto_new]);
        $pNew = $stNewP->fetch(PDO::FETCH_ASSOC);
        if (!$pNew) throw new RuntimeException("Producto nuevo no existe.");

        $stockNew = (int)($pNew['stock'] ?? 0);
        $stockNewNuevo = $stockNew + $cantidad_new;

        $pdo->prepare("UPDATE tb_almacen SET stock=:s, fyh_actualizacion=:fyh WHERE id_producto=:id")
            ->execute([':s' => $stockNewNuevo, ':fyh' => $fechaHora, ':id' => $id_producto_new]);
    }

    // 5) Actualizar compra
    $upd = $pdo->prepare("
        UPDATE tb_compras SET
          id_producto = :id_producto,
          nro_compra = :nro_compra,
          fecha_compra = :fecha_compra,
          id_proveedor = :id_proveedor,
          comprobante = :comprobante,
          id_usuario = :id_usuario,
          precio_compra = :precio_compra,
          cantidad = :cantidad,
          fyh_actualizacion = :fyh
        WHERE id_compra = :id_compra
    ");
    $ok = $upd->execute([
        ':id_producto'   => $id_producto_new,
        ':nro_compra'    => $nro_compra,
        ':fecha_compra'  => $fecha_compra,
        ':id_proveedor'  => $id_proveedor,
        ':comprobante'   => $comprobante,
        ':id_usuario'    => $id_usuario,
        ':precio_compra' => $precio_compra,
        ':cantidad'      => $cantidad_new,
        ':fyh'           => $fechaHora,
        ':id_compra'     => $id_compra,
    ]);
    if (!$ok) throw new RuntimeException("No se pudo actualizar la compra.");

    $pdo->commit();

    if ($isAjax) {
        json_response(['ok' => true, 'message' => 'Compra actualizada correctamente']);
        exit;
    }

    $_SESSION['mensaje'] = "Compra actualizada correctamente";
    $_SESSION['icono'] = "success";
    header('Location: ' . $URL . '/compras');
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();

    if ($isAjax) {
        json_response(['ok' => false, 'error' => $e->getMessage()], 422);
        exit;
    }

    ensure_session();
    $_SESSION['mensaje'] = $e->getMessage();
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/compras');
    exit;
}
