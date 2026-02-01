<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';

require_once __DIR__ . '/../app/controllers/almacen/cargar_producto.php';

// Validación mínima: si cargar_producto.php no cargó datos, redirigir
if (!isset($id_producto_get) || (int)$id_producto_get <= 0 || !isset($nombre)) {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $_SESSION['mensaje'] = 'Producto no encontrado o inválido.';
  $_SESSION['icono'] = 'error';
  header('Location: ' . $URL . '/almacen/');
  exit;
}

$idProducto = (int)$id_producto_get;

// Sanitización para salida HTML
$codigo_s = htmlspecialchars((string)($codigo ?? ''), ENT_QUOTES, 'UTF-8');
$nombre_s = htmlspecialchars((string)($nombre ?? ''), ENT_QUOTES, 'UTF-8');
$nombre_categoria_s = htmlspecialchars((string)($nombre_categoria ?? ''), ENT_QUOTES, 'UTF-8');
$email_s = htmlspecialchars((string)($email ?? ''), ENT_QUOTES, 'UTF-8');
$descripcion_s = htmlspecialchars((string)($descripcion ?? ''), ENT_QUOTES, 'UTF-8');

// Números (mostrar con fallback)
$stock_v = (int)($stock ?? 0);
$stock_minimo_v = ($stock_minimo === null || $stock_minimo === '') ? '' : (int)$stock_minimo;
$stock_maximo_v = ($stock_maximo === null || $stock_maximo === '') ? '' : (int)$stock_maximo;

// Precios: mantener formato simple
$precio_compra_v = htmlspecialchars((string)($precio_compra ?? ''), ENT_QUOTES, 'UTF-8');
$precio_venta_v  = htmlspecialchars((string)($precio_venta ?? ''), ENT_QUOTES, 'UTF-8');
$fecha_ingreso_v = htmlspecialchars((string)($fecha_ingreso ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0">Eliminar producto: <?php echo $nombre_s; ?></h1>
          <small class="text-muted">Revise la información antes de confirmar.</small>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="row">
        <div class="col-md-12">

          <div class="card card-danger">
            <div class="card-header">
              <h3 class="card-title">¿Está seguro de eliminar el producto?</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>

            <div class="card-body" style="display:block;">
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Esta acción eliminará el producto. Si tu sistema maneja historial o ventas vinculadas, considera usar “desactivar” en lugar de borrar.
              </div>

              <form id="form-delete-producto" action="../app/controllers/almacen/delete.php" method="post" novalidate>
                <?php if (function_exists('csrf_field')) {
                  echo csrf_field();
                } ?>
                <input type="hidden" name="id_producto" value="<?php echo $idProducto; ?>">

                <div class="row">
                  <div class="col-md-9">

                    <div class="row">
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Código:</label>
                          <input type="text" class="form-control" value="<?php echo $codigo_s; ?>" disabled>
                        </div>
                      </div>

                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Categoría:</label>
                          <input type="text" class="form-control" value="<?php echo $nombre_categoria_s; ?>" disabled>
                        </div>
                      </div>

                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Nombre del producto:</label>
                          <input type="text" class="form-control" value="<?php echo $nombre_s; ?>" disabled>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Usuario</label>
                          <input type="text" class="form-control" value="<?php echo $email_s; ?>" disabled>
                        </div>
                      </div>

                      <div class="col-md-8">
                        <div class="form-group">
                          <label>Descripción del producto:</label>
                          <textarea class="form-control" rows="2" disabled><?php echo $descripcion_s; ?></textarea>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Stock:</label>
                          <input type="number" class="form-control" value="<?php echo $stock_v; ?>" disabled>
                        </div>
                      </div>

                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Stock mínimo:</label>
                          <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)$stock_minimo_v); ?>" disabled>
                        </div>
                      </div>

                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Stock máximo:</label>
                          <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)$stock_maximo_v); ?>" disabled>
                        </div>
                      </div>

                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Precio compra:</label>
                          <input type="text" class="form-control" value="<?php echo $precio_compra_v; ?>" disabled>
                        </div>
                      </div>

                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Precio venta:</label>
                          <input type="text" class="form-control" value="<?php echo $precio_venta_v; ?>" disabled>
                        </div>
                      </div>

                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Fecha de ingreso:</label>
                          <input type="text" class="form-control" value="<?php echo $fecha_ingreso_v; ?>" disabled>
                        </div>
                      </div>
                    </div>

                  </div>

                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Imagen del producto</label>
                      <div class="border rounded p-2 bg-white">
                        <img
                          src="<?php echo product_image_url($imagen ?? '', $id_categoria ?? null, true); ?>"
                          style="width:100%; height:auto; border-radius:10px;"
                          alt="Imagen del producto">
                      </div>
                    </div>
                  </div>
                </div>

                <hr>

                <div class="form-group sov-btn-group">
                  <a href="index.php" class="btn btn-secondary">Cancelar</a>
                  <button id="btn-borrar" type="submit" class="btn btn-danger">
                    <i class="fa fa-trash"></i> Borrar producto
                  </button>
                </div>
              </form>
            </div>

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
    var form = document.getElementById('form-delete-producto');
    var btn = document.getElementById('btn-borrar');
    if (!form || !btn) return;

    form.addEventListener('submit', function() {
      // Evita doble submit
      btn.disabled = true;
    });
  })();
</script>