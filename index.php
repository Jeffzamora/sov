<?php

declare(strict_types=1);

// Para evitar includes frágiles:
$BASE_DIR = __DIR__;

require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';

require_once $BASE_DIR . '/layout/parte1.php';

// Controllers (usa require_once para evitar dobles includes)
require_once $BASE_DIR . '/app/controllers/usuarios/listado_de_usuarios.php';
require_once $BASE_DIR . '/app/controllers/roles/listado_de_roles.php';
require_once $BASE_DIR . '/app/controllers/categorias/listado_de_categoria.php';
require_once $BASE_DIR . '/app/controllers/almacen/listado_de_productos.php';
require_once $BASE_DIR . '/app/controllers/proveedores/listado_de_proveedores.php';

// Métricas de ventas / inventario para Dashboard
require_once $BASE_DIR . '/app/controllers/dashboard/metricas.php';
require_once $BASE_DIR . '/app/controllers/dashboard/graficas.php';

// Fallbacks defensivos (evita “Undefined variable”)
$URL = $URL ?? '';
$rol_sesion = $rol_sesion ?? 'Sin rol';

$usuarios_datos     = is_array($usuarios_datos ?? null) ? $usuarios_datos : [];
$roles_datos        = is_array($roles_datos ?? null) ? $roles_datos : [];
$categorias_datos   = is_array($categorias_datos ?? null) ? $categorias_datos : [];
$productos_datos    = is_array($productos_datos ?? null) ? $productos_datos : [];
$proveedores_datos  = is_array($proveedores_datos ?? null) ? $proveedores_datos : [];

// Contadores seguros
$contador_de_usuarios    = count($usuarios_datos);
$contador_de_roles       = count($roles_datos);
$contador_de_categorias  = count($categorias_datos);
$contador_de_productos   = count($productos_datos);
$contador_de_proveedores = count($proveedores_datos);

