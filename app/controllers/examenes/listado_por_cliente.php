<?php
// Listado de exámenes por cliente (para vistas)
if (!isset($pdo)) { require_once __DIR__ . '/../../config.php'; }

$id_cliente = isset($id_cliente) ? (int)$id_cliente : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$examenes = [];

try {
  $q = $pdo->prepare("
    SELECT e.*, u.email AS usuario_email
    FROM tb_examenes_optometricos e
    LEFT JOIN tb_usuarios u ON u.id_usuario = e.id_usuario
    WHERE e.id_cliente = :id
    ORDER BY e.fecha_examen DESC, e.id_examen DESC
  ");
  $q->execute([':id' => $id_cliente]);
  $examenes = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $examenes = [];
}
