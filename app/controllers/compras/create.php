<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

$isAjax = is_ajax_request();

try {
    // Importante: el usuario debe venir de sesión (no confiar en POST)
    ensure_session();

    $id_producto     = input_int('id_producto', true);
    $nro_compra      = input_int('nro_compra', true);
    $fecha_compra    = input_date('fecha_compra', true, false);
    $id_proveedor    = input_int('id_proveedor', true);
    $comprobante     = input_str('comprobante', 255, true);

    $id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
    if ($id_usuario <= 0) {
        // Fallback por compatibilidad (si tu sesión aún no está cableada en este módulo)
        $id_usuario = input_int('id_usuario', true);
    }
    $precio_compra   = input_decimal('precio_compra', true);
    $cantidad_compra = input_int('cantidad_compra', true);

    if ($cantidad_compra <= 0) throw new RuntimeException("Cantidad inválida.");

    $pdo->beginTransaction();

    // Bloquear fila del producto para stock consistente
    $st = $pdo->prepare("SELECT stock FROM tb_almacen WHERE id_producto = :id FOR UPDATE");
    $st->execute([':id' => $id_producto]);
    $prod = $st->fetch(PDO::FETCH_ASSOC);
    if (!$prod) throw new RuntimeException("Producto no existe.");

    $stock_actual = (int)($prod['stock'] ?? 0);
    $stock_nuevo  = $stock_actual + $cantidad_compra;

    // Insert compra (ACTIVO)
    $ins = $pdo->prepare("
        INSERT INTO tb_compras
          (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad, estado, fyh_creacion)
        VALUES
          (:id_producto, :nro_compra, :fecha_compra, :id_proveedor, :comprobante, :id_usuario, :precio_compra, :cantidad, 'ACTIVO', :fyh)
    ");
    $ok = $ins->execute([
        ':id_producto'   => $id_producto,
        ':nro_compra'    => $nro_compra,
        ':fecha_compra'  => $fecha_compra,
        ':id_proveedor'  => $id_proveedor,
        ':comprobante'   => $comprobante,
        ':id_usuario'    => $id_usuario,
        ':precio_compra' => $precio_compra,
        ':cantidad'      => $cantidad_compra,
        ':fyh'           => $fechaHora,
    ]);
    if (!$ok) throw new RuntimeException("No se pudo registrar la compra.");

    // Actualizar stock real
    $upd = $pdo->prepare("UPDATE tb_almacen SET stock = :s, fyh_actualizacion = :fyh WHERE id_producto = :id");
    $ok2 = $upd->execute([':s' => $stock_nuevo, ':fyh' => $fechaHora, ':id' => $id_producto]);
    if (!$ok2) throw new RuntimeException("Compra guardada, pero no se pudo actualizar stock.");

    $pdo->commit();

    if ($isAjax) json_response(['ok' => true, 'message' => 'Compra registrada correctamente']);

    ensure_session();
    $_SESSION['mensaje'] = "Compra registrada correctamente";
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
