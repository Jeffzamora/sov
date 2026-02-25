<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'citas.actualizar', $URL . '/');
require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/Helpers/db_schema.php';

$hasHorario = sov_table_exists($pdo, 'tb_horario_laboral');

// Cargar horario actual (si existe)
$rows = [];
if ($hasHorario) {
  $q = $pdo->query("SELECT dia_semana, hora_inicio, hora_fin, activo FROM tb_horario_laboral ORDER BY dia_semana ASC");
  $rows = $q->fetchAll() ?: [];
}

// Normalizar a 7 días
$map = [];
foreach ($rows as $r) {
  $map[(int)$r['dia_semana']] = $r;
}
for ($d = 1; $d <= 7; $d++) {
  if (!isset($map[$d])) {
    $map[$d] = ['dia_semana'=>$d,'hora_inicio'=>'08:00:00','hora_fin'=>'17:00:00','activo'=>($d===1?0:1)];
  }
}

function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$dias = [
  1=>'Domingo',
  2=>'Lunes',
  3=>'Martes',
  4=>'Miércoles',
  5=>'Jueves',
  6=>'Viernes',
  7=>'Sábado',
];

?>
<script>const CSRF = <?php echo json_encode(csrf_token()); ?>;</script>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-8 col-12">
          <h1 class="m-0">Configuración de horario</h1>
          <div class="text-muted" style="font-size:.95rem;">Define los días laborables y el rango de atención</div>
        </div>
        <div class="col-sm-4 col-12 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-secondary" href="<?php echo $URL; ?>/citas"><i class="fa fa-arrow-left"></i> Volver</a>
          <button class="btn btn-primary" id="btnGuardarHorario"><i class="fas fa-save"></i> Guardar</button>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-clock"></i> Horario semanal</h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th style="width:160px;">Día</th>
                  <th style="width:120px;">Activo</th>
                  <th style="width:160px;">Hora inicio</th>
                  <th style="width:160px;">Hora fin</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($map as $d => $r): ?>
                  <tr>
                    <td><?php echo h($dias[$d] ?? (string)$d); ?></td>
                    <td>
                      <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input horario-activo" id="act_<?php echo (int)$d; ?>" data-dia="<?php echo (int)$d; ?>" <?php echo ((int)($r['activo'] ?? 0)===1)?'checked':''; ?>>
                        <label class="custom-control-label" for="act_<?php echo (int)$d; ?>">Laborable</label>
                      </div>
                    </td>
                    <td><input type="time" class="form-control form-control-sm horario-inicio" data-dia="<?php echo (int)$d; ?>" value="<?php echo h(substr((string)$r['hora_inicio'],0,5)); ?>"></td>
                    <td><input type="time" class="form-control form-control-sm horario-fin" data-dia="<?php echo (int)$d; ?>" value="<?php echo h(substr((string)$r['hora_fin'],0,5)); ?>"></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="text-muted" style="font-size:.9rem;">Nota: el módulo de disponibilidad usa intervalos de 30 minutos.</div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
(function(){
  $('#btnGuardarHorario').on('click', function(){
    const payload = [];
    for(let d=1; d<=7; d++){
      const activo = $('#act_'+d).is(':checked') ? 1 : 0;
      const hi = ($('.horario-inicio[data-dia="'+d+'"]').val()||'').trim();
      const hf = ($('.horario-fin[data-dia="'+d+'"]').val()||'').trim();
      payload.push({dia_semana:d, activo:activo, hora_inicio:hi, hora_fin:hf});
    }
    if(!(window.SOV && SOV.ajaxJson)) return;
    SOV.ajaxJson({url:'<?php echo $URL; ?>/app/controllers/citas/horario_save.php', method:'POST', data:{_csrf:CSRF, items: JSON.stringify(payload)}})
      .done(resp=>{
        if(resp && resp.ok){ SOV.toast('success','Horario guardado.'); }
        else { SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo guardar.'); }
      })
      .fail(xhr=>{
        let msg='No se pudo guardar.';
        try{const j=JSON.parse(xhr.responseText); if(j&&j.error) msg=j.error;}catch(e){ if(xhr.responseText) msg=xhr.responseText; }
        SOV.toast('error', msg);
      });
  });
})();
</script>
