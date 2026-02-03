<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); echo 'Cita no válida.'; exit; }

$q = $pdo->prepare(
  "SELECT c.*, cli.nombre, cli.apellido, cli.tipo_documento, cli.numero_documento, cli.celular, cli.email
     FROM tb_citas c
     INNER JOIN tb_clientes cli ON cli.id_cliente = c.id_cliente
     WHERE c.id_cita = :id
     LIMIT 1"
);
$q->execute([':id'=>$id]);
$cita = $q->fetch(PDO::FETCH_ASSOC);
if (!$cita) { http_response_code(404); echo 'Cita no encontrada.'; exit; }

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fmt_fecha(string $ymd): string {
  if (!$ymd) return '';
  $dt = DateTime::createFromFormat('Y-m-d', $ymd);
  return $dt ? $dt->format('d/m/Y') : $ymd;
}
function fmt_hora(?string $t): string {
  $t = (string)$t;
  return $t ? substr($t, 0, 5) : '';
}
function line(?string $label, ?string $value): string {
  $label = trim((string)$label);
  $value = trim((string)$value);
  if ($value === '') return '';
  return "<div class='row'><span class='k'>".h($label)."</span><span class='v'>".h($value)."</span></div>";
}

$optica = function_exists('optica_info') ? optica_info() : [];
$optica_nombre = (string)($optica['nombre'] ?? 'SOV - Citas');
$optica_tel    = (string)($optica['telefono'] ?? '');
$optica_dir    = (string)($optica['direccion'] ?? '');
$optica_ruc    = (string)($optica['ruc'] ?? '');

$cliente = trim(($cita['nombre'] ?? '') . ' ' . ($cita['apellido'] ?? ''));
$doc     = trim(($cita['tipo_documento'] ?? '') . ' ' . ($cita['numero_documento'] ?? ''));
$cel     = (string)($cita['celular'] ?? '');
$email   = (string)($cita['email'] ?? '');

$fecha   = fmt_fecha((string)($cita['fecha'] ?? ''));
$hi      = fmt_hora($cita['hora_inicio'] ?? '');
$hf      = fmt_hora($cita['hora_fin'] ?? '');
$estado  = strtoupper((string)($cita['estado'] ?? 'PROGRAMADA'));
$motivo  = trim((string)($cita['motivo'] ?? ''));

// Cambia a 58 para papel 58mm (común en Epson TM-T20/TM-T88 con rollo angosto)
$PAPER_MM = 80; // 80 o 58

// (Opcional) URL para QR (si luego quieres)
// $qrUrl = $URL . '/citas/ver.php?id=' . $id;
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ticket Cita #<?php echo (int)$id; ?></title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $URL; ?>/public/images/optica/icon_bajo.png">
  <link rel="apple-touch-icon" href="<?php echo $URL; ?>/public/images/optica/icon_alto.png">
  <style>
    :root{
      --paper-mm: <?php echo (int)$PAPER_MM; ?>mm;
      --font: 12px;
      --lh: 1.25;
    }
    html,body{ margin:0; padding:0; background:#fff; }
    body{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-size: var(--font);
      line-height: var(--lh);
      color:#000;
    }
    .ticket{
      width: var(--paper-mm);
      padding: 6mm 4mm;
      box-sizing: border-box;
      margin: 0 auto;
    }
    .center{ text-align:center; }
    .muted{ opacity:.85; }
    .sep{
      margin: 8px 0;
      border-top: 1px dashed #000;
      height: 0;
    }
    .title{ font-weight:700; font-size: 14px; letter-spacing:.2px; }
    .small{ font-size: 11px; }
    .row{
      display:flex;
      justify-content:space-between;
      gap: 8px;
    }
    .k{ flex: 0 0 auto; font-weight:700; }
    .v{ flex: 1 1 auto; text-align:right; }
    .wrap{ white-space: pre-wrap; word-break: break-word; }
    .badge{
      display:inline-block;
      padding: 2px 6px;
      border: 1px solid #000;
      border-radius: 6px;
      font-weight:700;
      font-size: 11px;
      margin-top: 4px;
    }
    .actions{ margin: 10px 0 0; display:flex; gap:8px; justify-content:center; }
    .btn{
      border: 1px solid #111;
      background:#fff;
      padding: 6px 10px;
      font: inherit;
      cursor:pointer;
    }
    @media print{
      .no-print{ display:none !important; }
      .ticket{ padding: 0; width: 100%; }
      @page{
        /* Muchos drivers ignoran size, pero ayuda en algunos */
        size: auto;
        margin: 0;
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
      <div class="title">COMPROBANTE DE CITA</div>
      <div class="badge">#<?php echo (int)$id; ?> • <?php echo h($estado); ?></div>
    </div>

    <div class="sep"></div>

    <?php echo line('Cliente', $cliente); ?>
    <?php echo line('Doc', $doc); ?>
    <?php echo line('Cel', $cel); ?>
    <?php echo line('Email', $email); ?>

    <div class="sep"></div>

    <?php echo line('Fecha', $fecha); ?>
    <?php echo line('Hora', trim($hi . ($hf ? ' - ' . $hf : ''))); ?>

    <?php if ($motivo !== ''): ?>
      <div class="sep"></div>
      <div class="k">Motivo:</div>
      <div class="wrap"><?php echo h($motivo); ?></div>
    <?php endif; ?>

    <div class="sep"></div>

    <div class="row">
      <span class="k">Emitido</span>
      <span class="v"><?php echo h(date('d/m/Y H:i')); ?></span>
    </div>

    <div class="center muted small" style="margin-top:8px;">
      Gracias por su preferencia
    </div>

    <div class="center small" style="margin-top:6px;">
      --- FIN DEL TICKET ---
    </div>

    <div class="no-print actions">
      <button class="btn" onclick="window.print()">Imprimir</button>
      <button class="btn" onclick="window.close()">Cerrar</button>
    </div>
  </div>

  <script class="no-print">
    // Opcional: auto-imprimir al abrir y cerrar luego
    // Descomenta si quieres flujo "click imprimir desde calendario"
   // window.addEventListener('load', () => {
    //setTimeout(() => window.print(), 250);
   // window.addEventListener('afterprint', () => setTimeout(() => window.close(), 250));
   // });
  </script>
</body>
</html>
