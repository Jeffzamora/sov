<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';

require_once __DIR__ . '/../app/controllers/almacen/listado_de_productos.php';

// Helpers de salida segura
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function money($v): string
{
    if ($v === null || $v === '') return '';
    if (is_numeric($v)) return number_format((float)$v, 2);
    return h($v);
}
function clamp_text(string $s, int $max = 70): string
{
    $s = trim($s);
    if ($s === '') return '';
    if (mb_strlen($s) <= $max) return $s;
    return mb_substr($s, 0, $max - 1) . '…';
}
?>

<div class="content-wrapper">

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0">Almacén</h1>
                    <small class="text-muted">Gestione inventario, precios y stock.</small>
                </div>
                <div class="col-sm-5 text-sm-right mt-2 mt-sm-0">
                    <a href="<?php echo $URL; ?>/almacen/create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo producto
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-boxes mr-1"></i> Productos
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body">

                    <div class="table-responsive">
                        <table id="tbl-almacen" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th style="width:52px;">#</th>
                                    <th style="width:110px;">Código</th>
                                    <th style="width:140px;">Categoría</th>
                                    <th style="width:70px;">Img</th>
                                    <th>Producto</th>
                                    <th style="min-width:220px;">Descripción</th>
                                    <th style="width:120px;">Stock</th>
                                    <th style="width:120px;">Compra</th>
                                    <th style="width:120px;">Venta</th>
                                    <th style="width:120px;">Ingreso</th>
                                    <th style="width:170px;">Usuario</th>
                                    <th style="width:170px;">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $contador = 0;
                                foreach (($productos_datos ?? []) as $p):
                                    $contador++;
                                    $id_producto = (int)($p['id_producto'] ?? 0);

                                    $codigo = h($p['codigo'] ?? '');
                                    $categoria = h($p['categoria'] ?? '');
                                    $nombre = h($p['nombre'] ?? '');
                                    $descripcion = h(clamp_text((string)($p['descripcion'] ?? ''), 80));
                                    $email = h($p['email'] ?? '');
                                    $fecha = h($p['fecha_ingreso'] ?? '');

                                    $idCat = (int)($p['id_categoria'] ?? 0);
                                    $imgRel = (string)($p['imagen'] ?? '');

                                    $stock = (int)($p['stock'] ?? 0);
                                    $min = (int)($p['stock_minimo'] ?? 0);
                                    $max = (int)($p['stock_maximo'] ?? 0);

                                    // Badge stock
                                    $stockBadge = 'badge-secondary';
                                    $stockText = 'Normal';
                                    if ($min > 0 && $stock < $min) {
                                        $stockBadge = 'badge-danger';
                                        $stockText = 'Bajo';
                                    } else if ($max > 0 && $stock > $max) {
                                        $stockBadge = 'badge-success';
                                        $stockText = 'Alto';
                                    }

                                    $precioCompra = money($p['precio_compra'] ?? '');
                                    $precioVenta  = money($p['precio_venta'] ?? '');
                                ?>
                                    <tr>
                                        <td class="text-muted"><?php echo $contador; ?></td>
                                        <td><span class="font-weight-bold"><?php echo $codigo; ?></span></td>
                                        <td><?php echo $categoria; ?></td>
                                        <td>
                                            <img
                                                src="<?php echo product_image_url($imgRel, $idCat, true); ?>"
                                                width="44"
                                                height="44"
                                                style="object-fit:cover;border-radius:10px;border:1px solid rgba(0,0,0,.08);"
                                                alt="Producto">
                                        </td>
                                        <td>
                                            <div class="font-weight-bold"><?php echo $nombre; ?></div>
                                        </td>
                                        <td class="text-muted"><?php echo $descripcion; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-between" style="gap:.5rem;">
                                                <span class="font-weight-bold"><?php echo (int)$stock; ?></span>
                                                <span class="badge <?php echo $stockBadge; ?>"><?php echo $stockText; ?></span>
                                            </div>
                                            <small class="text-muted">
                                                Min: <?php echo (int)$min; ?> | Max: <?php echo (int)$max; ?>
                                            </small>
                                        </td>
                                        <td><?php echo $precioCompra; ?></td>
                                        <td><?php echo $precioVenta; ?></td>
                                        <td><?php echo $fecha; ?></td>
                                        <td class="text-muted"><?php echo $email; ?></td>
                                        <td>
                                            <div class="btn-group sov-btn-group">
                                                <a href="show.php?id=<?php echo $id_producto; ?>" class="btn btn-info btn-sm" title="Ver">
                                                    <i class="fa fa-eye"></i> <span class="d-none d-md-inline">Ver</span>
                                                </a>
                                                <a href="update.php?id=<?php echo $id_producto; ?>" class="btn btn-success btn-sm" title="Editar">
                                                    <i class="fa fa-pencil-alt"></i> <span class="d-none d-md-inline">Editar</span>
                                                </a>
                                                <!-- Si tu delete ahora es DESACTIVAR, cambia texto e icono -->
                                                <a href="delete.php?id=<?php echo $id_producto; ?>" class="btn btn-warning btn-sm" title="Desactivar">
                                                    <i class="fa fa-ban"></i> <span class="d-none d-md-inline">Desactivar</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
    $(function() {
        var $t = $("#tbl-almacen");

        var dt = $t.DataTable({
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, "Todos"]
            ],
            responsive: true,
            autoWidth: false,
            order: [
                [0, 'asc']
            ],
            language: {
                emptyTable: "No hay productos registrados",
                info: "Mostrando _START_ a _END_ de _TOTAL_ productos",
                infoEmpty: "Mostrando 0 a 0 de 0 productos",
                infoFiltered: "(filtrado de _MAX_ productos)",
                lengthMenu: "Mostrar _MENU_",
                loadingRecords: "Cargando...",
                processing: "Procesando...",
                search: "Buscar:",
                zeroRecords: "Sin resultados",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            dom: '<"row mb-2"<"col-md-6"B><"col-md-6"f>>' +
                '<"row"<"col-12"tr>>' +
                '<"row mt-2"<"col-md-5"i><"col-md-7"p>>',
            buttons: [{
                    extend: 'collection',
                    text: 'Reportes',
                    buttons: [{
                            extend: 'copy',
                            text: 'Copiar'
                        },
                        {
                            extend: 'excel',
                            text: 'Excel'
                        },
                        {
                            extend: 'csv',
                            text: 'CSV'
                        },
                        {
                            extend: 'pdf',
                            text: 'PDF'
                        },
                        {
                            extend: 'print',
                            text: 'Imprimir'
                        }
                    ]
                },
                {
                    extend: 'colvis',
                    text: 'Columnas'
                }
            ]
        });

        dt.buttons().container();
    });
</script>