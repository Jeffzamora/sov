<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'examenes.ver', $URL . '/');
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

require_once __DIR__ . '/../../app/controllers/examenes/listado_por_cliente.php';

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
          <h1 class="m-0">Exámenes</h1>
          <p class="text-muted mb-0">Cliente: <strong><?php echo h(($cliente['nombre'] ?? '').' '.($cliente['apellido'] ?? '')); ?></strong></p>
        </div>
        <div class="col-12 col-md-4 text-md-right mt-2 mt-md-0">
          <a href="<?php echo $URL; ?>/clientes/examenes/new.php?id=<?php echo (int)$id_cliente; ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Registrar examen
          </a>
          <a href="<?php echo $URL; ?>/clientes/show.php?id=<?php echo (int)$id_cliente; ?>" class="btn btn-secondary">
            <i class="fas fa-user"></i> Expediente
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-outline card-primary">
        <div class="card-body">
          <?php if (empty($examenes)): ?>
            <div class="alert alert-info mb-0">No hay exámenes registrados.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table id="tbl-examenes" class="table table-bordered table-striped table-sm">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>OD</th>
                    <th>OI</th>
                    <th>PD</th>
                    <th>Usuario</th>
                    <th style="width:130px;">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($examenes as $e): ?>
                    <tr>
                      <td><?php echo h($e['fecha_examen'] ?? ''); ?></td>
                      <td><?php echo h(($e['od_esfera'] ?? '').' / '.($e['od_cilindro'] ?? '').' x '.($e['od_eje'] ?? '').' ADD '.($e['od_add'] ?? '')); ?></td>
                      <td><?php echo h(($e['oi_esfera'] ?? '').' / '.($e['oi_cilindro'] ?? '').' x '.($e['oi_eje'] ?? '').' ADD '.($e['oi_add'] ?? '')); ?></td>
                      <td><?php echo h(($e['pd_lejos'] ?? '').' / '.($e['pd_cerca'] ?? '')); ?></td>
                      <td><?php echo h($e['usuario_email'] ?? ''); ?></td>
                      <td class="text-nowrap">
                        <a class="btn btn-info btn-sm" title="Ver" href="<?php echo $URL; ?>/clientes/examenes/show.php?id_examen=<?php echo (int)$e['id_examen']; ?>">
                          <i class="fa fa-eye"></i>
                        </a>
                        <a class="btn btn-secondary btn-sm" title="Imprimir" target="_blank" href="<?php echo $URL; ?>/clientes/examenes/print.php?id_examen=<?php echo (int)$e['id_examen']; ?>">
                          <i class="fa fa-print"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layout/parte2.php'; ?>

<script>
  $(function(){
    if ($.fn.DataTable) {
      $('#tbl-examenes').DataTable({
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        language: { search: "Buscar:" }
      });
    }
  });
</script>
