<?php
/**
 * Histórico de Pagos (Abonos) en PDF.
 *
 * - Si Dompdf está instalado (vendor/autoload.php), genera PDF real.
 * - Si no, muestra el HTML imprimible para que el usuario pueda "Guardar como PDF".
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  echo "Venta inválida";
  exit;
}

// Intentar Dompdf si existe
$autoload = __DIR__ . '../vendor/dompdf/autoload.inc.php';
$hasDompdf = is_file($autoload);
if ($hasDompdf) {
  require_once $autoload;
  $hasDompdf = class_exists('Dompdf\\Dompdf');
}

if (!$hasDompdf) {
  $_GET['id'] = $id;
  include __DIR__ . '/pagos_historico.php';
  echo "\n<script>try{setTimeout(()=>window.print(),250);}catch(e){}</script>";
  exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

ob_start();
$_GET['id'] = $id;
include __DIR__ . '/pagos_historico.php';
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('dpi', 96);

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="pagos_venta_' . $id . '.pdf"');
echo $dompdf->output();
