<?php
declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';
require_once __DIR__ . '/_export.php';

// RBAC (si está activo)
if (function_exists('require_perm')) {
  require_perm($pdo, 'reportes.ver', $URL . '/');
}

require_once $BASE_DIR . '/layout/parte1.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$from  = (string)($_GET['from'] ?? '');
$to    = (string)($_GET['to'] ?? '');
$group = strtolower((string)($_GET['group'] ?? 'day')); // day|week|month

if ($from === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if ($to   === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
if (!in_array($group, ['day','week','month'], true)) $group = 'day';

$selectKey = "DATE(v.fecha_venta)";
$labelKey  = "DATE(v.fecha_venta)";

if ($group === 'week') {
  $selectKey = "YEAR(v.fecha_venta)*100 + WEEK(v.fecha_venta, 1)";
  $labelKey  = "CONCAT(YEAR(v.fecha_venta), '-W', LPAD(WEEK(v.fecha_venta, 1), 2, '0'))";
} elseif ($group === 'month') {
  $selectKey = "DATE_FORMAT(v.fecha_venta, '%Y-%m')";
  $labelKey  = "DATE_FORMAT(v.fecha_venta, '%Y-%m')";
}

$rows = [];
try {
  $sql = "
    SELECT {$labelKey} AS periodo,
           COUNT(*) AS cant_ventas,
           SUM(v.subtotal) AS subtotal,
           SUM(v.descuento) AS descuento,
           SUM(v.impuesto) AS impuesto,
           SUM(v.total) AS total
      FROM tb_ventas v
     WHERE v.estado='activa'
       AND DATE(v.fecha_venta) BETWEEN :from AND :to
     GROUP BY {$selectKey}
     ORDER BY {$selectKey} ASC
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':from'=>$from, ':to'=>$to]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  error_log('[reportes.ventas_rango] ' . $e->getMessage());
}

$export = strtolower((string)($_GET['export'] ?? ''));
if ($export === 'csv' || $export === 'excel') {
  $out = [];
  foreach ($rows as $r) {
    $out[] = [
      (string)($r['periodo'] ?? ''),
      (string)((int)($r['cant_ventas'] ?? 0)),
      number_format((float)($r['subtotal'] ?? 0), 2, '.', ''),
      number_format((float)($r['descuento'] ?? 0), 2, '.', ''),
      number_format((float)($r['impuesto'] ?? 0), 2, '.', ''),
      number_format((float)($r['total'] ?? 0), 2, '.', ''),
    ];
  }
  report_export_csv("reporte_ventas_{$group}_{$from}_a_{$to}",
    ['Periodo','Cantidad ventas','Subtotal','Descuento','Impuesto','Total'], $out);
}

if ($export === 'pdf') {
  ob_start(); ?>
  <h2 style="margin:0 0 8px 0;">Reporte: Ventas por periodo</h2>
  <div style="color:#555;margin-bottom:10px;">
    Agrupación: <?php echo h($group); ?> | Rango: <?php echo h($from); ?> a <?php echo h($to); ?>
  </div>
  <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-size:12px;">
    <thead>
      <tr>
        <th align="left" style="border-bottom:1px solid #333;">Periodo</th>
        <th align="right" style="border-bottom:1px solid #333;">Ventas</th>
        <th align="right" style="border-bottom:1px solid #333;">Subtotal</th>
        <th align="right" style="border-bottom:1px solid #333;">Desc.</th>
        <th align="right" style="border-bottom:1px solid #333;">Imp.</th>
        <th align="right" style="border-bottom:1px solid #333;">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td style="border-bottom:1px solid #ddd;"><?php echo h($r['periodo'] ?? ''); ?></td>
        <td align="right" style="border-bottom:1px solid #ddd;"><?php echo (int)($r['cant_ventas'] ?? 0); ?></td>
        <td align="right" style="border-bottom:1px solid #ddd;">$<?php echo number_format((float)($r['subtotal'] ?? 0), 2); ?></td>
        <td align="right" style="border-bottom:1px solid #ddd;">$<?php echo number_format((float)($r['descuento'] ?? 0), 2); ?></td>
        <td align="right" style="border-bottom:1px solid #ddd;">$<?php echo number_format((float)($r['impuesto'] ?? 0), 2); ?></td>
        <td align="right" style="border-bottom:1px solid #ddd;">$<?php echo number_format((float)($r['total'] ?? 0), 2); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php
  $html = ob_get_clean();
  report_export_pdf("reporte_ventas_{$group}_{$from}_a_{$to}", $html, 'letter');
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Ventas por periodo</h1>
          <div class="text-muted">Día / Semana / Mes</div>
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
            <a class="btn btn-xs btn-outline-success"
               href="<?php echo $URL; ?>/reportes/ventas_rango.php?from=<?php echo h($from); ?>&to=<?php echo h($to); ?>&group=<?php echo h($group); ?>&export=csv">
              <i class="fas fa-file-excel"></i> Excel
            </a>
            <a class="btn btn-xs btn-outline-danger"
               href="<?php echo $URL; ?>/reportes/ventas_rango.php?from=<?php echo h($from); ?>&to=<?php echo h($to); ?>&group=<?php echo h($group); ?>&export=pdf">
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
            <label class="mr-2">Agrupar</label>
            <select name="group" class="form-control form-control-sm mr-3">
              <option value="day" <?php echo $group==='day'?'selected':''; ?>>Día</option>
              <option value="week" <?php echo $group==='week'?'selected':''; ?>>Semana</option>
              <option value="month" <?php echo $group==='month'?'selected':''; ?>>Mes</option>
            </select>
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Aplicar</button>
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
                    <th>Periodo</th>
                    <th class="text-right">Ventas</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">Desc.</th>
                    <th class="text-right">Imp.</th>
                    <th class="text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?php echo h($r['periodo'] ?? ''); ?></td>
                    <td class="text-right"><?php echo (int)($r['cant_ventas'] ?? 0); ?></td>
                    <td class="text-right">$<?php echo number_format((float)($r['subtotal'] ?? 0), 2); ?></td>
                    <td class="text-right">$<?php echo number_format((float)($r['descuento'] ?? 0), 2); ?></td>
                    <td class="text-right">$<?php echo number_format((float)($r['impuesto'] ?? 0), 2); ?></td>
                    <td class="text-right font-weight-bold">$<?php echo number_format((float)($r['total'] ?? 0), 2); ?></td>
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
