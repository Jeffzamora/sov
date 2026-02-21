<?php

/**
 * Configuración central (DB + URL base + utilidades).
 *
 * Objetivo:
 * - Evitar credenciales hardcodeadas.
 * - Tener una URL base consistente tanto en local como en producción.
 * - Forzar PDO seguro (errores por excepción, prepared statements reales, utf8mb4).
 *
 * Rutas esperadas:
 * - Local:  http://localhost/sov
 * - Prod:   https://devzamora.com/sov
 */

/**
 * Carga un archivo .env muy simple (KEY=VALUE).
 * - No requiere librerías externas.
 * - Ignora líneas vacías y comentarios (#).
 */
function sov_load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $k = trim(substr($line, 0, $pos));
        $v = trim(substr($line, $pos + 1));
        // quitar comillas simples/dobles si existen
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        if ($k !== '' && getenv($k) === false) {
            putenv($k . '=' . $v);
            $_ENV[$k] = $v;
        }
    }
}

sov_load_env(__DIR__ . '/.env'); // opcional: crea app/.env en prod y local

// --- DB ---
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sistemadeventas');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// --- App ---
// Zona horaria del sistema (Nicaragua por defecto)
define('APP_TZ', getenv('APP_TZ') ?: 'America/Managua');
// Ajusta solo si instalas en otra carpeta. Por defecto: /sov
define('APP_BASE_PATH', getenv('APP_BASE_PATH') ?: '/sov');
// WhatsApp country code (solo dígitos). Ej: 505 Nicaragua, 1 USA
define('APP_WHATSAPP_CC', preg_replace('/\D+/', '', (getenv('APP_WHATSAPP_CC') ?: '505')));


date_default_timezone_set(APP_TZ);
$fechaHora = date('Y-m-d H:i:s');

// Construye URL base:
// - Si APP_URL está definido en env, úsalo.
// - Si no, dedúcelo de la request actual.
$APP_URL = getenv('APP_URL');
if (!$APP_URL) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $APP_URL = $scheme . '://' . $host;
}
// Normaliza: sin slash final y con base path
$APP_URL = rtrim($APP_URL, '/');
$URL = $APP_URL . APP_BASE_PATH;

// --- PDO ---
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Alinea la sesión MySQL con Managua (UTC-06:00) para NOW()/CURRENT_TIMESTAMP
    // Nota: Nicaragua no usa DST, así que el offset es estable.
    $pdo->exec("SET time_zone = '-06:00'");
} catch (PDOException $e) {
    // No expongas detalles de la DB al usuario final.
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(500);
    echo "Error al conectar a la base de datos";
    exit;
}


/**
 * =========================
 * Seguridad (Prioridad 2)
 * =========================
 * - CSRF tokens
 * - Validación de inputs (server-side)
 * - Enforce POST en acciones sensibles
 * - Subida de imágenes segura (MIME real, tamaño, extensión)
 */

if (!function_exists('is_https')) {
    function is_https(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    }
}

if (!function_exists('ensure_session')) {
    function ensure_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        $secure = is_https();
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => APP_BASE_PATH ?: '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        ensure_session();
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $t . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): void
    {
        ensure_session();
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'POST') return; // solo aplica a POST
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $ok = is_string($sent) && is_string($_SESSION['_csrf'] ?? null) && hash_equals($_SESSION['_csrf'], $sent);
        if (!$ok) {
            http_response_code(403);
            echo "Acceso denegado (CSRF).";
            exit;
        }
    }
}

if (!function_exists('require_post')) {
    function require_post(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'POST') {
            http_response_code(405);
            echo "Método no permitido.";
            exit;
        }
    }
}

if (!function_exists('input_str')) {
    function input_str(string $key, int $maxLen = 255, bool $required = true, string $source = 'POST'): string
    {
        $arr = strtoupper($source) === 'GET' ? $_GET : $_POST;
        $v = $arr[$key] ?? '';
        if (!is_string($v)) $v = '';
        $v = trim($v);
        if ($required && $v === '') {
            http_response_code(422);
            echo "Campo requerido: {$key}";
            exit;
        }
        if (mb_strlen($v) > $maxLen) {
            http_response_code(422);
            echo "Campo demasiado largo: {$key}";
            exit;
        }
        return $v;
    }
}

