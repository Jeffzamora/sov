<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

require_post();
csrf_verify();

try {
  if (function_exists('require_perm')) {
    require_perm($pdo, 'categorias.crear', $URL . '/index.php');
  }

  $nombre_categoria = trim(input_str('nombre_categoria', 100, true));

  // Normaliza (consistencia UI/BD)
  // Mantén acentos, pero quita dobles espacios.
  $nombre_categoria = preg_replace('/\s+/', ' ', $nombre_categoria);

  $hasEstado = db_column_exists($pdo, 'tb_categorias', 'estado');

  // Evitar duplicados (case-insensitive). Si existe INACTIVO, se reactiva.
  $st = $pdo->prepare("SELECT id_categoria, COALESCE(UPPER(estado),'ACTIVO') AS estado FROM tb_categorias WHERE UPPER(nombre_categoria)=UPPER(?) LIMIT 1");
  $st->execute([$nombre_categoria]);
  $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;

  if ($row) {
    $idExist = (int)($row['id_categoria'] ?? 0);
    $estadoExist = strtoupper((string)($row['estado'] ?? 'ACTIVO'));
    if ($hasEstado && $estadoExist === 'INACTIVO' && $idExist > 0) {
      // Reactivar categoría existente
      $up = $pdo->prepare("UPDATE tb_categorias SET estado='ACTIVO', nombre_categoria=:n, fyh_actualizacion=:fyh WHERE id_categoria=:id LIMIT 1");
      $up->execute([':n'=>$nombre_categoria, ':fyh'=>date('Y-m-d H:i:s'), ':id'=>$idExist]);
      if (is_ajax_request()) json_response(['ok'=>true, 'id_categoria'=>$idExist, 'reactivado'=>true]);
      redirect($URL . '/categorias/', 'Categoría reactivada.', 'success');
    }
    throw new RuntimeException('Esta categoría ya existe.');
  }

  if ($hasEstado) {
    $stmt = $pdo->prepare("
      INSERT INTO tb_categorias (nombre_categoria, estado, fyh_creacion)
      VALUES (:n, 'ACTIVO', :fyh)
    ");
  } else {
    $stmt = $pdo->prepare("
      INSERT INTO tb_categorias (nombre_categoria, fyh_creacion)
      VALUES (:n, :fyh)
    ");
  }

  $stmt->execute([':n' => $nombre_categoria, ':fyh' => date('Y-m-d H:i:s')]);

  if (is_ajax_request()) json_response(['ok' => true, 'id_categoria' => (int)$pdo->lastInsertId()]);
  redirect($URL . '/categorias/', 'Categoría registrada.', 'success');
} catch (Throwable $e) {
  if (is_ajax_request()) json_response(['ok' => false, 'error' => $e->getMessage()], 422);
  redirect($URL . '/categorias/', $e->getMessage(), 'danger');
}
