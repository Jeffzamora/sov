<?php
declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Filtros
$from = (string)($_GET['from'] ?? '');
$to = (string)($_GET['to'] ?? '');
if ($from === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
  $from = date('Y-m-01');
}
if ($to === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
  $to = date('Y-m-d');
}

$rows = [];
try {
  if (
    (!function_exists('db_table_exists') || db_table_exists($pdo, 'tb_ventas')) &&
    (!function_exists('db_table_exists') || db_table_exists($pdo, 'tb_ventas_detalle')) &&
    (!function_exists('db_table_exists') || db_table_exists($pdo, 'tb_almacen'))
  ) {
    $sql = "
      SELECT a.codigo, a.nombre,
             SUM(d.cantidad) AS qty,
             SUM(d.total_linea) AS monto
        FROM tb_ventas_detalle d
        INNER JOIN tb_ventas v ON v.id_venta = d.id_venta
        INNER JOIN tb_almacen a ON a.id_producto = d.id_producto
       WHERE v.estado='activa'
         AND DATE(v.fecha_venta) BETWEEN :from AND :to
       GROUP BY d.id_producto
       ORDER BY qty DESC, monto DESC
       LIMIT 200
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':from' => $from, ':to' => $to]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
} catch (Throwable $e) {
  error_log('[reportes.ventas_productos] ' . $e->getMessage());
}

// Export
$export = strtolower((string)($_GET['export'] ?? ''));
if ($export !== '') { require_once __DIR__ . '/_export.php'; }

if ($export === 'csv' || $export === 'excel') {
  $outRows = [];
  foreach ($rows as $r) {
    $outRows[] = [
      (string)($r['codigo'] ?? ''),
      (string)($r['nombre'] ?? ''),
      (string)((int)($r['qty'] ?? 0)),
      number_format((float)($r['monto'] ?? 0), 2, '.', ''),
    ];
  }

  if ($export === 'excel') {
    report_export_excel(
      'reporte_ventas_productos_' . $from . '_a_' . $to,
      ['Código','Producto','Cantidad','Monto'],
      $outRows,
      'Ventas por producto'
    );
  }

  report_export_csv('reporte_ventas_productos_' . $from . '_a_' . $to, ['Código','Producto','Cantidad','Monto'], $outRows);
}

if ($export === 'pdf') {
  // HTML simple para PDF
  ob_start();
  ?>
  <h2 style="margin:0 0 6px 0;">Ventas por producto</h2>
  <div style="color:#555;margin-bottom:12px;">Rango: <?php echo h($from); ?> a <?php echo h($to); ?></div>
  <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-size:12px;">
    <thead>
      <tr>
        <th align="left" style="border-bottom:1px solid #333;">Código</th>
        <th align="left" style="border-bottom:1px solid #333;">Producto</th>
        <th align="right" style="border-bottom:1px solid #333;">Cantidad</th>
        <th align="right" style="border-bottom:1px solid #333;">Monto</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td style="border-bottom:1px solid #ddd;"><?php echo h($r['codigo'] ?? ''); ?></td>
          <td style="border-bottom:1px solid #ddd;"><?php echo h($r['nombre'] ?? ''); ?></td>
          <td align="right" style="border-bottom:1px solid #ddd;"><?php echo (int)($r['qty'] ?? 0); ?></td>
          <td align="right" style="border-bottom:1px solid #ddd;">$<?php echo number_format((float)($r['monto'] ?? 0), 2); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php
  $html = ob_get_clean();
  report_export_pdf('reporte_ventas_productos_' . $from . '_a_' . $to, $html, 'letter');
}

require_once $BASE_DIR . '/layout/parte1.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Ventas por producto</h1>
          <div class="text-muted">Ranking por cantidad y monto</div>
        </div>
        <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo $URL; ?>/reportes">
            <i class="fas fa-arrow-left"></i> Volver
          </a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
          <div class="card-tools">
            <a class="btn btn-xs btn-outline-success" href="<?php echo $URL; ?>/reportes/ventas_productos.php?from=<?php echo h($from); ?>&to=<?php echo h($to); ?>&export=excel">
              <i class="fas fa-file-excel"></i> Excel
            </a>
            <a class="btn btn-xs btn-outline-danger" href="<?php echo $URL; ?>/reportes/ventas_productos.php?from=<?php echo h($from); ?>&to=<?php echo h($to); ?>&export=pdf">
              <i class="fas fa-file-pdf"></i> PDF
            </a>
          </div>
        </div>
        <div class="card-body">
          <form method="GET" class="form-inline">
            <label class="mr-2">Desde</label>
            <input type="date" name="from" value="<?php echo h($from); ?>" class="form-control form-control-sm mr-3">
            <label class="mr-2">Hasta</label>
            <input type="date" name="to" value="<?php echo h($to); ?>" class="form-control form-control-sm mr-3">
            <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-search"></i> Aplicar</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <?php if (empty($rows)): ?>
            <div class="alert alert-info mb-0">No hay datos para el rango seleccionado.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover table-sm">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Producto</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Monto</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i=0; foreach ($rows as $r): $i++; ?>
                    <tr>
                      <td><?php echo (int)$i; ?></td>
                      <td><?php echo h($r['codigo'] ?? ''); ?></td>
                      <td><?php echo h($r['nombre'] ?? ''); ?></td>
                      <td class="text-right"><?php echo (int)($r['qty'] ?? 0); ?></td>
                      <td class="text-right">C$<?php echo number_format((float)($r['monto'] ?? 0), 2); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </section>
</div>

<?php require_once $BASE_DIR . '/layout/parte2.php'; ?>
