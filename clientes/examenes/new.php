<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../layout/sesion.php';
require_once __DIR__ . '/../../layout/parte1.php';

$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM tb_clientes WHERE id_cliente = :id LIMIT 1");
$stmt->execute([':id' => $id_cliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
  ensure_session();
  $_SESSION['mensaje'] = 'Cliente no encontrado';
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
          <h1 class="m-0">Nuevo examen optométrico</h1>
          <p class="text-muted mb-0">Cliente: <strong><?php echo h(($cliente['nombre'] ?? '').' '.($cliente['apellido'] ?? '')); ?></strong></p>
        </div>
        <div class="col-12 col-md-4 text-md-right mt-2 mt-md-0">
          <a href="<?php echo $URL; ?>/clientes/show.php?id=<?php echo (int)$id_cliente; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-primary card-outline">
        <div class="card-body">
          <form id="form-examen" method="POST" action="<?php echo $URL; ?>/app/controllers/examenes/create.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id_cliente" value="<?php echo (int)$id_cliente; ?>">

            <div class="row">
              <div class="col-12 col-md-4">
                <div class="form-group">
                  <label>Fecha de examen</label>
                  <input type="date" name="fecha_examen" disabled class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                <input type="hidden" name="fecha_examen" value="<?php echo date('Y-m-d'); ?>">
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group">
                  <label>PD Lejos (mm)</label>
                  <input type="number" step="0.01" name="pd_lejos" class="form-control" placeholder="Ej: 62.00">
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group">
                  <label>PD Cerca (mm)</label>
                  <input type="number" step="0.01" name="pd_cerca" class="form-control" placeholder="Ej: 60.00">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12 col-lg-6">
                <div class="card">
                  <div class="card-header">
                    <strong>OD (Ojo Derecho)</strong>
                  </div>
                  <div class="card-body">
                    <div class="form-row">
                      <div class="form-group col-6 col-md-3">
                        <label>Esfera</label>
                        <input type="number" step="0.25" name="od_esfera" class="form-control" placeholder="SPH">
                      </div>
                      <div class="form-group col-6 col-md-3">
                        <label>Cilindro</label>
                        <input type="number" step="0.25" name="od_cilindro" class="form-control" placeholder="CYL">
                      </div>
                      <div class="form-group col-6 col-md-3">
                        <label>Eje</label>
                        <input type="number" name="od_eje" class="form-control" placeholder="0-180" min="0" max="180">
                      </div>
                      <div class="form-group col-6 col-md-3">
                        <label>ADD</label>
                        <input type="number" step="0.25" name="od_add" class="form-control" placeholder="ADD">
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="form-group col-6 col-md-3">
                        <label>Prisma</label>
                        <input type="number" step="0.25" name="od_prisma" class="form-control" placeholder="Δ">
                      </div>
                      <div class="form-group col-6 col-md-3">
                        <label>Base</label>
                        <input type="text" name="od_base" class="form-control" placeholder="IN/OUT/UP/DN">
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12 col-lg-6">
                <div class="card">
                  <div class="card-header">
                    <strong>OI (Ojo Izquierdo)</strong>
                  </div>
                  <div class="card-body">
                    <div class="form-row">
                      <div class="form-group col-6 col-md-3">
                        <label>Esfera</label>
                        <input type="number" step="0.25" name="oi_esfera" class="form-control" placeholder="SPH">
                      </div>
                      <div class="form-group col-6 col-md-3">
                        <label>Cilindro</label>
                        <input type="number" step="0.25" name="oi_cilindro" class="form-control" placeholder="CYL">
                      </div>
                      <div class="form-group col-6 col-md-3">
                        <label>Eje</label>
                        <input type="number" name="oi_eje" class="form-control" placeholder="0-180" min="0" max="180">
                      </div>
                      <div class="form-group col-6 col-md-3">
                        <label>ADD</label>
                        <input type="number" step="0.25" name="oi_add" class="form-control" placeholder="ADD">
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="form-group col-6 col-md-3">
                        <label>Prisma</label>
                        <input type="number" step="0.25" name="oi_prisma" class="form-control" placeholder="Δ">
                      </div>
                      <div class="form-group col-6 col-md-3">
                        <label>Base</label>
                        <input type="text" name="oi_base" class="form-control" placeholder="IN/OUT/UP/DN">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Notas del optometrista</label>
              <textarea name="notas_optometrista" class="form-control" rows="3" placeholder="Observaciones, recomendaciones, etc."></textarea>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-end">
              <a class="btn btn-secondary mb-2 mb-md-0 mr-md-2" href="<?php echo $URL; ?>/clientes/show.php?id=<?php echo (int)$id_cliente; ?>">
                Cancelar
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar examen
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layout/parte2.php'; ?>

<script>
  // Mejor UX: enviar por AJAX para mostrar loader + evitar doble envío
  $('#form-examen').on('submit', function(e){
    if (window.SOV && SOV.ajaxJson) {
      e.preventDefault();
      const $f = $(this);
      // Nota: el backend ya valida formato/rangos cuando se ingresan valores.
      // Aquí permitimos guardar exámenes parciales (por ejemplo, solo PD o solo OD/OI).

      const data = new FormData(this);
      SOV.ajaxJson({url: $f.attr('action'), method: 'POST', data: data})
        .done(function(resp){
          if (resp && resp.ok && resp.redirect) {
            window.location.href = resp.redirect;
            return false;
          }
          window.location.href = '<?php echo $URL; ?>/clientes/show.php?id=<?php echo (int)$id_cliente; ?>';
        })
        .fail(function(xhr){
          let msg = 'No se pudo guardar el examen.';
          try { const j = JSON.parse(xhr.responseText); if (j && j.error) msg = j.error; } catch(e){}
          SOV.warnModal(msg, 'No se pudo guardar');
        });
    }
  });
</script>