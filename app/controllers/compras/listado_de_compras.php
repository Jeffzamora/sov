<?php
$sql = "
SELECT
  co.id_compra,
  co.nro_compra,
  co.fecha_compra,
  co.comprobante,
  co.precio_compra,
  co.cantidad,
  co.estado,
  co.fyh_creacion,

  pro.id_producto,
  pro.codigo,
  pro.nombre        AS nombre_producto,
  pro.descripcion   AS descripcion,
  pro.stock         AS stock,
  pro.stock_minimo  AS stock_minimo,
  pro.stock_maximo  AS stock_maximo,
  pro.precio_compra AS precio_compra_producto,
  pro.precio_venta  AS precio_venta_producto,
  pro.fecha_ingreso AS fecha_ingreso,
  pro.imagen        AS imagen,
  pro.id_categoria  AS id_categoria,

  cat.nombre_categoria AS nombre_categoria,

  prov.id_proveedor,
  prov.nombre_proveedor,
  prov.empresa          AS empresa,
  prov.celular          AS celular_proveedor,
  prov.telefono         AS telefono_proveedor,
  prov.email            AS email_proveedor,
  prov.direccion        AS direccion_proveedor,

  us.nombres AS nombres_usuario,
  (co.estado = 'ANULADO') AS anulado
FROM tb_compras co
JOIN tb_almacen pro     ON pro.id_producto = co.id_producto
JOIN tb_categorias cat  ON cat.id_categoria = pro.id_categoria
JOIN tb_proveedores prov ON prov.id_proveedor = co.id_proveedor
JOIN tb_usuarios us     ON us.id_usuario = co.id_usuario
ORDER BY co.id_compra DESC
";
$query = $pdo->prepare($sql);
$query->execute();
$compras_datos = $query->fetchAll(PDO::FETCH_ASSOC);
