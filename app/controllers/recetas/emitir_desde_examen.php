<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'examenes.ver', $URL . '/');

require_post();
csrf_verify();

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

try {
  ensure_session();

  $id_examen = input_int('id_examen', true);

  // usuario en sesión (si existe)
  $id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
  $id_usuario = $id_usuario > 0 ? $id_usuario : null;

  // Detectar tabla de exámenes
  $tblExamenes = null;
  foreach (['tb_examenes_optometricos', 'tb_examenes_optometrico', 'tb_examenes'] as $t) {
    $qq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
    $qq->execute([':t' => $t]);
    if ((int)$qq->fetchColumn() > 0) {
      $tblExamenes = $t;
      break;
    }
  }
  if (!$tblExamenes) {
    throw new RuntimeException('Tabla de exámenes no existe (tb_examenes_optometricos).');
  }

  // Detectar PK del examen
  $colIdEx = 'id_examen';
  foreach (['id_examen', 'id', 'examen_id'] as $c) {
    $qq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c");
    $qq->execute([':t' => $tblExamenes, ':c' => $c]);
    if ((int)$qq->fetchColumn() > 0) {
      $colIdEx = $c;
      break;
    }
  }

  // Cargar examen
  $q = $pdo->prepare("SELECT * FROM `$tblExamenes` WHERE `$colIdEx` = :id LIMIT 1");
  $q->execute([':id' => $id_examen]);
  $e = $q->fetch(PDO::FETCH_ASSOC);
  if (!$e) throw new RuntimeException('Examen no encontrado.');

  // Detectar tabla recetas
  $tblRecetas = null;
  foreach (['tb_recetas_opticas', 'tb_recetas_optica', 'tb_recetas'] as $t) {
    $qq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
    $qq->execute([':t' => $t]);
    if ((int)$qq->fetchColumn() > 0) {
      $tblRecetas = $t;
      break;
    }
  }
  if (!$tblRecetas) throw new RuntimeException('Tabla de recetas no existe.');

  // Opción A: requiere id_examen en recetas
  $qq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = 'id_examen'");
  $qq->execute([':t' => $tblRecetas]);
  $hasIdExamenRec = ((int)$qq->fetchColumn() > 0);
  if (!$hasIdExamenRec) {
    throw new RuntimeException("La tabla de recetas no tiene la columna id_examen. Aplica: ALTER TABLE `$tblRecetas` ADD COLUMN id_examen INT NULL; y crea UNIQUE(id_examen).");
  }

  // Si ya existe receta para este examen => 409
  $chk = $pdo->prepare("SELECT id_receta FROM `$tblRecetas` WHERE id_examen = :id LIMIT 1");
  $chk->execute([':id' => $id_examen]);
  $exist = $chk->fetch(PDO::FETCH_ASSOC);
  if ($exist) {
    if ($isAjax) {
      http_response_code(409);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'Este examen ya tiene una receta emitida.', 'id_receta' => (int)$exist['id_receta']]);
      exit;
    }
    $_SESSION['mensaje'] = 'Este examen ya tiene una receta emitida.';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $URL . '/clientes/examenes/show.php?id_examen=' . $id_examen);
    exit;
  }

  // Detectar si recetas guarda snapshot (od_esfera)
  $qq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = 'od_esfera'");
  $qq->execute([':t' => $tblRecetas]);
  $hasSnapshot = ((int)$qq->fetchColumn() > 0);

  // Columna vence_en (si existe)
  $qq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = 'vence_en'");
  $qq->execute([':t' => $tblRecetas]);
  $hasVence = ((int)$qq->fetchColumn() > 0);

  // Datos base
  $id_cliente = (int)($e['id_cliente'] ?? ($e['cliente_id'] ?? 0));
  if ($id_cliente <= 0) throw new RuntimeException('El examen no tiene id_cliente.');

  $fechaEx = $e['fecha_examen'] ?? ($e['fecha'] ?? ($e['fyh_creacion'] ?? ($e['created_at'] ?? '')));
  $detalle = 'Receta emitida desde examen ' . ($fechaEx ?: '');

  // Resumen para esquema simple
  $fmt = function ($v): string {
    $v = $v === null ? '' : trim((string)$v);
    return $v;
  };
  $od = trim($fmt($e['od_esfera'] ?? '') . ' ' . $fmt($e['od_cilindro'] ?? '') . ' x ' . $fmt($e['od_eje'] ?? '') . ' add ' . $fmt($e['od_add'] ?? ''));
  $oi = trim($fmt($e['oi_esfera'] ?? '') . ' ' . $fmt($e['oi_cilindro'] ?? '') . ' x ' . $fmt($e['oi_eje'] ?? '') . ' add ' . $fmt($e['oi_add'] ?? ''));
  $pd = trim($fmt($e['pd_lejos'] ?? '') . ' / ' . $fmt($e['pd_cerca'] ?? ''));
  $notaResumen = trim('OD ' . $od . ' | OI ' . $oi . ' | PD ' . $pd);

  // INSERT según esquema
  if ($hasSnapshot) {
    $sql = "
      INSERT INTO `$tblRecetas`
        (id_cliente, id_examen, id_usuario, fecha_receta, tipo,
         od_esfera, od_cilindro, od_eje, od_add,
         oi_esfera, oi_cilindro, oi_eje, oi_add,
         pd_lejos, pd_cerca, detalle, notas" . ($hasVence ? ", vence_en" : "") . ")
      VALUES
        (:id_cliente, :id_examen, :id_usuario, CURDATE(), 'LENTES',
         :od_esfera, :od_cilindro, :od_eje, :od_add,
         :oi_esfera, :oi_cilindro, :oi_eje, :oi_add,
         :pd_lejos, :pd_cerca, :detalle, :notas" . ($hasVence ? ", DATE_ADD(CURDATE(), INTERVAL 1 YEAR)" : "") . ")
    ";
    $stmt = $pdo->prepare($sql);

    try {
      $stmt->execute([
        ':id_cliente' => $id_cliente,
        ':id_examen'  => $id_examen,
        ':id_usuario' => $id_usuario,

        ':od_esfera'  => $e['od_esfera'] ?? null,
        ':od_cilindro' => $e['od_cilindro'] ?? null,
        ':od_eje'     => $e['od_eje'] ?? null,
        ':od_add'     => $e['od_add'] ?? null,

        ':oi_esfera'  => $e['oi_esfera'] ?? null,
        ':oi_cilindro' => $e['oi_cilindro'] ?? null,
        ':oi_eje'     => $e['oi_eje'] ?? null,
        ':oi_add'     => $e['oi_add'] ?? null,

        ':pd_lejos'   => $e['pd_lejos'] ?? null,
        ':pd_cerca'   => $e['pd_cerca'] ?? null,

        ':detalle'    => $detalle,
        ':notas'      => $e['notas_optometrista'] ?? ($e['notas'] ?? null),
      ]);
    } catch (PDOException $pe) {
      if (($pe->getCode() ?? '') === '23000') {
        if ($isAjax) {
          http_response_code(409);
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['ok' => false, 'error' => 'Este examen ya tiene una receta emitida.']);
          exit;
        }
        $_SESSION['mensaje'] = 'Este examen ya tiene una receta emitida.';
        $_SESSION['icono'] = 'warning';
        header('Location: ' . $URL . '/clientes/examenes/show.php?id_examen=' . $id_examen);
        exit;
      }
      throw $pe;
    }
  } else {
    // Esquema simple
    $sql = "
      INSERT INTO `$tblRecetas`
        (id_cliente, id_examen, id_usuario, fecha_receta, tipo" . ($hasVence ? ", vence_en" : "") . ", detalle, notas)
      VALUES
        (:id_cliente, :id_examen, :id_usuario, CURDATE(), 'LENTES'" . ($hasVence ? ", DATE_ADD(CURDATE(), INTERVAL 1 YEAR)" : "") . ", :detalle, :notas)
    ";
    $stmt = $pdo->prepare($sql);

    try {
      $stmt->execute([
        ':id_cliente' => $id_cliente,
        ':id_examen'  => $id_examen,
        ':id_usuario' => $id_usuario,
        ':detalle'    => $detalle,
        ':notas'      => ($e['notas_optometrista'] ?? ($e['notas'] ?? null)) ?: ($notaResumen ?: null),
      ]);
    } catch (PDOException $pe) {
      if (($pe->getCode() ?? '') === '23000') {
        if ($isAjax) {
          http_response_code(409);
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['ok' => false, 'error' => 'Este examen ya tiene una receta emitida.']);
          exit;
        }
        $_SESSION['mensaje'] = 'Este examen ya tiene una receta emitida.';
        $_SESSION['icono'] = 'warning';
        header('Location: ' . $URL . '/clientes/examenes/show.php?id_examen=' . $id_examen);
        exit;
      }
      throw $pe;
    }
  }

  $id_receta = (int)$pdo->lastInsertId();

  if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'id_receta' => $id_receta]);
    exit;
  }

  $_SESSION['mensaje'] = 'Receta emitida correctamente.';
  $_SESSION['icono'] = 'success';
  header('Location: ' . $URL . '/clientes/examenes/show.php?id_examen=' . $id_examen);
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
