<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'examenes.ver', $URL . '/');
require_once __DIR__ . '/../../layout/parte1.php';

$id_examen = isset($_GET['id_examen']) ? (int)$_GET['id_examen'] : 0;

$q = $pdo->prepare("
  SELECT e.*, c.nombre, c.apellido, c.numero_documento, c.email AS cliente_email
  FROM tb_examenes_optometricos e
  INNER JOIN tb_clientes c ON c.id_cliente = e.id_cliente
  WHERE e.id_examen = :id
  LIMIT 1
");
$q->execute([':id' => $id_examen]);
$e = $q->fetch(PDO::FETCH_ASSOC);

if (!$e) {
  ensure_session();
  $_SESSION['mensaje'] = 'Examen no encontrado';
  $_SESSION['icono'] = 'error';
  header('Location: ' . $URL . '/clientes');
  exit;
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<script>
  const CSRF = <?php echo json_encode(csrf_token()); ?>;
</script>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-12 col-md-8">
          <h1 class="m-0">Examen optométrico</h1>
          <p class="text-muted mb-0">
            Cliente: <strong><?php echo h(($e['nombre'] ?? '').' '.($e['apellido'] ?? '')); ?></strong>
            <?php if (!empty($e['numero_documento'])): ?> · Doc: <?php echo h($e['numero_documento']); ?><?php endif; ?>
          </p>
        </div>
        <div class="col-12 col-md-4 text-md-right mt-2 mt-md-0">
          <a class="btn btn-secondary" href="<?php echo $URL; ?>/clientes/examenes/index.php?id=<?php echo (int)$e['id_cliente']; ?>">
            <i class="fas fa-list"></i> Exámenes
          </a>
          <a class="btn btn-outline-secondary" target="_blank" href="<?php echo $URL; ?>/clientes/examenes/print.php?id_examen=<?php echo (int)$id_examen; ?>">
            <i class="fa fa-print"></i> Imprimir
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-primary card-outline">
        <div class="card-body">
          <div class="row">
            <div class="col-12 col-lg-6">
              <div class="card">
                <div class="card-header"><strong>OD (Derecho)</strong></div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-6">Esfera: <strong><?php echo h($e['od_esfera']); ?></strong></div>
                    <div class="col-6">Cilindro: <strong><?php echo h($e['od_cilindro']); ?></strong></div>
                    <div class="col-6">Eje: <strong><?php echo h($e['od_eje']); ?></strong></div>
                    <div class="col-6">ADD: <strong><?php echo h($e['od_add']); ?></strong></div>
                    <div class="col-6">Prisma: <strong><?php echo h($e['od_prisma']); ?></strong></div>
                    <div class="col-6">Base: <strong><?php echo h($e['od_base']); ?></strong></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-6">
              <div class="card">
                <div class="card-header"><strong>OI (Izquierdo)</strong></div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-6">Esfera: <strong><?php echo h($e['oi_esfera']); ?></strong></div>
                    <div class="col-6">Cilindro: <strong><?php echo h($e['oi_cilindro']); ?></strong></div>
                    <div class="col-6">Eje: <strong><?php echo h($e['oi_eje']); ?></strong></div>
                    <div class="col-6">ADD: <strong><?php echo h($e['oi_add']); ?></strong></div>
                    <div class="col-6">Prisma: <strong><?php echo h($e['oi_prisma']); ?></strong></div>
                    <div class="col-6">Base: <strong><?php echo h($e['oi_base']); ?></strong></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="callout callout-info">
                <div class="row">
                  <div class="col-12 col-md-4">Fecha: <strong><?php echo h($e['fecha_examen']); ?></strong></div>
                  <div class="col-12 col-md-4">PD Lejos: <strong><?php echo h($e['pd_lejos']); ?></strong></div>
                  <div class="col-12 col-md-4">PD Cerca: <strong><?php echo h($e['pd_cerca']); ?></strong></div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="form-group">
                <label>Notas</label>
                <div class="border rounded p-2" style="min-height:60px;">
                  <?php echo nl2br(h($e['notas_optometrista'] ?? '')); ?>
                </div>
              </div>
            </div>

          </div>

          <div class="d-flex flex-column flex-md-row justify-content-end">
            <form id="form-emitir" class="mr-md-2 mb-2 mb-md-0" method="POST" action="<?php echo $URL; ?>/app/controllers/recetas/emitir_desde_examen.php">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="id_examen" value="<?php echo (int)$id_examen; ?>">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-receipt"></i> Emitir receta desde examen
              </button>
            </form>
            <a class="btn btn-secondary" href="<?php echo $URL; ?>/clientes/show.php?id=<?php echo (int)$e['id_cliente']; ?>">
              <i class="fas fa-user"></i> Volver al expediente
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layout/parte2.php'; ?>

<script>
  $('#form-emitir').on('submit', function(e){
    if (window.SOV && SOV.ajaxJson) {
      e.preventDefault();
      const $f = $(this);
      SOV.ajaxJson({url: $f.attr('action'), method: 'POST', data: $f.serialize()})
        .done(function(resp){
          if (resp && resp.ok) {
            SOV.toast('success','Receta emitida correctamente.');
            // Llevar al expediente en pestaña "Recetas" para que el usuario vea el resultado.
            setTimeout(function(){
              window.location.href = '<?php echo $URL; ?>/clientes/show.php?id=<?php echo (int)$e['id_cliente']; ?>&tab=recetas';
            }, 300);
            return;
          }
          SOV.toast('info','Operación completada.');
        })
        .fail(function(xhr){
          let msg='No se pudo emitir la receta.';
          try { const j = JSON.parse(xhr.responseText); if (j && j.error) msg=j.error; } catch(e){}
          SOV.toast('error', msg);
        });
    }
  });
</script>
