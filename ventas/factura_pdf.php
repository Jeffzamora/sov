<?php
/**
 * FACTURA de Venta en PDF.
 *
 * - Si Dompdf está instalado (vendor/autoload.php), genera PDF real.
 * - Si no, muestra la factura HTML (imprimible) para que el usuario pueda
 *   "Guardar como PDF" desde el navegador.
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'ventas.imprimir', $URL . '/');

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
  // Fallback: render HTML imprimible
  $_GET['id'] = $id;
  include __DIR__ . '/factura.php';
  echo "\n<script>try{setTimeout(()=>window.print(),250);}catch(e){}</script>";
  exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

ob_start();
$_GET['id'] = $id;
include __DIR__ . '/factura.php';
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('dpi', 96);

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="factura_venta_' . $id . '.pdf"');
echo $dompdf->output();
