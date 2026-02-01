<?php

/**
 * Listado de productos (almacén)
 *
 * Mejoras:
 * - Evita SELECT * para prevenir colisiones de nombres.
 * - Alias consistentes.
 * - Filtra por estado=1 si existe la columna (para "desactivar" en vez de borrar).
 * - Ordena por id_producto DESC.
 */

$hasEstado = false;
try {
    $chk = $pdo->query("SHOW COLUMNS FROM tb_almacen LIKE 'estado'");
    $hasEstado = (bool)$chk->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $hasEstado = false;
}

// Si quieres permitir ver inactivos desde URL: ?ver_inactivos=1
$verInactivos = isset($_GET['ver_inactivos']) && (int)$_GET['ver_inactivos'] === 1;

$where = "";
if ($hasEstado && !$verInactivos) {
    $where = "WHERE a.estado = 1";
}

$sql = "
  SELECT
    a.id_producto,
    a.codigo,
    a.nombre,
    a.descripcion,
    a.stock,
    a.stock_minimo,
    a.stock_maximo,
    a.precio_compra,
    a.precio_venta,
    a.fecha_ingreso,
    a.imagen,
    a.id_categoria,
    a.id_usuario,
    " . ($hasEstado ? "a.estado," : "1 AS estado,") . "
    cat.nombre_categoria AS categoria,
    u.email AS email
  FROM tb_almacen a
  INNER JOIN tb_categorias cat ON cat.id_categoria = a.id_categoria
  INNER JOIN tb_usuarios u ON u.id_usuario = a.id_usuario
  $where
  ORDER BY a.id_producto DESC
";

$query = $pdo->prepare($sql);
$query->execute();
$productos_datos = $query->fetchAll(PDO::FETCH_ASSOC);
