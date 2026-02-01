<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  echo "Venta inválida";
  exit;
}

$stmt = $pdo->prepare("
  SELECT v.*, c.nombre, c.apellido, c.numero_documento
  FROM tb_ventas v
  INNER JOIN tb_clientes c ON c.id_cliente = v.id_cliente
  WHERE v.id_venta = ?
  LIMIT 1
");
$stmt->execute([$id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venta) {
  http_response_code(404);
  echo "Venta no encontrada";
  exit;
}

$det = $pdo->prepare("
  SELECT d.*, a.nombre AS producto
  FROM tb_ventas_detalle d
  INNER JOIN tb_almacen a ON a.id_producto = d.id_producto
  WHERE d.id_venta = ?
  ORDER BY d.id_detalle ASC
");
$det->execute([$id]);
$items = $det->fetchAll(PDO::FETCH_ASSOC) ?: [];

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

$nro = (int)($venta['nro_venta'] ?? $id);
$cliente = trim((string)($venta['apellido'] ?? '') . ' ' . (string)($venta['nombre'] ?? ''));
$doc = (string)($venta['numero_documento'] ?? '');
$metodo = (string)($venta['metodo_pago'] ?? '');
$fecha = fmt_dt($venta['fecha_venta'] ?? '');

$subtotal = money($venta['subtotal'] ?? 0);
$descuento = money($venta['descuento'] ?? 0);
$impuesto = money($venta['impuesto'] ?? 0);
$total = money($venta['total'] ?? 0);
$pagado = money($venta['pagado_inicial'] ?? 0);
$saldo = money($venta['saldo_pendiente'] ?? 0);

// Cambia según tu rollo:
$PAPER_MM = 80; // 80 o 58

// (Opcional) Moneda
$CURRENCY = 'C$';

// Formatos de impresión:
// - ticket (80/58mm) [default]
// - carta3: hoja carta con 3 copias (Cliente / Caja / Archivo)
$formato = strtolower(trim((string)($_GET['formato'] ?? 'ticket')));

// === Formato CARTA (3 partes) ===
if ($formato === 'carta3'):
  $optica_logo = (string)($optica['logo'] ?? '');
  $labels = ['CLIENTE', 'CAJA', 'ARCHIVO'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recibo Venta #<?php echo (int)$nro; ?> (Carta)</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $URL; ?>/public/images/optica/icon_bajo.png">
  <style>
    @page { size: letter; margin: 0.35in; }
    html, body { margin: 0; padding: 0; background: #fff; color: #000; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; }
    .sheet { width: 100%; }
    .copy {
      position: relative;
      height: 3.35in; /* 11in - 0.7in margins = 10.3in. 10.3/3 ≈ 3.43in; dejamos holgura */
      border: 1px dashed #333;
      padding: 0.14in;
      box-sizing: border-box;
      overflow: hidden;
    }
    .copy + .copy { margin-top: 0.12in; }
    .wm {
      position: absolute; inset: 0;
      display: flex; align-items: center; justify-content: center;
      pointer-events: none;
      opacity: 0.05;
      transform: rotate(-18deg);
    }
    .wm img { max-width: 70%; max-height: 70%; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .brand { font-weight: 800; font-size: 13px; }
    .meta { text-align: right; }
    .tag { display: inline-block; border: 1px solid #000; padding: 2px 6px; font-weight: 800; font-size: 10px; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 6px; }
    .box { border: 1px solid #000; padding: 6px; }
    .box h4 { margin: 0 0 4px 0; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th, td { border-bottom: 1px solid #ddd; padding: 4px 2px; }
    th { text-align: left; font-size: 10px; }
    td.r, th.r { text-align: right; }
    .tot { margin-top: 6px; display: flex; justify-content: flex-end; }
    .tot table { width: 55%; }
    .tot td { border-bottom: none; }
    .grand { font-weight: 900; font-size: 12px; border-top: 2px solid #000; padding-top: 4px; }
    .foot { margin-top: 6px; font-size: 10px; }
    .no-print { margin: 10px 0; text-align: center; }
    @media print { .no-print { display: none !important; } }
  </style>
</head>
<body>
  <div class="no-print">
    <button onclick="window.print()" style="padding:6px 10px;border:1px solid #111;background:#fff;cursor:pointer">Imprimir</button>
  </div>
  <div class="sheet">
    <?php foreach ($labels as $lab): ?>
      <section class="copy">
        <?php if ($optica_logo): ?>
          <div class="wm"><img src="<?php echo h($optica_logo); ?>" alt=""></div>
        <?php endif; ?>

        <div class="header">
          <div>
            <div class="brand"><?php echo h($optica_nombre); ?></div>
            <?php if ($optica_ruc): ?><div><?php echo h($optica_ruc); ?></div><?php endif; ?>
            <?php if ($optica_dir): ?><div><?php echo h($optica_dir); ?></div><?php endif; ?>
            <?php if ($optica_tel): ?><div>Tel: <?php echo h($optica_tel); ?></div><?php endif; ?>
          </div>
          <div class="meta">
            <div class="tag">COPIA: <?php echo h($lab); ?></div><br>
            <div style="margin-top:4px"><b>Venta #<?php echo (int)$nro; ?></b></div>
            <div><?php echo h($fecha); ?></div>
            <div>Método: <?php echo h($metodo); ?></div>
          </div>
        </div>

        <div class="grid">
          <div class="box">
            <h4>Cliente</h4>
            <div><?php echo h($cliente); ?></div>
            <?php if ($doc): ?><div>Doc: <?php echo h($doc); ?></div><?php endif; ?>
          </div>
          <div class="box">
            <h4>Totales</h4>
            <div>Subtotal: <?php echo h($CURRENCY); ?><?php echo h($subtotal); ?></div>
            <div>Descuento: <?php echo h($CURRENCY); ?><?php echo h($descuento); ?></div>
            <div>Impuesto: <?php echo h($CURRENCY); ?><?php echo h($impuesto); ?></div>
            <div class="grand">Total: <?php echo h($CURRENCY); ?><?php echo h($total); ?></div>
            <div>Pagado: <?php echo h($CURRENCY); ?><?php echo h($pagado); ?></div>
            <div>Saldo: <?php echo h($CURRENCY); ?><?php echo h($saldo); ?></div>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th>Producto</th>
              <th class="r">Cant</th>
              <th class="r">P/U</th>
              <th class="r">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it):
              $cant = (float)($it['cantidad'] ?? 0);
              $pu = (float)($it['precio_unitario'] ?? 0);
              $tl = $cant * $pu;
            ?>
              <tr>
                <td><?php echo h($it['producto'] ?? ''); ?></td>
                <td class="r"><?php echo h((string)$cant); ?></td>
                <td class="r"><?php echo h($CURRENCY); ?><?php echo h(money($pu)); ?></td>
                <td class="r"><?php echo h($CURRENCY); ?><?php echo h(money($tl)); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="foot">
          <div><b>Gracias por su compra.</b></div>
          <div class="muted">Este documento es una copia de control interno.</div>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
</body>
</html>
<?php exit; endif; ?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ticket Venta #<?php echo (int)$nro; ?></title>
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
      <div class="title">RECIBO DE VENTA</div>
      <div class="badge">#<?php echo (int)$nro; ?></div>
    </div>

    <div class="sep"></div>

    <?php echo row_lr('Fecha', $fecha); ?>
    <?php echo row_lr('Cliente', $cliente !== '' ? $cliente : 'N/D'); ?>
    <?php if ($doc !== ''): ?><?php echo row_lr('Documento', $doc); ?><?php endif; ?>
    <?php if ($metodo !== ''): ?><?php echo row_lr('Método', $metodo); ?><?php endif; ?>

    <div class="sep"></div>

    <div class="small muted">Detalle</div>
    <div class="items">
      <?php foreach ($items as $it):
        $prod = (string)($it['producto'] ?? 'Producto');
        $qty  = (int)($it['cantidad'] ?? 0);
        $line = money($it['total_linea'] ?? 0);
        // Si tienes precio unitario en tu tabla, úsalo; si no, lo calculamos
        $unit = ($qty > 0) ? money(((float)($it['total_linea'] ?? 0)) / $qty) : money(0);
      ?>
        <div class="item">
          <div class="i-top">
            <span class="i-name"><?php echo h($prod); ?></span>
            <span class="r"><?php echo h($CURRENCY . $line); ?></span>
          </div>
          <div class="i-sub small muted">
            <span><?php echo h("Cant: $qty"); ?></span>
            <span><?php echo h("Unit: $CURRENCY$unit"); ?></span>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (!$items): ?>
        <div class="small muted">Sin items.</div>
      <?php endif; ?>
    </div>

    <div class="sep"></div>

    <div class="totals">
      <?php echo row_lr('Subtotal', $CURRENCY . $subtotal); ?>
      <?php echo row_lr('Descuento', $CURRENCY . $descuento); ?>
      <?php echo row_lr('Impuesto', $CURRENCY . $impuesto); ?>

      <div class="lr grand">
        <span class="l">TOTAL</span>
        <span class="r"><?php echo h($CURRENCY . $total); ?></span>
      </div>

      <?php echo row_lr('Pagado', $CURRENCY . $pagado); ?>
      <?php echo row_lr('Saldo',  $CURRENCY . $saldo); ?>
    </div>

    <div class="sep"></div>

    <div class="center small muted">Gracias por su compra.</div>
    <div class="center small" style="margin-top:6px;">--- FIN DEL RECIBO ---</div>

    <div class="no-print">
      <button class="btn" onclick="window.print()">Imprimir</button>
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