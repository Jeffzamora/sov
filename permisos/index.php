<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

// Seguridad: solo ADMINISTRADOR para gestión de roles/permisos
if (function_exists('require_admin')) {
  require_admin($pdo, $URL . '/index.php');
} else {
  if (function_exists('require_perm')) require_perm($pdo, 'roles.ver', $URL . '/index.php');
}

require_once __DIR__ . '/../layout/parte1.php';
?>
<script>window.SOV_CSRF = <?php echo json_encode(csrf_token()); ?>;</script>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-8">
          <h1 class="m-0"><i class="fas fa-key mr-2 text-warning"></i>Permisos</h1>
          <small class="text-muted">Catálogo de permisos (ej: usuarios.crear, ventas.ver) y asignación por rol.</small>
        </div>
        <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
          <button class="btn btn-warning" data-toggle="modal" data-target="#modal-perm-create">
            <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Nuevo permiso</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php if (function_exists('flash_render')) flash_render(); ?>

      <div class="row">
        <div class="col-lg-4">
          <div class="card card-outline card-warning">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-user-shield mr-1"></i>Asignar a rol</h3>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label>Rol</label>
                <select id="perm_role_select" class="form-control">
                  <option value="">Seleccione...</option>
                </select>
                <small class="text-muted">Selecciona un rol para ver y editar sus permisos.</small>
              </div>

              <div class="d-flex justify-content-between">
                <button class="btn btn-outline-secondary btn-sm" id="btn_perm_select_all">
                  <i class="far fa-check-square"></i> Todo
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="btn_perm_clear_all">
                  <i class="far fa-square"></i> Limpiar
                </button>
                <button class="btn btn-warning btn-sm" id="btn_perm_save" disabled>
                  <i class="fas fa-save"></i> Guardar
                </button>
              </div>
              <hr>
              <input type="text" id="perm_search" class="form-control" placeholder="Buscar (ej: ventas.)">
              <small class="text-muted d-block mt-2">Tip: agrupa por módulo: ventas.*, usuarios.*, cajas.*</small>
            </div>
          </div>

          <div class="card card-outline card-secondary">
            <div class="card-body">
              <div class="d-flex align-items-start">
                <i class="fas fa-info-circle text-secondary mr-2 mt-1"></i>
                <div class="small text-muted">
                  Recomendación de claves (CRUD):
                  <div><b>&lt;modulo&gt;.ver</b>, <b>&lt;modulo&gt;.crear</b>, <b>&lt;modulo&gt;.actualizar</b>, <b>&lt;modulo&gt;.eliminar</b></div>
                  Ej: <b>usuarios.ver</b>, <b>usuarios.crear</b>, <b>roles.ver</b>, <b>cajas.cerrar</b>.
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="col-lg-8">
          <div class="card card-outline card-warning">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-list mr-1"></i>Catálogo de permisos</h3>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="permsTable" class="table table-bordered table-striped table-hover">
                  <thead>
                    <tr>
                      <th style="width:70px" class="text-center">#</th>
                      <th>Clave</th>
                      <th>Descripción</th>
                      <th style="width:140px" class="text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="perms_tbody"></tbody>
                </table>
              </div>
              <small class="text-muted d-block mt-2">
                Nota: “Eliminar” bloquea si el permiso está asignado a roles.
              </small>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<!-- Modal crear permiso -->
<div class="modal fade" id="modal-perm-create" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Nuevo permiso</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="form-perm-create" action="../app/controllers/permisos/create.php" method="POST">
        <div class="modal-body">
          <?php echo csrf_field(); ?>
          <div class="form-group">
            <label>Clave</label>
            <input type="text" name="clave" class="form-control" maxlength="80" required placeholder="ej: usuarios.crear" autocomplete="off">
            <small class="text-muted">Usa formato: <b>modulo.accion</b></small>
          </div>
          <div class="form-group">
            <label>Descripción</label>
            <input type="text" name="descripcion" class="form-control" maxlength="150" required placeholder="Permite crear usuarios">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal editar permiso -->
