<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

$id_pago = (int)($_GET['id_pago'] ?? 0);
if ($id_pago <= 0) {
  http_response_code(404);
  echo "Pago inválido";
  exit;
}

$stmt = $pdo->prepare("
  SELECT p.id_pago, p.id_venta, p.id_caja, p.fecha_pago, p.metodo_pago, p.monto, p.referencia, p.id_usuario,
         v.nro_venta, v.fecha_venta, v.total, v.saldo_pendiente, v.estado AS estado_venta,
         c.nombre, c.apellido, c.numero_documento
    FROM tb_ventas_pagos p
    INNER JOIN tb_ventas v ON v.id_venta = p.id_venta
    INNER JOIN tb_clientes c ON c.id_cliente = v.id_cliente
   WHERE p.id_pago = ?
   LIMIT 1
");
$stmt->execute([$id_pago]);
$pay = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pay) {
  http_response_code(404);
  echo "Pago no encontrado";
  exit;
}

function h($s): string
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function money($v): string
{
  return number_format((float)$v, 2, '.', '');
}
function fmt_dt($v): string
{
  $v = (string)$v;
  if ($v === '') return '';
  // intenta formatos comunes
  $dt = DateTime::createFromFormat('Y-m-d H:i:s', $v) ?: DateTime::createFromFormat('Y-m-d', $v);
  return $dt ? $dt->format('d/m/Y H:i') : $v;
}
function row_lr(string $l, string $r): string
{
  $l = trim($l);
  $r = trim($r);
  if ($l === '' && $r === '') return '';
  return "<div class='lr'><span class='l'>" . h($l) . "</span><span class='r'>" . h($r) . "</span></div>";
}

$optica = function_exists('optica_info') ? optica_info() : [];
$optica_nombre = (string)($optica['nombre'] ?? 'Óptica Alta Vision');
$optica_tel    = (string)($optica['telefono'] ?? '');
$optica_dir    = (string)($optica['direccion'] ?? '');
$optica_ruc    = (string)($optica['ruc'] ?? '');

$id_venta = (int)($pay['id_venta'] ?? 0);
$nro = (int)($pay['nro_venta'] ?? $id_venta);
$cliente = trim((string)($pay['apellido'] ?? '') . ' ' . (string)($pay['nombre'] ?? ''));
$doc = (string)($pay['numero_documento'] ?? '');
$metodo = strtoupper((string)($pay['metodo_pago'] ?? ''));
$fecha = fmt_dt($pay['fecha_pago'] ?? '');
$ref = (string)($pay['referencia'] ?? '');

$totalVenta = (float)($pay['total'] ?? 0);
$monto = (float)($pay['monto'] ?? 0);

// Calcula saldo antes/después usando el orden de id_pago (no requiere cambios de BD)
$prev = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM tb_ventas_pagos WHERE id_venta=? AND id_pago < ?");
$prev->execute([$id_venta, $id_pago]);
$sumPrev = (float)($prev->fetchColumn() ?? 0);

$saldoAntes = max(0.0, $totalVenta - $sumPrev);
$saldoDespues = max(0.0, $saldoAntes - $monto);
$deudaCancelada = ($saldoDespues <= 0.00001);

// Cambia según tu rollo:
$PAPER_MM = 80; // 80 o 58

