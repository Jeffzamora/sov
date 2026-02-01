<?php

// Por defecto solo activos. Para mostrar todos: ?all=1
$all = (int)($_GET['all'] ?? 0) === 1;

if ($all) {
  $sql = "SELECT * FROM tb_proveedores ORDER BY id_proveedor DESC";
  $st = $pdo->prepare($sql);
  $st->execute();
} else {
  $sql = "SELECT * FROM tb_proveedores WHERE estado = 1 ORDER BY id_proveedor DESC";
  $st = $pdo->prepare($sql);
  $st->execute();
}

$proveedores_datos = $st->fetchAll(PDO::FETCH_ASSOC);
