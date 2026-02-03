<?php
declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';
require_once __DIR__ . '/_export.php';

if (function_exists('require_perm')) {
  require_perm($pdo, 'reportes.ver', $URL . '/');
}

require_once $BASE_DIR . '/layout/parte1.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$asOf = (string)($_GET['as_of'] ?? '');
if ($asOf === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOf)) $asOf = date('Y-m-d');

$rows = [];
$sum = ['b7'=>0.0,'b15'=>0.0,'b30'=>0.0,'b31'=>0.0,'total'=>0.0];

try {
  // Créditos: método_pago = CREDITO o mixto (y saldo > 0)
  $sql = "
    SELECT v.id_venta, v.nro_venta, DATE(v.fecha_venta) AS fecha,
           v.total, v.saldo_pendiente, v.metodo_pago,
           c.id_cliente, c.nombre, c.apellido, c.numero_documento,
           DATEDIFF(:asof, DATE(v.fecha_venta)) AS dias
      FROM tb_ventas v
      INNER JOIN tb_clientes c ON c.id_cliente = v.id_cliente
     WHERE v.estado='activa'
       AND v.saldo_pendiente > 0
       AND UPPER(v.metodo_pago) IN ('CREDITO','MIXTO')
     ORDER BY dias DESC, v.fecha_venta ASC
     LIMIT 2000
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':asof'=>C$asOf]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

  foreach ($rows as $r) {
    $saldo = (float)($r['saldo_pendiente'] ?? 0);
    $dias  = (int)($r['dias'] ?? 0);
    $sum['total'] += $saldo;

    if ($dias <= 7) $sum['b7'] += $saldo;
    elseif ($dias <= 15) $sum['b15'] += $saldo;
    elseif ($dias <= 30) $sum['b30'] += $saldo;
    else $sum['b31'] += $saldo;
  }
} catch (Throwable $e) {
  error_log('[reportes.creditos_aging] ' . $e->getMessage());
}

$export = strtolower((string)($_GET['export'] ?? ''));
if ($export === 'csv' || $export === 'excel') {
  $out = [];
  foreach ($rows as $r) {
    $cliente = trim((string)($r['nombre'] ?? '') . ' ' . (string)($r['apellido'] ?? ''));
    $out[] = [
      (string)($r['nro_venta'] ?? ''),
      (string)($r['fecha'] ?? ''),
      (string)($r['dias'] ?? ''),
      (string)($r['metodo_pago'] ?? ''),
      $cliente,
      (string)($r['numero_documento'] ?? ''),
      number_format((float)($r['total'] ?? 0), 2, '.', ''),
      number_format((float)($r['saldo_pendiente'] ?? 0), 2, '.', ''),
    ];
  }
  report_export_csv("reporte_creditos_aging_{$asOf}",
    ['Nro Venta','Fecha','Días','Método','Cliente','Documento','Total','Saldo'], $out);
}

