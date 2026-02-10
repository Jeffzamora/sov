<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v): string { return number_format((float)$v, 2, '.', ''); }
function fmt_dt($v): string {
  $v = (string)$v;
  if ($v === '') return '';
  $dt = DateTime::createFromFormat('Y-m-d H:i:s', $v) ?: DateTime::createFromFormat('Y-m-d', $v);
  return $dt ? $dt->format('d/m/Y H:i') : $v;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); echo 'Venta inválida'; exit; }

// Venta + cliente
$q = $pdo->prepare("\
  SELECT v.id_venta, v.nro_venta, v.fecha_venta, v.total, v.pagado_inicial, v.saldo_pendiente, v.estado,
         c.nombre, c.apellido, c.numero_documento
  FROM tb_ventas v
  INNER JOIN tb_clientes c ON c.id_cliente = v.id_cliente
  WHERE v.id_venta = ?
  LIMIT 1
");
$q->execute([$id]);
$venta = $q->fetch(PDO::FETCH_ASSOC);
if (!$venta) { http_response_code(404); echo 'Venta no encontrada'; exit; }

// Pagos
$pq = $pdo->prepare("\
  SELECT id_pago, fecha_pago, metodo_pago, monto, referencia, id_usuario
  FROM tb_ventas_pagos
  WHERE id_venta = ?
  ORDER BY id_pago ASC
");
$pq->execute([$id]);
$pagos = $pq->fetchAll(PDO::FETCH_ASSOC);

$optica = function_exists('optica_info') ? optica_info() : [];
$optica_nombre = (string)($optica['nombre'] ?? 'Óptica');
$optica_tel    = (string)($optica['telefono'] ?? '');
$optica_dir    = (string)($optica['direccion'] ?? '');
$optica_ruc    = (string)($optica['ruc'] ?? '');
$CURRENCY = function_exists('currency_symbol') ? currency_symbol() : 'C$';

$totalVenta = (float)($venta['total'] ?? 0);
$pagadoInicial = (float)($venta['pagado_inicial'] ?? 0);
$sumPrev = 0.0;

?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Histórico de Pagos - Venta #<?php echo (int)$venta['id_venta']; ?></title>
  <style>
    body{font-family:DejaVu Sans, sans-serif; font-size:12px; color:#111;}
    .header{display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #111; padding-bottom:10px; margin-bottom:12px;}
    .h1{font-size:16px; font-weight:700;}
    .muted{color:#666;}
    table{width:100%; border-collapse:collapse; margin-top:10px;}
    th,td{border:1px solid #222; padding:6px;}
    th{background:#f3f3f3; text-align:left;}
    .text-right{text-align:right;}
    .text-center{text-align:center;}
    .badge{display:inline-block; padding:2px 6px; border:1px solid #999; border-radius:10px; font-size:11px;}
    .totals{margin-top:12px; width:45%; float:right;}
    .totals td{border:none; padding:4px;}
    .totals tr td:first-child{color:#555;}
    .footer{margin-top:22px; clear:both; text-align:center; color:#666; font-size:11px;}
  </style>
</head>
<body>

<div class="header">
  <div>
    <div class="h1"><?php echo h($optica_nombre); ?></div>
    <?php if ($optica_ruc !== ''): ?><div class="muted">RUC: <?php echo h($optica_ruc); ?></div><?php endif; ?>
    <?php if ($optica_dir !== ''): ?><div class="muted"><?php echo h($optica_dir); ?></div><?php endif; ?>
    <?php if ($optica_tel !== ''): ?><div class="muted">Tel: <?php echo h($optica_tel); ?></div><?php endif; ?>
  </div>
  <div style="text-align:right">
    <div class="h1">Histórico de Pagos</div>
    <div class="muted">Venta #<?php echo (int)($venta['nro_venta'] ?? $venta['id_venta']); ?></div>
    <div class="muted">Fecha venta: <?php echo h(fmt_dt($venta['fecha_venta'] ?? '')); ?></div>
  </div>
</div>

<div>
  <b>Cliente:</b> <?php echo h(($venta['apellido'] ?? '') . ' ' . ($venta['nombre'] ?? '')); ?>
  <?php if (!empty($venta['numero_documento'])): ?>
    <span class="muted">(Doc: <?php echo h($venta['numero_documento']); ?>)</span>
  <?php endif; ?>
</div>

<table>
  <thead>
    <tr>
      <th style="width:120px">Fecha</th>
      <th style="width:95px">Método</th>
      <th class="text-right" style="width:95px">Monto</th>
      <th class="text-right" style="width:110px">Saldo antes</th>
      <th class="text-right" style="width:110px">Saldo después</th>
      <th>Referencia</th>
      <th class="text-center" style="width:80px">Voucher</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$pagos): ?>
      <tr><td colspan="7" class="text-center muted" style="padding:14px;">Sin pagos registrados.</td></tr>
    <?php else: ?>
      <?php foreach ($pagos as $p):
        $monto = (float)($p['monto'] ?? 0);
        $paidBefore = max(0.0, $pagadoInicial + $sumPrev);
        $saldoAntes = max(0.0, $totalVenta - $paidBefore);
        $saldoDespues = max(0.0, $saldoAntes - $monto);
        $sumPrev += $monto;
      ?>
      <tr>
        <td class="muted"><?php echo h(fmt_dt($p['fecha_pago'] ?? '')); ?></td>
        <td><span class="badge"><?php echo h($p['metodo_pago'] ?? ''); ?></span></td>
        <td class="text-right"><b><?php echo h($CURRENCY); ?><?php echo money($monto); ?></b></td>
        <td class="text-right"><?php echo h($CURRENCY); ?><?php echo money($saldoAntes); ?></td>
        <td class="text-right"><?php echo h($CURRENCY); ?><?php echo money($saldoDespues); ?></td>
        <td class="muted"><?php echo h($p['referencia'] ?? ''); ?></td>
        <td class="text-center">#<?php echo (int)($p['id_pago'] ?? 0); ?></td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<table class="totals">
  <tr>
    <td>Total venta:</td>
    <td class="text-right"><b><?php echo h($CURRENCY); ?><?php echo money($totalVenta); ?></b></td>
  </tr>
  <tr>
    <td>Pagado inicial:</td>
    <td class="text-right"><?php echo h($CURRENCY); ?><?php echo money($pagadoInicial); ?></td>
  </tr>
  <tr>
    <td>Pagos (abonos):</td>
    <td class="text-right"><?php echo h($CURRENCY); ?><?php echo money($sumPrev); ?></td>
  </tr>
  <tr>
    <td>Saldo pendiente:</td>
    <td class="text-right"><b><?php echo h($CURRENCY); ?><?php echo money((float)($venta['saldo_pendiente'] ?? 0)); ?></b></td>
  </tr>
</table>

<div class="footer">
  Generado el <?php echo date('d/m/Y H:i'); ?>
</div>

</body>
</html>
