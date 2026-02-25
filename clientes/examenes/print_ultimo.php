<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'examenes.ver', $URL . '/');

$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
  $q = $pdo->prepare("SELECT id_examen FROM tb_examenes_optometricos WHERE id_cliente = :id ORDER BY fecha_examen DESC, id_examen DESC LIMIT 1");
  $q->execute([':id' => $id_cliente]);
  $id_examen = (int)($q->fetchColumn() ?: 0);
  if ($id_examen <= 0) {
    ensure_session();
    $_SESSION['mensaje'] = 'El cliente no tiene exámenes registrados.';
    $_SESSION['icono'] = 'info';
    header('Location: ' . $URL . '/clientes/show.php?id=' . $id_cliente);
    exit;
  }
  header('Location: ' . $URL . '/clientes/examenes/print.php?id_examen=' . $id_examen);
  exit;
} catch (Throwable $e) {
  ensure_session();
  $_SESSION['mensaje'] = 'No se pudo obtener el último examen.';
  $_SESSION['icono'] = 'error';
  header('Location: ' . $URL . '/clientes/show.php?id=' . $id_cliente);
  exit;
}