if (!function_exists('input_int')) {
    function input_int(string $key, bool $required = true, string $source = 'POST'): int
    {
        $arr = strtoupper($source) === 'GET' ? $_GET : $_POST;
        $v = $arr[$key] ?? null;
        if ($v === null || $v === '') {
            if ($required) {
                http_response_code(422);
                echo "Campo requerido: {$key}";
                exit;
            }
            return 0;
        }
        if (is_string($v)) $v = trim($v);
        if (!is_numeric($v) || (int)$v != $v) {
            // acepta strings numéricos enteros
            if (!preg_match('/^-?\d+$/', (string)$v)) {
                http_response_code(422);
                echo "Campo inválido (int): {$key}";
                exit;
            }
        }
        return (int)$v;
    }
}

if (!function_exists('input_decimal')) {
    function input_decimal(string $key, bool $required = true, string $source = 'POST'): string
    {
        $arr = strtoupper($source) === 'GET' ? $_GET : $_POST;
        $v = $arr[$key] ?? null;
        if ($v === null || $v === '') {
            if ($required) {
                http_response_code(422);
                echo "Campo requerido: {$key}";
                exit;
            }
            return "0.00";
        }
        if (is_string($v)) $v = trim($v);
        $v = str_replace(',', '.', (string)$v);
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $v)) {
            http_response_code(422);
            echo "Campo inválido (decimal): {$key}";
            exit;
        }
        // normaliza a 2 decimales
        return number_format((float)$v, 2, '.', '');
    }
}

if (!function_exists('input_email')) {
    function input_email(string $key, bool $required = true, string $source = 'POST'): string
    {
        $v = input_str($key, 320, $required, $source);
        if ($v === '' && !$required) return '';
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo "Email inválido.";
            exit;
        }
        return $v;
    }
}

// -----------------------------------------------------------------------------
// Normalización / validaciones comunes (nombres, teléfonos, documentos)
// -----------------------------------------------------------------------------

if (!function_exists('normalize_spaces')) {
    function normalize_spaces(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        return $s;
    }
}

if (!function_exists('format_person_name')) {
    /**
     * Formatea nombres/apellidos: Primera letra de cada palabra en mayúscula,
     * resto en minúscula, respetando tildes.
     */
    function format_person_name(string $s): string
    {
        $s = normalize_spaces($s);
        if ($s === '') return '';
        $s = mb_strtolower($s, 'UTF-8');
        // MB_CASE_TITLE capitaliza cada palabra
        $s = mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
        return $s;
    }
}

if (!function_exists('phone_normalize')) {
    /**
     * Normaliza un teléfono/celular removiendo guiones/espacios/paréntesis.
     * Retorna solo dígitos para mantener consistencia en BD.
     */
    function phone_normalize(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        $digits = preg_replace('/\D+/', '', $s) ?? '';
        return $digits;
    }
}

if (!function_exists('phone_validate')) {
    /**
     * Valida teléfono normalizado. Acepta 8 a 15 dígitos.
     */
    function phone_validate(string $digits): bool
    {
        if ($digits === '') return true;
        $len = strlen($digits);
        return ($len >= 8 && $len <= 15);
    }
}

if (!function_exists('input_phone')) {
    function input_phone(string $key, bool $required = false, string $source = 'POST'): string
    {
        $raw = input_str($key, 40, $required, $source);
        if ($raw === '' && !$required) return '';
        $digits = phone_normalize($raw);
        if (!phone_validate($digits)) {
            http_response_code(422);
            echo 'Número de celular/teléfono inválido. Use solo números (8 a 15 dígitos).';
            exit;
        }
        return $digits;
    }
}

if (!function_exists('doc_normalize_simple')) {
    /**
     * Normaliza un documento genérico: quita espacios/guiones y pone mayúsculas.
     */
    function doc_normalize_simple(string $s): string
    {
        $s = strtoupper(trim($s));
        $s = preg_replace('/[\s\-]+/u', '', $s) ?? $s;
        return $s;
    }
}