<div class="modal fade" id="modal-perm-edit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar permiso</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="form-perm-edit" action="../app/controllers/permisos/update.php" method="POST">
        <div class="modal-body">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id_permiso" id="edit_id_permiso">
          <div class="form-group">
            <label>Clave</label>
            <input type="text" name="clave" id="edit_clave" class="form-control" maxlength="80" required autocomplete="off">
          </div>
          <div class="form-group">
            <label>Descripción</label>
            <input type="text" name="descripcion" id="edit_desc" class="form-control" maxlength="150" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
(function(){
  if(!(window.SOV && SOV.ajaxJson)) return;

  var $tbody = $('#perms_tbody');
  var $role = $('#perm_role_select');
  var selectedRole = null;
  var rolePerms = {}; // clave => true
  var allPerms = [];

  function esc(s){ return String(s||'').replace(/[&<>"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]); }); }

  function loadRoles(){
    SOV.ajaxJson({url:'../app/controllers/roles/list_json.php', method:'GET'})
      .done(function(resp){
        if(!resp || !resp.ok){ return; }
        var roles = resp.data || [];
        $role.empty().append('<option value="">Seleccione...</option>');
        roles.forEach(function(r){
          $role.append('<option value="'+Number(r.id_rol)+'">'+esc(r.rol)+'</option>');
        });
      });
  }

  function renderChecklist(){
    var $c = $('#perm_container_list');
    if($c.length===0){
      $('#btn_perm_save').closest('.card-body').append('<div class="mt-3" id="perm_container_list"></div>');
      $c = $('#perm_container_list');
    }
    $c.empty();
    var q = ($('#perm_search').val()||'').trim().toLowerCase();
    allPerms.forEach(function(p){
      var clave = String(p.clave||'');
      var desc = String(p.descripcion||'');
      if(q && clave.toLowerCase().indexOf(q)===-1 && desc.toLowerCase().indexOf(q)===-1) return;
      var checked = !!rolePerms[clave];
      var id = 'chk_'+Number(p.id_permiso);
      $c.append(
        '<div class="custom-control custom-checkbox mb-2">'+
        '<input type="checkbox" class="custom-control-input perm-chk" id="'+id+'" data-clave="'+esc(clave)+'" '+(checked?'checked':'')+' '+(!selectedRole?'disabled':'')+'>'+
        '<label class="custom-control-label" for="'+id+'"><code>'+esc(clave)+'</code> <span class="text-muted">- '+esc(desc)+'</span></label>'+
        '</div>'
      );
    });
    $('#btn_perm_save').prop('disabled', !selectedRole);
  }

  function renderTable(){
    $tbody.empty();
    allPerms.forEach(function(p, i){
      $tbody.append(
        '<tr data-id="'+Number(p.id_permiso)+'" data-clave="'+esc(p.clave)+'" data-desc="'+esc(p.descripcion)+'">'+
        '<td class="text-center">'+(i+1)+'</td>'+
        '<td><code>'+esc(p.clave)+'</code></td>'+
        '<td>'+esc(p.descripcion)+'</td>'+
        '<td class="text-center"><div class="btn-group">'+
          '<button class="btn btn-outline-primary btn-sm btn-perm-edit" type="button"><i class="fas fa-edit"></i></button>'+
          '<button class="btn btn-outline-danger btn-sm btn-perm-del" type="button"><i class="fas fa-trash"></i></button>'+
        '</div></td>'+
        '</tr>'
      );
    });
  }

  function loadPerms(){
    return SOV.ajaxJson({url:'../app/controllers/permisos/list.php', method:'GET'})
      .done(function(resp){
        if(!resp || !resp.ok) return;
        allPerms = resp.data || [];
        renderTable();
        renderChecklist();
      });
  }

  function loadRolePerms(idRol){
    selectedRole = idRol ? Number(idRol) : null;
    rolePerms = {};
    if(!selectedRole){ renderChecklist(); return; }
    SOV.ajaxJson({url:'../app/controllers/permisos/role_perms.php', method:'GET', data:{id_rol:selectedRole}})
      .done(function(resp){
        if(resp && resp.ok){
          (resp.data||[]).forEach(function(k){ rolePerms[String(k)] = true; });
        }
        renderChecklist();
      });
  }

  function saveRolePerms(){
    if(!selectedRole) return;
    var perms = [];
    $('.perm-chk:checked').each(function(){ perms.push($(this).data('clave')); });
    SOV.ajaxJson({url:'../app/controllers/permisos/role_save.php', method:'POST', data:{_csrf: window.SOV_CSRF, id_rol:selectedRole, permisos: perms}})
      .done(function(resp){
        if(resp && resp.ok){
          if(typeof Swal!=='undefined' && Swal.fire){ Swal.fire({icon:'success', title:'Guardado', text:'Permisos actualizados.'}); }
          else alert('Permisos actualizados.');
        } else SOV.warnModal((resp&&resp.error)?resp.error:'No se pudo guardar.');
      })
      .fail(function(){ SOV.warnModal('No se pudo guardar.'); });
  }

  $role.on('change', function(){ loadRolePerms($(this).val()); });
  $('#perm_search').on('input', renderChecklist);
  $('#btn_perm_select_all').on('click', function(){ $('.perm-chk:not(:disabled)').prop('checked', true); });
  $('#btn_perm_clear_all').on('click', function(){ $('.perm-chk:not(:disabled)').prop('checked', false); });
  $('#btn_perm_save').on('click', saveRolePerms);

  $('#form-perm-create, #form-perm-edit').on('submit', function(e){
    e.preventDefault();
    var $f=$(this);
    var fd=new FormData(this);
    SOV.ajaxJson({url:$f.attr('action'), method:'POST', data:fd})
      .done(function(resp){
        if(resp && resp.ok){ $('.modal').modal('hide'); loadPerms(); }
        else SOV.warnModal((resp&&resp.error)?resp.error:'No se pudo guardar.');
      })
      .fail(function(xhr){
        var msg='No se pudo guardar.';
        try{ var j=JSON.parse(xhr.responseText); if(j.error) msg=j.error; }catch(e){}
        SOV.warnModal(msg);
      });
  });

  $(document).on('click', '.btn-perm-edit', function(){
    var $tr=$(this).closest('tr');
    $('#edit_id_permiso').val($tr.data('id'));
    $('#edit_clave').val($tr.data('clave'));
    $('#edit_desc').val($tr.data('desc'));
    $('#modal-perm-edit').modal('show');
  });

  $(document).on('click', '.btn-perm-del', function(){
    var $tr=$(this).closest('tr');
    var id=$tr.data('id');
    var clave=$tr.data('clave');
    var go=function(){
      SOV.ajaxJson({url:'../app/controllers/permisos/delete.php', method:'POST', data:{_csrf: window.SOV_CSRF, id_permiso:id}})
        .done(function(resp){ if(resp&&resp.ok){ loadPerms(); } else SOV.warnModal((resp&&resp.error)?resp.error:'No se pudo eliminar.'); })
        .fail(function(){ SOV.warnModal('No se pudo eliminar.'); });
    };
    if(typeof Swal!=='undefined' && Swal.fire){
      Swal.fire({icon:'warning', title:'Eliminar permiso', text:'¿Eliminar: '+clave+'?', showCancelButton:true, confirmButtonText:'Sí', cancelButtonText:'Cancelar'})
        .then((r)=>{ if(r.isConfirmed) go(); });
    } else { if(confirm('¿Eliminar: '+clave+'?')) go(); }
  });

  loadRoles();
  loadPerms();
})();
</script>
