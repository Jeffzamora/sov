<?php
declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$rows = [];
try {
  if (!function_exists('db_table_exists') || db_table_exists($pdo, 'tb_almacen')) {
    $sql = "
      SELECT id_producto, codigo, nombre, stock, stock_minimo, stock_maximo
        FROM tb_almacen
       WHERE stock_minimo IS NOT NULL
         AND stock <= stock_minimo
       ORDER BY stock ASC, nombre ASC
    ";
    $st = $pdo->query($sql);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
} catch (Throwable $e) {
  error_log('[reportes.stock_bajo] ' . $e->getMessage());
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
      (string)((int)($r['stock'] ?? 0)),
      (string)((int)($r['stock_minimo'] ?? 0)),
    ];
  }

  if ($export === 'excel') {
    report_export_excel(
      'reporte_stock_bajo_' . date('Y-m-d'),
      ['Código','Producto','Stock','Mínimo'],
      $outRows,
      'Stock bajo (mínimo)'
    );
  }

  report_export_csv('reporte_stock_bajo_' . date('Y-m-d'), ['Código','Producto','Stock','Mínimo'], $outRows);
}

if ($export === 'pdf') {
  ob_start();
  ?>
  <h2 style="margin:0 0 6px 0;">Stock bajo (mínimo)</h2>
  <div style="color:#555;margin-bottom:12px;">Generado: <?php echo h(date('Y-m-d H:i')); ?></div>
  <table style="width:100%;border-collapse:collapse;font-size:12px;">
    <thead>
      <tr>
        <th style="text-align:left;border-bottom:1px solid #ccc;padding:6px;">Código</th>
        <th style="text-align:left;border-bottom:1px solid #ccc;padding:6px;">Producto</th>
        <th style="text-align:right;border-bottom:1px solid #ccc;padding:6px;">Stock</th>
        <th style="text-align:right;border-bottom:1px solid #ccc;padding:6px;">Mínimo</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td style="border-bottom:1px solid #eee;padding:6px;"><?php echo h($r['codigo'] ?? ''); ?></td>
          <td style="border-bottom:1px solid #eee;padding:6px;"><?php echo h($r['nombre'] ?? ''); ?></td>
          <td style="border-bottom:1px solid #eee;padding:6px;text-align:right;"><?php echo (int)($r['stock'] ?? 0); ?></td>
          <td style="border-bottom:1px solid #eee;padding:6px;text-align:right;"><?php echo (int)($r['stock_minimo'] ?? 0); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php
  $html = ob_get_clean();
  report_export_pdf('reporte_stock_bajo_' . date('Y-m-d'), $html, 'letter');
}

require_once $BASE_DIR . '/layout/parte1.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Stock bajo</h1>
          <div class="text-muted">Productos con stock menor o igual al mínimo</div>
        </div>
        <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-sm btn-outline-success" href="<?php echo $URL; ?>/reportes/stock_bajo.php?export=excel">
            <i class="fas fa-file-excel"></i> Excel
          </a>
          <a class="btn btn-sm btn-outline-danger" href="<?php echo $URL; ?>/reportes/stock_bajo.php?export=pdf">
            <i class="fas fa-file-pdf"></i> PDF
          </a>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo $URL; ?>/reportes">
            <i class="fas fa-arrow-left"></i> Volver
          </a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="card card-outline card-warning">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-box-open"></i> Lista de productos</h3>
        </div>
        <div class="card-body">

          <?php if (empty($rows)): ?>
            <div class="alert alert-success mb-0">
              <i class="fas fa-check"></i> No hay productos en stock mínimo.
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover table-sm">
                <thead>
                  <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th class="text-right">Stock</th>
                    <th class="text-right">Mínimo</th>
                    <th class="text-right">Máximo</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                    <tr>
                      <td><?php echo h($r['codigo'] ?? ''); ?></td>
                      <td><?php echo h($r['nombre'] ?? ''); ?></td>
                      <td class="text-right"><span class="badge badge-danger"><?php echo (int)($r['stock'] ?? 0); ?></span></td>
                      <td class="text-right"><?php echo (int)($r['stock_minimo'] ?? 0); ?></td>
                      <td class="text-right"><?php echo h($r['stock_maximo'] ?? ''); ?></td>
                      <td class="text-right">
                        <a class="btn btn-xs btn-outline-primary" href="<?php echo $URL; ?>/almacen/update.php?id=<?php echo (int)($r['id_producto'] ?? 0); ?>">
                          <i class="fas fa-edit"></i> Editar
                        </a>
                      </td>
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
