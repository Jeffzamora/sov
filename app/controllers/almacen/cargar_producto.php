<?php

/**
 * Cargar producto por id (para show/update/delete).
 *
 * Mejoras:
 * - No lanza excepciones a la vista: usa flash + redirect (mejor UX).
 * - Alias consistentes (categoria_nombre).
 * - Cast/normalización de tipos.
 * - Soporte opcional para "estado" (activo/inactivo) si existe.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function flash_redirect(string $msg, string $icon, string $to): void
{
    $_SESSION['mensaje'] = $msg;
    $_SESSION['icono'] = $icon;
    header('Location: ' . $to);
    exit;
}

// 1) Validar ID
$id_producto_get = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_producto_get <= 0) {
    flash_redirect('ID de producto inválido.', 'warning', $URL . '/almacen/');
}

// 2) Opcional: permitir ver inactivos (ej: ?id=5&include_inactive=1)
// Por defecto NO muestra inactivos.
$includeInactive = isset($_GET['include_inactive']) && (int)$_GET['include_inactive'] === 1;

// 3) Detectar si existe columna "estado" en tb_almacen (sin romper si no existe)
$hasEstado = false;
try {
    $chk = $pdo->query("SHOW COLUMNS FROM tb_almacen LIKE 'estado'");
    $hasEstado = (bool)$chk->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $hasEstado = false; // no bloquea carga
}

// 4) Query consistente
$whereEstado = '';
$params = [':id' => $id_producto_get];

if ($hasEstado && !$includeInactive) {
    $whereEstado = " AND a.estado = 1 ";
}

$sql = "
  SELECT
    a.*,
    cat.nombre_categoria AS categoria_nombre,
    u.email AS usuario_email,
    u.id_usuario AS usuario_id
  FROM tb_almacen a
  INNER JOIN tb_categorias cat ON a.id_categoria = cat.id_categoria
  INNER JOIN tb_usuarios u ON u.id_usuario = a.id_usuario
  WHERE a.id_producto = :id
  {$whereEstado}
  LIMIT 1
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    flash_redirect('Error consultando producto.', 'error', $URL . '/almacen/');
}

if (!$row) {
    // Si existe estado, puede estar inactivo; si quieres, muestra mensaje más específico
    $msg = ($hasEstado && !$includeInactive)
        ? 'Producto no encontrado o está desactivado.'
        : 'Producto no encontrado.';
    flash_redirect($msg, 'warning', $URL . '/almacen/');
}

// 5) Mapear a variables esperadas por tus vistas
$codigo          = (string)($row['codigo'] ?? '');
$id_categoria    = (int)($row['id_categoria'] ?? 0);
$nombre_categoria = (string)($row['categoria_nombre'] ?? '');
$nombre          = (string)($row['nombre'] ?? '');
$email           = (string)($row['usuario_email'] ?? '');
$id_usuario      = isset($row['usuario_id']) ? (int)$row['usuario_id'] : null;
$descripcion     = (string)($row['descripcion'] ?? '');

$stock           = (int)($row['stock'] ?? 0);
$stock_minimo    = (int)($row['stock_minimo'] ?? 0);
$stock_maximo    = (int)($row['stock_maximo'] ?? 0);

$precio_compra   = $row['precio_compra'] ?? 0;
$precio_venta    = $row['precio_venta'] ?? 0;

$fecha_ingreso   = (string)($row['fecha_ingreso'] ?? '');
$imagen          = (string)($row['imagen'] ?? '');

// Si existe estado, expón variable
$estado          = $hasEstado ? (int)($row['estado'] ?? 1) : 1;

// Guarda la fila completa si la necesitas (debug o extensiones)
$producto_row = $row;
