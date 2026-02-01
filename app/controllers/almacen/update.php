<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Obtener id usuario desde sesión REAL del middleware.
 * Middleware usa: $_SESSION['sesion_id_usuario']
 * Dejamos fallback a otros nombres por compatibilidad.
 */
$sessionUserId =
    (int)($_SESSION['sesion_id_usuario'] ?? 0) ? (int)$_SESSION['sesion_id_usuario']
    : ((int)($_SESSION['id_usuario'] ?? 0) ? (int)$_SESSION['id_usuario']
        : ((int)($_SESSION['user_id'] ?? 0) ? (int)$_SESSION['user_id'] : 0));

if ($sessionUserId <= 0) {
    $_SESSION['mensaje'] = "Sesión no válida. Inicia sesión nuevamente.";
    $_SESSION['icono'] = "error";
    header('Location: ' . rtrim((string)$URL, '/') . '/login');
    exit;
}

// Inputs
$id_producto    = input_int('id_producto', true);
$id_categoria   = input_int('id_categoria', true);
$nombre         = input_str('nombre', 255, true);
$descripcion    = input_str('descripcion', 1000, false);
$stock          = input_int('stock', true);
$stock_minimo   = input_int('stock_minimo', true);
$stock_maximo   = input_int('stock_maximo', true);
$precio_compra  = input_decimal('precio_compra', true);
$precio_venta   = input_decimal('precio_venta', true);
$fecha_ingreso  = input_date('fecha_ingreso', true, false);

// Validaciones
if ($stock < 0 || $stock_minimo < 0 || $stock_maximo < 0) {
    $_SESSION['mensaje'] = "Los valores de stock no pueden ser negativos.";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/almacen/update.php?id=' . $id_producto);
    exit;
}
if ($stock_maximo > 0 && $stock_minimo > $stock_maximo) {
    $_SESSION['mensaje'] = "Stock mínimo no puede ser mayor que stock máximo.";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/almacen/update.php?id=' . $id_producto);
    exit;
}
if ($precio_compra < 0 || $precio_venta < 0) {
    $_SESSION['mensaje'] = "Los precios no pueden ser negativos.";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/almacen/update.php?id=' . $id_producto);
    exit;
}

if (!isset($fechaHora) || !$fechaHora) {
    $fechaHora = date('Y-m-d H:i:s');
}

try {
    // Leer producto actual (imagen real)
    $stmtOld = $pdo->prepare("SELECT imagen FROM tb_almacen WHERE id_producto = :id LIMIT 1");
    $stmtOld->execute([':id' => $id_producto]);
    $rowOld = $stmtOld->fetch(PDO::FETCH_ASSOC);

    if (!$rowOld) {
        $_SESSION['mensaje'] = "Producto no encontrado.";
        $_SESSION['icono'] = "error";
        header('Location: ' . $URL . '/almacen/');
        exit;
    }

    $oldImage = (string)($rowOld['imagen'] ?? '');

    // Imagen (segura)
    $new = upload_product_image('image', false);
    if ($new !== '') {
        $imagen = $new;
        // Limpieza (tu helper debe evitar borrar defaults)
        delete_product_image_files($oldImage);
    } else {
        if ($oldImage === '') {
            $imagen = product_default_image_rel($id_categoria);
        } else {
            if (preg_match('/^defaults\/cat_\d+\.png$/', $oldImage)) {
                $imagen = product_default_image_rel($id_categoria);
            } else {
                $imagen = $oldImage;
            }
        }
    }

    $sql = "
        UPDATE tb_almacen
           SET nombre = :nombre,
               descripcion = :descripcion,
               stock = :stock,
               stock_minimo = :stock_minimo,
               stock_maximo = :stock_maximo,
               precio_compra = :precio_compra,
               precio_venta = :precio_venta,
               fecha_ingreso = :fecha_ingreso,
               imagen = :imagen,
               id_usuario = :id_usuario,
               id_categoria = :id_categoria,
               fyh_actualizacion = :fyh_actualizacion
         WHERE id_producto = :id_producto
         LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre'            => $nombre,
        ':descripcion'       => $descripcion,
        ':stock'             => $stock,
        ':stock_minimo'      => $stock_minimo,
        ':stock_maximo'      => $stock_maximo,
        ':precio_compra'     => (string)$precio_compra,
        ':precio_venta'      => (string)$precio_venta,
        ':fecha_ingreso'     => $fecha_ingreso,
        ':imagen'            => $imagen,
        ':id_usuario'        => $sessionUserId,
        ':id_categoria'      => $id_categoria,
        ':fyh_actualizacion' => $fechaHora,
        ':id_producto'       => $id_producto,
    ]);

    $_SESSION['mensaje'] = "Producto actualizado correctamente.";
    $_SESSION['icono'] = "success";
    header('Location: ' . $URL . '/almacen/');
    exit;
} catch (Throwable $e) {
    $_SESSION['mensaje'] = "Error interno al actualizar el producto.";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/almacen/update.php?id=' . $id_producto);
    exit;
}