// Métricas (fallbacks defensivos)
$ventas_total_monto   = (float)($ventas_total_monto ?? 0);
$ventas_hoy_monto     = (float)($ventas_hoy_monto ?? 0);
$ventas_hoy_count     = (int)($ventas_hoy_count ?? 0);
$top_producto_nombre  = (string)($top_producto_nombre ?? '');
$top_producto_cant    = (int)($top_producto_cant ?? 0);
$low_stock_count      = (int)($low_stock_count ?? 0);
$low_stock_min_nombre = (string)($low_stock_min_nombre ?? '');
$low_stock_min_stock  = (int)($low_stock_min_stock ?? 0);
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0">Bienvenido a la optica - <?php echo htmlspecialchars((string)$rol_sesion, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <div>
          <div class="text-muted">Información general del sistema</div>
          <div class="small text-muted">Última actualización: <?php echo htmlspecialchars((string)$fechaHora, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div class="mt-2 mt-sm-0">
          <a class="btn btn-sm btn-outline-primary" href="<?php echo $URL; ?>/reportes">
            <i class="fas fa-chart-line"></i> Ver reportes
          </a>
        </div>
      </div>

      <!-- Dashboard con gráficas (Chart.js) -->
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-chart-area"></i> Ventas últimos 7 días</h3>
              <div class="card-tools">
                <span class="badge badge-light">Hoy: C$<?php echo number_format($ventas_hoy_monto, 2); ?> (<?php echo (int)$ventas_hoy_count; ?>)</span>
              </div>
            </div>
            <div class="card-body">
              <canvas id="chVentas7" height="120"></canvas>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-chart-pie"></i> Ventas por método (30 días)</h3>
              <div class="card-tools">
                <span class="badge badge-light">Total histórico: C$<?php echo number_format($ventas_total_monto, 2); ?></span>
              </div>
            </div>
            <div class="card-body">
              <canvas id="chMetodos" height="120"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-chart-bar"></i> Top 5 productos (30 días)</h3>
              <div class="card-tools">
                <a class="btn btn-xs btn-outline-primary" href="<?php echo $URL; ?>/reportes/ventas_productos.php">
                  Ver reporte
                </a>
              </div>
            </div>
            <div class="card-body">
              <canvas id="chTop5" height="120"></canvas>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Stock bajo (Top 5)</h3>
              <div class="card-tools">
                <a class="btn btn-xs btn-outline-danger" href="<?php echo $URL; ?>/reportes/stock_bajo.php">
                  Ver reporte
                </a>
              </div>
            </div>
            <div class="card-body">
              <?php if (empty($low5_labels ?? [])): ?>
                <div class="text-muted">Sin productos en mínimo stock.</div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-sm table-striped">
                    <thead>
                      <tr>
                        <th>Producto</th>
                        <th class="text-right">Stock</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php for ($i = 0; $i < count($low5_labels); $i++): ?>
                        <tr>
                          <td><?php echo htmlspecialchars((string)$low5_labels[$i], ENT_QUOTES, 'UTF-8'); ?></td>
                          <td class="text-right"><?php echo (int)($low5_values[$i] ?? 0); ?></td>
                        </tr>
                      <?php endfor; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <?php if ($low_stock_min_nombre !== ''): ?>
        <div class="alert alert-warning">
          <i class="fas fa-box-open"></i>
          <strong>Atención:</strong> El producto <strong><?php echo htmlspecialchars($low_stock_min_nombre, ENT_QUOTES, 'UTF-8'); ?></strong>
          está en <strong><?php echo (int)$low_stock_min_stock; ?></strong> unidades.
        </div>
      <?php endif; ?>

      <div class="row">

        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3><?php echo (int)$contador_de_usuarios; ?></h3>
              <p>Usuarios Registrados</p>
            </div>
            <a href="<?php echo $URL; ?>/usuarios/create.php">
              <div class="icon">
                <i class="fas fa-user-plus"></i>
              </div>
            </a>
            <a href="<?php echo $URL; ?>/usuarios" class="small-box-footer">
              Más detalle <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo (int)$contador_de_roles; ?></h3>
              <p>Roles Registrados</p>
            </div>
            <a href="<?php echo $URL; ?>/roles/create.php">
              <div class="icon">
                <i class="fas fa-address-card"></i>
              </div>
            </a>
            <a href="<?php echo $URL; ?>/roles" class="small-box-footer">
              Más detalle <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?php echo (int)$contador_de_categorias; ?></h3>
              <p>Categorías Registradas</p>
            </div>
            <a href="<?php echo $URL; ?>/categorias">
              <div class="icon">
                <i class="fas fa-tags"></i>
              </div>
            </a>
            <a href="<?php echo $URL; ?>/categorias" class="small-box-footer">
              Más detalle <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-primary">
            <div class="inner">
              <h3><?php echo (int)$contador_de_productos; ?></h3>
              <p>Productos Registrados</p>
            </div>
            <a href="<?php echo $URL; ?>/almacen/create.php">
              <div class="icon">
                <i class="fas fa-list"></i>
              </div>
            </a>
            <a href="<?php echo $URL; ?>/almacen" class="small-box-footer">
              Más detalle <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-dark">
            <div class="inner">
              <h3><?php echo (int)$contador_de_proveedores; ?></h3>
              <p>Proveedores Registrados</p>
            </div>
            <a href="<?php echo $URL; ?>/proveedores">
              <div class="icon">
                <i class="fas fa-car"></i>
              </div>
            </a>
            <a href="<?php echo $URL; ?>/proveedores" class="small-box-footer">
              Más detalle <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/chart.js/Chart.min.js"></script>
<script>
  (function() {
    if (typeof Chart === 'undefined') return;

    const ventas7Labels = <?php echo json_encode($ventas_7d_labels ?? [], JSON_UNESCAPED_UNICODE); ?>;
    const ventas7Values = <?php echo json_encode($ventas_7d_values ?? [], JSON_UNESCAPED_UNICODE); ?>;

    const metLabels = <?php echo json_encode($ventas_metodo_labels ?? [], JSON_UNESCAPED_UNICODE); ?>;
    const metValues = <?php echo json_encode($ventas_metodo_values ?? [], JSON_UNESCAPED_UNICODE); ?>;

    const topLabels = <?php echo json_encode($top5_labels ?? [], JSON_UNESCAPED_UNICODE); ?>;
    const topValues = <?php echo json_encode($top5_values ?? [], JSON_UNESCAPED_UNICODE); ?>;

    const ctx1 = document.getElementById('chVentas7');
    if (ctx1) {
      new Chart(ctx1, {
        type: 'line',
        data: {
          labels: ventas7Labels,
          datasets: [{
            label: 'Ventas',
            data: ventas7Values,
            fill: false,
            tension: 0.25
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    }

    const ctx2 = document.getElementById('chMetodos');
    if (ctx2) {
      new Chart(ctx2, {
        type: 'doughnut',
        data: {
          labels: metLabels,
          datasets: [{
            data: metValues
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    }

    const ctx3 = document.getElementById('chTop5');
    if (ctx3) {
      new Chart(ctx3, {
        type: 'bar',
        data: {
          labels: topLabels,
          datasets: [{
            label: 'Cantidad',
            data: topValues
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    }
  })();
</script>

<?php require_once $BASE_DIR . '/layout/parte2.php'; ?>