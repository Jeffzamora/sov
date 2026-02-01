<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';

require_once __DIR__ . '/../app/controllers/almacen/cargar_producto.php';

// Helpers de salida segura
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function money($v): string
{
    if ($v === null || $v === '') return '';
    return is_numeric($v) ? number_format((float)$v, 2) : h($v);
}

// Datos (cargar_producto.php debe poblar estas variables)
$id_producto = (int)($id_producto_get ?? $id_producto ?? 0);

$stock_actual = (int)($stock ?? 0);
$stock_min = (int)($stock_minimo ?? 0);
$stock_max = (int)($stock_maximo ?? 0);

$badge = 'badge-secondary';
$badgeText = 'Normal';
if ($stock_min > 0 && $stock_actual < $stock_min) {
    $badge = 'badge-danger';
    $badgeText = 'Stock bajo';
} else if ($stock_max > 0 && $stock_actual > $stock_max) {
    $badge = 'badge-success';
    $badgeText = 'Stock alto';
}
?>

<div class="content-wrapper">

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0">Producto</h1>
                    <small class="text-muted">
                        Almacén / <a href="index.php">Listado</a> / Detalle
                    </small>
                </div>
                <div class="col-sm-5 text-sm-right mt-2 mt-sm-0">
                    <div class="btn-group sov-btn-group">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <a href="update.php?id=<?php echo $id_producto; ?>" class="btn btn-success">
                            <i class="fas fa-pencil-alt"></i> Editar
                        </a>
                        <!-- Si delete.php ahora DESACTIVA, mantenlo con icono ban -->
                        <a href="delete.php?id=<?php echo $id_producto; ?>" class="btn btn-warning">
                            <i class="fas fa-ban"></i> Desactivar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <div class="row">

                <!-- Columna izquierda: Imagen + resumen -->
                <div class="col-lg-4">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-image mr-1"></i> Imagen</h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <img
                                    src="<?php echo product_image_url($imagen, $id_categoria ?? null, true); ?>"
                                    alt="Imagen del producto"
                                    style="width:100%;max-width:360px;object-fit:cover;border-radius:14px;border:1px solid rgba(0,0,0,.08);">
                            </div>

                            <hr>

                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted">Stock</span>
                                <span class="badge <?php echo $badge; ?>"><?php echo h($badgeText); ?></span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <span class="font-weight-bold" style="font-size:1.4rem;"><?php echo (int)$stock_actual; ?></span>
                                <small class="text-muted">
                                    Min: <?php echo (int)$stock_min; ?> | Max: <?php echo (int)$stock_max; ?>
                                </small>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Compra</small>
                                    <div class="font-weight-bold"><?php echo money($precio_compra ?? ''); ?></div>
                                </div>
                                <div class="col-6 text-right">
                                    <small class="text-muted">Venta</small>
                                    <div class="font-weight-bold"><?php echo money($precio_venta ?? ''); ?></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Columna derecha: Detalle -->
                <div class="col-lg-8">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-box mr-1"></i> Detalle del producto
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Código</label>
                                        <input type="text" class="form-control" value="<?php echo h($codigo ?? ''); ?>" disabled>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Categoría</label>
                                        <input type="text" class="form-control" value="<?php echo h($nombre_categoria ?? ''); ?>" disabled>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nombre</label>
                                        <input type="text" class="form-control" value="<?php echo h($nombre ?? ''); ?>" disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Usuario</label>
                                        <input type="text" class="form-control" value="<?php echo h($email ?? ''); ?>" disabled>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Descripción</label>
                                        <textarea class="form-control" rows="3" disabled><?php echo h($descripcion ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 col-md-3">
                                    <div class="form-group">
                                        <label>Stock</label>
                                        <input type="number" class="form-control" value="<?php echo (int)$stock_actual; ?>" disabled>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <div class="form-group">
                                        <label>Stock mínimo</label>
                                        <input type="number" class="form-control" value="<?php echo (int)$stock_min; ?>" disabled>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <div class="form-group">
                                        <label>Stock máximo</label>
                                        <input type="number" class="form-control" value="<?php echo (int)$stock_max; ?>" disabled>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <div class="form-group">
                                        <label>Fecha de ingreso</label>
                                        <input type="date" class="form-control" value="<?php echo h($fecha_ingreso ?? ''); ?>" disabled>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Consejo: desactiva productos en lugar de borrarlos para mantener historial.
                            </small>
                            <a href="index.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>