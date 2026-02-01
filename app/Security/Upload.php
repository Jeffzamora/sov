<?php
// app/Security/Upload.php
declare(strict_types=1);

/**
 * Subida segura de imagen.
 * - Valida error
 * - Valida tamaño (por defecto 2MB)
 * - Valida MIME real usando finfo
 * - Fuerza extensiones permitidas
 * - Nombre aleatorio
 * Retorna ruta relativa para guardar en DB (ej: 'uploads/products/abc123.jpg')
 */
function upload_image(string $field, string $destDirRel='uploads/products', int $maxBytes=2097152): ?string
{
  if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) return null;

  $f = $_FILES[$field];
  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
  if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    throw new RuntimeException('Error al subir archivo.');
  }
  $size = (int)($f['size'] ?? 0);
  if ($size <= 0 || $size > $maxBytes) {
    throw new RuntimeException('Tamaño de imagen inválido (máx 2MB).');
  }
  $tmp = (string)($f['tmp_name'] ?? '');
  if ($tmp === '' || !is_uploaded_file($tmp)) {
    throw new RuntimeException('Archivo inválido.');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp) ?: '';
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];
  if (!isset($allowed[$mime])) {
    throw new RuntimeException('Formato no permitido. Use JPG/PNG/WEBP.');
  }
  $ext = $allowed[$mime];

  $rand = bin2hex(random_bytes(16));
  $fileName = $rand . '.' . $ext;

  // destino absoluto
  $root = dirname(__DIR__, 2); // app/ -> proyecto/
  $destDirAbs = $root . DIRECTORY_SEPARATOR . $destDirRel;
  if (!is_dir($destDirAbs)) {
    if (!mkdir($destDirAbs, 0755, true) && !is_dir($destDirAbs)) {
      throw new RuntimeException('No se pudo crear carpeta de uploads.');
    }
  }
  $destAbs = $destDirAbs . DIRECTORY_SEPARATOR . $fileName;

  if (!move_uploaded_file($tmp, $destAbs)) {
    throw new RuntimeException('No se pudo guardar la imagen.');
  }

  return rtrim($destDirRel,'/') . '/' . $fileName;
}
