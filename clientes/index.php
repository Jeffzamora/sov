<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';

require_once __DIR__ . '/../app/controllers/clientes/listado_de_clientes.php';
?>
<script>
  const CSRF = <?php echo json_encode(csrf_token()); ?>;
</script>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0">Clientes</h1>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="row">
        <div class="col-12">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Listado de clientes</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-create-cliente">
                  <i class="fa fa-plus"></i> Nuevo cliente
                </button>
              </div>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table id="tabla_clientes" class="table table-bordered table-striped table-sm">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Documento</th>
                      <th>Nombre</th>
                      <th>Nacimiento</th>
                      <th>Celular</th>
                      <th>Email</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $c=0; foreach($clientes_datos as $cl){ $c++; ?>
                      <tr>
                        <td><?php echo $c; ?></td>
                        <td><?php echo htmlspecialchars(($cl['tipo_documento'] ?? '').' '.($cl['numero_documento'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars(($cl['nombre'] ?? '').' '.($cl['apellido'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($cl['fecha_nacimiento'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cl['celular'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cl['email'] ?? ''); ?></td>
                        <td>
                          <a class="btn btn-info btn-sm" href="<?php echo $URL; ?>/clientes/show.php?id=<?php echo (int)$cl['id_cliente']; ?>">
                            <i class="fa fa-eye"></i>
                          </a>

<a class="btn btn-primary btn-sm" title="Ver exámenes" href="<?php echo $URL; ?>/clientes/examenes/index.php?id=<?php echo (int)$cl['id_cliente']; ?>">
  <i class="fas fa-glasses"></i>
</a>
<a class="btn btn-secondary btn-sm" title="Imprimir último examen" target="_blank" href="<?php echo $URL; ?>/clientes/examenes/print_ultimo.php?id=<?php echo (int)$cl['id_cliente']; ?>">
  <i class="fa fa-print"></i>
</a>
                          <button type="button" class="btn btn-warning btn-sm btn-edit"
                            data-id="<?php echo (int)$cl['id_cliente']; ?>"
                            data-nombre="<?php echo htmlspecialchars($cl['nombre'] ?? '', ENT_QUOTES); ?>"
                            data-apellido="<?php echo htmlspecialchars($cl['apellido'] ?? '', ENT_QUOTES); ?>"
                            data-tipo_documento="<?php echo htmlspecialchars($cl['tipo_documento'] ?? '', ENT_QUOTES); ?>"
                            data-numero_documento="<?php echo htmlspecialchars($cl['numero_documento'] ?? '', ENT_QUOTES); ?>"
                            data-fecha_nacimiento="<?php echo htmlspecialchars($cl['fecha_nacimiento'] ?? '', ENT_QUOTES); ?>"
                            data-celular="<?php echo htmlspecialchars($cl['celular'] ?? '', ENT_QUOTES); ?>"
                            data-email="<?php echo htmlspecialchars($cl['email'] ?? '', ENT_QUOTES); ?>"
                            data-direccion="<?php echo htmlspecialchars($cl['direccion'] ?? '', ENT_QUOTES); ?>"
                          >
                            <i class="fa fa-edit"></i>
                          </button>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal Create -->
<div class="modal fade" id="modal-create-cliente">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title">Nuevo cliente</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="form-create-cliente">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Apellido *</label>
                <input type="text" name="apellido" class="form-control" required>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Tipo documento *</label>
                <select name="tipo_documento" class="form-control" required>
                  <option value="" selected disabled>Seleccione...</option>
                  <option value="DNI">DNI</option>
                  <option value="Cédula">Cédula</option>
                  <option value="Pasaporte">Pasaporte</option>
                  <option value="Otro">Otro</option>
                  <option value="Menor">Menor (sin documento)</option>
                </select>
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group">
                <label>Número documento</label>
                <input type="text" name="numero_documento" class="form-control" placeholder="Ej: 0011401970010N" autocapitalize="characters" spellcheck="false">
                <div class="invalid-feedback">Documento inválido. Verifica el formato.</div>
                <small class="text-muted">Si es cédula NIC: MMM + DDMMAA + #### + letra (opcional).</small>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" class="form-control">
                <small class="text-muted">Si el cliente es menor, se permitirá guardar sin documento.</small>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Celular</label>
                <input type="tel" name="celular" class="form-control" inputmode="numeric" autocomplete="tel" maxlength="15" placeholder="Ej: 88887777 o 50588887777">
                <div class="invalid-feedback">Celular inválido. Usa 8 a 15 dígitos (solo números).</div>
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control">
              </div>
            </div>

            <div class="col-12">
              <div class="form-group">
                <label>Dirección</label>
                <textarea name="direccion" class="form-control" rows="2"></textarea>
              </div>
            </div>
          </div>
        </form>
        <div id="create_cliente_error" class="text-danger small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-cliente">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modal-edit-cliente">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Editar cliente</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="form-edit-cliente">
          <input type="hidden" name="id_cliente" id="edit_id_cliente">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Apellido *</label>
                <input type="text" name="apellido" id="edit_apellido" class="form-control" required>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Tipo documento *</label>
                <select name="tipo_documento" id="edit_tipo_documento" class="form-control" required>
                  <option value="Menor">Menor (sin documento)</option>
                  <option value="DNI">DNI</option>
                  <option value="Cédula">Cédula</option>
                  <option value="Pasaporte">Pasaporte</option>
                  <option value="Otro">Otro</option>
                </select>
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group">
                <label>Número documento</label>
                <input type="text" name="numero_documento" id="edit_numero_documento" class="form-control" placeholder="Ej: 0011401970010N">
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" class="form-control">
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Celular</label>
                <input type="tel" name="celular" id="edit_celular" class="form-control" inputmode="numeric" autocomplete="tel" maxlength="15" placeholder="Ej: 88887777 o 50588887777">
                <div class="invalid-feedback">Celular inválido. Usa 8 a 15 dígitos (solo números).</div>
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" class="form-control">
              </div>
            </div>

            <div class="col-12">
              <div class="form-group">
                <label>Dirección</label>
                <textarea name="direccion" id="edit_direccion" class="form-control" rows="2"></textarea>
              </div>
            </div>
          </div>
        </form>
        <div id="edit_cliente_error" class="text-danger small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btn-actualizar-cliente">Actualizar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
$(function(){
  $('#tabla_clientes').DataTable({
    pageLength: 10,
    responsive: true,
    lengthChange: true,
    autoWidth: false,
    language: {
      emptyTable: "No hay información",
      search: "Buscar:",
      lengthMenu: "Mostrar _MENU_",
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  // abrir modal editar
  $(document).on('click', '.btn-edit', function(){
    const $b = $(this);
    $('#edit_id_cliente').val($b.data('id'));
    $('#edit_nombre').val($b.data('nombre'));
    $('#edit_apellido').val($b.data('apellido'));
    $('#edit_tipo_documento').val($b.data('tipo_documento'));
    $('#edit_numero_documento').val($b.data('numero_documento'));
    $('#edit_fecha_nacimiento').val($b.data('fecha_nacimiento'));
    $('#edit_celular').val($b.data('celular'));
    $('#edit_email').val($b.data('email'));
    $('#edit_direccion').val($b.data('direccion'));
    $('#edit_cliente_error').text('');
    if(!validateClienteForm($('#form-edit-cliente'))){
      $('#edit_cliente_error').text('Revisa los campos marcados en rojo.');
      return;
    }

    applyDocRules($('#edit_tipo_documento'), $('#edit_numero_documento'), $('#edit_fecha_nacimiento'));
    $('#modal-edit-cliente').modal('show');
  });

  function yearsFromDob(dob){
    try{
      if(!dob) return null;
      const d = new Date(dob + 'T00:00:00');
      if(isNaN(d.getTime())) return null;
      const now = new Date();
      let age = now.getFullYear() - d.getFullYear();
      const m = now.getMonth() - d.getMonth();
      if(m < 0 || (m===0 && now.getDate() < d.getDate())) age--;
      return age;
    }catch(e){ return null; }
  }

  function parseNicCedula(s){
    if(!s) return null;
    const v = String(s).toUpperCase().replace(/[\s\-]+/g,'');
    const m = v.match(/^(\d{3})(\d{2})(\d{2})(\d{2})(\d{4})([A-Z])?$/);
    if(!m) return null;
    const dd = parseInt(m[2],10), mm = parseInt(m[3],10), yy = parseInt(m[4],10);
    const nowYY = new Date().getFullYear() % 100;
    const year = (yy > nowYY) ? (1900 + yy) : (2000 + yy);
    // Validación fecha
    const dt = new Date(year, mm-1, dd);
    if(dt.getFullYear()!==year || (dt.getMonth()+1)!==mm || dt.getDate()!==dd) return null;
    const dob = String(year).padStart(4,'0')+'-'+String(mm).padStart(2,'0')+'-'+String(dd).padStart(2,'0');
    return { municipio:m[1], dob };
  }

  function titleCaseName(s){
    if(!s) return '';
    let v = String(s).trim().replace(/\s+/g,' ');
    v = v.toLowerCase();
    // Capitaliza cada palabra (unicode)
    try{
      v = v.replace(/\b\p{L}/gu, (m)=>m.toUpperCase());
    }catch(e){
      // fallback básico
      v = v.replace(/(^|\s)[a-záéíóúñ]/g, (m)=>m.toUpperCase());
    }
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
  function setInvalid($el, msg){
    if(!$el || !$el.length) return;
    $el.addClass("is-invalid");
    const $fb = $el.closest(".form-group").find(".invalid-feedback").first();
    if(msg && $fb.length) $fb.text(msg);
  }

  function clearInvalid($form){
    $form.find(".is-invalid").removeClass("is-invalid");
  }

  function validateClienteForm($form){
    clearInvalid($form);
    let ok = true;
    const $tipo = $form.find("select[name=\"tipo_documento\"]");
    const $doc  = $form.find("input[name=\"numero_documento\"]");
    const $cel  = $form.find("input[name=\"celular\"]");

    const tipo = String($tipo.val()||"");
    const doc  = normalizeDoc($doc.val());
    const cel  = normalizePhone($cel.val());

    if(cel && (cel.length < 8 || cel.length > 15)){
      ok = false;
      setInvalid($cel, "Celular inválido. Usa 8 a 15 dígitos (solo números)." );
    }

    if(tipo && tipo.toLowerCase() !== "menor" && doc){
      if(doc.length < 5){
        ok = false;
        setInvalid($doc, "Documento demasiado corto." );
      }
      const t = tipo.toLowerCase();
      if((t.includes("cédula") || t.includes("cedula") || t==="ced") && !parseNicCedula(doc)){
        ok = false;
        setInvalid($doc, "Cédula NIC inválida. Ej: 0011401970010N (sin guiones)." );
      }
    }

    return ok;
  }


  function applyDocRules($tipo, $doc, $dob){
    const tipo = String($tipo.val()||'');
    const dob = String($dob.val()||'');
    const age = yearsFromDob(dob);

    // Regla UI: SOLO deshabilitamos/limpiamos documento si el usuario selecciona "Menor".
    // Si la fecha indica <18, NO forzamos el tipo a "Menor" (para permitir casos donde sí tienen documento).
    const tipoIsMenor = (tipo.toLowerCase()==='menor');

    if(tipoIsMenor){
      $doc.val('');
      $doc.prop('disabled', true);
    }else{
      $doc.prop('disabled', false);
    }

    // Hint suave (no bloqueante)
    const $hint = $doc.closest('.form-group').find('.js-menor-hint');
    if(age !== null && age < 18 && !tipoIsMenor){
      if($hint.length===0){
        $doc.closest('.form-group').append('<small class="text-muted js-menor-hint">Nota: Por la fecha de nacimiento, parece menor de edad. Si no tiene documento, selecciona "Menor".</small>');
      }
    }else{
      $hint.remove();
    }
  }

  // Reglas en Create
  const $cTipo = $('#form-create-cliente select[name="tipo_documento"]');
  const $cDoc  = $('#form-create-cliente input[name="numero_documento"]');
  const $cDob  = $('#form-create-cliente input[name="fecha_nacimiento"]');
  const $cNom  = $('#form-create-cliente input[name="nombre"]');
  const $cApe  = $('#form-create-cliente input[name="apellido"]');
  const $cCel  = $('#form-create-cliente input[name="celular"]');

  $cNom.on('blur', ()=> $cNom.val(titleCaseName($cNom.val())));
  $cApe.on('blur', ()=> $cApe.val(titleCaseName($cApe.val())));
  $cCel.on('blur', ()=> $cCel.val(normalizePhone($cCel.val())));
  $cTipo.on('change', ()=> applyDocRules($cTipo,$cDoc,$cDob));
  $cDob.on('change', ()=> applyDocRules($cTipo,$cDoc,$cDob));
  $cDoc.on('blur', ()=>{
    $cDoc.val(normalizeDoc($cDoc.val()));
    // Si es cédula NIC, autocompletar DOB si está vacío
    const tipo = String($cTipo.val()||'');
    if(tipo.toLowerCase().includes('cédula') || tipo.toLowerCase().includes('cedula') || tipo.toLowerCase()==='ced'){
      const p = parseNicCedula($cDoc.val());
      if(p && !$cDob.val()) $cDob.val(p.dob);
    }
  });

  // Reglas en Edit
  const $eTipo = $('#edit_tipo_documento');
  const $eDoc  = $('#edit_numero_documento');
  const $eDob  = $('#edit_fecha_nacimiento');
  const $eNom  = $('#form-edit-cliente input[name="nombre"]');
  const $eApe  = $('#form-edit-cliente input[name="apellido"]');
  const $eCel  = $('#form-edit-cliente input[name="celular"]');

  $eNom.on('blur', ()=> $eNom.val(titleCaseName($eNom.val())));
  $eApe.on('blur', ()=> $eApe.val(titleCaseName($eApe.val())));
  $eCel.on('blur', ()=> $eCel.val(normalizePhone($eCel.val())));
  $eTipo.on('change', ()=> applyDocRules($eTipo,$eDoc,$eDob));
  $eDob.on('change', ()=> applyDocRules($eTipo,$eDoc,$eDob));
  $eDoc.on('blur', ()=>{
    $eDoc.val(normalizeDoc($eDoc.val()));
    const tipo = String($eTipo.val()||'');
    if(tipo.toLowerCase().includes('cédula') || tipo.toLowerCase().includes('cedula') || tipo.toLowerCase()==='ced'){
      const p = parseNicCedula($eDoc.val());
      if(p && !$eDob.val()) $eDob.val(p.dob);
    }
  });

  // estado inicial
  applyDocRules($cTipo,$cDoc,$cDob);
  applyDocRules($eTipo,$eDoc,$eDob);

  $('#btn-guardar-cliente').on('click', function(){
    $('#create_cliente_error').text('');
    if(!validateClienteForm($('#form-create-cliente'))){
      $('#create_cliente_error').text('Revisa los campos marcados en rojo.');
      return;
    }
    const data = $('#form-create-cliente').serializeArray();
    data.push({name:'_csrf', value: CSRF});
    $.ajax({
      url: '../app/controllers/clientes/create.php',
      method: 'POST',
      headers: {'X-Requested-With':'XMLHttpRequest'},
      dataType: 'json',
      data: $.param(data)
    }).done(function(resp){
      if(resp && resp.ok){
        // cerrar modal y recargar solo si OK
        $('#modal-create-cliente').one('hidden.bs.modal', function(){ location.reload(); });
        $('#modal-create-cliente').modal('hide');
      } else {
        $('#create_cliente_error').text((resp && resp.error) ? resp.error : 'No se pudo guardar.');
      }
    }).fail(function(xhr){
      let msg = 'No se pudo guardar.';
      try{ const j = JSON.parse(xhr.responseText); if(j.error) msg=j.error; }catch(e){}
      $('#create_cliente_error').text(msg);
    });
  });

  $('#btn-actualizar-cliente').on('click', function(){
    $('#edit_cliente_error').text('');
    if(!validateClienteForm($('#form-edit-cliente'))){
      $('#edit_cliente_error').text('Revisa los campos marcados en rojo.');
      return;
    }
    const data = $('#form-edit-cliente').serializeArray();
    data.push({name:'_csrf', value: CSRF});
    $.ajax({
      url: '../app/controllers/clientes/update.php',
      method: 'POST',
      headers: {'X-Requested-With':'XMLHttpRequest'},
      dataType: 'json',
      data: $.param(data)
    }).done(function(resp){
      if(resp && resp.ok){
        $('#modal-edit-cliente').one('hidden.bs.modal', function(){ location.reload(); });
        $('#modal-edit-cliente').modal('hide');
      } else {
        $('#edit_cliente_error').text((resp && resp.error) ? resp.error : 'No se pudo actualizar.');
      }
    }).fail(function(xhr){
      let msg = 'No se pudo actualizar.';
      try{ const j = JSON.parse(xhr.responseText); if(j.error) msg=j.error; }catch(e){}
      $('#edit_cliente_error').text(msg);
    });
  });
});
</script>
