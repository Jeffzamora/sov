<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function flash_and_redirect(string $msg, string $icon, string $to): void
{
    $_SESSION['mensaje'] = $msg;
    $_SESSION['icono'] = $icon;
    header('Location: ' . $to);
    exit;
}

/**
 * Detecta si existe una tabla en el schema actual.
 */
function table_exists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare("
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = :t
    LIMIT 1
  ");
    $st->execute([':t' => $table]);
    return (bool)$st->fetchColumn();
}

$id_producto = input_int('id_producto', true);

// 1) Verificar producto + estado actual
try {
    $st = $pdo->prepare("SELECT id_producto, estado FROM tb_almacen WHERE id_producto = :id LIMIT 1");
    $st->execute([':id' => $id_producto]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        flash_and_redirect('Producto no encontrado o ya fue eliminado.', 'warning', $URL . '/almacen/');
    }

    // Si tu BD todavía no tiene estado, esto fallará. En ese caso ejecuta el SQL de abajo.
    $estadoActual = (int)($row['estado'] ?? 1);
    if ($estadoActual === 0) {
        flash_and_redirect('El producto ya estaba desactivado.', 'info', $URL . '/almacen/');
    }
} catch (Throwable $e) {
    flash_and_redirect('Error consultando producto: ' . $e->getMessage(), 'error', $URL . '/almacen/');
}

// 2) Si existen ventas, bloquear si está referenciado en detalle
// Ajusta el nombre de columna si tu detalle usa otro (ej: producto_id).
try {
    if (table_exists($pdo, 'tb_ventas') && table_exists($pdo, 'tb_ventas_detalle')) {

        // Verificación mínima: si existe al menos 1 fila en detalle para ese producto, bloqueamos.
        $ref = $pdo->prepare("SELECT 1 FROM tb_ventas_detalle WHERE id_producto = :id LIMIT 1");
        $ref->execute([':id' => $id_producto]);

        if ($ref->fetchColumn()) {
            flash_and_redirect(
                'No se puede desactivar: el producto ya está registrado en ventas. Recomendado: mantenerlo activo para no afectar historial.',
                'warning',
                $URL . '/almacen/'
            );
        }
    }
} catch (Throwable $e) {
    // Si falla esta validación por cualquier motivo, es mejor detenerse
    flash_and_redirect('Error validando ventas: ' . $e->getMessage(), 'error', $URL . '/almacen/');
}

// 3) Desactivar (soft delete)
try {
    $up = $pdo->prepare("UPDATE tb_almacen SET estado = 0 WHERE id_producto = :id");
    $up->execute([':id' => $id_producto]);

    if ($up->rowCount() > 0) {
        flash_and_redirect('Producto desactivado correctamente.', 'success', $URL . '/almacen/');
    }

    // Si rowCount==0 puede ser porque no cambió nada o no existe
    flash_and_redirect('No se pudo desactivar el producto.', 'error', $URL . '/almacen/');
} catch (Throwable $e) {
    flash_and_redirect('Error BD: ' . $e->getMessage(), 'error', $URL . '/almacen/');
}
