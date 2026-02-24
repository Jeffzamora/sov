<?php
declare(strict_types=1);

/**
 * Helpers compartidos para exportar Reportes a:
 * - CSV (compatible con Excel)
 * - Excel (XLS vía HTML)
 * - PDF (Dompdf sin Composer: vendor/dompdf)
 *
 * Notas importantes:
 * - Este archivo NO debe incluir layout (parte1/parte2) para evitar "headers already sent".
 * - Para PDF con imágenes (logo PNG/JPG), PHP requiere la extensión GD.
 *   Si GD no está disponible o si "print.show_logo" está desactivado, se omiten imágenes.
 */

if (!function_exists('report_no_cache_headers')) {
  function report_no_cache_headers(): void
  {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
  }
}

if (!function_exists('report_safe_filename')) {
  function report_safe_filename(string $name): string
  {
    $name = trim($name);
    if ($name === '') $name = 'reporte';
    return preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name);
  }
}

if (!function_exists('report_csv_delimiter')) {
  function report_csv_delimiter(): string
  {
    $d = (string)(getenv('APP_CSV_DELIMITER') ?: '');
    if ($d === ';' || $d === ',') return $d;
    return ',';
  }
}

if (!function_exists('report_export_csv')) {
  function report_export_csv(string $filenameSinExt, array $headers, array $rows): void
  {
    $filename = report_safe_filename($filenameSinExt) . '.csv';

    while (ob_get_level() > 0) { @ob_end_clean(); }
    report_no_cache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'wb');
    if ($out === false) {
      http_response_code(500);
      echo 'No se pudo abrir el stream de salida.';
      exit;
    }

    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8
    $delim = report_csv_delimiter();
    fputcsv($out, $headers, $delim);

    foreach ($rows as $r) {
      if (!is_array($r)) continue;
      $line = [];
      foreach ($r as $v) {
        if (is_bool($v)) $v = $v ? '1' : '0';
        elseif ($v === null) $v = '';
        elseif (is_array($v) || is_object($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
        $line[] = (string)$v;
      }
      fputcsv($out, $line, $delim);
    }

    fclose($out);
    exit;
  }
}

if (!function_exists('report_export_excel')) {
  function report_export_excel(string $filenameSinExt, array $headers, array $rows, string $title = ''): void
  {
    $filename = report_safe_filename($filenameSinExt) . '.xls';

    while (ob_get_level() > 0) { @ob_end_clean(); }
    report_no_cache_headers();
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $esc = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    echo "<!doctype html><html lang='es'><head><meta charset='utf-8'>";
    echo "<style>
      body{font-family:Arial,Helvetica,sans-serif}
      table{border-collapse:collapse;font-size:11pt}
      th,td{border:1px solid #d0d0d0;padding:6px;vertical-align:top}
      th{background:#f2f2f2;font-weight:bold}
      .num{mso-number-format:'0.00';text-align:right}
      .int{mso-number-format:'0';text-align:right}
      .txt{mso-number-format:'\\@'}
    </style></head><body>";

    if ($title !== '') {
      echo "<h2 style='margin:0 0 10px 0'>" . $esc($title) . "</h2>";
    }

    echo "<table><thead><tr>";
    foreach ($headers as $h) echo '<th>' . $esc($h) . '</th>';
    echo "</tr></thead><tbody>";

    foreach ($rows as $r) {
      if (!is_array($r)) continue;
      echo "<tr>";
      foreach (array_values($r) as $v) {
        $class = 'txt';
        if (is_int($v)) $class = 'int';
        elseif (is_float($v)) $class = 'num';
        elseif (is_numeric($v) && preg_match('/^\d+$/', (string)$v)) $class = 'int';
        echo '<td class="' . $class . '">' . $esc($v) . '</td>';
      }
      echo "</tr>";
    }

    echo "</tbody></table></body></html>";
    exit;
  }
}

if (!function_exists('report_export_pdf')) {
  function report_export_pdf(string $filenameSinExt, string $html, string $paper = 'letter', string $orientation = 'portrait'): void
  {
    $filename = report_safe_filename($filenameSinExt) . '.pdf';

    $optica = function_exists('optica_info') ? (array)optica_info() : [];
    $opticaNombre = (string)($optica['nombre'] ?? '');
    $opticaTel = (string)($optica['telefono'] ?? '');
    $opticaLogo = (string)($optica['logo'] ?? '');

    $showLogoSetting = function_exists('setting') ? (bool)setting('print.show_logo', true) : true;
    $gdOk = extension_loaded('gd');
    $canShowLogo = $showLogoSetting && $gdOk;

    $wrapped = "<!doctype html><html lang='es'><head><meta charset='utf-8'>";
    $wrapped .= "<style>
      body{font-family:DejaVu Sans, Arial, Helvetica, sans-serif;font-size:12px;color:#111}
      .hdr{width:100%;margin:0 0 10px 0;padding:0 0 8px 0;border-bottom:1px solid #d0d0d0}
      .hdr .row{display:table;width:100%}
      .hdr .c{display:table-cell;vertical-align:middle}
      .muted{color:#666}
      table{border-collapse:collapse;width:100%}
      th,td{padding:6px;border-bottom:1px solid #e6e6e6}
      th{border-bottom:1px solid #333;text-align:left}
    </style></head><body>";

    if ($opticaNombre !== '' || $opticaLogo !== '' || $opticaTel !== '') {
      $wrapped .= "<div class='hdr'><div class='row'>";
      $wrapped .= "<div class='c' style='width:60px'>";
      if ($opticaLogo !== '' && $canShowLogo) {
        $wrapped .= "<img src='" . htmlspecialchars($opticaLogo, ENT_QUOTES, 'UTF-8') . "' style='height:48px'>";
      }
      $wrapped .= "</div>";
      $wrapped .= "<div class='c'>";
      if ($opticaNombre !== '') {
        $wrapped .= "<div style='font-size:16px;font-weight:bold'>" . htmlspecialchars($opticaNombre, ENT_QUOTES, 'UTF-8') . "</div>";
      }
      if ($opticaTel !== '') {
        $wrapped .= "<div class='muted'>Tel: " . htmlspecialchars($opticaTel, ENT_QUOTES, 'UTF-8') . "</div>";
      }
      if (!$gdOk && $showLogoSetting && $opticaLogo !== '') {
        $wrapped .= "<div class='muted' style='font-size:10px'>Nota: logo omitido (PHP GD no instalado)</div>";
      }
      $wrapped .= "</div>";
      $wrapped .= "<div class='c' style='text-align:right'>";
      $wrapped .= "<div class='muted'>Generado: " . htmlspecialchars(date('Y-m-d H:i'), ENT_QUOTES, 'UTF-8') . "</div>";
      $wrapped .= "</div></div></div>";
    }

    $wrapped .= $html;
    $wrapped .= "</body></html>";

    // Dompdf sin Composer
    $autoload = __DIR__ . '/../vendor/dompdf/autoload.inc.php';
    $hasDompdf = false;
    if (is_file($autoload)) {
      require_once $autoload;
      $hasDompdf = class_exists('\Dompdf\Dompdf');
    }

    if ($hasDompdf) {
      try {
        if (class_exists('\Dompdf\Options')) {
          $options = new \Dompdf\Options();
          $options->set('isRemoteEnabled', true);
          $options->set('dpi', 96);
          $dompdf = new \Dompdf\Dompdf($options);
        } else {
          $dompdf = new \Dompdf\Dompdf();
          if (method_exists($dompdf, 'set_option')) {
            $dompdf->set_option('isRemoteEnabled', true);
            $dompdf->set_option('dpi', 96);
          }
        }

        $dompdf->setPaper($paper, $orientation);
        $dompdf->loadHtml($wrapped, 'UTF-8');
        $dompdf->render();

        try {
          $canvas = $dompdf->getCanvas();
          $font = $dompdf->getFontMetrics()->get_font('Helvetica', 'normal');
          $canvas->page_text(520, 815, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 9, [0, 0, 0]);
        } catch (Throwable $e) {
          // silencioso
        }

        while (ob_get_level() > 0) { @ob_end_clean(); }
        report_no_cache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $dompdf->output();
        exit;
      } catch (Throwable $e) {
        // fall-through a HTML
      }
    }

    // Fallback: HTML imprimible
    while (ob_get_level() > 0) { @ob_end_clean(); }
    report_no_cache_headers();
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!doctype html><html lang='es'><head><meta charset='utf-8'><title>" . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . "</title>";
    echo "<style>@media print{.no-print{display:none!important} body{background:#fff}}</style>";
    echo "</head><body>";
    echo "<div class='no-print' style='margin:12px 0;display:flex;gap:8px;align-items:center;flex-wrap:wrap'>";
    echo "<button onclick='window.print()' style='padding:8px 12px'>Imprimir / Guardar PDF</button>";
    echo "<span style='color:#666'>Tip: en el diálogo de impresión elige \"Guardar como PDF\".</span>";
    echo "</div>";
    echo $wrapped;
    echo "</body></html>";
    exit;
  }
}
