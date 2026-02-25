<?php
/**
 * Voucher de Abono en PDF.
 *
 * - Si Dompdf está instalado (vendor/autoload.php), genera PDF real.
 * - Si no, muestra el voucher HTML imprimible (Guardar como PDF).
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'ventas.pagos', $URL . '/');

$id_pago = (int)($_GET['id_pago'] ?? 0);
if ($id_pago <= 0) {
  http_response_code(404);
  echo "Pago inválido";
  exit;
}

$autoload = __DIR__ . '../vendor/dompdf/autoload.inc.php';
$hasDompdf = is_file($autoload);
if ($hasDompdf) {
  require_once $autoload;
  $hasDompdf = class_exists('Dompdf\\Dompdf');
}

if (!$hasDompdf) {
  $_GET['id_pago'] = $id_pago;
  include __DIR__ . '/abono_voucher.php';
  echo "\n<script>try{setTimeout(()=>window.print(),250);}catch(e){}</script>";
  exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

ob_start();
$_GET['id_pago'] = $id_pago;
include __DIR__ . '/abono_voucher.php';
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('dpi', 72);

$dompdf = new Dompdf($options);
$paperWidthPt = 226.77; // 80mm
$paperHeightPt = 1400;
$dompdf->setPaper([0, 0, $paperWidthPt, $paperHeightPt]);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="abono_'.$id_pago.'.pdf"');
echo $dompdf->output();
