<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function flash_redirect(string $msg, string $icon, string $to): void
{
  $_SESSION['mensaje'] = $msg;
  $_SESSION['icono'] = $icon;
  header('Location: ' . $to);
  exit;
}

// Campos reales de tu BD / formulario
$codigo        = input_str('codigo', 64, true);
$id_categoria  = input_int('id_categoria', true);
$nombre        = input_str('nombre', 255, true);
$id_usuario    = input_int('id_usuario', true);

$descripcion   = input_str('descripcion', 1000, false);
$stock         = input_int('stock', true);
$stock_minimo  = input_int('stock_minimo', false); // puede venir vacío
$stock_maximo  = input_int('stock_maximo', false);

$precio_compra = input_decimal('precio_compra', true);
$precio_venta  = input_decimal('precio_venta', true);
$fecha_ingreso = input_date('fecha_ingreso', true, false);

// Normalizaciones por si vienen vacíos (evitar null/warnings)
$stock_minimo = $stock_minimo === null ? 0 : (int)$stock_minimo;
$stock_maximo = $stock_maximo === null ? 0 : (int)$stock_maximo;

// Imagen
$uploaded = upload_product_image('image', false); // tu form usa name="image"
$imagen   = ($uploaded !== '') ? $uploaded : product_default_image_rel($id_categoria);

try {
  $sql = "INSERT INTO tb_almacen
    (codigo, nombre, descripcion, stock, stock_minimo, stock_maximo,
     precio_compra, precio_venta, fecha_ingreso, imagen,
     id_usuario, id_categoria, fyh_creacion)
    VALUES
    (:codigo, :nombre, :descripcion, :stock, :stock_minimo, :stock_maximo,
     :precio_compra, :precio_venta, :fecha_ingreso, :imagen,
     :id_usuario, :id_categoria, :fyh_creacion)";

  $st = $pdo->prepare($sql);
  $st->execute([
    ':codigo'        => $codigo,
    ':nombre'        => $nombre,
    ':descripcion'   => $descripcion,
    ':stock'         => (int)$stock,
    ':stock_minimo'  => (int)$stock_minimo,
    ':stock_maximo'  => (int)$stock_maximo,
    ':precio_compra' => $precio_compra,
    ':precio_venta'  => $precio_venta,
    ':fecha_ingreso' => $fecha_ingreso,
    ':imagen'        => $imagen,
    ':id_usuario'    => (int)$id_usuario,
    ':id_categoria'  => (int)$id_categoria,
    ':fyh_creacion'  => $fechaHora,
  ]);

  flash_redirect('Producto registrado correctamente.', 'success', $URL . '/almacen/');
} catch (Throwable $e) {
  // Si subiste imagen y falló el insert, opcional: limpiar archivos (si tienes helper)
  // if (!empty($uploaded) && function_exists('delete_product_image_files')) delete_product_image_files($uploaded);

  flash_redirect('Error al registrar producto. Verifique los datos.', 'error', $URL . '/almacen/create.php');
}