// (Opcional) Moneda
$CURRENCY = 'C$';
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Voucher Abono #<?php echo (int)$id_pago; ?></title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $URL; ?>/public/images/optica/icon_bajo.png">
  <link rel="apple-touch-icon" href="<?php echo $URL; ?>/public/images/optica/icon_alto.png">

  <style>
    :root {
      --paper-mm: <?php echo (int)$PAPER_MM; ?>mm;
      --font: 12px;
      --lh: 1.25;
    }

    html,
    body {
      margin: 0;
      padding: 0;
      background: #fff;
    }

    body {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-size: var(--font);
      line-height: var(--lh);
      color: #000;
    }

    .ticket {
      width: var(--paper-mm);
      padding: 6mm 4mm;
      box-sizing: border-box;
      margin: 0 auto;
    }

    .center {
      text-align: center;
    }

    .muted {
      opacity: .85;
    }

    .title {
      font-weight: 800;
      font-size: 14px;
      letter-spacing: .2px;
    }

    .small {
      font-size: 11px;
    }

    .sep {
      margin: 8px 0;
      border-top: 1px dashed #000;
    }

    .lr {
      display: flex;
      justify-content: space-between;
      gap: 8px;
    }

    .l {
      font-weight: 700;
    }

    .r {
      text-align: right;
    }

    .items {
      margin-top: 6px;
    }

    .item {
      padding: 6px 0;
      border-bottom: 1px dashed #000;
    }

    .item:last-child {
      border-bottom: none;
    }

    .i-top {
      display: flex;
      justify-content: space-between;
      gap: 8px;
    }

    .i-name {
      font-weight: 700;
    }

    .i-sub {
      display: flex;
      justify-content: space-between;
      gap: 8px;
      margin-top: 2px;
    }

    .badge {
      display: inline-block;
      padding: 2px 6px;
      border: 1px solid #000;
      border-radius: 6px;
      font-weight: 800;
      font-size: 11px;
      margin-top: 4px;
    }

    .totals .lr {
      padding: 3px 0;
    }

    .grand {
      font-size: 14px;
      font-weight: 900;
      padding-top: 6px;
      border-top: 2px solid #000;
      margin-top: 6px;
    }

    .no-print {
      margin-top: 10px;
      display: flex;
      gap: 8px;
      justify-content: center;
    }

    .btn {
      border: 1px solid #111;
      background: #fff;
      padding: 6px 10px;
      font: inherit;
      cursor: pointer;
    }

    @media print {
      .no-print {
        display: none !important;
      }

      .ticket {
        padding: 0;
        width: 100%;
      }

      @page {
        margin: 0;
        size: auto;
      }
    }
  </style>
</head>

<body>
  <div class="ticket">
    <div class="center">
      <div class="title"><?php echo h($optica_nombre); ?></div>
      <?php if ($optica_ruc): ?><div class="small muted"><?php echo h($optica_ruc); ?></div><?php endif; ?>
      <?php if ($optica_dir): ?><div class="small muted"><?php echo h($optica_dir); ?></div><?php endif; ?>
      <?php if ($optica_tel): ?><div class="small muted">Tel: <?php echo h($optica_tel); ?></div><?php endif; ?>
    </div>

    <div class="sep"></div>

    <div class="center">
      <div class="title">VOUCHER DE ABONO</div>
      <div class="badge">Pago #<?php echo (int)$id_pago; ?> • Venta #<?php echo (int)$nro; ?></div>
      <?php if ($deudaCancelada): ?>
        <div class="badge">DEUDA CANCELADA</div>
      <?php endif; ?>
    </div>

    <div class="sep"></div>

    <?php echo row_lr('Fecha pago', $fecha); ?>
    <?php echo row_lr('Cliente', $cliente !== '' ? $cliente : 'N/D'); ?>
    <?php if ($doc !== ''): ?><?php echo row_lr('Documento', $doc); ?><?php endif; ?>
    <?php if ($metodo !== ''): ?><?php echo row_lr('Método', $metodo); ?><?php endif; ?>
    <?php if ($ref !== ''): ?><?php echo row_lr('Referencia', $ref); ?><?php endif; ?>

    <div class="sep"></div>

    <div class="lr grand">
      <span class="l">ABONO</span>
      <span class="r"><?php echo h($CURRENCY . money($monto)); ?></span>
    </div>

    <div class="sep"></div>

    <div class="totals">
      <?php echo row_lr('Total venta', $CURRENCY . money($totalVenta)); ?>
      <?php echo row_lr('Saldo antes', $CURRENCY . money($saldoAntes)); ?>
      <?php echo row_lr('Saldo después', $CURRENCY . money($saldoDespues)); ?>
    </div>

    <div class="sep"></div>

    <div class="center small muted">Conserve este comprobante.</div>
    <div class="center small" style="margin-top:6px;">--- FIN DEL VOUCHER ---</div>

    <div class="no-print">
      <button class="btn" onclick="window.print()">Imprimir</button>
      <a class="btn" href="<?php echo h($URL . '/ventas/ver.php?id=' . $id_venta); ?>">Volver</a>
      <button class="btn" onclick="window.close()">Cerrar</button>
    </div>
  </div>

  <script class="no-print">
    // Opcional: auto-print + auto-close (útil si lo abres en ventana nueva)
    window.addEventListener('load', () => {
    setTimeout(() => window.print(), 250);
    window.addEventListener('afterprint', () => setTimeout(() => window.close(), 250));
    });
  </script>
</body>

</html>