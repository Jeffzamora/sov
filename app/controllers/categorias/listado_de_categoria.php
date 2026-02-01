<?php
try {
  $hasEstado = db_column_exists($pdo, 'tb_categorias', 'estado');

  $sql = $hasEstado
    ? "SELECT id_categoria, nombre_categoria, UPPER(estado) AS estado
         FROM tb_categorias
        ORDER BY nombre_categoria ASC"
    : "SELECT id_categoria, nombre_categoria
         FROM tb_categorias
        ORDER BY nombre_categoria ASC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $categorias_datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  foreach ($categorias_datos as &$c) {
    $c['id_categoria'] = (int)($c['id_categoria'] ?? 0);
    $c['nombre_categoria'] = (string)($c['nombre_categoria'] ?? '');
    if ($hasEstado) {
      $c['estado'] = strtoupper((string)($c['estado'] ?? 'ACTIVO'));
      if (!in_array($c['estado'], ['ACTIVO', 'INACTIVO'], true)) $c['estado'] = 'ACTIVO';
    }
  }
  unset($c);
} catch (Throwable $e) {
  error_log('categorias listado error: ' . $e->getMessage());
  $categorias_datos = [];
}
