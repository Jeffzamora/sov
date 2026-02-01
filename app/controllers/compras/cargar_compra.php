<?php
/**
 * app/controllers/compras/cargar_compra.php
 * Cargar compra por id (para vista show/update).
 */

$id_compra_get = (int)($_GET['id'] ?? 0);
if ($id_compra_get <= 0) {
  throw new RuntimeException('ID de compra inválido');
}

$sql = "
SELECT
  co.id_compra,
  co.id_producto,
  co.id_proveedor,
  co.id_usuario,
  co.nro_compra,
  co.fecha_compra,
  co.comprobante,
  co.precio_compra,
  co.cantidad,
  co.estado,
  co.fyh_creacion,
  co.fyh_actualizacion,

  -- Campos de anulación (para bloquear edición y mostrar estado en show/update)
  co.fyh_anulado,
  co.anulado_por,
  co.motivo_anulacion,

  pro.codigo,
  pro.nombre        AS nombre_producto,
  pro.descripcion,
  pro.stock,
  pro.stock_minimo,
  pro.stock_maximo,
  pro.precio_compra AS precio_compra_producto,
  pro.precio_venta  AS precio_venta_producto,
  pro.fecha_ingreso,
  pro.imagen,
  pro.id_categoria,

  cat.nombre_categoria,

  us.nombres        AS nombres_usuario,
  usa.nombres       AS nombres_usuario_anula,

  prov.nombre_proveedor,
  prov.celular       AS celular_proveedor,
  prov.telefono      AS telefono_proveedor,
  prov.empresa       AS empresa_proveedor,
  prov.email         AS email_proveedor,
  prov.direccion     AS direccion_proveedor

FROM tb_compras co
JOIN tb_almacen     pro   ON pro.id_producto   = co.id_producto
JOIN tb_categorias  cat   ON cat.id_categoria  = pro.id_categoria
JOIN tb_usuarios    us    ON us.id_usuario     = co.id_usuario
LEFT JOIN tb_usuarios usa  ON usa.id_usuario   = co.anulado_por
JOIN tb_proveedores prov  ON prov.id_proveedor = co.id_proveedor
WHERE co.id_compra = :id
LIMIT 1
";

$q = $pdo->prepare($sql);
$q->execute([':id' => $id_compra_get]);
$compras_dato = $q->fetch(PDO::FETCH_ASSOC);

if (!$compras_dato) {
  throw new RuntimeException('Compra no encontrada');
}

// Derivados útiles para las vistas: bandera de anulación
$compras_dato['anulado'] = !empty($compras_dato['fyh_anulado']) ? 1 : 0;

// (Opcional) variables sueltas para mostrar en show/update
$fyh_anulado = $compras_dato['fyh_anulado'] ?? null;
$motivo_anulacion = $compras_dato['motivo_anulacion'] ?? null;
$anulado_por = $compras_dato['anulado_por'] ?? null;
$nombres_usuario_anula = $compras_dato['nombres_usuario_anula'] ?? null;

// (Si tu vista usa variables sueltas, aquí las asignas)
$id_compra = (int)$compras_dato['id_compra'];
$id_producto = (int)$compras_dato['id_producto'];
$id_proveedor_tabla = (int)$compras_dato['id_proveedor'];
$nro_compra = (int)$compras_dato['nro_compra'];
$codigo = (string)$compras_dato['codigo'];
$id_categoria = (int)$compras_dato['id_categoria'];
$nombre_categoria = (string)$compras_dato['nombre_categoria'];
$nombre_producto = (string)$compras_dato['nombre_producto'];
$nombres_usuario = (string)$compras_dato['nombres_usuario'];
$descripcion = (string)$compras_dato['descripcion'];
$stock = (int)$compras_dato['stock'];
$stock_minimo = (int)$compras_dato['stock_minimo'];
$stock_maximo = (int)$compras_dato['stock_maximo'];
$precio_compra_producto = (string)$compras_dato['precio_compra_producto'];
$precio_venta_producto = (string)$compras_dato['precio_venta_producto'];
$fecha_ingreso = (string)$compras_dato['fecha_ingreso'];
$imagen = (string)$compras_dato['imagen'];

$nombre_proveedor_tabla = (string)$compras_dato['nombre_proveedor'];
$celular_proveedor = (string)$compras_dato['celular_proveedor'];
$telefono_proveedor = (string)$compras_dato['telefono_proveedor'];
$empresa_proveedor = (string)$compras_dato['empresa_proveedor'];
$email_proveedor = (string)$compras_dato['email_proveedor'];
$direccion_proveedor = (string)$compras_dato['direccion_proveedor'];

$fecha_compra = (string)$compras_dato['fecha_compra'];
$comprobante = (string)$compras_dato['comprobante'];
$precio_compra = (string)$compras_dato['precio_compra'];
$cantidad = (int)$compras_dato['cantidad'];
$estado = (string)$compras_dato['estado'];
