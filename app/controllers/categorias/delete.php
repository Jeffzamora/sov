<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

require_post();
csrf_verify();

try {
  // Permisos (si tu sesion.php ya carga permisos en $_SESSION['_perms']).
  if (function_exists('require_perm')) {
    require_perm($pdo, 'categorias.eliminar', $URL . '/index.php');
  }

  $id_categoria = input_int('id_categoria', true);

  // Si existe estado, desactivamos (recomendado)
  if (db_column_exists($pdo, 'tb_categorias', 'estado')) {

    // Evitar desactivar si quieres proteger categorías en uso (opcional):
    // Si prefieres PERMITIR desactivar aunque tenga productos, comenta este bloque.
    $st = $pdo->prepare("SELECT COUNT(*) FROM tb_almacen WHERE id_categoria = :id");
    $st->execute([':id' => $id_categoria]);
    $count = (int)$st->fetchColumn();
    if ($count > 0) {
      throw new RuntimeException("No se puede desactivar: hay productos asociados a esta categoría.");
    }

    // Si ya está inactiva, respondemos OK (idempotente)
    $st0 = $pdo->prepare("SELECT estado FROM tb_categorias WHERE id_categoria=:id LIMIT 1");
    $st0->execute([':id' => $id_categoria]);
    $estadoActual = strtoupper((string)($st0->fetchColumn() ?? ''));
    if ($estadoActual === 'INACTIVO') {
      if (is_ajax_request()) json_response(['ok' => true, 'estado' => 'INACTIVO']);
      redirect($URL . '/categorias/', 'La categoría ya estaba desactivada.', 'info');
    }

    // Intentar con fyh_actualizacion si existe, si no, reintentar sin esa columna.
    $fyh = date('Y-m-d H:i:s');
    if (function_exists('db_column_exists') && db_column_exists($pdo, 'tb_categorias', 'fyh_actualizacion')) {
      $stmt = $pdo->prepare("UPDATE tb_categorias SET estado='INACTIVO', fyh_actualizacion=:fyh WHERE id_categoria=:id LIMIT 1");
      $stmt->execute([':fyh' => $fyh, ':id' => $id_categoria]);
      if ($stmt->rowCount() < 1) throw new RuntimeException("No se pudo desactivar la categoría.");
    } else {
      $stmt2 = $pdo->prepare("UPDATE tb_categorias SET estado='INACTIVO' WHERE id_categoria=:id LIMIT 1");
      $stmt2->execute([':id' => $id_categoria]);
      if ($stmt2->rowCount() < 1) throw new RuntimeException("No se pudo desactivar la categoría.");
    }

    if (is_ajax_request()) json_response(['ok' => true, 'estado' => 'INACTIVO']);
    redirect($URL . '/categorias/', 'Categoría desactivada.', 'success');
  }

  // Si no existe estado, fallback: impedir eliminar si hay productos
  $st = $pdo->prepare("SELECT COUNT(*) FROM tb_almacen WHERE id_categoria = :id");
  $st->execute([':id' => $id_categoria]);
  $count = (int)$st->fetchColumn();
  if ($count > 0) throw new RuntimeException("No se puede eliminar: hay productos asociados.");

  $stmt = $pdo->prepare("DELETE FROM tb_categorias WHERE id_categoria=:id LIMIT 1");
  $stmt->execute([':id' => $id_categoria]);
  if ($stmt->rowCount() < 1) throw new RuntimeException("No se pudo eliminar la categoría.");

  if (is_ajax_request()) json_response(['ok' => true]);
  redirect($URL . '/categorias/', 'Categoría eliminada.', 'success');
} catch (Throwable $e) {
  if (is_ajax_request()) json_response(['ok' => false, 'error' => $e->getMessage()], 422);
  redirect($URL . '/categorias/', $e->getMessage(), 'danger');
}
