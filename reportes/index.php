<?php

declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';

require_once $BASE_DIR . '/layout/parte1.php';

function h($v): string
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Reportes</h1>
          <div class="text-muted">Panel de análisis y exportación</div>
        </div>
        <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo $URL; ?>/">
            <i class="fas fa-home"></i> Dashboard
          </a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="alert alert-light border">
        <i class="fas fa-download"></i>
        Todos los reportes permiten <strong>exportar a Excel (CSV)</strong> y <strong>PDF</strong> desde los botones del encabezado.
      </div>

      <div class="row">

        <!-- ✅ NUEVO: Control anual de exámenes -->
        <div class="col-md-6 col-lg-4">
          <div class="card card-outline card-info">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-eye"></i> Control anual de exámenes
              </h3>
            </div>
            <div class="card-body">
              Clientes con <strong>más de 1 año</strong> desde su último examen o que están
              <strong>próximos</strong> a cumplirlo (por ejemplo, ventana 30 días).
            </div>
            <div class="card-footer">
              <a class="btn btn-info btn-sm" href="<?php echo $URL; ?>/reportes/clientes_control_examen.php">
                Ver reporte
              </a>

              <!-- acceso rápido a filtros -->
              <a class="btn btn-outline-danger btn-sm ml-1"
                href="<?php echo $URL; ?>/reportes/clientes_control_examen.php?tipo=vencidos">
                Vencidos
              </a>

              <a class="btn btn-outline-warning btn-sm ml-1"
                href="<?php echo $URL; ?>/reportes/clientes_control_examen.php?tipo=proximos&window=30">
                Próximos (30d)
              </a>
            </div>
          </div>
        </div>

        <!-- Existentes -->
        <div class="col-md-6 col-lg-4">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-chart-bar"></i> Ventas por producto</h3>
            </div>
            <div class="card-body">
              Ranking de productos más vendidos por cantidad y monto.
            </div>
            <div class="card-footer">
              <a class="btn btn-primary btn-sm" href="<?php echo $URL; ?>/reportes/ventas_productos.php">
                Ver reporte
              </a>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="card card-outline card-warning">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Stock bajo</h3>
            </div>
            <div class="card-body">
              Productos en mínimo (stock ≤ stock mínimo). Ideal para reabastecimiento.
            </div>
            <div class="card-footer">
              <a class="btn btn-warning btn-sm" href="<?php echo $URL; ?>/reportes/stock_bajo.php">
                Ver reporte
              </a>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="card card-outline card-success">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Ventas por periodo</h3>
            </div>
            <div class="card-body">
              Ventas por rango de fechas agrupadas por día / semana / mes.
            </div>
            <div class="card-footer">
              <a class="btn btn-success btn-sm" href="<?php echo $URL; ?>/reportes/ventas_rango.php">
                Ver reporte
              </a>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="card card-outline card-danger">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-hand-holding-usd"></i> Créditos (Aging)</h3>
            </div>
            <div class="card-body">
              Saldos pendientes solo para método <strong>CREDITO</strong> o <strong>mixto</strong>, con antigüedad 7/15/30 días.
            </div>
            <div class="card-footer">
              <a class="btn btn-danger btn-sm" href="<?php echo $URL; ?>/reportes/creditos_aging.php">
                Ver reporte
              </a>
            </div>
          </div>
        </div>

      </div>

    </div>
  </section>
</div>

<?php require_once $BASE_DIR . '/layout/parte2.php'; ?>