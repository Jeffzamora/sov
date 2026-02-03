<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';

require_once __DIR__ . '/../app/controllers/almacen/listado_de_productos.php';
require_once __DIR__ . '/../app/controllers/categorias/listado_de_categoria.php';

/**
 * Nota:
 * - El código REAL debe generarse/validarse en el controller (create.php) para evitar duplicados.
 * - Aquí solo mostramos un preview.
 */
function ceros($numero, $cantidad_ceros = 5)
{
    $numero = (string)$numero;
    $len = $cantidad_ceros - strlen($numero);
    if ($len < 0) $len = 0;
    return str_repeat('0', $len) . $numero;
}

$contador_de_id_productos = 1;
if (!empty($productos_datos) && is_array($productos_datos)) {
    $contador_de_id_productos = count($productos_datos) + 1;
}
$codigo_preview = 'P-' . ceros($contador_de_id_productos);
$today = date('Y-m-d');
?>

<div class="content-wrapper">

    <!-- Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h1 class="m-0">Nuevo producto</h1>
                    <ol class="breadcrumb float-sm-left mt-2">
                        <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/almacen/">Almacén</a></li>
                        <li class="breadcrumb-item active">Crear</li>
                    </ol>
                </div>

                <div class="mt-2 mt-sm-0">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Content -->
    <section class="content">
        <div class="container-fluid">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="card-title">
                            <i class="fas fa-box-open mr-1"></i> Registro de producto
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Minimizar">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <form id="form-producto"
                    action="../app/controllers/almacen/create.php"
                    method="post"
                    enctype="multipart/form-data"
                    novalidate>

                    <div class="card-body">
                        <?php if (function_exists('csrf_field')) echo csrf_field(); ?>

                        <div class="row">
                            <!-- Left column -->
                            <div class="col-lg-8">

                                <div class="alert alert-light border d-flex align-items-start" role="note">
                                    <i class="fas fa-info-circle mt-1 mr-2 text-primary"></i>
                                    <div>
                                        <div class="font-weight-bold">Recomendación</div>
                                        <div class="text-muted">
                                            El <b>código</b> debe generarse AUTO.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Código -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Código</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                </div>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($codigo_preview); ?>" disabled>
                                                <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($codigo_preview); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Categoría -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Categoría <span class="text-danger">*</span></label>
                                            <div class="d-flex" style="gap:.5rem;">
                                                <select name="id_categoria" id="id_categoria" class="form-control" required>
                                                    <option value="" selected disabled>Seleccione...</option>
                                                    <?php if (!empty($categorias_datos) && is_array($categorias_datos)): ?>
                                                        <?php foreach ($categorias_datos as $cat): ?>
                                                            <option value="<?php echo (int)$cat['id_categoria']; ?>">
                                                                <?php echo htmlspecialchars((string)$cat['nombre_categoria']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <a href="<?php echo $URL; ?>/categorias" class="btn btn-primary" title="Crear categoría">
                                                    <i class="fa fa-plus"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Fecha -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Fecha de ingreso <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                </div>
                                                <input type="date" name="fecha_ingreso" class="form-control" required value="<?php echo $today; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nombre + Usuario -->
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Nombre del producto <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                                </div>
                                                <input type="text"
                                                    name="nombre"
                                                    id="nombre"
                                                    class="form-control"
                                                    required
                                                    maxlength="120"
                                                    autocomplete="off"
                                                    placeholder="Ej: Lentes AR, Solución, Montura...">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Usuario</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                </div>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)$email_sesion); ?>" disabled>
                                                <input type="hidden" name="id_usuario" value="<?php echo (int)$id_usuario_sesion; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descripción -->
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <textarea name="descripcion"
                                        class="form-control"
                                        rows="3"
                                        maxlength="500"
                                        placeholder="Detalles del producto (opcional)"></textarea>
                                    <small class="text-muted">Máx. 500 caracteres.</small>
                                </div>

                                <hr class="my-4">

                                <!-- Stock + Precios -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Stock <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                                </div>
                                                <input type="number" name="stock" id="stock" class="form-control" required min="0" step="1" value="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Stock mínimo</label>
                                            <input type="number" name="stock_minimo" id="stock_minimo" class="form-control" min="0" step="1" value="0">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Stock máximo</label>
                                            <input type="number" name="stock_maximo" id="stock_maximo" class="form-control" min="0" step="1" value="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Precio compra <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                                </div>
                                                <input type="number" name="precio_compra" id="precio_compra"
                                                    class="form-control" required min="0" step="0.01" inputmode="decimal"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Precio venta <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-cash-register"></i></span>
                                                </div>
                                                <input type="number" name="precio_venta" id="precio_venta"
                                                    class="form-control" required min="0" step="0.01" inputmode="decimal"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Métricas rápidas -->
                                    <div class="col-md-4">
                                        <div class="small text-muted mb-1">Métrica rápida</div>
                                        <div class="p-2 border rounded bg-light">
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Ganancia</span>
                                                <span class="font-weight-bold" id="mx_ganancia">C$0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Margen</span>
                                                <span class="font-weight-bold" id="mx_margen">0%</span>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1">Calculado con base en compra/venta.</small>
                                    </div>
                                </div>

                            </div>

                            <!-- Right column: image -->
                            <div class="col-lg-4">
                                <div class="card border-0 shadow-sm" style="position: sticky; top: 1rem;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h5 class="mb-0"><i class="far fa-image mr-1"></i> Imagen</h5>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearImg" style="display:none;">
                                                Quitar
                                            </button>
                                        </div>

                                        <input type="file"
                                            name="image"
                                            class="form-control"
                                            id="file"
                                            accept="image/png,image/jpeg,image/webp">

                                        <small class="text-muted d-block mt-2">
                                            PNG/JPG/WebP. Recomendado ≤ 2MB.
                                        </small>

                                        <div class="mt-3" id="previewWrap" style="display:none;">
                                            <div class="border rounded p-2 bg-white">
                                                <img id="imgPreview" src="" alt="Preview" style="width:100%; height:auto; border-radius:10px;">
                                            </div>
                                        </div>

                                        <div class="mt-3 text-muted small" id="noPreview">
                                            Si no subes una imagen, el sistema usará el default de la categoría.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- row -->
                    </div><!-- card-body -->

                    <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <button id="btn-guardar" type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar producto
                        </button>
                    </div>

                </form>

            </div><!-- card -->

        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
    (function() {
        var form = document.getElementById('form-producto');
        var btn = document.getElementById('btn-guardar');

        // Imagen preview
        var input = document.getElementById('file');
        var img = document.getElementById('imgPreview');
        var previewWrap = document.getElementById('previewWrap');
        var noPreview = document.getElementById('noPreview');
        var btnClear = document.getElementById('btnClearImg');

        function money(n) {
            n = Number(n || 0);
            return n.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function calcMetrics() {
            var compra = parseFloat((document.getElementById('precio_compra').value || '0'));
            var venta = parseFloat((document.getElementById('precio_venta').value || '0'));
            var g = (venta - compra);
            var m = (venta > 0) ? (g / venta) * 100 : 0;

            document.getElementById('mx_ganancia').textContent = 'C$' + money(g);
            document.getElementById('mx_margen').textContent = (isFinite(m) ? m.toFixed(1) : '0.0') + '%';
        }

        ['precio_compra', 'precio_venta'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', calcMetrics);
        });
        calcMetrics();

        function clearImg() {
            if (!input) return;
            input.value = '';
            previewWrap.style.display = 'none';
            btnClear.style.display = 'none';
            noPreview.style.display = 'block';
            if (img) img.src = '';
        }

        function onFileChange(e) {
            var files = e.target.files || [];
            if (!files.length) {
                clearImg();
                return;
            }

            var f = files[0];
            if (!f.type || !/^image\//.test(f.type)) {
                clearImg();
                if (window.SOV && SOV.warnModal) SOV.warnModal('Seleccione una imagen válida (PNG/JPG/WebP).', 'Imagen');
                return;
            }

            var max = 2 * 1024 * 1024;
            if (f.size > max) {
                if (window.SOV && SOV.warnModal) SOV.warnModal('La imagen supera 2MB. Se recomienda optimizarla.', 'Imagen');
            }

            var reader = new FileReader();
            reader.onload = function(ev) {
                img.src = ev.target.result;
                previewWrap.style.display = 'block';
                btnClear.style.display = 'inline-block';
                noPreview.style.display = 'none';
            };
            reader.readAsDataURL(f);
        }

        if (input) input.addEventListener('change', onFileChange, false);
        if (btnClear) btnClear.addEventListener('click', clearImg);

        // Validación UX
        if (form) {
            form.addEventListener('submit', function(e) {
                var cat = (document.getElementById('id_categoria').value || '').trim();
                var nombre = (document.getElementById('nombre').value || '').trim();

                var stockMin = parseInt((document.getElementById('stock_minimo').value || '0'), 10);
                var stockMax = parseInt((document.getElementById('stock_maximo').value || '0'), 10);

                var compra = parseFloat((document.getElementById('precio_compra').value || '0'));
                var venta = parseFloat((document.getElementById('precio_venta').value || '0'));

                if (!cat) {
                    e.preventDefault();
                    if (window.SOV && SOV.warnModal) SOV.warnModal('Seleccione una categoría.', 'Validación');
                    return;
                }

                if (!nombre) {
                    e.preventDefault();
                    if (window.SOV && SOV.warnModal) SOV.warnModal('Ingrese el nombre del producto.', 'Validación');
                    return;
                }

                if (stockMax > 0 && stockMin > stockMax) {
                    e.preventDefault();
                    if (window.SOV && SOV.warnModal) SOV.warnModal('El stock mínimo no puede ser mayor que el stock máximo.', 'Validación');
                    return;
                }

                if (venta > 0 && compra > 0 && venta < compra) {
                    e.preventDefault();
                    if (window.SOV && SOV.warnModal) SOV.warnModal('El precio de venta no puede ser menor que el precio de compra.', 'Validación');
                    return;
                }

                // Evita doble envío
                if (btn) btn.disabled = true;
            });
        }
    })();
</script>