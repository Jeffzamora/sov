<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'ventas.pagos', $URL . '/');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  echo "Venta inválida";
  exit;
}

// Venta base
$st = $pdo->prepare("SELECT id_venta, nro_venta, total, pagado_inicial, saldo_pendiente, fyh_creacion FROM tb_ventas WHERE id_venta=? LIMIT 1");
$st->execute([$id]);
$venta = $st->fetch(PDO::FETCH_ASSOC);
if (!$venta) {
  http_response_code(404);
  echo "Venta no encontrada";
  exit;
}

$pagadoInicial = (float)($venta['pagado_inicial'] ?? 0);
$totalVenta = (float)($venta['total'] ?? 0);

$pay = $pdo->prepare("SELECT id_pago, fecha_pago, metodo_pago, monto, referencia FROM tb_ventas_pagos WHERE id_venta=? ORDER BY id_pago ASC");
$pay->execute([$id]);
$rows = $pay->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="pagos_venta_' . (int)$id . '.csv"');

// BOM para Excel
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

fputcsv($out, ['Venta', (string)($venta['nro_venta'] ?? $id)]);
fputcsv($out, ['Total', number_format($totalVenta, 2, '.', '')]);
fputcsv($out, ['Pagado inicial', number_format($pagadoInicial, 2, '.', '')]);
fputcsv($out, []);

fputcsv($out, ['#', 'Fecha', 'Método', 'Monto', 'Saldo antes', 'Saldo después', 'Referencia']);

$sumPrev = 0.0;
$idx = 0;
foreach ($rows as $r) {
  $idx++;
  $monto = (float)($r['monto'] ?? 0);
  $paidBefore = max(0.0, $pagadoInicial + $sumPrev);
  $saldoAntes = max(0.0, $totalVenta - $paidBefore);
  $saldoDespues = max(0.0, $saldoAntes - $monto);

  $fecha = (string)($r['fecha_pago'] ?? '');
  $met = (string)($r['metodo_pago'] ?? '');
  $ref = (string)($r['referencia'] ?? '');

  fputcsv($out, [
    $idx,
    $fecha,
    $met,
    number_format($monto, 2, '.', ''),
    number_format($saldoAntes, 2, '.', ''),
    number_format($saldoDespues, 2, '.', ''),
    $ref,
  ]);

  $sumPrev += $monto;
}

fclose($out);
