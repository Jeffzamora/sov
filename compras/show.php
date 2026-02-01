<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';

require_once __DIR__ . '/../app/controllers/compras/cargar_compra.php';

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Estado anulado robusto (preferimos la bandera 'anulado' y fallback a fyh_anulado)
$anulado = false;
if (isset($compras_dato['anulado'])) {
    $anulado = (int)$compras_dato['anulado'] === 1;
} elseif (!empty($compras_dato['fyh_anulado'])) {
    $anulado = true;
}
?>

<div class="content-wrapper">

    <div class="content-header">
        <div class="container-fluid">
            <div class="row align-items-center mb-2">
                <div class="col-sm-8">
                    <h1 class="m-0">
                        <i class="fas fa-receipt text-info mr-2"></i>
                        Compra #<?php echo h($nro_compra); ?>
                        <?php if ($anulado): ?>
                            <span class="badge badge-danger ml-2"><i class="fas fa-ban"></i> ANULADA</span>
                        <?php else: ?>
                            <span class="badge badge-success ml-2"><i class="fas fa-check"></i> ACTIVA</span>
                        <?php endif; ?>
                    </h1>
                    <small class="text-muted">Detalle completo de compra, producto y proveedor.</small>

                    <?php if ($anulado): ?>
                        <div class="alert alert-danger mt-2 mb-0">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-ban mr-2 mt-1"></i>
                                <div>
                                    <div><b>Compra ANULADA</b></div>
                                    <?php if (!empty($compras_dato['fyh_anulado'])): ?>
                                        <div><small><b>Fecha:</b> <?php echo h($compras_dato['fyh_anulado']); ?></small></div>
                                    <?php endif; ?>
                                    <?php if (!empty($compras_dato['nombres_usuario_anula']) || !empty($compras_dato['anulado_por'])): ?>
                                        <div><small><b>Anulada por:</b> <?php echo h(($compras_dato['nombres_usuario_anula'] ?? '') ?: ($compras_dato['anulado_por'] ?? '')); ?></small></div>
                                    <?php endif; ?>
                                    <?php if (!empty($compras_dato['motivo_anulacion'])): ?>
                                        <div><small><b>Motivo:</b> <?php echo h($compras_dato['motivo_anulacion']); ?></small></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <?php if (!$anulado): ?>
                        <a href="update.php?id=<?php echo (int)$id_compra_get; ?>" class="btn btn-success">
                            <i class="fa fa-pencil-alt"></i> Editar
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled title="Compra anulada">
                            <i class="fas fa-lock"></i> Editar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-lg-8">

                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-box mr-1"></i> Producto</h3>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label>Código</label>
                                            <input class="form-control" value="<?php echo h($codigo); ?>" disabled>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Categoría</label>
                                            <input class="form-control" value="<?php echo h($nombre_categoria); ?>" disabled>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Producto</label>
                                            <input class="form-control" value="<?php echo h($nombre_producto); ?>" disabled>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label>Usuario</label>
                                            <input class="form-control" value="<?php echo h($nombres_usuario); ?>" disabled>
                                        </div>
                                        <div class="col-md-8">
                                            <label>Descripción</label>
                                            <textarea class="form-control" rows="2" disabled><?php echo h($descripcion); ?></textarea>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-md-2">
                                            <label>Stock</label>
                                            <input class="form-control" value="<?php echo h($stock); ?>" disabled>
                                        </div>
                                        <div class="col-md-2">
                                            <label>Min</label>
                                            <input class="form-control" value="<?php echo h($stock_minimo); ?>" disabled>
                                        </div>
                                        <div class="col-md-2">
                                            <label>Max</label>
                                            <input class="form-control" value="<?php echo h($stock_maximo); ?>" disabled>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Compra</label>
                                            <input class="form-control" value="<?php echo h($precio_compra_producto); ?>" disabled>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Venta</label>
                                            <input class="form-control" value="<?php echo h($precio_venta_producto); ?>" disabled>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 text-center">
                                    <label class="d-block">Imagen</label>
                                    <img
                                        src="<?php echo product_image_url($imagen, (int)$id_categoria, true); ?>"
                                        class="img-fluid img-thumbnail"
                                        style="max-height:160px;"
                                        alt="Producto">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-truck mr-1"></i> Proveedor</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Proveedor</label>
                                    <input class="form-control" value="<?php echo h($nombre_proveedor_tabla); ?>" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label>Celular</label>
                                    <input class="form-control" value="<?php echo h($celular_proveedor); ?>" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label>Teléfono</label>
                                    <input class="form-control" value="<?php echo h($telefono_proveedor); ?>" disabled>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <label>Empresa</label>
                                    <input class="form-control" value="<?php echo h($empresa_proveedor); ?>" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label>Email</label>
                                    <input class="form-control" value="<?php echo h($email_proveedor); ?>" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label>Dirección</label>
                                    <textarea class="form-control" rows="2" disabled><?php echo h($direccion_proveedor); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Detalle de compra</h3>
                        </div>
                        <div class="card-body">

                            <div class="form-group">
                                <label>Número de compra</label>
                                <input class="form-control text-center" value="<?php echo h($nro_compra); ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label>Fecha</label>
                                <input class="form-control" value="<?php echo h($fecha_compra); ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label>Comprobante</label>
                                <input class="form-control" value="<?php echo h($comprobante); ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label>Precio compra</label>
                                <input class="form-control" value="<?php echo h($precio_compra); ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label>Cantidad</label>
                                <input class="form-control text-center" value="<?php echo h($cantidad); ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label>Usuario</label>
                                <input class="form-control" value="<?php echo h($nombres_usuario); ?>" disabled>
                            </div>

                            <hr>

                            <?php if (!$anulado): ?>
                                <button type="button"
                                    class="btn btn-warning btn-block"
                                    id="btnAnularCompra"
                                    data-id="<?php echo (int)$id_compra_get; ?>">
                                    <i class="fas fa-ban"></i> Anular compra
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-block" disabled>
                                    <i class="fas fa-check"></i> Ya está anulada
                                </button>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>

            </div>

        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
    (function() {
        var $btn = $('#btnAnularCompra');
        if (!$btn.length) return; // si la compra ya está anulada, el botón no existe

        $btn.on('click', function() {
            var id = $(this).data('id');
            if (!id) return;

            var go = function(motivo) {
                $.ajax({
                    url: "../app/controllers/compras/delete.php",
                    method: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    dataType: "json",
                    data: {
                        _csrf: (window.SOV_CSRF || ''),
                        id_compra: id,
                        motivo_anulacion: (motivo || '').trim()
                    }
                }).done(function(resp) {
                    if (resp && resp.ok) {
                        location.reload();
                    } else {
                        var msg = (resp && resp.error) ? resp.error : "No se pudo anular.";
                        if (window.SOV) SOV.warnModal(msg);
                        else alert(msg);
                    }
                }).fail(function(xhr) {
                    var msg = xhr.responseText || ("HTTP " + xhr.status);
                    if (window.SOV) SOV.warnModal(msg);
                    else alert(msg);
                });
            };

            if (typeof Swal !== 'undefined' && Swal.fire) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Anular compra',
                    html: 'Esto revertirá el stock del producto.<br><b>Motivo (obligatorio):</b>',
                    input: 'textarea',
                    inputPlaceholder: 'Escribe el motivo...',
                    inputAttributes: { maxlength: 250 },
                    showCancelButton: true,
                    confirmButtonText: 'Sí, anular',
                    cancelButtonText: 'Cancelar',
                    preConfirm: (val) => {
                        if (!val || !val.trim()) {
                            Swal.showValidationMessage('El motivo es obligatorio');
                            return false;
                        }
                        return val.trim();
                    }
                }).then(function(r) {
                    if (r.isConfirmed) go(r.value);
                });
            } else {
                var motivo = prompt('Motivo de anulación (obligatorio):');
                if (motivo && motivo.trim()) {
                    if (confirm('¿Anular compra? Esto revertirá stock.')) go(motivo);
                }
            }
        });
    })();
</script>