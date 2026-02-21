<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/Helpers/db_schema.php';

// Verificar tablas necesarias; no romper la vista si faltan.
$hasCitas   = sov_table_exists($pdo, 'tb_citas');
$hasHorario = sov_table_exists($pdo, 'tb_horario_laboral');
$hasBloq    = sov_table_exists($pdo, 'tb_citas_bloqueos');

function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<script>const CSRF = <?php echo json_encode(csrf_token()); ?>;</script>

<!-- FullCalendar (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">

<!-- Select2 (AdminLTE local) -->
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-8 col-12">
          <h1 class="m-0">Citas</h1>
          <div class="text-muted" style="font-size:.95rem;">Agenda de citas y bloqueos</div>
        </div>
        <div class="col-sm-4 col-12 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-outline-primary" href="<?php echo $URL; ?>/citas/config.php"><i class="fas fa-cog"></i> Configurar horario</a>
          <button class="btn btn-primary" id="btnNuevaCita"><i class="fas fa-plus"></i> Nueva cita</button>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="row">
        <div class="col-12 col-lg-8">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title"><i class="far fa-calendar"></i> Calendario</h3>
            </div>
            <div class="card-body">
              <div id="calendar"></div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <div class="card card-outline card-info">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-list"></i> Agenda del día</h3>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <div class="text-muted" style="font-size:.9rem;">Fecha</div>
                  <div id="agendaFecha" style="font-weight:600;"></div>
                </div>
                <button class="btn btn-sm btn-primary" id="btnNuevaCitaDia"><i class="fas fa-plus"></i> Agendar</button>
              </div>
              <div id="agendaList" class="text-muted">Seleccione un día en el calendario.</div>
            </div>
          </div>

          <div class="card card-outline card-warning">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-ban"></i> Bloquear fecha</h3>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="mb-1">Fecha</label>
                <input type="date" class="form-control" id="bloqFecha">
              </div>
              <div class="form-row">
                <div class="form-group col-6">
                  <label class="mb-1">Inicio (opcional)</label>
                  <input type="time" class="form-control" id="bloqInicio">
                </div>
                <div class="form-group col-6">
                  <label class="mb-1">Fin (opcional)</label>
                  <input type="time" class="form-control" id="bloqFin">
                </div>
              </div>
              <div class="form-group">
                <label class="mb-1">Motivo</label>
                <input type="text" class="form-control" id="bloqMotivo" placeholder="Ej: Reunión, feriado, mantenimiento">
              </div>
              <button class="btn btn-warning" id="btnGuardarBloqueo"><i class="fas fa-save"></i> Guardar bloqueo</button>
            </div>
          </div>

          <div class="card card-outline card-success">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-bell"></i> Próximas citas</h3>
              <div class="card-tools">
                <button class="btn btn-tool" id="btnReloadProximas" title="Recargar"><i class="fas fa-sync"></i></button>
              </div>
            </div>
            <div class="card-body">
              <div class="text-muted" style="font-size:.9rem;">Muestra las próximas citas programadas (hoy en adelante).</div>
              <div id="proximasRange" class="text-muted" style="font-size:.85rem;"></div>
              <div id="proximasList" class="mt-2 text-muted">Cargando…</div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Nueva cita -->
<div class="modal fade" id="modal-cita" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title"><i class="fas fa-calendar-plus"></i> Agendar cita</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#tab-cliente-existente" role="tab">Cliente existente</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tab-cliente-nuevo" role="tab">Crear cliente</a>
          </li>
        </ul>

        <div class="tab-content pt-3">
          <div class="tab-pane fade show active" id="tab-cliente-existente" role="tabpanel">
            <div class="form-group">
              <label class="mb-1">Cliente</label>
              <select id="selCliente" class="form-control" style="width:100%"></select>
              <small class="text-muted">Escriba para buscar por nombre, apellido, documento, celular o email.</small>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-cliente-nuevo" role="tabpanel">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="mb-1">Nombre</label>
                <input type="text" class="form-control" id="newNombre">
              </div>
              <div class="form-group col-md-6">
                <label class="mb-1">Apellido</label>
                <input type="text" class="form-control" id="newApellido">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label class="mb-1">Tipo doc</label>
                <select class="form-control" id="newTipoDoc">
                  <option value="DNI">DNI</option>
                  <option value="CED">CED</option>
                  <option value="PAS">PAS</option>
                </select>
              </div>
              <div class="form-group col-md-8">
                <label class="mb-1">Número doc</label>
                <input type="text" class="form-control" id="newNumDoc">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="mb-1">Celular</label>
                <input type="text" class="form-control" id="newCelular">
              </div>
              <div class="form-group col-md-6">
                <label class="mb-1">Email</label>
                <input type="email" class="form-control" id="newEmail">
              </div>
            </div>
            <div class="form-group">
              <label class="mb-1">Dirección</label>
              <input type="text" class="form-control" id="newDireccion">
            </div>
            <button class="btn btn-outline-success" id="btnCrearCliente">
              <i class="fas fa-user-plus"></i> Crear y seleccionar
            </button>
          </div>
        </div>

        <hr>

        <div class="alert alert-light border" id="citaClienteBox" style="display:none;">
          <div><strong>Cliente:</strong> <span id="citaClienteNombre"></span> <span class="text-muted" id="citaClienteDoc"></span></div>
          <div class="text-muted" style="font-size:.9rem;">ID: <span id="citaClienteId"></span></div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-4">
            <label class="mb-1">Fecha</label>
            <input type="date" class="form-control" id="citaFecha">
          </div>
          <div class="form-group col-md-4">
            <label class="mb-1">Duración</label>
            <select class="form-control" id="citaDuracion">
              <option value="30">30 min</option>
              <option value="60">60 min</option>
              <option value="90">90 min</option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label class="mb-1">Hora</label>
            <select class="form-control" id="citaHora">
              <option value="">Seleccione fecha…</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="mb-1">Motivo</label>
          <input type="text" class="form-control" id="citaMotivo" placeholder="Ej: Examen visual, retiro, ajuste, control">
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarCita"><i class="fas fa-save"></i> Guardar cita</button>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<!-- Select2 (AdminLTE local) -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>

<script>
(function(){
  // 1) Mover el modal a body (AdminLTE wrapper a veces genera aria-hidden/focus warnings)
  try { $('#modal-cita').appendTo('body'); } catch(e) {}

  let selectedDate = null;
  let selectedClient = null;
  let editingCitaId = null;

  // Evita problemas de zona horaria (toISOString() usa UTC y puede cambiar la fecha local)
  function fmtDateLocal(d){
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    const day = String(d.getDate()).padStart(2,'0');
    return `${y}-${m}-${day}`;
  }

  function setAgendaFecha(iso){
    $('#agendaFecha').text(iso || '—');
  }

  function renderAgendaList(items){
    if(!items || !items.length){
      $('#agendaList').html('<div class="text-muted">No hay citas para esta fecha.</div>');
      return;
    }
    const rows = items.map(it => {
      const badge = it.estado === 'cancelada' ? 'badge-secondary' : (it.estado === 'atendida' ? 'badge-success' : 'badge-primary');
      const disabled = (it.estado === 'cancelada' || it.estado === 'atendida') ? 'disabled' : '';
      const estadoLabel = it.estado === 'programada' ? 'programada' : it.estado;
      return `
        <div class="border rounded p-2 mb-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div style="font-weight:600;">${it.hora_inicio} - ${it.hora_fin} <span class="badge ${badge}">${estadoLabel}</span></div>
              <div class="text-muted" style="font-size:.9rem;">${it.cliente}</div>
              ${it.motivo ? `<div style="font-size:.9rem;">${it.motivo}</div>` : ''}
            </div>
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-info btnEditCita" data-id="${it.id}"><i class="fas fa-edit"></i></button>
              <a class="btn btn-outline-dark" href="<?php echo $URL; ?>/citas/print.php?id=${it.id}" target="_blank" title="Imprimir comprobante"><i class="fas fa-print"></i></a>
              <button class="btn btn-success btnAtenderCita" data-id="${it.id}" ${disabled} title="Marcar atendida"><i class="fas fa-check"></i></button>
              <button class="btn btn-secondary btnCancelarCita" data-id="${it.id}" ${disabled} title="Cancelar"><i class="fas fa-ban"></i></button>
            </div>
          </div>
        </div>`;
    }).join('');
    $('#agendaList').html(rows);
  }

  function loadAgenda(iso){
    if(!iso) return;
    setAgendaFecha(iso);
    if(window.SOV && SOV.ajaxJson){
      SOV.ajaxJson({url:'<?php echo $URL; ?>/app/controllers/citas/list_by_date.php', method:'GET', data:{date:iso}})
        .done(resp => { if(resp && resp.ok) renderAgendaList(resp.items || []); else renderAgendaList([]); })
        .fail(()=> renderAgendaList([]));
    }
  }

  function reloadCalendar(){
    if(window._sovCalendar) window._sovCalendar.refetchEvents();
    if(selectedDate) loadAgenda(selectedDate);
    loadProximas();
  }

  function renderProximas(items, range){
    if(range && range.from && range.to){
      $('#proximasRange').text(`Rango: ${range.from} a ${range.to}`);
    }
    if(!items || !items.length){
      $('#proximasList').html('<div class="text-muted">No hay citas próximas.</div>');
      return;
    }
    const html = items.map(it=>{
      const when = `${it.fecha} ${it.hora_inicio}`;
      const motive = it.motivo ? `<div class="text-muted" style="font-size:.85rem;">${it.motivo}</div>` : '';
      return `
        <div class="border rounded p-2 mb-2">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div style="font-weight:600;">${when} - ${it.hora_fin}</div>
              <div class="text-muted" style="font-size:.9rem;">${it.cliente}</div>
              ${motive}
            </div>
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-info btnEditCita" data-id="${it.id}" title="Editar"><i class="fas fa-edit"></i></button>
              <a class="btn btn-outline-dark" href="<?php echo $URL; ?>/citas/print.php?id=${it.id}" target="_blank" title="Imprimir"><i class="fas fa-print"></i></a>
            </div>
          </div>
        </div>`;
    }).join('');
    $('#proximasList').html(html);
  }

  function loadProximas(){
    if(!($('#proximasList').length)) return;
    if(!(window.SOV && SOV.ajaxJson)) return;
    SOV.ajaxJson({url:'<?php echo $URL; ?>/app/controllers/citas/proximas.php', method:'GET', data:{days:7, limit:12}})
      .done(resp=>{
        if(resp && resp.ok) renderProximas(resp.items||[], resp.range||null);
        else renderProximas([], null);
      })
      .fail(()=> renderProximas([], null));
  }

  function selectClient(c){
    selectedClient = c;
    $('#citaClienteBox').show();
    $('#citaClienteNombre').text(c.nombre);
    $('#citaClienteDoc').text(c.doc ? `(${c.doc})` : '');
    $('#citaClienteId').text(c.id);
  }

  function refreshHoras(){
    const fecha = ($('#citaFecha').val()||'').trim();
    const dur = parseInt($('#citaDuracion').val()||'30',10);
    const $sel = $('#citaHora');
    $sel.html('<option value="">Cargando…</option>');
    if(!fecha){ $sel.html('<option value="">Seleccione fecha…</option>'); return; }
    if(window.SOV && SOV.ajaxJson){
      SOV.ajaxJson({
        url:'<?php echo $URL; ?>/app/controllers/citas/availability.php',
        method:'GET',
        data:{date:fecha, duration:dur, exclude_id: editingCitaId || ''}
      })
        .done(resp => {
          if(resp && resp.ok && resp.slots && resp.slots.length){
            $sel.html(resp.slots.map(s=>`<option value="${s}">${s}</option>`).join(''));
          } else {
            const msg = (resp && resp.error) ? resp.error : 'Sin horas disponibles.';
            $sel.html(`<option value="">${msg}</option>`);
          }
        })
        .fail(()=> $sel.html('<option value="">Sin horas disponibles.</option>'));
    }
  }

  // 2) Select2: inicialización segura (sin destroy si no está inicializado)
  function initClienteSelect2(){
    const $sel = $('#selCliente');
    if(!$sel.length || typeof $sel.select2 !== 'function') return;

    // destroy SOLO si está inicializado
    if ($sel.hasClass('select2-hidden-accessible')) {
      try { $sel.select2('destroy'); } catch(e) {}
    }

    // limpiar opciones y re-inicializar
    $sel.empty().append('<option></option>');

    $sel.select2({
      theme: 'bootstrap4',
      placeholder: 'Buscar cliente…',
      allowClear: true,
      minimumInputLength: 2,
      width: '100%',
      dropdownParent: $('#modal-cita'),
      ajax: {
        url: '<?php echo $URL; ?>/app/controllers/citas/search_clientes_select2.php',
        dataType: 'json',
        delay: 300,
        data: function(params){
          return { q: params.term || '', page: params.page || 1 };
        },
        processResults: function(data, params){
          params.page = params.page || 1;

          if(data && data.results){
            return { results: data.results, pagination: data.pagination || {more:false} };
          }

          const items = (data && data.ok) ? (data.items || []) : [];
          return {
            results: items.map(it => ({
              id: it.id,
              text: it.nombre || it.text || ('Cliente #' + it.id),
              doc: it.doc || ''
            })),
            pagination: { more: false }
          };
        },
        cache: true
      },
      templateResult: function(item){
        if(item.loading) return item.text;
        const doc = item.doc ? `<div class="text-muted" style="font-size:.85rem;">${item.doc}</div>` : '';
        return $(`<div><div style="font-weight:600;">${item.text}</div>${doc}</div>`);
      },
      templateSelection: function(item){
        return item && item.text ? item.text : '';
      }
    });

    $sel.off('select2:select').on('select2:select', function(e){
      const d = (e && e.params && e.params.data) ? e.params.data : null;
      if(!d) return;
      const id = parseInt(String(d.id||0),10);
      selectClient({ id: id, nombre: d.text || '', doc: d.doc || '' });
    });

    $sel.off('select2:clear').on('select2:clear', function(){
      selectedClient = null;
      $('#citaClienteBox').hide();
    });
  }

  function openCitaModal(prefDate){
    editingCitaId = null;

    initClienteSelect2();
    try { $('#selCliente').val(null).trigger('change'); } catch(e) {}

    selectedClient = null;
    $('#citaClienteBox').hide();

    $('#modal-cita .modal-title').html('<i class="fas fa-calendar-plus"></i> Agendar cita');
    $('#btnGuardarCita').html('<i class="fas fa-save"></i> Guardar cita');

    if(prefDate){
      $('#citaFecha').val(prefDate);
      selectedDate = prefDate;
      refreshHoras();
    }

    $('#modal-cita').modal('show');
  }

  function openEditCitaModal(idCita){
    editingCitaId = idCita;

    initClienteSelect2();

    $('#modal-cita .modal-title').html('<i class="fas fa-edit"></i> Editar cita');
    $('#btnGuardarCita').html('<i class="fas fa-save"></i> Guardar cambios');

    selectedClient = null;
    $('#citaClienteBox').hide();
    $('#citaMotivo').val('');
    $('#citaHora').html('<option value="">Cargando…</option>');

    if(!(window.SOV && SOV.ajaxJson)){
      $('#modal-cita').modal('show');
      return;
    }

    SOV.ajaxJson({url:'<?php echo $URL; ?>/app/controllers/citas/get.php', method:'GET', data:{id:idCita}})
      .done(resp => {
        if(!(resp && resp.ok && resp.cita)){
          SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo cargar la cita.');
          return;
        }
        const c = resp.cita;

        // Preseleccionar en Select2 (cliente existente)
        try {
          const $sel = $('#selCliente');
          const opt = new Option(c.cliente, c.id_cliente, true, true);
          $sel.append(opt).trigger('change');
        } catch(e) {}

        selectClient({id: c.id_cliente, nombre: c.cliente, doc: c.doc});
        $('#citaFecha').val(c.fecha);
        $('#citaDuracion').val(String(c.duracion || 30));
        $('#citaMotivo').val(c.motivo || '');

        refreshHoras();
        setTimeout(()=>{ $('#citaHora').val(c.hora_inicio); }, 250);

        $('#modal-cita').modal('show');
      })
      .fail(xhr => {
        let msg='No se pudo cargar la cita.';
        try{const j=JSON.parse(xhr.responseText); if(j&&j.error) msg=j.error;}catch(e){}
        SOV.toast('error', msg);
      });
  }

  // FullCalendar
  const calEl = document.getElementById('calendar');
  if(calEl){
    const cal = new FullCalendar.Calendar(calEl, {
      initialView: 'dayGridMonth',
      height: 'auto',
      locale: 'es',
      headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
      nowIndicator: true,
      selectable: true,
      editable: true,
      eventResizableFromStart: false,
      eventDurationEditable: true,
      dateClick: function(info){
        selectedDate = info.dateStr;
        loadAgenda(info.dateStr);
      },
      select: function(info){
        selectedDate = info.startStr.substring(0,10);
        loadAgenda(selectedDate);
        openCitaModal(selectedDate);
      },
      eventClick: function(info){
        const kind = (info.event.extendedProps && info.event.extendedProps.kind) ? info.event.extendedProps.kind : '';
        if(kind !== 'cita') return;
        const id = parseInt(String(info.event.id || 0), 10);
        if(id) openEditCitaModal(id);
      },
      eventAllow: function(dropInfo, draggedEvent){
        const kind = (draggedEvent.extendedProps && draggedEvent.extendedProps.kind) ? draggedEvent.extendedProps.kind : '';
        const estado = (draggedEvent.extendedProps && draggedEvent.extendedProps.estado) ? draggedEvent.extendedProps.estado : '';
        if(kind !== 'cita') return false;
        if(estado === 'cancelada' || estado === 'atendida') return false;
        return true;
      },
      eventDrop: function(info){
        const ev = info.event;
        const kind = (ev.extendedProps && ev.extendedProps.kind) ? ev.extendedProps.kind : '';
        if(kind !== 'cita') { info.revert(); return; }
        const id = parseInt(String(ev.id||0),10);
        if(!id){ info.revert(); return; }

        const start = ev.start;
        const end = ev.end;
        const date = fmtDateLocal(start);
        const hhmm = start.toTimeString().substring(0,5);
        let dur = 30;
        if(end){ dur = Math.max(15, Math.round((end.getTime()-start.getTime())/60000)); }

        if(!(window.SOV && SOV.ajaxJson)) { info.revert(); return; }
        SOV.ajaxJson({url:'<?php echo $URL; ?>/app/controllers/citas/move.php', method:'POST', data:{_csrf:CSRF, id_cita:id, fecha:date, hora_inicio:hhmm, duracion:dur}})
          .done(resp=>{
            if(resp && resp.ok){ SOV.toast('success','Cita reprogramada.'); reloadCalendar(); }
            else { SOV.toast('error', (resp&&resp.error)?resp.error:'No se pudo reprogramar.'); info.revert(); }
          })
          .fail(xhr=>{
            let msg='No se pudo reprogramar.';
            try{const j=JSON.parse(xhr.responseText); if(j&&j.error) msg=j.error;}catch(e){}
            SOV.toast('error', msg);
            info.revert();
          });
      },
      eventResize: function(info){
        const ev = info.event;
        const kind = (ev.extendedProps && ev.extendedProps.kind) ? ev.extendedProps.kind : '';
        if(kind !== 'cita') { info.revert(); return; }
        const id = parseInt(String(ev.id||0),10);
        if(!id){ info.revert(); return; }

        const start = ev.start;
        const end = ev.end;
        if(!end){ info.revert(); return; }

        const date = fmtDateLocal(start);
        const hhmm = start.toTimeString().substring(0,5);
        const dur = Math.max(15, Math.round((end.getTime()-start.getTime())/60000));

        if(!(window.SOV && SOV.ajaxJson)) { info.revert(); return; }
        SOV.ajaxJson({url:'<?php echo $URL; ?>/app/controllers/citas/move.php', method:'POST', data:{_csrf:CSRF, id_cita:id, fecha:date, hora_inicio:hhmm, duracion:dur}})
          .done(resp=>{
            if(resp && resp.ok){ SOV.toast('success','Duración actualizada.'); reloadCalendar(); }
            else { SOV.toast('error', (resp&&resp.error)?resp.error:'No se pudo actualizar.'); info.revert(); }
          })
          .fail(xhr=>{
            let msg='No se pudo actualizar.';
            try{const j=JSON.parse(xhr.responseText); if(j&&j.error) msg=j.error;}catch(e){}
            SOV.toast('error', msg);
            info.revert();
          });
      },
      events: function(fetchInfo, success, failure){
        if(!(window.SOV && SOV.ajaxJson)){ failure(); return; }
        SOV.ajaxJson({url:'<?php echo $URL; ?>/app/controllers/citas/events.php', method:'GET', data:{start:fetchInfo.startStr, end:fetchInfo.endStr}})
          .done(resp => success((resp && resp.ok) ? (resp.events||[]) : []))
          .fail(()=> failure());
      }
    });
    cal.render();
    window._sovCalendar = cal;
    loadProximas();
  }

  // Botones
  $('#btnNuevaCita').on('click', ()=> openCitaModal(selectedDate || null));
  $('#btnReloadProximas').on('click', loadProximas);
  $('#btnNuevaCitaDia').on('click', ()=> openCitaModal(selectedDate || ($('#bloqFecha').val()||null)));

  // Acciones en agenda del día
  $(document).on('click','.btnEditCita', function(){
    const id = parseInt($(this).data('id')||0,10);
    if(id) openEditCitaModal(id);
  });

  function setEstadoCita(id, estado){
    return SOV.ajaxJson({
      url:'<?php echo $URL; ?>/app/controllers/citas/set_estado.php',
      method:'POST',
      data:{_csrf:CSRF, id_cita:id, estado:estado}
    });
  }

  $(document).on('click','.btnAtenderCita', function(){
    const id = parseInt($(this).data('id')||0,10);
    if(!id) return;
    setEstadoCita(id,'atendida')
      .done(resp=>{
        if(resp && resp.ok){ SOV.toast('success','Cita marcada como atendida.'); reloadCalendar(); }
        else SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo actualizar.');
      })
      .fail(xhr=>{
        let msg='No se pudo actualizar.';
        try{const j=JSON.parse(xhr.responseText); if(j&&j.error) msg=j.error;}catch(e){}
        SOV.toast('error', msg);
      });
  });

  $(document).on('click','.btnCancelarCita', function(){
    const id = parseInt($(this).data('id')||0,10);
    if(!id) return;
    if(!confirm('¿Cancelar esta cita?')) return;
    setEstadoCita(id,'cancelada')
      .done(resp=>{
        if(resp && resp.ok){ SOV.toast('success','Cita cancelada.'); reloadCalendar(); }
        else SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo cancelar.');
      })
      .fail(xhr=>{
        let msg='No se pudo cancelar.';
        try{const j=JSON.parse(xhr.responseText); if(j&&j.error) msg=j.error;}catch(e){}
        SOV.toast('error', msg);
      });
  });

  $('#citaFecha,#citaDuracion').on('change', refreshHoras);

  function titleCaseName(s){
    if(!s) return '';
    let v = String(s).trim().replace(/\s+/g,' ');
    v = v.toLowerCase();
    try{ v = v.replace(/\b\p{L}/gu, (m)=>m.toUpperCase()); }
    catch(e){ v = v.replace(/(^|\s)[a-záéíóúñ]/g, (m)=>m.toUpperCase()); }
    return v;
  }
  function normalizeDoc(s){
    if(!s) return '';
    return String(s).toUpperCase().replace(/[\s\-]+/g,'');
  }
  function normalizePhone(s){
    if(!s) return '';
    return String(s).replace(/\D+/g,'');
  }

  // UI: formateo rápido al salir del input
  $('#newNombre').on('blur', ()=> $('#newNombre').val(titleCaseName($('#newNombre').val())));
  $('#newApellido').on('blur', ()=> $('#newApellido').val(titleCaseName($('#newApellido').val())));
  $('#newCelular').on('blur', ()=> $('#newCelular').val(normalizePhone($('#newCelular').val())));
  $('#newNumDoc').on('blur', ()=> $('#newNumDoc').val(normalizeDoc($('#newNumDoc').val())));

  $('#btnCrearCliente').on('click', function(){
    const data = {
      _csrf: CSRF,
      nombre: titleCaseName(($('#newNombre').val()||'').trim()),
      apellido: titleCaseName(($('#newApellido').val()||'').trim()),
      tipo_documento: ($('#newTipoDoc').val()||'').trim(),
      numero_documento: normalizeDoc(($('#newNumDoc').val()||'').trim()),
      celular: normalizePhone(($('#newCelular').val()||'').trim()),
      email: ($('#newEmail').val()||'').trim(),
      direccion: ($('#newDireccion').val()||'').trim(),
    };
    SOV.ajaxJson({url:'<?php echo $URL; ?>/app/controllers/citas/cliente_quick_create.php', method:'POST', data:data})
      .done(resp=>{
        if(resp && resp.ok){
          selectClient(resp.cliente);
          // preseleccionar en Select2
          try{
            const $sel = $('#selCliente');
            const opt = new Option(resp.cliente.nombre, resp.cliente.id, true, true);
            $sel.append(opt).trigger('change');
          }catch(e){}
          SOV.toast('success','Cliente creado.');
        } else {
          SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo crear el cliente.');
        }
      })
      .fail(xhr=>{
        let msg='No se pudo crear el cliente.';
        try{const j=JSON.parse(xhr.responseText); if(j&&j.error) msg=j.error;}catch(e){}
        SOV.toast('error', msg);
      });
  });

  $('#btnGuardarCita').on('click', function(){
    const id_cliente = selectedClient ? selectedClient.id : 0;
    const fecha = ($('#citaFecha').val()||'').trim();
    const hora = ($('#citaHora').val()||'').trim();
    const dur = parseInt($('#citaDuracion').val()||'30',10);
    const motivo = ($('#citaMotivo').val()||'').trim();

    if(!id_cliente){ SOV.toast('warning','Seleccione o cree un cliente.'); return; }
    if(!fecha || !hora){ SOV.toast('warning','Seleccione fecha y hora.'); return; }

    const url = editingCitaId ? '<?php echo $URL; ?>/app/controllers/citas/update.php' : '<?php echo $URL; ?>/app/controllers/citas/create.php';
    const payload = {_csrf:CSRF, id_cliente:id_cliente, fecha:fecha, hora_inicio:hora, duracion:dur, motivo:motivo};
    if(editingCitaId) payload.id_cita = editingCitaId;

    SOV.ajaxJson({url:url, method:'POST', data:payload})
      .done(resp=>{
        if(resp && resp.ok){
          $('#modal-cita').modal('hide');
          SOV.toast('success', editingCitaId ? 'Cita actualizada.' : 'Cita registrada.');
          reloadCalendar();
        } else {
          SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo guardar la cita.');
        }
      })
      .fail(xhr=>{
        let msg='No se pudo guardar la cita.';
        try{
          const j=JSON.parse(xhr.responseText);
          if(j&&j.error) msg=j.error;
          else if(typeof xhr.responseText==='string' && xhr.responseText) msg=xhr.responseText;
        }catch(e){
          if(typeof xhr.responseText==='string' && xhr.responseText) msg=xhr.responseText;
        }
        SOV.toast('error', msg);
      });
  });

  $('#btnGuardarBloqueo').on('click', function(){
    const fecha = ($('#bloqFecha').val()||'').trim();
    const hi = ($('#bloqInicio').val()||'').trim();
    const hf = ($('#bloqFin').val()||'').trim();
    const motivo = ($('#bloqMotivo').val()||'').trim();
    if(!fecha){ SOV.toast('warning','Seleccione una fecha.'); return; }

    SOV.ajaxJson({url:'<?php echo $URL; ?>/app/controllers/citas/bloqueo_create.php', method:'POST', data:{_csrf:CSRF, fecha:fecha, hora_inicio:hi, hora_fin:hf, motivo:motivo}})
      .done(resp=>{
        if(resp && resp.ok){
          SOV.toast('success','Bloqueo guardado.');
          $('#bloqMotivo').val('');
          reloadCalendar();
        } else {
          SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo guardar el bloqueo.');
        }
      })
      .fail(xhr=>{
        let msg='No se pudo guardar el bloqueo.';
        try{const j=JSON.parse(xhr.responseText); if(j&&j.error) msg=j.error;}catch(e){}
        SOV.toast('error', msg);
      });
  });

  // 3) Control de foco (reduce warning aria-hidden)
  $('#modal-cita').on('shown.bs.modal', function(){
    try {
      // Enfocar el select2 input si existe; sino el primer campo visible
      setTimeout(function(){
        const $search = $(this).find('.select2-search__field');
        if($search.length) $search.trigger('focus');
        else $(this).find('input,select,textarea,button').filter(':visible').first().trigger('focus');
      }.bind(this), 120);
    } catch(e) {}
  });

  $('#modal-cita').on('hide.bs.modal', function(){
    try {
      const el = document.activeElement;
      if (el && this.contains(el)) el.blur();
    } catch (e) {}
  });

})();
</script>