if (!function_exists('pdo_exception_user_message')) {
    /**
     * Mapea errores comunes de MySQL (duplicate key, etc.) a mensajes amigables.
     */
    function pdo_exception_user_message(Throwable $e): ?string
    {
        if (!($e instanceof PDOException)) return null;
        // 23000: integrity constraint violation (incluye duplicate key)
        if (($e->getCode() ?? '') === '23000') {
            $msg = $e->getMessage();
            if (stripos($msg, 'ux_clientes_numero_documento') !== false) {
                return 'Ya existe un cliente con ese número de documento.';
            }
            if (stripos($msg, 'ux_proveedores_email') !== false) {
                return 'Ya existe un proveedor con ese email.';
            }
            return 'No se pudo guardar: el valor ya existe o viola una restricción.';
        }
        return null;
    }
}


if (!function_exists('input_date')) {
    /**
     * Valida fecha en formato YYYY-MM-DD (o YYYY-MM-DD HH:MM / YYYY-MM-DDTHH:MM si $allowTime=true).
     */
    function input_date(string $key, bool $required = true, bool $allowTime = false, string $source = 'POST'): string
    {
        $v = input_str($key, 32, $required, $source);
        if ($v === '' && !$required) return '';
        $v = trim($v);
        if ($allowTime) {
            $v2 = str_replace('T', ' ', $v);
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v2)) {
                http_response_code(422);
                echo "Fecha/hora inválida: {$key}";
                exit;
            }
            return $v2;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            http_response_code(422);
            echo "Fecha inválida: {$key}";
            exit;
        }
        return $v;
    }
}

if (!function_exists('require_password_min')) {
    function require_password_min(string $pwd, int $min = 8): void
    {
        if (strlen($pwd) < $min) {
            http_response_code(422);
            echo "La contraseña debe tener al menos {$min} caracteres.";
            exit;
        }
    }
}

if (!function_exists('upload_image')) {
    /**
     * Sube una imagen de forma segura.
     * Retorna la ruta relativa (para guardar en BD), o '' si no se subió (cuando required=false).
     */
    function upload_image(string $field, string $destDirRel = 'public/images/uploads', bool $required = false, int $maxBytes = 2097152): string
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            if ($required) {
                http_response_code(422);
                echo "Imagen requerida.";
                exit;
            }
            return '';
        }

        $f = $_FILES[$field];
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                http_response_code(422);
                echo "Imagen requerida.";
                exit;
            }
            return '';
        }

        if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo "Error al subir imagen.";
            exit;
        }

        if (($f['size'] ?? 0) > $maxBytes) {
            http_response_code(413);
            echo "Imagen demasiado grande (máx 2MB).";
            exit;
        }

        $tmp = $f['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            http_response_code(400);
            echo "Subida inválida.";
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            http_response_code(415);
            echo "Formato de imagen no permitido.";
            exit;
        }

        $ext = $allowed[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        $destDirAbs = rtrim(__DIR__ . '/../' . ltrim($destDirRel, '/'), '/');
        if (!is_dir($destDirAbs)) {
            if (!mkdir($destDirAbs, 0755, true) && !is_dir($destDirAbs)) {
                http_response_code(500);
                echo "No se pudo crear carpeta de uploads.";
                exit;
            }
        }

        $destAbs = $destDirAbs . '/' . $name;
        if (!move_uploaded_file($tmp, $destAbs)) {
            http_response_code(500);
            echo "No se pudo guardar la imagen.";
            exit;
        }

        // ruta relativa para guardar/mostrar
        $rel = rtrim($destDirRel, '/') . '/' . $name;
        return $rel;
    }
}

/**
 * =========================
 * Imágenes de productos (defaults + thumbnails + cleanup)
 * =========================
 *
 * Convención:
 * - Carpeta real:   /almacen/img_productos
 * - Guardado en BD: nombre o subruta relativa dentro de img_productos
 *   Ej: "a1b2c3.jpg" o "defaults/cat_1.png"
 * - Thumbnails:     /almacen/img_productos/thumbs/<misma ruta>
 */

