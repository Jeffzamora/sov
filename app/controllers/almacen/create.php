<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function flash_and_redirect(string $msg, string $icon, string $to): void {
  $_SESSION['mensaje'] = $msg;
  $_SESSION['icono'] = $icon;
  header('Location: ' . $to);
  exit;
}

// Inputs base
$codigo       = trim((string)($_POST['codigo'] ?? '')); // no confiar en cliente
$id_categoria = input_int('id_categoria', true);
$nombre       = input_str('nombre', 255, true);
$id_usuario   = input_int('id_usuario', true);
$descripcion  = input_str('descripcion', 1000, false);

$stock        = input_int('stock', true);

// stock_min/max: la tabla permite NULL, así que si viene vacío guardamos NULL
$stock_minimo_raw = trim((string)($_POST['stock_minimo'] ?? ''));
$stock_maximo_raw = trim((string)($_POST['stock_maximo'] ?? ''));

$stock_minimo = ($stock_minimo_raw === '') ? null : (int)$stock_minimo_raw;
$stock_maximo = ($stock_maximo_raw === '') ? null : (int)$stock_maximo_raw;

$precio_compra = input_decimal('precio_compra', true);
$precio_venta  = input_decimal('precio_venta', true);
$fecha_ingreso = input_date('fecha_ingreso', true, false);

// Validaciones
if ($stock < 0) {
  flash_and_redirect('El stock no puede ser negativo.', 'warning', $URL . '/almacen/create.php');
}
if ($stock_minimo !== null && $stock_minimo < 0) {
  flash_and_redirect('El stock mínimo no puede ser negativo.', 'warning', $URL . '/almacen/create.php');
}
if ($stock_maximo !== null && $stock_maximo < 0) {
  flash_and_redirect('El stock máximo no puede ser negativo.', 'warning', $URL . '/almacen/create.php');
}
if ($stock_minimo !== null && $stock_maximo !== null && $stock_minimo > $stock_maximo) {
  flash_and_redirect('El stock mínimo no puede ser mayor que el stock máximo.', 'warning', $URL . '/almacen/create.php');
}
if ($precio_compra < 0 || $precio_venta < 0) {
  flash_and_redirect('Los precios no pueden ser negativos.', 'warning', $URL . '/almacen/create.php');
}
if ($precio_compra > 0 && $precio_venta > 0 && $precio_venta < $precio_compra) {
  flash_and_redirect('El precio de venta no puede ser menor que el precio de compra.', 'warning', $URL . '/almacen/create.php');
}

// Validar FK categoría
try {
  $stCat = $pdo->prepare("SELECT 1 FROM tb_categorias WHERE id_categoria = :id LIMIT 1");
  $stCat->execute([':id' => $id_categoria]);
  if (!$stCat->fetchColumn()) {
    flash_and_redirect('La categoría seleccionada no existe.', 'error', $URL . '/almacen/create.php');
  }
} catch (Throwable $e) {
  flash_and_redirect('Error validando categoría: ' . $e->getMessage(), 'error', $URL . '/almacen/create.php');
}

// Generar código en backend (alineado a PK id_producto)
if ($codigo === '') {
  try {
    $maxId = (int)$pdo->query("SELECT COALESCE(MAX(id_producto), 0) FROM tb_almacen")->fetchColumn();
    $next = $maxId + 1;
    $codigo = 'P-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
  } catch (Throwable $e) {
    $codigo = 'P-' . date('ymdHis');
  }
}

// Verificar colisión por UNIQUE(codigo)
try {
  $stCode = $pdo->prepare("SELECT 1 FROM tb_almacen WHERE codigo = :c LIMIT 1");
  $stCode->execute([':c' => $codigo]);
  if ($stCode->fetchColumn()) {
    // fallback de última instancia
    $codigo = 'P-' . date('ymdHis');
  }
} catch (Throwable $e) {
  flash_and_redirect('Error validando código: ' . $e->getMessage(), 'error', $URL . '/almacen/create.php');
}

// Imagen
$uploaded = upload_product_image('image', false);
$imagen = ($uploaded !== '') ? $uploaded : product_default_image_rel($id_categoria);

try {
  // fyh_creacion lo puede poner la BD sola, pero lo dejamos si ya lo usas
  $sql = "INSERT INTO tb_almacen
    (codigo, nombre, descripcion, stock, stock_minimo, stock_maximo,
     precio_compra, precio_venta, fecha_ingreso, imagen, id_usuario, id_categoria, fyh_creacion)
    VALUES
    (:codigo, :nombre, :descripcion, :stock, :stock_minimo, :stock_maximo,
     :precio_compra, :precio_venta, :fecha_ingreso, :imagen, :id_usuario, :id_categoria, :fyh_creacion)";

  $stmt = $pdo->prepare($sql);
  $ok = $stmt->execute([
    ':codigo'        => $codigo,
    ':nombre'        => $nombre,
    ':descripcion'   => $descripcion !== '' ? $descripcion : null,
    ':stock'         => $stock,
    ':stock_minimo'  => $stock_minimo,
    ':stock_maximo'  => $stock_maximo,
    ':precio_compra' => $precio_compra,
    ':precio_venta'  => $precio_venta,
    ':fecha_ingreso' => $fecha_ingreso,
    ':imagen'        => $imagen,
    ':id_usuario'    => $id_usuario,
    ':id_categoria'  => $id_categoria,
    ':fyh_creacion'  => $fechaHora,
  ]);

  if ($ok) {
    flash_and_redirect('Se registró el producto correctamente.', 'success', $URL . '/almacen/');
  }

  flash_and_redirect('Error: no se pudo registrar el producto.', 'error', $URL . '/almacen/create.php');

} catch (Throwable $e) {
  flash_and_redirect('Error BD: ' . $e->getMessage(), 'error', $URL . '/almacen/create.php');
}
