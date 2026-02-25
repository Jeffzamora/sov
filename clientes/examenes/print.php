<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'examenes.ver', $URL . '/');

$id_examen = isset($_GET['id_examen']) ? (int)$_GET['id_examen'] : 0;

$q = $pdo->prepare("
  SELECT e.*, c.nombre, c.apellido, c.celular, c.numero_documento
  FROM tb_examenes_optometricos e
  INNER JOIN tb_clientes c ON c.id_cliente = e.id_cliente
  WHERE e.id_examen = :id
  LIMIT 1
");
$q->execute([':id' => $id_examen]);
$e = $q->fetch(PDO::FETCH_ASSOC);

if (!$e) {
  die('Examen no encontrado');
}

function h($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$optica = optica_info();
$optica_nombre = $optica['nombre'] ?? '';
$optica_logo = $optica['logo'] ?? '';
$optica_tel = $optica['telefono'] ?? '';
$optica_dir = $optica['direccion'] ?? '';
$optica_ruc = $optica['ruc'] ?? '';
$optica_web = $optica['web'] ?? '';
$verif_code = strtoupper(substr(hash('sha256', $id_examen . '|' . ($e['id_cliente'] ?? '') . '|' . ($e['fecha_examen'] ?? '')), 0, 10));
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Impresión Examen</title> <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $URL; ?>/public/images/optica/icon_bajo.png">
  <link rel="apple-touch-icon" href="<?php echo $URL; ?>/public/images/optica/icon_alto.png">
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/plugins/fontawesome-free/css/all.min.css">
  <style>
    @page {
      size: letter;
      margin: 0.4in;
    }

    body {
      font-family: Arial, sans-serif;
      margin: 0;
    }

    .page {
      height: 10.2in;
      max-width: 8.5in;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 0.15in;
      box-sizing: border-box;
    }

    .ticket {
      flex: 1 1 0;
      border: 1px solid #111;
      padding: 0.18in;
      box-sizing: border-box;
      position: relative;
      overflow: hidden;

      /* Fondo sutil que ya tienes */
      background: repeating-linear-gradient(135deg,
          rgba(0, 0, 0, 0.02),
          rgba(0, 0, 0, 0.02) 6px,
          rgba(0, 0, 0, 0) 6px,
          rgba(0, 0, 0, 0) 12px);
    }

    /* ✅ Marca de agua (logo) */
    .ticket.has-watermark::before {
      content: "";
      position: absolute;
      inset: 0;
      background-image: var(--wm-url);
      background-repeat: no-repeat;
      background-position: center;
      background-size: 70%;
      opacity: 0.06;
      /* <- ajusta 0.03 a 0.10 según te guste */
      pointer-events: none;
      z-index: 0;
      filter: grayscale(100%);
      /* opcional: lo hace más “watermark” */
    }

    /* Asegura que el contenido esté por encima del watermark */
    .ticket>* {
      position: relative;
      z-index: 1;
    }

    .cutline {
      height: 0;
      border-top: 1px dashed #666;
      margin: -0.05in 0;
    }

    .hdr {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 8px;
    }

    .hdr .brand {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .hdr img {
      height: 34px;
      max-width: 160px;
      object-fit: contain;
    }

    .title {
      font-size: 14px;
      font-weight: 700;
    }

    .muted {
      color: #444;
      font-size: 11px;
    }

    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 11px;
    }

    th,
    td {
      border: 1px solid #111;
      padding: 5px;
      text-align: center;
    }

    th {
      font-weight: 700;
    }

    .meta {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin: 6px 0 8px;
      font-size: 11px;
    }

    .meta div {
      white-space: nowrap;
    }

    .notes {
      font-size: 10px;
      margin-top: 6px;
    }

    .notes .box {
      border: 1px solid #111;
      padding: 6px;
      min-height: 34px;
    }

    .footer {
      position: absolute;
      bottom: 0.18in;
      left: 0.18in;
      right: 0.18in;
      display: flex;
      justify-content: space-between;
      font-size: 10px;
      color: #333;
    }

    .label-copy {
      position: absolute;
      top: 0.18in;
      right: 0.18in;
      font-size: 10px;
      padding: 2px 6px;
      border: 1px solid #111;
      background: #fff;
      z-index: 2;
      /* sobre watermark */
    }

    @media print {
      .no-print {
        display: none !important;
      }

      body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>

<body>
  <div class="no-print" style="padding:10px;">
    <button onclick="window.print()" style="padding:8px 12px;">Imprimir</button>
    <button onclick="window.close()" style="padding:8px 12px;">Cerrar</button>
  </div>

  <div class="page">

    <?php
    $copies = ['ORIGINAL', 'COPIA', 'ARCHIVO'];

    // ✅ Armamos la URL del logo como CSS var
    // Debe ser una URL accesible por el navegador (http/https o ruta pública).
    $wm = '';
    if (!empty($optica_logo)) {
      $wm = (strpos($optica_logo, 'http') === 0) ? $optica_logo : ($URL . '/' . ltrim($optica_logo, '/'));
      $wm = $optica_logo;
    }

    // css var: url("...")
    $wmCss = $wm ? 'url("' . h($wm) . '")' : 'none';

    foreach ($copies as $idx => $label):
    ?>
      <div class="ticket <?php echo $wm ? 'has-watermark' : ''; ?>"
        style="--wm-url: <?php echo $wmCss; ?>;">
        <div class="label-copy"><?php echo h($label); ?></div>


        <div class="hdr">
          <div class="brand">
            <?php if (!empty($optica_logo)) { ?>
              <img src="<?php echo h($optica_logo); ?>" alt="Logo">
            <?php } ?>
            <div>
              <div class="title"><?php echo h($optica_nombre); ?></div>
              <div class="muted">
                <?php if ($optica_dir) {
                  echo h($optica_dir) . ' · ';
                } ?>
                <?php if ($optica_tel) {
                  echo h($optica_tel) . ' · ';
                } ?>
                <?php if ($optica_ruc) {
                  echo h($optica_ruc) . ' · ';
                } ?>
                <?php echo h($optica_web); ?>
              </div>
            </div>
          </div>
          <div class="muted" style="text-align:right;">
            <div><strong>Examen</strong> #<?php echo (int)$e['id_examen']; ?></div>
            <div>Fecha: <?php echo h($e['fecha_examen']); ?></div>
            <div>Código: <strong><?php echo h($verif_code); ?></strong></div>
          </div>
        </div>

        <div class="meta">
          <div><strong>Cliente:</strong> <?php echo h(($e['nombre'] ?? '') . ' ' . ($e['apellido'] ?? '')); ?></div>
          <?php if (!empty($e['numero_documento'])): ?><div><strong>Doc:</strong> <?php echo h($e['numero_documento']); ?></div><?php endif; ?>
          <?php if (!empty($e['celular'])): ?><div><strong>Cel:</strong> <?php echo h($e['celular']); ?></div><?php endif; ?>
        </div>

        <div class="grid">
          <div>
            <table>
              <thead>
                <tr>
                  <th colspan="4">OD (Derecho)</th>
                </tr>
                <tr>
                  <th>SPH</th>
                  <th>CYL</th>
                  <th>AX</th>
                  <th>ADD</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><?php echo h($e['od_esfera']); ?></td>
                  <td><?php echo h($e['od_cilindro']); ?></td>
                  <td><?php echo h($e['od_eje']); ?></td>
                  <td><?php echo h($e['od_add']); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div>
            <table>
              <thead>
                <tr>
                  <th colspan="4">OI (Izquierdo)</th>
                </tr>
                <tr>
                  <th>SPH</th>
                  <th>CYL</th>
                  <th>AX</th>
                  <th>ADD</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><?php echo h($e['oi_esfera']); ?></td>
                  <td><?php echo h($e['oi_cilindro']); ?></td>
                  <td><?php echo h($e['oi_eje']); ?></td>
                  <td><?php echo h($e['oi_add']); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="notes">
          <div class="muted"><strong>Notas del optometrista</strong></div>
          <div class="box"><?php echo nl2br(h($e['notas_optometrista'] ?? '')); ?></div>
        </div>

        <div style="display:flex; gap:12px; margin-top:8px;">
          <div style="flex:1;">
            <div class="muted">Firma / Sello</div>
            <div style="border-bottom:1px solid #111; height:22px;"></div>
          </div>
          <div style="flex:1;">
            <div class="muted">Paciente</div>
            <div style="border-bottom:1px solid #111; height:22px;"></div>
          </div>
        </div>

        <div class="footer">
          <div>Generado por SOV Óptica</div>
          <div>Verificación: <?php echo h($verif_code); ?></div>
        </div>
      </div>

      <?php if ($idx < 2): ?>
        <div class="cutline"></div>
      <?php endif; ?>

    <?php endforeach; ?>

  </div>

</body>

</html>