if (!defined('PRODUCT_IMG_DIR_ABS')) {
    define('PRODUCT_IMG_DIR_ABS', __DIR__ . '/../almacen/img_productos');
}
if (!defined('DEFAULT_PRODUCT_IMAGE_REL')) {
    define('DEFAULT_PRODUCT_IMAGE_REL', 'defaults/producto_default.png');
}
if (!defined('DEFAULT_CATEGORY_IMAGE_REL')) {
    define('DEFAULT_CATEGORY_IMAGE_REL', 'defaults/cat_default.png');
}

if (!function_exists('product_img_abs')) {
    function product_img_abs(string $rel): string
    {
        $rel = ltrim($rel, '/');
        return rtrim(PRODUCT_IMG_DIR_ABS, '/') . '/' . $rel;
    }
}

if (!function_exists('product_thumb_rel')) {
    function product_thumb_rel(string $rel): string
    {
        $rel = ltrim($rel, '/');
        return 'thumbs/' . $rel;
    }
}

if (!function_exists('product_default_image_rel')) {
    /**
     * Devuelve la imagen por defecto considerando categoría.
     * - Si existe defaults/cat_<id>.png => usa esa.
     * - Si no existe, usa defaults/cat_default.png (si existe).
     * - Si tampoco, usa defaults/producto_default.png.
     */
    function product_default_image_rel(?int $idCategoria): string
    {
        $cand = '';
        if ($idCategoria && $idCategoria > 0) {
            $cand = 'defaults/cat_' . $idCategoria . '.png';
            if (is_file(product_img_abs($cand))) return $cand;
        }
        if (is_file(product_img_abs(DEFAULT_CATEGORY_IMAGE_REL))) return DEFAULT_CATEGORY_IMAGE_REL;
        return DEFAULT_PRODUCT_IMAGE_REL;
    }
}

if (!function_exists('product_image_rel_resolve')) {
    /**
     * Resuelve una imagen real:
     * - Si $rel no está vacío y el archivo existe => retorna $rel
     * - Si no => default por categoría
     */
    function product_image_rel_resolve(?string $rel, ?int $idCategoria): string
    {
        $rel = is_string($rel) ? trim($rel) : '';
        if ($rel !== '' && is_file(product_img_abs($rel))) return $rel;
        return product_default_image_rel($idCategoria);
    }
}

if (!function_exists('product_image_url')) {
    /**
     * Retorna URL final para mostrar.
     * Si $preferThumb=true y existe thumbnail, usa thumbs/<rel>.
     */
    function product_image_url(?string $rel, ?int $idCategoria = null, bool $preferThumb = true): string
    {
        global $URL;
        $rel = product_image_rel_resolve($rel, $idCategoria);

        if ($preferThumb) {
            $thumbRel = product_thumb_rel($rel);
            if (is_file(product_img_abs($thumbRel))) {
                return $URL . '/almacen/img_productos/' . $thumbRel;
            }
        }
        return $URL . '/almacen/img_productos/' . $rel;
    }
}

if (!function_exists('create_thumbnail')) {
    /**
     * Crea thumbnail con GD (si está disponible).
     * - Mantiene proporción.
     * - Soporta JPG/PNG/WEBP.
     */
    function create_thumbnail(string $srcAbs, string $destAbs, int $maxW = 280, int $maxH = 280): void
    {
        if (!extension_loaded('gd')) return;
        if (!is_file($srcAbs)) return;

        $info = @getimagesize($srcAbs);
        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) return;

        $w = (int)$info[0];
        $h = (int)$info[1];
        $mime = (string)$info['mime'];

        $create = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png'  => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
            default => null,
        };
        $save = match ($mime) {
            'image/jpeg' => 'imagejpeg',
            'image/png'  => 'imagepng',
            'image/webp' => 'imagewebp',
            default => null,
        };
        if (!$create || !$save || !function_exists($create) || !function_exists($save)) return;

        $ratio = min($maxW / $w, $maxH / $h, 1);
        $nw = max(1, (int)floor($w * $ratio));
        $nh = max(1, (int)floor($h * $ratio));

        $src = @$create($srcAbs);
        if (!$src) return;

        $dst = imagecreatetruecolor($nw, $nh);
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $destDir = dirname($destAbs);
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        // calidad razonable
        if ($mime === 'image/jpeg') {
            @$save($dst, $destAbs, 82);
        } elseif ($mime === 'image/png') {
            @$save($dst, $destAbs, 6);
        } else {
            @$save($dst, $destAbs, 82);
        }

        imagedestroy($dst);
        imagedestroy($src);
    }
}