if ($export === 'pdf') {
  ob_start(); ?>
  <h2 style="margin:0 0 8px 0;">Reporte: Créditos (Aging)</h2>
  <div style="color:#555;margin-bottom:10px;">Corte al: <?php echo h($asOf); ?></div>

  <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-size:12px;margin-bottom:10px;">
    <tr>
      <td><strong>0-7</strong>: C$<?php echo number_format($sum['b7'],2); ?></td>
      <td><strong>8-15</strong>: C$<?php echo number_format($sum['b15'],2); ?></td>
      <td><strong>16-30</strong>: C$<?php echo number_format($sum['b30'],2); ?></td>
      <td><strong>31+</strong>: C$<?php echo number_format($sum['b31'],2); ?></td>
      <td><strong>Total</strong>: C$<?php echo number_format($sum['total'],2); ?></td>
    </tr>
  </table>

  <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-size:11px;">
    <thead>
      <tr>
        <th align="left" style="border-bottom:1px solid #333;">Venta</th>
        <th align="left" style="border-bottom:1px solid #333;">Fecha</th>
        <th align="right" style="border-bottom:1px solid #333;">Días</th>
        <th align="left" style="border-bottom:1px solid #333;">Método</th>
        <th align="left" style="border-bottom:1px solid #333;">Cliente</th>
        <th align="right" style="border-bottom:1px solid #333;">Saldo</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): $cliente=trim(($r['nombre']??'').' '.($r['apellido']??'')); ?>
      <tr>
        <td style="border-bottom:1px solid #ddd;"><?php echo h($r['nro_venta'] ?? ''); ?></td>
        <td style="border-bottom:1px solid #ddd;"><?php echo h($r['fecha'] ?? ''); ?></td>
        <td align="right" style="border-bottom:1px solid #ddd;"><?php echo (int)($r['dias'] ?? 0); ?></td>
        <td style="border-bottom:1px solid #ddd;"><?php echo h($r['metodo_pago'] ?? ''); ?></td>
        <td style="border-bottom:1px solid #ddd;"><?php echo h($cliente); ?></td>
        <td align="right" style="border-bottom:1px solid #ddd;">C$<?php echo number_format((float)($r['saldo_pendiente'] ?? 0),2); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php
  $html = ob_get_clean();
  report_export_pdf("reporte_creditos_aging_{$asOf}", $html, 'letter');
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Créditos (Aging)</h1>
          <div class="text-muted">Solo método: CREDITO / mixto (saldo pendiente &gt; 0)</div>
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
          <h3 class="card-title"><i class="fas fa-filter"></i> Corte</h3>
          <div class="card-tools">
            <a class="btn btn-xs btn-outline-success"
               href="<?php echo $URL; ?>/reportes/creditos_aging.php?as_of=<?php echo h($asOf); ?>&export=csv">
              <i class="fas fa-file-excel"></i> Excel
            </a>
            <a class="btn btn-xs btn-outline-danger"
               href="<?php echo $URL; ?>/reportes/creditos_aging.php?as_of=<?php echo h($asOf); ?>&export=pdf">
              <i class="fas fa-file-pdf"></i> PDF
            </a>
          </div>
        </div>
        <div class="card-body">
          <form method="GET" class="form-inline">
            <label class="mr-2">Corte al</label>
            <input type="date" name="as_of" value="<?php echo h($asOf); ?>" class="form-control form-control-sm mr-3">
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Aplicar</button>
          </form>
        </div>
      </div>

      <div class="row">
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
          <div class="info-box-content"><span class="info-box-text">0-7 días</span><span class="info-box-number">C$<?php echo number_format($sum['b7'],2); ?></span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
          <div class="info-box-content"><span class="info-box-text">8-15 días</span><span class="info-box-number">C$<?php echo number_format($sum['b15'],2); ?></span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-orange"><i class="fas fa-clock"></i></span>
          <div class="info-box-content"><span class="info-box-text">16-30 días</span><span class="info-box-number">C$<?php echo number_format($sum['b30'],2); ?></span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
          <div class="info-box-content"><span class="info-box-text">31+ días</span><span class="info-box-number">C$<?php echo number_format($sum['b31'],2); ?></span></div></div></div>
      </div>

      <div class="card">
        <div class="card-body">
          <?php if (empty($rows)): ?>
            <div class="alert alert-success mb-0">No hay créditos pendientes 🎉</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover table-sm">
                <thead>
                  <tr>
                    <th>Venta</th>
                    <th>Fecha</th>
                    <th class="text-right">Días</th>
                    <th>Método</th>
                    <th>Cliente</th>
                    <th>Documento</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Saldo</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r):
                    $cliente = trim(($r['nombre']??'').' '.($r['apellido']??''));
                  ?>
                  <tr>
                    <td><?php echo h($r['nro_venta'] ?? ''); ?></td>
                    <td><?php echo h($r['fecha'] ?? ''); ?></td>
                    <td class="text-right"><?php echo (int)($r['dias'] ?? 0); ?></td>
                    <td><?php echo h($r['metodo_pago'] ?? ''); ?></td>
                    <td><?php echo h($cliente); ?></td>
                    <td><?php echo h($r['numero_documento'] ?? ''); ?></td>
                    <td class="text-right">C$<?php echo number_format((float)($r['total'] ?? 0),2); ?></td>
                    <td class="text-right font-weight-bold">C$<?php echo number_format((float)($r['saldo_pendiente'] ?? 0),2); ?></td>
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
