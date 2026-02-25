<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'examenes.actualizar', $URL . '/');

require_post();
csrf_verify();

try {
  $id_cliente = input_int('id_cliente');
  $nota = input_str('nota');

  if (trim($nota) === '') {
    throw new RuntimeException('Debe escribir una nota.');
  }

  
  // Detectar tabla real de notas usando information_schema (más confiable que SHOW TABLES LIKE con PDO)
  $tblNotas = null;
  foreach (['tb_notas_optometrista','tb_notas_optometristas','tb_notas'] as $t) {
    $qq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
    $qq->execute([':t' => $t]);
    if ((int)$qq->fetchColumn() > 0) { $tblNotas = $t; break; }
  }
  if (!$tblNotas) {
    throw new RuntimeException('Tabla de notas no existe (tb_notas_optometrista).');
  }

  // Detectar columna de texto (por si el esquema cambia)
  $colNota = 'nota';
  foreach (['nota','detalle','descripcion','contenido'] as $c) {
    $qq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c");
    $qq->execute([':t' => $tblNotas, ':c' => $c]);
    if ((int)$qq->fetchColumn() > 0) { $colNota = $c; break; }
  }

  // id_usuario es opcional según esquema
  $hasUser = false;
  $qq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = 'id_usuario'");
  $qq->execute([':t' => $tblNotas]);
  $hasUser = ((int)$qq->fetchColumn() > 0);

  if ($hasUser) {
    $stmt = $pdo->prepare("INSERT INTO `$tblNotas` (id_cliente, id_usuario, `$colNota`) VALUES (:id_cliente, :id_usuario, :nota)");
    $stmt->execute([
      ':id_cliente' => $id_cliente,
      ':id_usuario' => $id_usuario_sesion ?? null,
      ':nota' => $nota,
    ]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO `$tblNotas` (id_cliente, `$colNota`) VALUES (:id_cliente, :nota)");
    $stmt->execute([
      ':id_cliente' => $id_cliente,
      ':nota' => $nota,
    ]);
  }

  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
  }

  ensure_session();
  $_SESSION['mensaje'] = 'Nota agregada.';
  $_SESSION['icono'] = 'success';
  header('Location: ' . $URL . '/clientes/show.php?id=' . $id_cliente . '&tab=notas');
  exit;

} catch (Throwable $e) {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  if ($isAjax) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
  }
  ensure_session();
  $_SESSION['mensaje'] = $e->getMessage();
  $_SESSION['icono'] = 'error';
  header('Location: ' . $URL . '/clientes');
  exit;
}