if (!function_exists('upload_product_image')) {
    /**
     * Sube imagen de producto a /almacen/img_productos y genera thumbnail.
     * Retorna nombre relativo a img_productos (ej: "a1b2.jpg") o '' si no se subió.
     */
    function upload_product_image(string $field, bool $required = false): string
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            if ($required) {
                http_response_code(422);
                echo "Imagen requerida.";
                exit;
            }
            return '';
        }

        $f = $_FILES[$field];
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                http_response_code(422);
                echo "Imagen requerida.";
                exit;
            }
            return '';
        }

        if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo "Error al subir imagen.";
            exit;
        }

        $maxBytes = 2 * 1024 * 1024; // 2MB
        if (($f['size'] ?? 0) > $maxBytes) {
            http_response_code(413);
            echo "Imagen demasiado grande (máx 2MB).";
            exit;
        }

        $tmp = $f['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            http_response_code(400);
            echo "Subida inválida.";
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            http_response_code(415);
            echo "Formato de imagen no permitido.";
            exit;
        }

        if (!is_dir(PRODUCT_IMG_DIR_ABS)) {
            @mkdir(PRODUCT_IMG_DIR_ABS, 0755, true);
        }

        $ext = $allowed[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $destAbs = rtrim(PRODUCT_IMG_DIR_ABS, '/') . '/' . $name;

        if (!move_uploaded_file($tmp, $destAbs)) {
            http_response_code(500);
            echo "No se pudo guardar la imagen.";
            exit;
        }

        // thumbnail espejo: thumbs/<name>
        $thumbAbs = product_img_abs(product_thumb_rel($name));
        create_thumbnail($destAbs, $thumbAbs);

        return $name;
    }
}

if (!function_exists('delete_product_image_files')) {
    /**
     * Borra imagen y thumbnail si:
     * - No es default
     * - Existe en disco
     */
    function delete_product_image_files(?string $rel): void
    {
        $rel = is_string($rel) ? trim($rel) : '';
        if ($rel === '') return;

        // no borrar defaults
        if (str_starts_with($rel, 'defaults/')) return;

        $abs = product_img_abs($rel);
        if (is_file($abs)) @unlink($abs);

        $thumbAbs = product_img_abs(product_thumb_rel($rel));
        if (is_file($thumbAbs)) @unlink($thumbAbs);
    }
}

/**
 * Datos de la óptica (branding y cabeceras de impresión).
 * Puedes personalizar estos valores a tu gusto.
 */
function optica_info(): array
{
    global $URL;
    return [
        'nombre'    => 'Óptica Alta Vision',
        'telefono'  => '+505 8173 1664',
        'direccion' => 'Cruz Blanca 25 Vrs al Sur',
        'web'       => '',
        'ruc'       => 'Ruc: 4020809910000X',
        'logo'      => $URL . '/public/images/optica/logo_bajo.png',
        'favicon'   => $URL . '/public/images/optica/favicon.png',
    ];
}

// === Helpers adicionales (Optica / Exámenes) ===
// Decimal con signo (permite negativos). Retorna string normalizada "0.00".
// Si required=false y viene vacío, retorna "0.00".
function input_decimal_signed(string $key, bool $required = true, string $source = 'POST'): string
{
    $arr = strtoupper($source) === 'GET' ? $_GET : $_POST;
    $v = $arr[$key] ?? null;
    if ($v === null || $v === '') {
        if ($required) {
            http_response_code(422);
            echo "Campo requerido: {$key}";
            exit;
        }
        return "0.00";
    }
    if (is_string($v)) $v = trim($v);
    $v = str_replace(',', '.', (string)$v);
    if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $v)) {
        http_response_code(422);
        echo "Campo inválido (decimal): {$key}";
        exit;
    }
    return number_format((float)$v, 2, '.', '');
}

