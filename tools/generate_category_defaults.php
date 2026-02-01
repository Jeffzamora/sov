<?php
/**
 * Genera imágenes por defecto por categoría:
 * - Crea /almacen/img_productos/defaults/cat_<id>.png
 * - También genera thumbnails espejo en /almacen/img_productos/thumbs/defaults/
 *
 * Uso:
 *   php tools/generate_category_defaults.php
 *
 * Requiere:
 * - GD habilitado (extension=gd)
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';

function fail(string $msg, int $code = 1): void {
  fwrite(STDERR, $msg . PHP_EOL);
  exit($code);
}

if (!extension_loaded('gd')) {
  fail("GD no está habilitado. Activa la extensión GD en PHP.");
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
  fail("No se encontró una instancia PDO en config.php (\$pdo).");
}

if (!function_exists('product_img_abs')) {
  fail("Falta la función product_img_abs(). Verifica helpers de imágenes.");
}
if (!function_exists('product_thumb_rel')) {
  fail("Falta la función product_thumb_rel(). Verifica helpers de thumbnails.");
}
if (!function_exists('create_thumbnail')) {
  fail("Falta la función create_thumbnail(). Verifica helpers de thumbnails.");
}

function ensure_dir(string $dir): void {
  if (is_dir($dir)) return;
  if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
    fail("No se pudo crear el directorio: {$dir}");
  }
}

/**
 * Uppercase compatible con tildes/ñ si mbstring está disponible.
 */
function upper(string $s): string {
  $s = trim($s);
  if ($s === '') return '';
  if (function_exists('mb_strtoupper')) return mb_strtoupper($s, 'UTF-8');
  return strtoupper($s);
}

/**
 * Word wrap simple para imagen sin TTF.
 * - evita líneas vacías
 * - si una palabra supera el límite, la corta
 */
function wrap_lines(string $text, int $maxLen = 16): array {
  $text = preg_replace('/\s+/', ' ', trim($text ?? ''));
  if ($text === '') return [];

  $words = explode(' ', $text);
  $lines = [];
  $line = '';

  foreach ($words as $word) {
    $word = trim($word);
    if ($word === '') continue;

    // Si una palabra es más larga que maxLen, córtala en partes
    while (strlen($word) > $maxLen) {
      $chunk = substr($word, 0, $maxLen);
      $word = substr($word, $maxLen);
      if ($line !== '') { $lines[] = $line; $line = ''; }
      $lines[] = $chunk;
    }

    $test = $line === '' ? $word : ($line . ' ' . $word);
    if (strlen($test) <= $maxLen) {
      $line = $test;
    } else {
      if ($line !== '') $lines[] = $line;
      $line = $word;
    }
  }

  if ($line !== '') $lines[] = $line;
  return $lines;
}

/**
 * Genera PNG (640x640) con estilo simple.
 * Sin depender de fuentes TTF.
 */
function make_png(string $path, string $title): void {
  $w = 640; $h = 640;
  $img = imagecreatetruecolor($w, $h);
  if (!$img) fail("No se pudo crear imagen en memoria.");

  // Colores
  $bg1 = imagecolorallocate($img, 247, 249, 252);
  $bg2 = imagecolorallocate($img, 238, 243, 251);
  $fg  = imagecolorallocate($img, 45, 55, 72);
  $bd  = imagecolorallocate($img, 200, 208, 222);
  $ac  = imagecolorallocate($img, 29, 54, 182);

  // Fondo con gradiente vertical (simple)
  for ($y = 0; $y < $h; $y++) {
    $t = $y / ($h - 1);
    $r = (int)((1 - $t) * 247 + $t * 238);
    $g = (int)((1 - $t) * 249 + $t * 243);
    $b = (int)((1 - $t) * 252 + $t * 251);
    $c = imagecolorallocate($img, $r, $g, $b);
    imageline($img, 0, $y, $w, $y, $c);
  }

  // Marco
  imagesetthickness($img, 6);
  imagerectangle($img, 12, 12, $w - 12, $h - 12, $bd);

  // Ícono simple (una “etiqueta”)
  // rect principal
  imagefilledrectangle($img, 80, 90, $w - 80, 220, imagecolorallocate($img, 255, 255, 255));
  imagerectangle($img, 80, 90, $w - 80, 220, $bd);
  // círculo agujero
  imagefilledellipse($img, 120, 155, 26, 26, $bg2);
  imageellipse($img, 120, 155, 26, 26, $bd);
  // línea acento
  imagefilledrectangle($img, 80, 90, $w - 80, 104, $ac);

  // Texto
  $title = upper($title);
  if ($title === '') $title = 'CATEGORIA';

  $lines = wrap_lines($title, 18);
  if (!$lines) $lines = ['CATEGORIA'];

  $font = 5; // built-in font
  $lineHeight = 26;

  $totalH = count($lines) * $lineHeight;
  $yStart = (int)(($h - $totalH) / 2) + 80;

  $y = $yStart;
  foreach ($lines as $ln) {
    $tw = imagefontwidth($font) * strlen($ln);
    $x = (int)(($w - $tw) / 2);
    imagestring($img, $font, $x, $y, $ln, $fg);
    $y += $lineHeight;
  }

  // Guardar
  $dir = dirname($path);
  ensure_dir($dir);

  if (!imagepng($img, $path, 6)) {
    imagedestroy($img);
    fail("No se pudo escribir PNG: {$path}");
  }
  imagedestroy($img);
}

try {
  $cats = $pdo->query("SELECT id_categoria, nombre_categoria FROM tb_categorias ORDER BY id_categoria")
              ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  fail("Error consultando categorías: " . $e->getMessage());
}

if (!$cats) {
  echo "No hay categorías.\n";
  exit(0);
}

$defaultsDir = product_img_abs('defaults');
$thumbDefaultsDir = product_img_abs('thumbs/defaults');
ensure_dir($defaultsDir);
ensure_dir($thumbDefaultsDir);

$created = 0;
$skipped = 0;
$thumbs  = 0;

foreach ($cats as $c) {
  $id = (int)($c['id_categoria'] ?? 0);
  $name = (string)($c['nombre_categoria'] ?? '');

  if ($id <= 0) {
    echo "Saltando categoría con id inválido.\n";
    continue;
  }

  $rel = 'defaults/cat_' . $id . '.png';
  $abs = product_img_abs($rel);

  if (!is_file($abs)) {
    make_png($abs, $name);
    echo "Creada: {$rel}\n";
    $created++;
  } else {
    echo "Existe: {$rel}\n";
    $skipped++;
  }

  // Thumbnail espejo
  $thumbRel = product_thumb_rel($rel);
  $thumbAbs = product_img_abs($thumbRel);

  try {
    ensure_dir(dirname($thumbAbs));
    if (create_thumbnail($abs, $thumbAbs)) {
      $thumbs++;
    } else {
      echo "Advertencia: no se pudo crear thumbnail para {$rel}\n";
    }
  } catch (Throwable $e) {
    echo "Advertencia thumbnail {$rel}: " . $e->getMessage() . "\n";
  }
}

echo "Listo. Creadas={$created}, Existentes={$skipped}, Thumbs={$thumbs}\n";
