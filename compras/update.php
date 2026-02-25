<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'compras.actualizar', $URL . '/');

require_once __DIR__ . '/../layout/parte1.php';

require_once __DIR__ . '/../app/controllers/almacen/listado_de_productos.php';
require_once __DIR__ . '/../app/controllers/proveedores/listado_de_proveedores.php';
require_once __DIR__ . '/../app/controllers/compras/cargar_compra.php';

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// CSRF disponible para AJAX (sov.ajax.js)
echo "<script>window.SOV_CSRF = " . json_encode(csrf_token()) . ";</script>";

// Bloqueo de edición si la compra está anulada
$isAnulada = !empty($compras_dato['fyh_anulado'] ?? null);
if ($isAnulada) {
    ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-8">
                        <h1 class="m-0"><i class="fas fa-ban text-danger mr-2"></i> Compra anulada</h1>
                        <small class="text-muted">Esta compra ya fue anulada y no se puede editar.</small>
                    </div>
                    <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                        <a href="show.php?id=<?php echo (int)$id_compra_get; ?>" class="btn btn-outline-primary"><i class="fas fa-eye"></i> Ver</a>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="alert alert-danger">
                    <h5 class="mb-2"><i class="fas fa-ban mr-1"></i> COMPRA ANULADA</h5>
                    <?php if (!empty($compras_dato['fyh_anulado'])): ?>
                        <div><b>Fecha:</b> <?php echo h($compras_dato['fyh_anulado']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($compras_dato['motivo_anulacion'])): ?>
                        <div><b>Motivo:</b> <?php echo h($compras_dato['motivo_anulacion']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
    <?php
    require_once __DIR__ . '/../layout/parte2.php';
    exit;
}
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-8">
                    <h1 class="m-0">
                        <i class="fas fa-edit text-success mr-2"></i>
                        Actualización de la compra #<?php echo h($nro_compra); ?>
                        <span class="badge badge-light ml-2">Editar</span>
                    </h1>
                    <small class="text-muted">Modifica los datos necesarios y guarda los cambios.</small>
                </div>
                <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <a href="show.php?id=<?php echo (int)$id_compra_get; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye"></i> Ver
                    </a>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->


    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title">Llene los datos con cuidado</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                                        </button>
                                    </div>

                                </div>

                                <div class="card-body" style="display: block;">
                                    <div style="display: flex">
                                        <h5>Datos del producto </h5>
                                        <div style="width: 20px"></div>
                                        <button type="button" class="btn btn-primary" data-toggle="modal"
                                                data-target="#modal-buscar_producto">
                                            <i class="fa fa-search"></i>
                                            Buscar producto
                                        </button>
                                        <!-- modal para visualizar datos de los proveedor -->
                                        <div class="modal fade" id="modal-buscar_producto">
                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background-color: #1d36b6;color: white">
                                                        <h4 class="modal-title">Busqueda del producto</h4>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="table table-responsive">
                                                            <table id="example1" class="table table-bordered table-striped table-sm">
                                                                <thead>
                                                                <tr>
                                                                    <th><center>Nro</center></th>
                                                                    <th><center>Selecionar</center></th>
                                                                    <th><center>Código</center></th>
                                                                    <th><center>Categoría</center></th>
                                                                    <th><center>Imagen</center></th>
                                                                    <th><center>Nombre</center></th>
                                                                    <th><center>Descripción</center></th>
                                                                    <th><center>Stock</center></th>
                                                                    <th><center>Precio compra</center></th>
                                                                    <th><center>Precio venta</center></th>
                                                                    <th><center>Fecha compra</center></th>
                                                                    <th><center>Usuario</center></th>
                                                                </tr>
                                                                </thead>
                                                                <tbody>
                                                                <?php
                                                                $contador = 0;
                                                                foreach ($productos_datos as $productos_dato){
                                                                    $id_producto = $productos_dato['id_producto']; ?>
                                                                    <tr>
                                                                        <td><?php echo $contador = $contador + 1; ?></td>
                                                                        <td>
                                                                            <button class="btn btn-info" id="btn_selecionar<?php echo $id_producto;?>">
                                                                                Selecionar
                                                                            </button>
                                                                            <script>
                                                                                $('#btn_selecionar<?php echo $id_producto;?>').click(function () {


                                                                                    var id_producto = "<?php echo $productos_dato['id_producto'];?>";
                                                                                    $('#id_producto').val(id_producto);

                                                                                    var codigo = "<?php echo $productos_dato['codigo'];?>";
                                                                                    $('#codigo').val(codigo);

                                                                                    var categoria = "<?php echo $productos_dato['categoria'];?>";
                                                                                    $('#categoria').val(categoria);

                                                                                    var nombre = "<?php echo $productos_dato['nombre'];?>";
                                                                                    $('#nombre_producto').val(nombre);

                                                                                    var email = "<?php echo $productos_dato['email'];?>";
                                                                                    $('#usuario_producto').val(email);

                                                                                    var descripcion = "<?php echo $productos_dato['descripcion'];?>";
                                                                                    $('#descripcio_producto').val(descripcion);

                                                                                    var stock = "<?php echo $productos_dato['stock'];?>";
                                                                                    $('#stock').val(stock);
                                                                                    $('#stock_actual').val(stock);

                                                                                    var stock_minimo = "<?php echo $productos_dato['stock_minimo'];?>";
                                                                                    $('#stock_minimo').val(stock_minimo);

                                                                                    var stock_maximo = "<?php echo $productos_dato['stock_maximo'];?>";
                                                                                    $('#stock_maximo').val(stock_maximo);

                                                                                    var precio_compra = "<?php echo $productos_dato['precio_compra'];?>";
                                                                                    $('#precio_compra').val(precio_compra);
                                                                                    $('#precio_compra_controlador').val(precio_compra);

                                                                                    var precio_venta = "<?php echo $productos_dato['precio_venta'];?>";
                                                                                    $('#precio_venta').val(precio_venta);

                                                                                    var fecha_ingreso = "<?php echo $productos_dato['fecha_ingreso'];?>";
                                                                                    $('#fecha_ingreso').val(fecha_ingreso);

                                                                                    var ruta_img = "<?php echo product_image_url($productos_dato['imagen'], (int)($productos_dato['id_categoria'] ?? 0), true); ?>";
                                                                                    $('#img_producto').attr({src: ruta_img });

                                                                                   // Evita warning de accesibilidad (aria-hidden con foco dentro del modal)
                                                                                   try { $(this).blur(); } catch(e) {}
                                                                                   $('#modal-buscar_producto').modal('hide');
                                                                                   setTimeout(function(){ $('#cantidad_compra').trigger('focus'); }, 250);

                                                                                   // Recalcular stock_total cuando se selecciona el producto (stock_actual cambia)
                                                                                   if (typeof window.recalcStockCompra === 'function') {
                                                                                       window.recalcStockCompra();
                                                                                   }

                                                                                });
                                                                            </script>
                                                                        </td>
                                                                        <td><?php echo $productos_dato['codigo'];?></td>
                                                                        <td><?php echo $productos_dato['categoria'];?></td>
                                                                        <td>
                                                                            <img src="<?php echo product_image_url($productos_dato['imagen'], (int)($productos_dato['id_categoria'] ?? 0), true); ?>" width="50px" alt="Producto">
                                                                        </td>
                                                                        <td><?php echo $productos_dato['nombre'];?></td>
                                                                        <td><?php echo $productos_dato['descripcion'];?></td>
                                                                        <td><?php echo $productos_dato['stock'];?></td>
                                                                        <td><?php echo $productos_dato['precio_compra'];?></td>
                                                                        <td><?php echo $productos_dato['precio_venta'];?></td>
                                                                        <td><?php echo $productos_dato['fecha_ingreso'];?></td>
                                                                        <td><?php echo $productos_dato['email'];?></td>
                                                                    </tr>
                                                                    <?php
                                                                }
                                                                ?>
                                                                </tbody>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- /.modal-content -->
                                            </div>
                                            <!-- /.modal-dialog -->
                                        </div>
                                        <!-- /.modal -->
                                    </div>

                                    <hr>
                                    <div class="row" style="font-size: 12px">
                                        <div class="col-md-9">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <input type="text" value="<?= $id_producto; ?>" id="id_producto" hidden>
                                                        <label for="">Código:</label>
                                                        <input type="text" value="<?= $codigo; ?>" class="form-control" id="codigo" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="">Categoría:</label>
                                                        <div style="display: flex">
                                                            <input type="text" value="<?= $nombre_categoria; ?>" class="form-control" id="categoria" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="">Nombre del producto:</label>
                                                        <input type="text" value="<?= $nombre_producto; ?>" name="nombre" id="nombre_producto" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="">Usuario</label>
                                                        <input type="text" value="<?= $nombres_usuario; ?>" class="form-control" id="usuario_producto" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <label for="">Descripción del producto:</label>
                                                        <textarea name="descripcion" id="descripcio_producto" cols="30" rows="2" class="form-control" disabled><?= $descripcion; ?></textarea>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="">Stock:</label>
                                                        <input type="number" value="<?= $stock; ?>" name="stock" id="stock" class="form-control" style="background-color: #fff819" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="">Stock mínimo:</label>
                                                        <input type="number" value="<?= $stock_minimo; ?>" name="stock_minimo" id="stock_minimo" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="">Stock máximo:</label>
                                                        <input type="number" value="<?= $stock_maximo; ?>" name="stock_maximo" id="stock_maximo" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="">Precio compra:</label>
                                                        <input type="number" value="<?= $precio_compra_producto; ?>" name="precio_compra" id="precio_compra" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="">Precio venta:</label>
                                                        <input type="number" value="<?= $precio_venta_producto; ?>" name="precio_venta" id="precio_venta" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="">Fecha de ingreso:</label>
                                                        <input type="date" style="font-size: 12px" value="<?= $fecha_ingreso; ?>" name="fecha_ingreso" id="fecha_ingreso" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="">Imagen del producto</label>
                                                <center>
                                                    <img src="<?php echo product_image_url($imagen, $id_categoria ?? null, true);?>" id="img_producto" width="50%" alt="">
                                                </center>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <div style="display: flex">
                                        <h5>Datos del proveedor </h5>
                                        <div style="width: 20px"></div>
                                        <button type="button" class="btn btn-primary" data-toggle="modal"
                                                data-target="#modal-buscar_proveedor">
                                            <i class="fa fa-search"></i>
                                            Buscar proveedor
                                        </button>
                                        <!-- modal para visualizar datos de los proveedor -->
                                        <div class="modal fade" id="modal-buscar_proveedor">
                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background-color: #1d36b6;color: white">
                                                        <h4 class="modal-title">Busqueda de proveedor</h4>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="table table-responsive">
                                                            <table id="example2" class="table table-bordered table-striped table-sm">
                                                                <thead>
                                                                <tr>
                                                                    <th><center>Nro</center></th>
                                                                    <th><center>Selecionar</center></th>
                                                                    <th><center>Nombre del proveedor</center></th>
                                                                    <th><center>Celular</center></th>
                                                                    <th><center>Teléfono</center></th>
                                                                    <th><center>Empresa</center></th>
                                                                    <th><center>Email</center></th>
                                                                    <th><center>Dirección</center></th>
                                                                </tr>
                                                                </thead>
                                                                <tbody>
                                                                <?php
                                                                $contador = 0;
                                                                foreach ($proveedores_datos as $proveedores_dato){
                                                                    $id_proveedor = $proveedores_dato['id_proveedor'];
                                                                    $nombre_proveedor = $proveedores_dato['nombre_proveedor']; ?>
                                                                    <tr>
                                                                        <td><center><?php echo $contador = $contador + 1;?></center></td>
                                                                        <td>
                                                                            <button class="btn btn-info" id="btn_selecionar_proveedor<?php echo $id_proveedor;?>">
                                                                                Selecionar
                                                                            </button>
                                                                            <script>
                                                                                $('#btn_selecionar_proveedor<?php echo $id_proveedor;?>').click(function () {

                                                                                    var id_proveedor = '<?php echo $id_proveedor; ?>';
                                                                                    $('#id_proveedor').val(id_proveedor);

                                                                                    var nombre_proveedor = '<?php echo $nombre_proveedor; ?>';
                                                                                    $('#nombre_proveedor').val(nombre_proveedor);

                                                                                    var celular_proveedor = '<?php echo $proveedores_dato['celular']; ?>';
                                                                                    $('#celular').val(celular_proveedor);

                                                                                    var telefono_proveedor = '<?php echo $proveedores_dato['telefono']; ?>';
                                                                                    $('#telefono').val(telefono_proveedor);

                                                                                    var empresa_proveedor = '<?php echo $proveedores_dato['empresa']; ?>';
                                                                                    $('#empresa').val(empresa_proveedor);

                                                                                    var email_proveedor = '<?php echo $proveedores_dato['email']; ?>';
                                                                                    $('#email').val(email_proveedor);

                                                                                    var direccion_proveedor = '<?php echo $proveedores_dato['direccion']; ?>';
                                                                                    $('#direccion').val(direccion_proveedor);

                                                                                   // Evita warning de accesibilidad (aria-hidden con foco dentro del modal)
                                                                                   try { $(this).blur(); } catch(e) {}
                                                                                   $('#modal-buscar_proveedor').modal('hide');
                                                                                   setTimeout(function(){ $('#comprobante').trigger('focus'); }, 250);

                                                                                });
                                                                            </script>
                                                                        </td>
                                                                        <td><?php echo $nombre_proveedor;?></td>
                                                                        <td>
                                                                            <a href="https://wa.me/<?php echo APP_WHATSAPP_CC . preg_replace('/\\D+/', '', (string)($proveedores_dato['celular'] ?? '')); ?>" target="_blank" class="btn btn-success">
                                                                                <i class="fa fa-phone"></i>
                                                                                <?php echo $proveedores_dato['celular'];?>
                                                                            </a>
                                                                        </td>
                                                                        <td><?php echo $proveedores_dato['telefono'];?></td>
                                                                        <td><?php echo $proveedores_dato['empresa'];?></td>
                                                                        <td><?php echo $proveedores_dato['email'];?></td>
                                                                        <td><?php echo $proveedores_dato['direccion'];?></td>
                                                                    </tr>
                                                                    <?php
                                                                }
                                                                ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- /.modal-content -->
                                            </div>
                                            <!-- /.modal-dialog -->
                                        </div>
                                        <!-- /.modal -->
                                    </div>

                                    <hr>

                                    <div class="container-fluid" style="font-size: 12px">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <input type="text" value="<?= $id_proveedor_tabla; ?>" id="id_proveedor" hidden>
                                                    <label for="">Nombre del proveedor </label>
                                                    <input type="text" value="<?= $nombre_proveedor_tabla; ?>" id="nombre_proveedor" class="form-control" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="">Celular</label>
                                                    <input type="number" value="<?= $celular_proveedor; ?>" id="celular" class="form-control" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="">Teléfono</label>
                                                    <input type="number" value="<?= $telefono_proveedor; ?>" id="telefono" class="form-control" disabled>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="">Empresa </label>
                                                    <input type="text" value="<?= $empresa_proveedor; ?>" id="empresa" class="form-control" disabled>

                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="">Email</label>
                                                    <input type="email" value="<?= $email_proveedor; ?>" id="email" class="form-control" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="">Dirección</label>
                                                    <textarea name="" id="direccion" cols="30" rows="3" class="form-control" disabled><?= $direccion_proveedor; ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Detalle de la compra</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>

                                </div>

                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="">Número de la compra</label>
                                                <input type="text" value="<?php echo $nro_compra; ?>" style="text-align: center" class="form-control" disabled>
                                                <input type="text" value="<?php echo $nro_compra; ?>" id="nro_compra" hidden>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="">Fecha de la compra</label>
                                                <input type="date" value="<?= $fecha_compra; ?>" class="form-control" id="fecha_compra">
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="">Comprobante de la compra</label>
                                                <input type="text" value="<?= $comprobante; ?>" class="form-control" id="comprobante">
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="">Precio de la compra</label>
                                               <div class="input-group">
                                                   <div class="input-group-prepend">
                                                       <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                                   </div>
                                                   <input type="number" step="0.01" min="0" value="<?= h($precio_compra); ?>" class="form-control" id="precio_compra_controlador">
                                               </div>
                                               <small class="text-muted">Ajusta el precio de compra si corresponde.</small>
                                            </div>
                                        </div>

                                       <div class="col-md-12">
                                           <div class="form-group">
                                               <label for="">Total</label>
                                               <div class="input-group">
                                                   <div class="input-group-prepend">
                                                       <span class="input-group-text"><i class="fas fa-calculator"></i></span>
                                                   </div>
                                                   <input type="text" class="form-control" id="total_compra" disabled>
                                               </div>
                                               <small class="text-muted">Calculado: precio × cantidad.</small>
                                           </div>
                                       </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Stock actual</label>
                                                <input type="text" value="<?= $stock=$stock-$cantidad; ?>" style="background-color: #fff819;text-align: center" id="stock_actual" class="form-control" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">Stock Total</label>
                                                <input type="text" style="text-align: center" id="stock_total" class="form-control" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="">Cantidad de la compra</label>
                                                <input type="number" value="<?= $cantidad; ?>" id="cantidad_compra" style="text-align: center" class="form-control">
                                            </div>
                                            <script>
                                                (function(){
                                                    function toInt(v){
                                                        if (v === null || v === undefined) return 0;
                                                        var s = String(v).trim();
                                                        if (s === '') return 0;
                                                        var n = parseInt(s, 10);
                                                        return isNaN(n) ? 0 : n;
                                                    }

                                                    // Se expone para poder recalcular cuando cambia stock_actual (al seleccionar producto)
                                                    window.recalcStockCompra = function(){
                                                        var stock_actual = toInt($('#stock_actual').val());
                                                        var stock_compra  = toInt($('#cantidad_compra').val());
                                                        $('#stock_total').val(stock_actual + stock_compra);

                                                        // Total = precio * cantidad
                                                        var precio = parseFloat(String($('#precio_compra_controlador').val() || '0').replace(',', '.'));
                                                        if (isNaN(precio)) precio = 0;
                                                        var total = precio * stock_compra;
                                                        $('#total_compra').val(total ? total.toFixed(2) : '0.00');
                                                    };

                                                    $('#cantidad_compra').on('input change', window.recalcStockCompra);
                                                    $('#precio_compra_controlador').on('input change', window.recalcStockCompra);
                                                    window.recalcStockCompra();
                                                })();
                                            </script>
                                        </div>


                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="">Usuario</label>
                                                <input type="text" class="form-control" value="<?php echo $nombres_usuario; ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <button type="button" class="btn btn-success btn-block" id="btn_actualizar_compra">Actualizar compra</button>
                                        </div>
                                    </div>
                                    <script>
                                        $('#btn_actualizar_compra').click(function () {

                                            var id_compra = '<?php echo $id_compra; ?>';
                                            var id_producto = $('#id_producto').val();
                                            var nro_compra = $('#nro_compra').val();
                                            var fecha_compra = $('#fecha_compra').val();
                                            var id_proveedor = $('#id_proveedor').val();
                                            var comprobante = $('#comprobante').val();
                                            var id_usuario = '<?php echo $id_usuario_sesion;?>';
                                            var precio_compra = $('#precio_compra_controlador').val();
                                            var cantidad_compra = $('#cantidad_compra').val();

                                            var stock_total = $('#stock_total').val();

                                            if(!window.SOV){
                                                alert('Falta cargar helpers SOV (public/js/sov.ajax.js).');
                                                return;
                                            }

                                            if(!SOV.requireValue(id_producto, '#id_producto', 'Debe seleccionar un producto')) return;
                                            if(!SOV.requireValue(id_proveedor, '#id_proveedor', 'Debe seleccionar un proveedor')) return;
                                            if(!SOV.requireValue(fecha_compra, '#fecha_compra', 'Debe indicar la fecha')) return;
                                            if(!SOV.requireValue(comprobante, '#comprobante', 'Debe indicar el comprobante')) return;
                                            if(!SOV.requireValue(precio_compra, '#precio_compra_controlador', 'Debe indicar el precio')) return;
                                            if(!SOV.requireValue(cantidad_compra, '#cantidad_compra', 'Debe indicar la cantidad')) return;

                                            var url = "../app/controllers/compras/update.php";
                                            SOV.ajaxJson({
                                                url: url,
                                                method: 'POST',
                                                data: {
                                                    _csrf: '<?php echo csrf_token(); ?>',
                                                    id_compra: id_compra,
                                                    id_producto: id_producto,
                                                    nro_compra: nro_compra,
                                                    fecha_compra: fecha_compra,
                                                    id_proveedor: id_proveedor,
                                                    comprobante: comprobante,
                                                    id_usuario: id_usuario,
                                                    precio_compra: precio_compra,
                                                    cantidad_compra: cantidad_compra,
                                                    stock_total: stock_total
                                                }
                                            }).done(function (resp) {
                                                if (resp && resp.ok) {
                                                    SOV.toast('success', 'Compra actualizada');
                                                    window.location.href = "../compras";
                                                } else {
                                                    SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo actualizar');
                                                }
                                            }).fail(function (xhr) {
                                                var msg = 'Error al actualizar';
                                                try {
                                                    var r = JSON.parse(xhr.responseText);
                                                    if (r && r.error) msg = r.error;
                                                } catch (e) {}
                                                SOV.toast('error', msg);
                                            });

                                        });
                                    </script>
                                </div>

                            </div>

                        </div>

                        <div id="respuesta_update"></div>

                    </div>


                </div>
            </div>

            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>



<script>
    $(function () {
        $("#example1").DataTable({
            "pageLength": 5,
            "language": {
                "emptyTable": "No hay información",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ Productos",
                "infoEmpty": "Mostrando 0 a 0 de 0 Productos",
                "infoFiltered": "(Filtrado de _MAX_ total Productos)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ Productos",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscador:",
                "zeroRecords": "Sin resultados encontrados",
                "paginate": {
                    "first": "Primero",
                    "last": "Ultimo",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            "responsive": true, "lengthChange": true, "autoWidth": false,

        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    });


    $(function () {
        $("#example2").DataTable({
            "pageLength": 5,
            "language": {
                "emptyTable": "No hay información",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ Proveedores",
                "infoEmpty": "Mostrando 0 a 0 de 0 Proveedores",
                "infoFiltered": "(Filtrado de _MAX_ total Proveedores)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ Proveedores",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscador:",
                "zeroRecords": "Sin resultados encontrados",
                "paginate": {
                    "first": "Primero",
                    "last": "Ultimo",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            "responsive": true, "lengthChange": true, "autoWidth": false,

        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    });
</script>