// Decimal con signo nullable. Si viene vacío retorna null (no hace exit).
function input_decimal_signed_nullable(string $key, string $source = 'POST'): ?string
{
    $arr = strtoupper($source) === 'GET' ? $_GET : $_POST;
    $v = $arr[$key] ?? null;
    if ($v === null || $v === '') return null;
    if (is_string($v)) $v = trim($v);
    $v = str_replace(',', '.', (string)$v);
    if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $v)) {
        http_response_code(422);
        echo "Campo inválido (decimal): {$key}";
        exit;
    }
    return number_format((float)$v, 2, '.', '');
}

// INT nullable: si viene vacío retorna null
function input_int_nullable(string $key, string $source = 'POST'): ?int
{
    $arr = strtoupper($source) === 'GET' ? $_GET : $_POST;
    $v = $arr[$key] ?? null;
    if ($v === null || $v === '') return null;
    if (is_string($v)) $v = trim($v);
    if (!preg_match('/^-?\d+$/', (string)$v)) {
        http_response_code(422);
        echo "Campo inválido (int): {$key}";
        exit;
    }
    return (int)$v;
}

// String nullable: si viene vacío retorna null
function input_str_nullable(string $key, int $maxLen = 255, string $source = 'POST'): ?string
{
    $arr = strtoupper($source) === 'GET' ? $_GET : $_POST;
    $v = $arr[$key] ?? '';
    if (!is_string($v)) $v = '';
    $v = trim($v);
    if ($v === '') return null;
    if (mb_strlen($v) > $maxLen) {
        http_response_code(422);
        echo "Campo demasiado largo: {$key}";
        exit;
    }
    return $v;
}

// Respuesta JSON estándar
function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}



if (!function_exists('flash_set')) {
    function flash_set(string $message, string $type = 'info', string $title = ''): void
    {
        ensure_session();
        $_SESSION['_flash'] = [
            'message' => $message,
            'type' => $type,
            'title' => $title,
        ];
    }
}

