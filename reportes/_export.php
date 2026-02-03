<?php
declare(strict_types=1);

// Helper compartido para exportar Reportes a CSV (Excel) y PDF.

if (!function_exists('report_export_csv')) {
  /**
   * Exporta como CSV (compatible con Excel).
   * @param string $filenameSinExt  Ej: 'ventas_productos_2026-01-30'
   * @param array $headers          Ej: ['Producto','Cantidad','Total']
   * @param array $rows             Array de arrays (mismo orden que headers)
   */
  function report_export_csv(string $filenameSinExt, array $headers, array $rows): void
  {
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filenameSinExt) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'wb');
    // UTF-8 BOM para Excel
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, $headers);
    foreach ($rows as $r) {
      if (!is_array($r)) continue;
      fputcsv($out, $r);
    }
    fclose($out);
    exit;
  }
}

if (!function_exists('report_export_pdf')) {
  /**
   * Exporta un HTML a PDF usando Dompdf si está disponible.
   * Si Dompdf no está instalado, hace fallback a HTML listo para "Guardar como PDF".
   */
  function report_export_pdf(string $filenameSinExt, string $html, string $paper = 'letter'): void
  {
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filenameSinExt) . '.pdf';

    $autoload = __DIR__ . '../vendor/dompdf/autoload.inc.php';
    if (is_file($autoload)) {
      require_once $autoload;
      $options = new \Dompdf\Options();
      $options->set('isRemoteEnabled', true);
      $options->set('dpi', 96);
      $dompdf = new \Dompdf\Dompdf($options);
      $dompdf->setPaper($paper, 'portrait');
      $dompdf->loadHtml($html, 'UTF-8');
      $dompdf->render();
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="' . $filename . '"');
      echo $dompdf->output();
      exit;
    }

    // Fallback: HTML imprimible (usuario puede "Guardar como PDF")
    header('Content-Type: text/html; charset=utf-8');
    echo "<!doctype html><html lang='es'><head><meta charset='utf-8'><title>" . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . "</title>";
    echo "<style>@media print{.no-print{display:none!important} body{background:#fff}}</style>";
    echo "</head><body>";
    echo "<div class='no-print' style='margin:12px 0;display:flex;gap:8px'>";
    echo "<button onclick='window.print()' style='padding:8px 12px'>Imprimir / Guardar PDF</button>";
    echo "<span style='color:#666'>Tip: en el diálogo de impresión elige \"Guardar como PDF\".</span>";
    echo "</div>";
    echo $html;
    echo "</body></html>";
    exit;
  }
}
