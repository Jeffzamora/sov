<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=utf-8');

function json_ok(array $items): void {
  echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
  exit;
}
function json_err(int $code, string $msg): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
  if ($q === '' || strlen($q) < 2) json_ok([]);

  // limita
  if (strlen($q) > 80) $q = substr($q, 0, 80);
  $like = '%' . $q . '%';

  // Intentos de compatibilidad: algunos proyectos usan apellido vs apellidos, email vs correo.
  // NOTA: devolvemos llaves uniformes para el frontend: {id, nombre, doc} y también {text, meta} por compat.
  $candidates = [
    // A: apellido + email
    "SELECT id_cliente, nombre, apellido AS ap, numero_documento AS doc, celular AS cel, email AS mail
     FROM tb_clientes
     WHERE nombre LIKE :q OR apellido LIKE :q OR numero_documento LIKE :q OR celular LIKE :q OR email LIKE :q
     ORDER BY nombre ASC
     LIMIT 25",

    // B: apellidos + email
    "SELECT id_cliente, nombre, apellidos AS ap, numero_documento AS doc, celular AS cel, email AS mail
     FROM tb_clientes
     WHERE nombre LIKE :q OR apellidos LIKE :q OR numero_documento LIKE :q OR celular LIKE :q OR email LIKE :q
     ORDER BY nombre ASC
     LIMIT 25",

    // C: apellidos + correo
    "SELECT id_cliente, nombre, apellidos AS ap, numero_documento AS doc, celular AS cel, correo AS mail
     FROM tb_clientes
     WHERE nombre LIKE :q OR apellidos LIKE :q OR numero_documento LIKE :q OR celular LIKE :q OR correo LIKE :q
     ORDER BY nombre ASC
     LIMIT 25",

    // D: apellido + correo
    "SELECT id_cliente, nombre, apellido AS ap, numero_documento AS doc, celular AS cel, correo AS mail
     FROM tb_clientes
     WHERE nombre LIKE :q OR apellido LIKE :q OR numero_documento LIKE :q OR celular LIKE :q OR correo LIKE :q
     ORDER BY nombre ASC
     LIMIT 25",

    // E: mínimo (si no existen columnas mail/ap)
    "SELECT id_cliente, nombre, '' AS ap, numero_documento AS doc, celular AS cel, '' AS mail
     FROM tb_clientes
     WHERE nombre LIKE :q OR numero_documento LIKE :q OR celular LIKE :q
     ORDER BY nombre ASC
     LIMIT 25",
  ];

  $rows = null;
  $lastErr = null;

  foreach ($candidates as $sql) {
    try {
      $st = $pdo->prepare($sql);
      $st->execute([':q' => $like]);
      $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
      $lastErr = null;
      break;
    } catch (Throwable $e) {
      $lastErr = $e->getMessage();
      continue;
    }
  }

  if ($rows === null) {
    json_err(500, $lastErr ?: 'No se pudo ejecutar la búsqueda de clientes.');
  }

  $items = [];
  foreach ($rows as $r) {
    $nombreFull = trim((string)($r['nombre'] ?? '') . ' ' . (string)($r['ap'] ?? ''));
    $doc = (string)($r['doc'] ?? '');
    $meta = array_filter([
      $doc,
      (string)($r['cel'] ?? ''),
      (string)($r['mail'] ?? ''),
    ]);
    $items[] = [
      'id' => (int)($r['id_cliente'] ?? 0),
      // llaves esperadas por citas/index.php
      'nombre' => $nombreFull !== '' ? $nombreFull : ('Cliente #' . (int)($r['id_cliente'] ?? 0)),
      'doc' => $doc,
      // compat (por si alguna UI antigua usa text/meta)
      'text' => $nombreFull !== '' ? $nombreFull : ('Cliente #' . (int)($r['id_cliente'] ?? 0)),
      'meta' => implode(' • ', $meta),
    ];
  }

  json_ok($items);

} catch (Throwable $e) {
  json_err(500, $e->getMessage());
}
