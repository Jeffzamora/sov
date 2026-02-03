<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/controllers/cajas/_caja_lib.php';

$caja = caja_abierta_actual($pdo);
if (!$caja) {
  redirect($URL . '/cajas', 'No hay una caja abierta.', 'warning');
}

$idCaja = (int)($caja['id_caja'] ?? 0);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-7">
          <h1 class="m-0">Movimientos de Caja</h1>
          <div class="text-muted">Caja #<?php echo (int)$idCaja; ?> · Ingresos/Egresos manuales</div>
        </div>
        <div class="col-sm-5">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/cajas">Caja</a></li>
            <li class="breadcrumb-item active">Movimientos</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php flash_render(); ?>

      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center" style="gap:.75rem;flex-wrap:wrap;">
            <div>
              <strong>Filtros</strong>
            </div>
            <div>
              <a class="btn btn-sm btn-outline-secondary" href="<?php echo $URL; ?>/cajas"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3">
              <label>Desde</label>
              <input id="f_desde" type="date" class="form-control">
            </div>
            <div class="col-md-3">
              <label>Hasta</label>
              <input id="f_hasta" type="date" class="form-control">
            </div>
            <div class="col-md-2">
              <label>Tipo</label>
              <select id="f_tipo" class="form-control">
                <option value="">Todos</option>
                <option value="ingreso">Ingreso</option>
                <option value="egreso">Egreso</option>
              </select>
            </div>
            <div class="col-md-2">
              <label>Método</label>
              <select id="f_metodo" class="form-control">
                <option value="">Todos</option>
                <option value="efectivo">Efectivo</option>
                <option value="deposito">Depósito</option>
              </select>
            </div>
            <div class="col-md-2">
              <label>&nbsp;</label>
              <button id="btn_buscar" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Buscar</button>
            </div>
          </div>

          <div class="table-responsive mt-3">
            <table class="table table-bordered table-hover table-sm">
              <thead class="thead-light">
                <tr>
                  <th style="width:90px;">ID</th>
                  <th style="width:150px;">Fecha</th>
                  <th style="width:90px;">Tipo</th>
                  <th style="width:110px;">Método</th>
                  <th>Concepto</th>
                  <th style="width:150px;">Referencia</th>
                  <th style="width:110px;" class="text-right">Monto</th>
                  <th style="width:110px;">Estado</th>
                  <th style="width:140px;">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbody_movs">
                <tr><td colspan="9" class="text-center text-muted">Cargando...</td></tr>
              </tbody>
            </table>
          </div>

          <small class="text-muted">Tip: por defecto se ocultan movimientos anulados. Puedes verlos activando <b>ver_anulados</b> en el endpoint si lo deseas.</small>
        </div>
      </div>

    </div>
  </section>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
(function(){
  const URL_BASE = <?php echo json_encode($URL); ?>;
  const ID_CAJA = <?php echo (int)$idCaja; ?>;

  const $desde = document.getElementById('f_desde');
  const $hasta = document.getElementById('f_hasta');
  const $tipo = document.getElementById('f_tipo');
  const $metodo = document.getElementById('f_metodo');
  const $btn = document.getElementById('btn_buscar');
  const $tbody = document.getElementById('tbody_movs');

  function money(n){
    return (Number(n)||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  }
  function fmtMonto(tipo, monto){
    const t = String(tipo||'').toLowerCase();
    const n = Math.abs(Number(monto)||0);
    const sign = (t==='egreso') ? '-' : '+';
    return sign + "C$" + money(n);
  }
  function badgeEstado(estado){
    const e = String(estado||'activo').toLowerCase();
    if (e==='anulado') return '<span class="badge badge-danger">Anulado</span>';
    return '<span class="badge badge-success">Activo</span>';
  }

  async function fetchMovs(){
    const params = new URLSearchParams();
    params.set('id_caja', ID_CAJA);
    if ($desde.value) params.set('desde', $desde.value);
    if ($hasta.value) params.set('hasta', $hasta.value);
    if ($tipo.value) params.set('tipo', $tipo.value);
    if ($metodo.value) params.set('metodo', $metodo.value);

    $tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Cargando...</td></tr>';

    const res = await fetch(URL_BASE + '/app/controllers/cajas/movimientos_json.php?' + params.toString(), {
      headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
    });

    const ct = (res.headers.get('content-type') || '').toLowerCase();
    if (!ct.includes('application/json')) {
      const text = await res.text();
      $tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Respuesta inválida (no JSON). Revisa sesión/errores en PHP.</td></tr>';
      console.error('Respuesta no-JSON:', text);
      return;
    }

    const data = await res.json();
    if (!data.ok){
      $tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">' + (data.error || 'Error') + '</td></tr>';
      return;
    }

    const rows = Array.isArray(data.data) ? data.data : [];
    if (!rows.length){
      $tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Sin movimientos.</td></tr>';
      return;
    }

    $tbody.innerHTML = rows.map(r => {
      const id = r.id_movimiento;
      const estado = String(r.estado||'activo');
      const canAnular = (estado.toLowerCase() !== 'anulado');

      const btnAnular = canAnular ? `\
        <button class="btn btn-sm btn-outline-danger" onclick="anularMov(${id})">\
          <i class="fas fa-ban mr-1"></i>Anular\
        </button>` : '';

      return `\
        <tr>\
          <td>${id}</td>\
          <td>${r.fecha || ''}</td>\
          <td>${r.tipo || ''}</td>\
          <td>${r.metodo_pago || ''}</td>\
          <td>${(r.concepto || '')}</td>\
          <td>${(r.referencia || '')}</td>\
          <td class="text-right mono">$${money(r.monto)}</td>\
          <td>${badgeEstado(estado)}</td>\
          <td>${btnAnular}</td>\
        </tr>`;
    }).join('');
  }

  window.anularMov = async function(id){
    const ok = (typeof Swal !== 'undefined')
      ? (await Swal.fire({title:'Anular movimiento', text:'Esta acción registra anulación para auditoría.', icon:'warning', input:'text', inputLabel:'Motivo (opcional)', showCancelButton:true, confirmButtonText:'Anular', cancelButtonText:'Cancelar'})).isConfirmed
      : confirm('¿Anular este movimiento?');

    if (!ok) return;

    const motivo = (typeof Swal !== 'undefined' && Swal.getInput) ? (Swal.getInput().value || '') : '';

    const form = new FormData();
    form.append('_csrf', <?php echo json_encode(csrf_token()); ?>);
    form.append('id_movimiento', String(id));
    form.append('motivo', motivo);

    const res = await fetch(URL_BASE + '/app/controllers/cajas/anular_movimiento.php', {
      method:'POST',
      body:form,
      headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}
    });

    const ct = (res.headers.get('content-type') || '').toLowerCase();
    if (!ct.includes('application/json')) {
      const text = await res.text();
      console.error('Respuesta no-JSON (anular):', text);
      if (typeof Swal !== 'undefined') Swal.fire('Error', 'Respuesta inválida del servidor (no JSON).', 'error');
      else alert('Respuesta inválida del servidor (no JSON).');
      return;
    }

    const data = await res.json();
    if (!data.ok){
      if (typeof Swal !== 'undefined') Swal.fire('Error', data.error || 'No se pudo anular', 'error');
      else alert(data.error || 'No se pudo anular');
      return;
    }
    if (typeof Swal !== 'undefined') Swal.fire('Listo', 'Movimiento anulado.', 'success');
    fetchMovs();
  }

  $btn.addEventListener('click', fetchMovs);
  fetchMovs();
})();
</script>