if (!function_exists('flash_pull')) {
    function flash_pull(): ?array
    {
        ensure_session();
        if (empty($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) return null;
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
}

if (!function_exists('flash_render')) {
    function flash_render(): void
    {
        $f = flash_pull();
        if (!$f) return;

        $type = (string)($f['type'] ?? 'info');
        $msg  = (string)($f['message'] ?? '');
        $title = (string)($f['title'] ?? '');

        $map = [
            'success' => 'success',
            'danger'  => 'danger',
            'error'   => 'danger',
            'warning' => 'warning',
            'info'    => 'info',
            'primary' => 'primary',
            'secondary' => 'secondary',
            'light' => 'light',
            'dark' => 'dark',
        ];
        $bs = $map[$type] ?? 'info';

        $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

        echo '<div class="alert alert-' . $h($bs) . ' alert-dismissible fade show" role="alert">';
        if ($title !== '') echo '<strong>' . $h($title) . '</strong> ';
        echo $h($msg);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        echo '<span aria-hidden="true">&times;</span>';
        echo '</button></div>';
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, string $message = '', string $type = 'info', string $title = ''): void
    {
        if ($message !== '') {
            flash_set($message, $type, $title);
        }
        header('Location: ' . $url);
        exit;
    }
}

// =========================
// Helpers UI/AJAX + RBAC
// =========================

if (!function_exists('is_ajax_request')) {
    function is_ajax_request(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }
}

if (!function_exists('ui_can')) {
    /**
     * Uso en UI: if (ui_can('usuarios.crear')) { ... }
     * Soporta comodín '*' (cuando aún no has migrado RBAC o para superadmin).
     */
    function ui_can(string $perm): bool
    {
        ensure_session();
        $perms = $_SESSION['_perms'] ?? [];
        if (!is_array($perms)) return false;

        if (!empty($perms['*']) && $perms['*'] === true) return true;

        return !empty($perms[$perm]) && $perms[$perm] === true;
    }
}

if (!function_exists('require_perm')) {
    /**
     * Protege páginas/acciones.
     * Ej: require_perm($pdo, 'usuarios.ver', $URL.'/index.php');
     */
    function require_perm(PDO $pdo, string $perm, string $redirectTo): void
    {
        if (!ui_can($perm)) {
            redirect($redirectTo, 'Acceso denegado.', 'danger');
        }
    }
}

if (!function_exists('require_admin')) {
    /**
     * Permite solo ADMINISTRADOR (por nombre de rol) o si tiene '*' en permisos.
     * Nota: depende de que sesion.php haya cargado el rol en sesión.
     */
    function require_admin(PDO $pdo, string $redirectTo): void
    {
        ensure_session();
        $perms = $_SESSION['_perms'] ?? [];
        if (is_array($perms) && !empty($perms['*'])) return;

        $rol = strtoupper((string)($_SESSION['sesion_rol_nombre'] ?? ''));
        if ($rol === 'ADMINISTRADOR') return;

        redirect($redirectTo, 'Solo ADMINISTRADOR.', 'danger');
    }
}

if (!function_exists('load_role_perms')) {
    /**
     * Carga permisos de un rol desde tb_permisos + tb_roles_permisos.
     * Retorna array clave => true.
     */
    function load_role_perms(PDO $pdo, int $idRol): array
    {
        $out = [];
        if ($idRol <= 0) return $out;

        $sql = "
      SELECT p.clave
        FROM tb_roles_permisos rp
        INNER JOIN tb_permisos p ON p.id_permiso = rp.id_permiso
       WHERE rp.id_rol = ?
    ";
        $st = $pdo->prepare($sql);
        $st->execute([$idRol]);
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $k = (string)($row['clave'] ?? '');
            if ($k !== '') $out[$k] = true;
        }
        return $out;
    }
}


if (!function_exists('db_table_exists')) {
    function db_table_exists(PDO $pdo, string $table): bool
    {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('db_column_exists')) {
    function db_column_exists(PDO $pdo, string $table, string $column): bool
    {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

// =========================
// Moneda / Formateo dinero
// =========================
if (!defined('APP_CURRENCY')) {
    // Moneda por defecto (Nicaragua): Córdoba
    define('APP_CURRENCY', 'C$');
}

if (!function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return (string)(defined('APP_CURRENCY') ? APP_CURRENCY : 'C$');
    }
}

if (!function_exists('money_fmt')) {
    /**
     * Formatea un monto con 2 decimales.
     * - $withSymbol: incluye el prefijo de moneda (ej: C$).
     * - $forceSign: null => sin signo, true => siempre +/-, false => solo negativo.
     */
    function money_fmt($amount, bool $withSymbol = true, ?bool $forceSign = null): string
    {
        $n = (float)($amount ?? 0);
        $abs = number_format(abs($n), 2, '.', ',');
        $sign = '';
        if ($forceSign === true) {
            $sign = ($n < 0) ? '-' : '+';
        } elseif ($forceSign === false) {
            $sign = ($n < 0) ? '-' : '';
        }
        $cur = currency_symbol();
        return ($sign !== '' ? $sign : '') . ($withSymbol ? $cur : '') . $abs;
    }
}

if (!function_exists('money_signed_by_tipo')) {
    /**
     * Para movimientos: si $tipo = 'egreso' -> negativo, si 'ingreso' -> positivo.
     * Monto en BD normalmente viene positivo.
     */
    function money_signed_by_tipo(string $tipo, $monto, bool $withSymbol = true): string
    {
        $t = strtolower(trim($tipo));
        $n = (float)($monto ?? 0);
        if ($t === 'egreso') $n = -abs($n);
        else $n = abs($n); // ingreso
        return money_fmt($n, $withSymbol, true);
    }
}


// --- Auditoría (tb_auditoria) ---
if (is_file(__DIR__ . '/Helpers/auditoria.php')) {
    require_once __DIR__ . '/Helpers/auditoria.php';
}
