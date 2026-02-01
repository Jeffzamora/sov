<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM tb_clientes WHERE id_cliente = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cliente) {
  ensure_session();
  $_SESSION['mensaje'] = 'Cliente no encontrado';
  $_SESSION['icono'] = 'error';
  header('Location: ' . $URL . '/clientes');
  exit;
}

// -----------------------------
// Expediente Óptica (robusto)
// - Detección por information_schema (más fiable que SHOW TABLES)
// - Diagnóstico con ?debug=1
// -----------------------------

$debug = isset($_GET['debug']) && (string)$_GET['debug'] === '1';

function safe_scalar(PDO $pdo, string $sql): ?string
{
  try {
    $v = $pdo->query($sql)->fetchColumn();
    return $v === false ? null : (string)$v;
  } catch (Throwable $e) {
    return null;
  }
}

function table_exists(PDO $pdo, string $table): bool
{
  // 1) information_schema (case-insensitive)
  try {
    $q = $pdo->prepare(
      'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND LOWER(table_name) = LOWER(:t) LIMIT 1'
    );
    $q->execute([':t' => $table]);
    if ($q->fetchColumn()) return true;
  } catch (Throwable $e) {
    // ignore
  }

  // 2) fallback SHOW TABLES
  try {
    $q = $pdo->prepare('SHOW TABLES LIKE :t');
    $q->execute([':t' => $table]);
    return (bool)$q->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

function column_exists(PDO $pdo, string $table, string $col): bool
{
  try {
    $q = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :c");
    $q->execute([':c' => $col]);
    return (bool)$q->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

function pick_existing_table(PDO $pdo, array $candidates): ?string
{
  foreach ($candidates as $t) {
    if (table_exists($pdo, $t)) return $t;
  }
  return null;
}

function pick_existing_column(PDO $pdo, string $table, array $candidates): ?string
{
  foreach ($candidates as $c) {
    if (column_exists($pdo, $table, $c)) return $c;
  }
  return null;
}


$examenes = [];
$recetas = [];
$notas = [];

// Detectar tablas (compatibilidad con variaciones)
$tblExamenes = pick_existing_table($pdo, ['tb_examenes_optometricos', 'tb_examenes_optometrico', 'tb_examenes']);
$tblRecetas  = pick_existing_table($pdo, ['tb_recetas_opticas', 'tb_recetas_optica', 'tb_recetas']);
$tblNotas    = pick_existing_table($pdo, ['tb_notas_optometrista', 'tb_notas_optometristas', 'tb_notas']);

// Columnas claves (por si el esquema difiere)
$colClienteEx = $tblExamenes ? (pick_existing_column($pdo, $tblExamenes, ['id_cliente', 'cliente_id', 'idcliente']) ?? 'id_cliente') : null;
$colClienteRe = $tblRecetas  ? (pick_existing_column($pdo, $tblRecetas,  ['id_cliente', 'cliente_id', 'idcliente']) ?? 'id_cliente') : null;

$recetasTieneIdExamen = $tblRecetas ? (pick_existing_column($pdo, $tblRecetas, ['id_examen']) !== null) : false;
$colClienteNo = $tblNotas    ? (pick_existing_column($pdo, $tblNotas,    ['id_cliente', 'cliente_id', 'idcliente']) ?? 'id_cliente') : null;

// Para Resumen (últimos registros)
$ultimo_examen = null;
$ultima_receta = null;
$ultima_nota = null;

if ($tblExamenes) {
  $colFecha = pick_existing_column($pdo, $tblExamenes, ['fecha_examen', 'fecha', 'fyh_creacion', 'created_at']) ?? 'fecha_examen';
  $colId    = pick_existing_column($pdo, $tblExamenes, ['id_examen', 'id', 'examen_id']) ?? 'id_examen';
  $q = $pdo->prepare("SELECT * FROM `$tblExamenes` WHERE `$colClienteEx` = :id ORDER BY `$colFecha` DESC, `$colId` DESC");
  $q->execute([':id' => $id]);
  $examenes = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];


  // Mapear receta_id por examen (Opción A: 1 receta por examen)
  if ($tblRecetas && $recetasTieneIdExamen && !empty($examenes)) {
    $colIdExForMap = pick_existing_column($pdo, $tblExamenes, ['id_examen', 'id', 'examen_id']) ?? 'id_examen';
    $colRecetaId = pick_existing_column($pdo, $tblRecetas, ['id_receta', 'id', 'receta_id']) ?? 'id_receta';

    $ids = [];
    foreach ($examenes as $row) {
      $ids[] = (int)($row[$colIdExForMap] ?? 0);
    }
    $ids = array_values(array_filter(array_unique($ids)));
    if (!empty($ids)) {
      $placeholders = implode(',', array_fill(0, count($ids), '?'));
      $qr = $pdo->prepare("SELECT id_examen, `$colRecetaId` AS id_receta FROM `$tblRecetas` WHERE id_examen IN ($placeholders)");
      $qr->execute($ids);
      $map = [];
      while ($rr = $qr->fetch(PDO::FETCH_ASSOC)) {
        $map[(int)$rr['id_examen']] = (int)$rr['id_receta'];
      }
      // inyectar receta_id a cada examen
      foreach ($examenes as &$row) {
        $exId = (int)($row[$colIdExForMap] ?? 0);
        if ($exId > 0 && isset($map[$exId])) $row['id_receta'] = $map[$exId];
      }
      unset($row);
    }
  }
  $ultimo_examen = $examenes[0] ?? null;
}

if ($tblRecetas) {
  $colFecha = pick_existing_column($pdo, $tblRecetas, ['fecha_receta', 'fecha', 'fyh_creacion', 'created_at']) ?? 'fecha_receta';
  $colId    = pick_existing_column($pdo, $tblRecetas, ['id_receta', 'id', 'receta_id']) ?? 'id_receta';
  $q = $pdo->prepare("SELECT * FROM `$tblRecetas` WHERE `$colClienteRe` = :id ORDER BY `$colFecha` DESC, `$colId` DESC");
  $q->execute([':id' => $id]);
  $recetas = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $ultima_receta = $recetas[0] ?? null;
}

if ($tblNotas) {
  $colFecha = pick_existing_column($pdo, $tblNotas, ['fecha_nota', 'fecha', 'fyh_creacion', 'created_at']) ?? 'fecha_nota';
  $colId    = pick_existing_column($pdo, $tblNotas, ['id_nota', 'id', 'nota_id']) ?? 'id_nota';

  // join usuario si existe columna id_usuario y campo email en tb_usuarios
  $hasUserCol = column_exists($pdo, $tblNotas, 'id_usuario');
  $hasEmail = table_exists($pdo, 'tb_usuarios') ? column_exists($pdo, 'tb_usuarios', 'email') : false;

  if ($hasUserCol && $hasEmail) {
    $q = $pdo->prepare("SELECT n.*, u.email AS usuario_email
                        FROM `$tblNotas` n
                        LEFT JOIN tb_usuarios u ON u.id_usuario = n.id_usuario
                        WHERE n.`$colClienteNo` = :id
                        ORDER BY n.`$colFecha` DESC, n.`$colId` DESC");
  } else {
    $q = $pdo->prepare("SELECT * FROM `$tblNotas` WHERE `$colClienteNo` = :id ORDER BY `$colFecha` DESC, `$colId` DESC");
  }
  $q->execute([':id' => $id]);
  $notas = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $ultima_nota = $notas[0] ?? null;
}

function h(?string $v): string
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

?>
<script>
  const CSRF = <?php echo json_encode(csrf_token()); ?>;
</script>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-8 col-12">
          <h1 class="m-0">Expediente de cliente</h1>
          <div class="text-muted" style="font-size:.95rem;">
            <?php echo h(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? '')); ?>
          </div>
        </div>
        <div class="col-sm-4 col-12 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-secondary" href="<?php echo $URL; ?>/clientes"><i class="fa fa-arrow-left"></i> Volver</a>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <?php if ($debug):
        $dbName = safe_scalar($pdo, 'SELECT DATABASE()');
        $dbHost = safe_scalar($pdo, 'SELECT @@hostname');
        $dbPort = safe_scalar($pdo, 'SELECT @@port');
        $dbVer  = safe_scalar($pdo, 'SELECT VERSION()');

        $cntEx = null;
        $cntRe = null;
        $cntNo = null;
        try {
          if ($tblExamenes) {
            $qq = $pdo->prepare("SELECT COUNT(*) FROM `$tblExamenes` WHERE `$colClienteEx`=:id");
            $qq->execute([':id' => $id]);
            $cntEx = (int)$qq->fetchColumn();
          }
        } catch (Throwable $e) {
          $cntEx = 'ERR: ' . $e->getMessage();
        }
        try {
          if ($tblRecetas) {
            $qq = $pdo->prepare("SELECT COUNT(*) FROM `$tblRecetas` WHERE `$colClienteRe`=:id");
            $qq->execute([':id' => $id]);
            $cntRe = (int)$qq->fetchColumn();
          }
        } catch (Throwable $e) {
          $cntRe = 'ERR: ' . $e->getMessage();
        }
        try {
          if ($tblNotas) {
            $qq = $pdo->prepare("SELECT COUNT(*) FROM `$tblNotas` WHERE `$colClienteNo`=:id");
            $qq->execute([':id' => $id]);
            $cntNo = (int)$qq->fetchColumn();
          }
        } catch (Throwable $e) {
          $cntNo = 'ERR: ' . $e->getMessage();
        }
        
      ?>

      <?php endif; ?>

      <div class="row">
        <div class="col-12 col-lg-4">
          <div class="card card-outline card-info">
            <div class="card-header">
              <h3 class="card-title">Datos del cliente</h3>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-5">Documento</dt>
                <dd class="col-7"><?php echo h(($cliente['tipo_documento'] ?? '') . ' ' . ($cliente['numero_documento'] ?? '')); ?></dd>

                <dt class="col-5">Celular</dt>
                <dd class="col-7"><?php echo h($cliente['celular'] ?? ''); ?></dd>

                <dt class="col-5">Email</dt>
                <dd class="col-7"><?php echo h($cliente['email'] ?? ''); ?></dd>

                <dt class="col-5">Dirección</dt>
                <dd class="col-7"><?php echo nl2br(h($cliente['direccion'] ?? '')); ?></dd>

                <dt class="col-5">Creado</dt>
                <dd class="col-7"><?php echo h($cliente['fyh_creacion'] ?? ''); ?></dd>

                <dt class="col-5">Actualizado</dt>
                <dd class="col-7"><?php echo h($cliente['fyh_actualizacion'] ?? ''); ?></dd>
              </dl>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-8">
          <div class="card card-outline card-primary">
            <div class="card-header p-0 border-bottom-0">
              <ul class="nav nav-tabs" id="tabs-expediente" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="tab-resumen" data-toggle="pill" href="#pane-resumen" role="tab" aria-controls="pane-resumen" aria-selected="true">
                    <i class="fas fa-notes-medical"></i> Resumen
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tab-examenes" data-toggle="pill" href="#pane-examenes" role="tab" aria-controls="pane-examenes" aria-selected="false">
                    <i class="fas fa-eye"></i> Exámenes
                    <span class="badge badge-light ml-1"><?php echo (int)count($examenes); ?></span>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tab-recetas" data-toggle="pill" href="#pane-recetas" role="tab" aria-controls="pane-recetas" aria-selected="false">
                    <i class="fas fa-file-prescription"></i> Recetas
                    <span class="badge badge-light ml-1"><?php echo (int)count($recetas); ?></span>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tab-notas" data-toggle="pill" href="#pane-notas" role="tab" aria-controls="pane-notas" aria-selected="false">
                    <i class="fas fa-sticky-note"></i> Notas
                    <span class="badge badge-light ml-1"><?php echo (int)count($notas); ?></span>
                  </a>
                </li>
              </ul>
            </div>

            <div class="card-body">
              <div class="tab-content" id="tabs-expedienteContent">

                <!-- Resumen -->
                <div class="tab-pane fade show active" id="pane-resumen" role="tabpanel" aria-labelledby="tab-resumen">

                  <div class="row">
                    <div class="col-12">
                      <div class="callout callout-info mb-3">
                        <h5 class="mb-1">Último examen y graduación</h5>

                        <div class="d-flex flex-column flex-md-row mt-2">
                          <a class="btn btn-primary btn-sm mr-md-2 mb-2 mb-md-0" href="<?php echo $URL; ?>/clientes/examenes/new.php?id=<?php echo (int)$id; ?>">
                            <i class="fas fa-plus"></i> Registrar examen
                          </a>
                          <button type="button" class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#modal-add-nota">
                            <i class="fas fa-sticky-note"></i> Agregar nota
                          </button>
                        </div>
                        <?php if (!empty($examenes)):
                          $e = $examenes[0];
                        ?>
                          <div class="text-muted" style="font-size:.95rem;">Fecha: <?php echo h($e['fecha_examen'] ?? ''); ?></div>
                          <div class="mt-2">
                            <div class="table-responsive">
                              <table class="table table-sm table-bordered mb-0">
                                <thead>
                                  <tr>
                                    <th></th>
                                    <th>Esfera</th>
                                    <th>Cilindro</th>
                                    <th>Eje</th>
                                    <th>Add</th>
                                    <th>PD</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <tr>
                                    <th style="width:90px;">OD</th>
                                    <td><?php echo h($e['od_esfera'] ?? ''); ?></td>
                                    <td><?php echo h($e['od_cilindro'] ?? ''); ?></td>
                                    <td><?php echo h($e['od_eje'] ?? ''); ?></td>
                                    <td><?php echo h($e['od_add'] ?? ''); ?></td>
                                    <td rowspan="2"><?php echo h($e['pd_lejos'] ?? ''); ?><?php echo ($e['pd_cerca'] ?? '') ? ' / ' . h($e['pd_cerca']) : ''; ?></td>
                                  </tr>
                                  <tr>
                                    <th>OI</th>
                                    <td><?php echo h($e['oi_esfera'] ?? ''); ?></td>
                                    <td><?php echo h($e['oi_cilindro'] ?? ''); ?></td>
                                    <td><?php echo h($e['oi_eje'] ?? ''); ?></td>
                                    <td><?php echo h($e['oi_add'] ?? ''); ?></td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </div>
                          <?php if (!empty($e['notas_optometrista'])): ?>
                            <div class="mt-2"><strong>Notas:</strong> <?php echo nl2br(h($e['notas_optometrista'])); ?></div>
                          <?php endif; ?>
                        <?php else: ?>
                          <div class="text-muted">Aún no hay exámenes registrados para este cliente.</div>
                        <?php endif; ?>
                      </div>

                      <div class="row">
                        <div class="col-12 col-md-6">
                          <div class="card card-outline card-primary">
                            <div class="card-header">
                              <h3 class="card-title"><i class="fas fa-file-prescription"></i> Última receta</h3>
                            </div>
                            <div class="card-body">
                              <?php if ($ultima_receta): ?>
                                <div class="text-muted" style="font-size:.95rem;">Fecha: <?php echo h($ultima_receta['fecha_receta'] ?? ''); ?></div>
                                <div class="mt-2">
                                  <div><strong>Tipo:</strong> <?php echo h($ultima_receta['tipo'] ?? ''); ?></div>
                                  <div class="mt-1"><strong>Detalle:</strong> <?php echo h($ultima_receta['detalle'] ?? ''); ?></div>
                                  <?php if (!empty($ultima_receta['notas'])): ?>
                                    <div class="mt-1"><strong>Notas:</strong> <?php echo nl2br(h($ultima_receta['notas'])); ?></div>
                                  <?php endif; ?>
                                </div>
                              <?php else: ?>
                                <div class="text-muted">Aún no hay recetas para este cliente.</div>
                                <div class="text-muted" style="font-size:.9rem;">Puedes emitir una receta desde un examen en la pestaña <strong>Exámenes</strong>.</div>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>

                        <div class="col-12 col-md-6">
                          <div class="card card-outline card-warning">
                            <div class="card-header">
                              <h3 class="card-title"><i class="fas fa-sticky-note"></i> Última nota</h3>
                            </div>
                            <div class="card-body">
                              <?php if ($ultima_nota): ?>
                                <div class="text-muted" style="font-size:.95rem;">Fecha: <?php echo h($ultima_nota['fecha_nota'] ?? ''); ?></div>
                                <div class="mt-2" style="white-space:pre-wrap;"><?php echo h($ultima_nota['nota'] ?? ''); ?></div>
                                <?php if (!empty($ultima_nota['usuario_email'])): ?>
                                  <div class="text-muted mt-2" style="font-size:.9rem;">Por: <?php echo h($ultima_nota['usuario_email']); ?></div>
                                <?php endif; ?>
                              <?php else: ?>
                                <div class="text-muted">Aún no hay notas.</div>
                                <button type="button" class="btn btn-warning btn-sm mt-2" data-toggle="modal" data-target="#modal-add-nota">
                                  <i class="fas fa-plus"></i> Agregar nota
                                </button>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12 col-md-6">
                      <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-eye"></i></span>
                        <div class="info-box-content">
                          <span class="info-box-text">Total exámenes</span>
                          <span class="info-box-number"><?php echo count($examenes); ?></span>
                        </div>
                      </div>
                    </div>
                    <div class="col-12 col-md-6">
                      <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-file-prescription"></i></span>
                        <div class="info-box-content">
                          <span class="info-box-text">Total recetas</span>
                          <span class="info-box-number"><?php echo count($recetas); ?></span>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>

                <!-- Exámenes -->
                <div class="tab-pane fade" id="pane-examenes" role="tabpanel" aria-labelledby="tab-examenes">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Historial de exámenes</h5>
                    <a class="btn btn-primary btn-sm" href="<?php echo $URL; ?>/clientes/examenes/new.php?id=<?php echo (int)$id; ?>"><i class="fas fa-plus"></i> Registrar examen</a>
                  </div>

                  <?php if (!($tblExamenes !== null)): ?>
                    <div class="alert alert-warning mb-0">
                      La tabla <code>tb_examenes_optometricos</code> no existe aún. Ejecuta la migración <code>db/migrations/030_expediente_optica.sql</code>.
                    </div>
                  <?php elseif (empty($examenes)): ?>
                    <div class="text-muted">Sin registros.</div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table table-bordered table-sm">
                        <thead>
                          <tr>
                            <th>Fecha</th>
                            <th>OD (Esf/Cil/Eje/Add)</th>
                            <th>OI (Esf/Cil/Eje/Add)</th>
                            <th>PD (lejos/cerca)</th>
                            <th>Notas</th>
                            <th style="width:160px;">Acciones</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($examenes as $e): ?>
                            <tr>
                              <td><?php echo h($e['fecha_examen'] ?? ''); ?></td>
                              <td><?php echo h(($e['od_esfera'] ?? '') . ' / ' . ($e['od_cilindro'] ?? '') . ' / ' . ($e['od_eje'] ?? '') . ' / ' . ($e['od_add'] ?? '')); ?></td>
                              <td><?php echo h(($e['oi_esfera'] ?? '') . ' / ' . ($e['oi_cilindro'] ?? '') . ' / ' . ($e['oi_eje'] ?? '') . ' / ' . ($e['oi_add'] ?? '')); ?></td>
                              <td class="text-nowrap"><?php echo h($e['pd_lejos'] ?? ''); ?><?php echo ($e['pd_cerca'] ?? '') ? ' / ' . h($e['pd_cerca']) : ''; ?></td>
                              <td style="max-width:320px;"><?php echo nl2br(h($e['notas_optometrista'] ?? '')); ?></td>
                              <td>
                                <div class="btn-group btn-group-sm" role="group">
                                  <a class="btn btn-info" href="<?php echo $URL; ?>/clientes/examenes/show.php?id_examen=<?php echo (int)($e['id_examen'] ?? 0); ?>">
                                    <i class="fas fa-eye"></i>
                                  </a>

                                  <?php if (($tblRecetas !== null)): ?>
                                    <?php
                                    $yaEmitida = !empty($e['id_receta']);
                                    ?>
                                    <?php if (!$recetasTieneIdExamen): ?>
                                      <span class="text-muted" title="Falta columna id_examen en recetas. Aplica migración para bloquear emisiones duplicadas.">
                                        <i class="fas fa-info-circle"></i>
                                      </span>
                                    <?php elseif ($yaEmitida): ?>
                                      <a class="btn btn-outline-success" title="Ver receta emitida"
                                        href="<?php echo $URL; ?>/recetas/show.php?id=<?php echo (int)$e['id_receta']; ?>">
                                        <i class="fas fa-eye"></i>
                                      </a>
                                    <?php else: ?>
                                      <form class="frm-emitir-receta" action="<?php echo $URL; ?>/app/controllers/recetas/emitir_desde_examen.php" method="post" style="display:inline;">
                                        <input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
                                        <input type="hidden" name="id_examen" value="<?php echo (int)($e['id_examen'] ?? 0); ?>">
                                        <input type="hidden" name="id_cliente" value="<?php echo (int)$id; ?>">
                                        <button type="submit" class="btn btn-primary" title="Emitir receta desde este examen">
                                          <i class="fas fa-file-prescription"></i>
                                        </button>
                                      </form>
                                    <?php endif; ?>
                                  <?php endif; ?>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Recetas -->
                <div class="tab-pane fade" id="pane-recetas" role="tabpanel" aria-labelledby="tab-recetas">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Historial de recetas</h5>
                    <span class="text-muted" style="font-size:.9rem;">Tip: emite una receta desde la pestaña <strong>Exámenes</strong>.</span>
                  </div>

                  <?php if (!($tblRecetas !== null)): ?>
                    <div class="alert alert-warning mb-0">
                      La tabla <code>tb_recetas_opticas</code> no existe aún. Ejecuta la migración <code>db/migrations/030_expediente_optica.sql</code>.
                    </div>
                  <?php elseif (empty($recetas)): ?>
                    <div class="text-muted">Sin registros.</div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table table-bordered table-sm">
                        <thead>
                          <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Vence</th>
                            <th>Detalle</th>
                            <th>Notas</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($recetas as $r): ?>
                            <tr>
                              <td><?php echo h($r['fecha_receta'] ?? ''); ?></td>
                              <td><?php echo h($r['tipo'] ?? ''); ?></td>
                              <td><?php echo h($r['vence_en'] ?? ''); ?></td>
                              <td><?php echo h($r['detalle'] ?? ''); ?></td>
                              <td style="max-width:320px;"><?php echo nl2br(h($r['notas'] ?? '')); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Notas -->
                <div class="tab-pane fade" id="pane-notas" role="tabpanel" aria-labelledby="tab-notas">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Notas del optometrista</h5>
                    <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-add-nota">
                      <i class="fas fa-plus"></i> Agregar nota
                    </button>
                  </div>

                  <?php if (!($tblNotas !== null)): ?>
                    <div class="alert alert-warning mb-0">
                      La tabla <code>tb_notas_optometrista</code> no existe aún. Ejecuta la migración <code>db/migrations/030_expediente_optica.sql</code>.
                    </div>
                  <?php elseif (empty($notas)): ?>
                    <div class="text-muted">Sin registros.</div>
                  <?php else: ?>
                    <div class="timeline">
                      <?php foreach ($notas as $n): ?>
                        <div>
                          <i class="fas fa-sticky-note bg-warning"></i>
                          <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> <?php echo h($n['fecha_nota'] ?? ''); ?></span>
                            <h3 class="timeline-header">
                              <?php echo h($n['usuario_email'] ?? ''); ?>
                            </h3>
                            <div class="timeline-body">
                              <?php echo nl2br(h($n['nota'] ?? '')); ?>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                      <div>
                        <i class="fas fa-clock bg-gray"></i>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal: Agregar nota -->
<div class="modal fade" id="modal-add-nota" tabindex="-1" role="dialog" aria-labelledby="modalAddNotaLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddNotaLabel"><i class="fas fa-sticky-note"></i> Agregar nota</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group mb-0">
          <label for="nota_text" class="mb-1">Nota</label>
          <textarea id="nota_text" class="form-control" rows="5" placeholder="Escribe una nota..."></textarea>
          <small class="text-muted">Se guardará en el expediente del cliente.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btn_guardar_nota"><i class="fas fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
  $('#btn_guardar_nota').click(function() {
    var nota = ($('#nota_text').val() || '').trim();
    if (!nota) {
      if (window.SOV) SOV.toast('warning', 'Debe escribir una nota.');
      return;
    }
    if (window.SOV && SOV.ajaxJson) {
      SOV.ajaxJson({
          url: '<?php echo $URL; ?>/app/controllers/notas/create.php',
          method: 'POST',
          data: {
            _csrf: CSRF,
            id_cliente: <?php echo (int)$id; ?>,
            nota: nota
          }
        })
        .done(function(resp) {
          if (resp && resp.ok) {
            $('#modal-add-nota').modal('hide');
            window.location.href = window.location.pathname + '?id=<?php echo (int)$id; ?>&tab=notas';
          } else {
            SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo guardar la nota.');
          }
        })
        .fail(function(xhr) {
          let msg = 'No se pudo guardar la nota.';
          try {
            const j = JSON.parse(xhr.responseText);
            if (j && j.error) msg = j.error;
          } catch (e) {}
          SOV.toast('error', msg);
        });
    }
  });
</script>

<script>
  $(document).on('submit', '.frm-emitir-receta', function(e) {
    if (window.SOV && SOV.ajaxJson) {
      e.preventDefault();
      var $f = $(this);
      SOV.ajaxJson({
          url: $f.attr('action'),
          method: 'POST',
          data: $f.serialize()
        })
        .done(function(resp) {
          if (resp && resp.ok) {
            SOV.toast('success', 'Receta emitida.');
            // Recargar para que aparezca en Resumen/Recetas.
            setTimeout(function() {
              window.location.href = window.location.pathname + '?id=<?php echo (int)$id; ?>&tab=recetas';
            }, 350);
          } else {
            SOV.toast('info', 'Listo.');
          }
        })
        .fail(function(xhr) {
          let msg = 'No se pudo emitir.';
          try {
            const j = JSON.parse(xhr.responseText);
            if (j && j.error) msg = j.error;
          } catch (e) {}
          SOV.toast('error', msg);
        });
    }
  });
</script>

<script>
  // UX: limpiar nota cuando se cierra el modal
  $('#modal-add-nota').on('hidden.bs.modal', function() {
    $('#nota_text').val('');
  });
</script>


<script>
  (function() {
    try {
      const params = new URLSearchParams(window.location.search);
      const tab = (params.get('tab') || '').toLowerCase();
      if (tab) {
        const map = {
          resumen: '#tab-resumen',
          examenes: '#tab-examenes',
          recetas: '#tab-recetas',
          notas: '#tab-notas'
        };
        const sel = map[tab];
        if (sel && window.jQuery) {
          $(sel).tab('show');
        }
      }
    } catch (e) {}
  })();
</script>