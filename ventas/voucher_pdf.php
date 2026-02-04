<?php
/**
 * Voucher/Recibo de Venta en PDF.
 *
 * - Si Dompdf está instalado (vendor/autoload.php), genera PDF real.
 * - Si no, muestra el voucher HTML (imprimible) para que el usuario pueda
 *   "Guardar como PDF" desde el navegador.
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
$autoload = __DIR__ . '/../vendor/autoload.php';
$hasDompdf = is_file($autoload);
if ($hasDompdf) {
  require_once $autoload;
  $hasDompdf = class_exists('Dompdf\\Dompdf');
}

if (!$hasDompdf) {
  // Fallback: render HTML imprimible
  // Reutilizamos el voucher HTML actual y pedimos al navegador imprimir/guardar PDF.
  $_GET['id'] = $id; // por si el include lo lee
  include __DIR__ . '/voucher.php';
  echo "\n<script>try{setTimeout(()=>window.print(),250);}catch(e){}</script>";
  exit;
}

$formato = strtolower(trim((string)($_GET['formato'] ?? 'ticket')));

use Dompdf\Dompdf;
use Dompdf\Options;

// Obtener HTML del voucher existente
ob_start();
$_GET['id'] = $id;
include __DIR__ . '/voucher.php';
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('dpi', 72);

$dompdf = new Dompdf($options);
// Papel según formato
if ($formato === 'carta3') {
  $dompdf->setPaper('letter', 'portrait');
} else {
  // 80mm: 226.77pt. Para 58mm usa 164.41pt.
  $paperWidthPt = 226.77;
  $paperHeightPt = 1600;
  $dompdf->setPaper([0, 0, $paperWidthPt, $paperHeightPt]);
}
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="ticket_venta_'.$id.'.pdf"');
echo $dompdf->output();
