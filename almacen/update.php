<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/controllers/categorias/listado_de_categoria.php';
require_once __DIR__ . '/../app/controllers/almacen/cargar_producto.php';

$idProducto = (int)($id_producto_get ?? 0);
$catSelId   = (int)($id_categoria ?? 0); // id_categoria del producto actual
$imgUrl     = product_image_url($imagen, $catSelId ?: null, true);
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div class="mb-2">
                    <h1 class="m-0">Actualizar producto</h1>
                    <small class="text-muted">Código: <strong><?= e($codigo) ?></strong></small>
                </div>

                <div class="mb-2">
                    <a href="show.php?id=<?= $idProducto ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> Ver
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title">Edite solo lo necesario</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>

                        <form id="form-product-update"
                            action="../app/controllers/almacen/update.php"
                            method="post"
                            enctype="multipart/form-data"
                            novalidate>
                            <?= csrf_field(); ?>

                            <input type="hidden" name="id_producto" value="<?= $idProducto ?>">
                            <input type="hidden" name="codigo" value="<?= e($codigo) ?>">
                            <input type="hidden" name="imagen_actual" value="<?= e($imagen) ?>">

                            <div class="card-body">

                                <div class="row">
                                    <!-- Col principal -->
                                    <div class="col-lg-9">

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Código</label>
                                                    <input type="text" class="form-control" value="<?= e($codigo) ?>" disabled>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Categoría <span class="text-danger">*</span></label>
                                                    <select name="id_categoria" class="form-control" required>
                                                        <?php foreach ($categorias_datos as $c): ?>
                                                            <?php
                                                            $cid = (int)($c['id_categoria'] ?? 0);
                                                            $cn  = (string)($c['nombre_categoria'] ?? '');
                                                            $sel = ($cid === $catSelId) ? 'selected' : '';
                                                            ?>
                                                            <option value="<?= $cid ?>" <?= $sel ?>><?= e($cn) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="text-muted">Si cambia la categoría, la imagen por defecto (si aplica) también cambia.</small>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Nombre del producto <span class="text-danger">*</span></label>
                                                    <input type="text" name="nombre" class="form-control" required
                                                        value="<?= e($nombre) ?>"
                                                        maxlength="255"
                                                        placeholder="Ej: Lentes AR Premium">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Usuario</label>
                                                    <input type="text" class="form-control" value="<?= e($email ?? '') ?>" disabled>
                                                    <input type="hidden" name="id_usuario" value="<?= (int)($id_usuario ?? 0) ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Descripción</label>
                                                    <textarea name="descripcion" class="form-control" rows="3"
                                                        maxlength="1000"
                                                        placeholder="Detalles del producto..."><?= e($descripcion) ?></textarea>
                                                    <small class="text-muted">Máximo 1000 caracteres.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Stock <span class="text-danger">*</span></label>
                                                    <input type="number" name="stock" class="form-control" required
                                                        value="<?= (int)$stock ?>"
                                                        min="0" step="1">
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Stock mínimo</label>
                                                    <input type="number" name="stock_minimo" class="form-control"
                                                        value="<?= (int)$stock_minimo ?>"
                                                        min="0" step="1">
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Stock máximo</label>
                                                    <input type="number" name="stock_maximo" class="form-control"
                                                        value="<?= (int)$stock_maximo ?>"
                                                        min="0" step="1">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Precio compra <span class="text-danger">*</span></label>
                                                    <input type="number" name="precio_compra" class="form-control" required
                                                        value="<?= e($precio_compra) ?>"
                                                        min="0" step="0.01" inputmode="decimal">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Precio venta <span class="text-danger">*</span></label>
                                                    <input type="number" name="precio_venta" class="form-control" required
                                                        value="<?= e($precio_venta) ?>"
                                                        min="0" step="0.01" inputmode="decimal">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Fecha de ingreso <span class="text-danger">*</span></label>
                                                    <input type="date" name="fecha_ingreso" class="form-control" required
                                                        value="<?= e($fecha_ingreso) ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-9">
                                                <div class="alert alert-light border mb-0">
                                                    <div class="d-flex align-items-start">
                                                        <i class="fas fa-info-circle mr-2 mt-1 text-muted"></i>
                                                        <div>
                                                            <div><strong>Recomendación:</strong> configure stock mínimo y máximo para alertas visuales en el listado.</div>
                                                            <small class="text-muted">Si stock &lt; mínimo se marca rojo; si stock &gt; máximo se marca verde.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Col imagen -->
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>Imagen del producto</label>

                                            <div class="custom-file">
                                                <input type="file" name="image" class="custom-file-input" id="file"
                                                    accept="image/*">
                                                <label class="custom-file-label" for="file">Elegir imagen</label>
                                            </div>

                                            <small class="text-muted d-block mt-2">
                                                Formatos: JPG/PNG/WebP. Recomendado: 800x800.
                                            </small>

                                            <div class="mt-3">
                                                <div class="border rounded p-2 bg-white">
                                                    <img id="imgPreview" src="<?= e($imgUrl) ?>" alt="Imagen del producto" style="width:100%; height:auto; border-radius: 8px;">
                                                </div>

                                                <div class="d-flex gap-2 mt-2">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetImg">
                                                        <i class="fas fa-undo"></i> Revertir
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnClearFile">
                                                        <i class="fas fa-times"></i> Quitar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>

                            <div class="card-footer d-flex justify-content-between flex-wrap">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Guardar cambios
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
    (function() {
        const $file = document.getElementById('file');
        const $img = document.getElementById('imgPreview');
        const $reset = document.getElementById('btnResetImg');
        const $clear = document.getElementById('btnClearFile');
        const originalSrc = $img.getAttribute('src');

        function setLabel(name) {
            const label = document.querySelector('label[for="file"].custom-file-label');
            if (label) label.textContent = name || 'Elegir imagen';
        }

        function isImage(file) {
            return file && file.type && file.type.startsWith('image/');
        }

        $file && $file.addEventListener('change', function() {
            const f = this.files && this.files[0] ? this.files[0] : null;
            if (!f) {
                setLabel('');
                return;
            }
            if (!isImage(f)) {
                setLabel('');
                this.value = '';
                if (window.SOV && SOV.warnModal) SOV.warnModal('Seleccione un archivo de imagen válido.', 'Imagen');
                return;
            }
            setLabel(f.name);

            const reader = new FileReader();
            reader.onload = function(e) {
                $img.src = e.target.result;
            };
            reader.readAsDataURL(f);
        });

        $reset && $reset.addEventListener('click', function() {
            $img.src = originalSrc;
            setLabel('');
            if ($file) $file.value = '';
        });

        $clear && $clear.addEventListener('click', function() {
            setLabel('');
            if ($file) $file.value = '';
            $img.src = originalSrc;
        });

        // Validaciones UX mínimas (sin bloquear servidor)
        document.getElementById('form-product-update').addEventListener('submit', function(e) {
            const nombre = (this.nombre.value || '').trim();
            if (!nombre) {
                e.preventDefault();
                if (window.SOV && SOV.warnModal) SOV.warnModal('El nombre del producto es obligatorio.', 'Validación');
                this.nombre.focus();
                return;
            }
        });
    })();
</script>