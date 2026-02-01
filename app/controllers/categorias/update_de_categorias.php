<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

require_post();
csrf_verify();

try {
  if (function_exists('require_perm')) {
    require_perm($pdo, 'categorias.actualizar', $URL . '/index.php');
  }

  $id = input_int('id_categoria', true);
  $nombre = input_str('nombre_categoria', 100, true);
  $nombre = preg_replace('/\s+/', ' ', trim($nombre));

  // Evitar duplicados (case-insensitive) excluyendo el propio ID
  $dup = $pdo->prepare(
    "SELECT id_categoria, UPPER(COALESCE(estado,'ACTIVO')) AS estado
       FROM tb_categorias
      WHERE UPPER(nombre_categoria)=UPPER(?)
        AND id_categoria<>?
      LIMIT 1"
  );
  $dup->execute([$nombre, $id]);
  $rowDup = $dup->fetch(PDO::FETCH_ASSOC);
  if ($rowDup) {
    throw new RuntimeException('Ya existe otra categoría con ese nombre.');
  }

  $st = $pdo->prepare("SELECT 1 FROM tb_categorias WHERE id_categoria=? LIMIT 1");
  $st->execute([$id]);
  if (!$st->fetchColumn()) throw new RuntimeException('Categoría no encontrada.');

  $stmt = $pdo->prepare("
    UPDATE tb_categorias
       SET nombre_categoria=:n,
           fyh_actualizacion=:fyh
     WHERE id_categoria=:id
     LIMIT 1
  ");
  $stmt->execute([':n' => $nombre, ':fyh' => date('Y-m-d H:i:s'), ':id' => $id]);

  if (is_ajax_request()) json_response(['ok' => true]);

  redirect($URL . '/categorias/', 'Categoría actualizada correctamente', 'success');
} catch (Throwable $e) {
  if (is_ajax_request()) json_response(['ok' => false, 'error' => $e->getMessage()], 422);
  redirect($URL . '/categorias/', $e->getMessage(), 'danger');
}
