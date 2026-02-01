<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

// Solo ADMINISTRADOR para Roles/Permisos
if (function_exists('require_admin')) {
  require_admin($pdo, $URL . '/index.php');
} else {
  if (function_exists('require_perm')) require_perm($pdo, 'roles.ver', $URL . '/index.php');
}

require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/controllers/roles/listado_de_roles.php';

function e($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<script>
  window.SOV_CSRF = <?php echo json_encode(csrf_token()); ?>;
</script>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-8">
          <h1 class="m-0"><i class="fas fa-user-shield mr-2 text-primary"></i>Roles</h1>
          <small class="text-muted">Crea roles y gestiona accesos por permisos (RBAC).</small>
        </div>
        <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
          <button class="btn btn-primary" data-toggle="modal" data-target="#modal-role-create">
            <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Nuevo rol</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php if (function_exists('flash_render')) flash_render(); ?>

      <div class="row">
        <div class="col-lg-4 col-md-5">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo count($roles_datos ?? []); ?></h3>
              <p>Roles registrados</p>
            </div>
            <div class="icon"><i class="fas fa-id-badge"></i></div>
          </div>

          <div class="card card-outline card-info">
            <div class="card-body">
              <div class="d-flex align-items-start">
                <i class="fas fa-lightbulb text-info mr-2 mt-1"></i>
                <div>
                  <div class="font-weight-bold">Buenas prácticas</div>
                  <small class="text-muted d-block">
                    Mantén <b>ADMINISTRADOR</b> como rol del sistema.
                    Crea roles por función: <b>CAJERO</b>, <b>VENDEDOR</b>, <b>ALMACEN</b>.
                  </small>
                  <small class="text-muted d-block mt-2">
                    “Eliminar” = <b>Desactivar</b> para no romper integridad referencial.
                  </small>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="col-lg-8 col-md-7">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-list mr-1"></i>Listado</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
              </div>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table id="rolesTable" class="table table-bordered table-striped table-hover">
                  <thead>
                    <tr>
                      <th style="width:70px" class="text-center">#</th>
                      <th>Rol</th>
                      <th style="width:130px" class="text-center">Estado</th>
                      <th style="width:260px" class="text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i = 0;
                    foreach (($roles_datos ?? []) as $r): $i++; ?>
                      <?php
                      $idRol = (int)($r['id_rol'] ?? 0);
                      $rolNombre = (string)($r['rol'] ?? '');
                      $estado = strtoupper((string)($r['estado'] ?? 'ACTIVO'));
                      $esAdminRol = (strtoupper($rolNombre) === 'ADMINISTRADOR');
                      $badge = ($estado === 'ACTIVO') ? 'success' : 'secondary';
                      ?>
                      <tr data-id="<?php echo $idRol; ?>"
                        data-rol="<?php echo e($rolNombre); ?>"
                        data-estado="<?php echo e($estado); ?>">
                        <td class="text-center"><?php echo $i; ?></td>
                        <td>
                          <?php if ($esAdminRol): ?><span class="badge badge-primary mr-2">Sistema</span><?php endif; ?>
                          <?php echo e($rolNombre); ?>
                        </td>
                        <td class="text-center">
                          <span class="badge badge-<?php echo $badge; ?>"><?php echo e($estado); ?></span>
                        </td>
                        <td class="text-center">
                          <div class="btn-group sov-btn-group">
                            <button class="btn btn-outline-primary btn-sm btn-role-perms" type="button" title="Permisos">
                              <i class="fas fa-key"></i> <span class="d-none d-md-inline">Permisos</span>
                            </button>

                            <button class="btn btn-success btn-sm btn-role-edit" type="button" title="Editar"
                              <?php echo $esAdminRol ? 'disabled' : ''; ?>>
                              <i class="fas fa-edit"></i> <span class="d-none d-md-inline">Editar</span>
                            </button>

                            <button class="btn btn-outline-warning btn-sm btn-role-toggle" type="button" title="Activar/Desactivar"
                              <?php echo $esAdminRol ? 'disabled' : ''; ?>>
                              <i class="fas fa-toggle-on"></i>
                            </button>
                          </div>
                          <?php if ($esAdminRol): ?><div><small class="text-muted">Protegido.</small></div><?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <small class="text-muted d-block mt-2">
                Tip: usa “Permisos” para asignar accesos por módulo.
              </small>
            </div>

          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<!-- Modal: Crear Rol -->
<div class="modal fade" id="modal-role-create" tabindex="-1" aria-hidden="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Nuevo rol</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="form-role-create" action="../app/controllers/roles/create.php" method="POST">
        <div class="modal-body">
          <?php echo csrf_field(); ?>
          <div class="form-group">
            <label>Nombre del rol</label>
            <input type="text" name="rol" class="form-control" maxlength="50" placeholder="Ej: CAJERO" required autocomplete="off">
            <small class="text-muted">Recomendado: MAYÚSCULAS y sin símbolos raros.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Editar Rol -->
<div class="modal fade" id="modal-role-edit" tabindex="-1" aria-hidden="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar rol</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="form-role-edit" action="../app/controllers/roles/update_roles.php" method="POST">
        <div class="modal-body">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id_rol" id="edit_id_rol">
          <div class="form-group">
            <label>Nombre del rol</label>
            <input type="text" name="rol" id="edit_rol" class="form-control" maxlength="50" required autocomplete="off">
          </div>
          <div class="alert alert-info py-2 mb-0">
            <small><i class="fas fa-info-circle mr-1"></i>Se asocia por ID. Cambiar nombre no rompe usuarios.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Permisos (placeholder UI) -->
<div class="modal fade" id="modal-role-perms" tabindex="-1" aria-hidden="false">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-dark">
        <h5 class="modal-title"><i class="fas fa-key mr-2"></i>Permisos del rol: <span id="perms_role_name"></span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-5">
            <input type="text" id="perm_search" class="form-control" placeholder="Buscar permiso (ej: ventas.crear)">
          </div>
          <div class="col-md-7 text-right mt-2 mt-md-0">
            <button type="button" class="btn btn-outline-light btn-sm" id="perm_select_all">
              <i class="fas fa-check-square"></i> Seleccionar todo
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="perm_clear_all">
              <i class="fas fa-square"></i> Limpiar
            </button>
          </div>
        </div>
        <hr>
        <div id="perm_container">
          <div class="text-muted">Cargando permisos...</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" id="btn-save-perms" disabled>
          <i class="fas fa-save"></i> Guardar permisos
        </button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
  $(function() {
    if ($.fn.DataTable) {
      $('#rolesTable').DataTable({
        pageLength: 10,
        responsive: true,
        lengthChange: true,
        autoWidth: false,
        language: {
          search: "Buscar:",
          lengthMenu: "Mostrar _MENU_",
          info: "Mostrando _START_ a _END_ de _TOTAL_",
          infoEmpty: "Sin registros",
          zeroRecords: "Sin resultados",
          paginate: {
            first: "Primero",
            last: "Último",
            next: "Siguiente",
            previous: "Anterior"
          }
        }
      });
    }

    // Edit
    $(document).on('click', '.btn-role-edit:not([disabled])', function() {
      var $tr = $(this).closest('tr');
      $('#edit_id_rol').val($tr.data('id'));
      $('#edit_rol').val($tr.data('rol'));
      $('#modal-role-edit').modal('show');
    });

    // Permisos
    $(document).on('click', '.btn-role-perms', function() {
      var $tr = $(this).closest('tr');
      $('#perms_role_name').text($tr.data('rol'));
      $('#modal-role-perms').modal('show');
    });

    // Toggle estado (requiere controller roles/toggle_estado.php)
    $(document).on('click', '.btn-role-toggle:not([disabled])', function() {
      if (!(window.SOV && SOV.ajaxJson)) return;
      var $tr = $(this).closest('tr');
      var id = $tr.data('id');
      var rol = $tr.data('rol');
      var st = String($tr.data('estado') || 'ACTIVO').toUpperCase();
      var next = (st === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

      var go = function() {
        SOV.ajaxJson({
            url: '../app/controllers/roles/toggle_estado.php',
            method: 'POST',
            data: {
              _csrf: window.SOV_CSRF,
              id_rol: id,
              estado: next
            }
          })
          .done(function(resp) {
            if (resp && resp.ok) {
              location.reload();
            } else SOV.warnModal(resp.error || 'No se pudo actualizar.');
          })
          .fail(function(xhr) {
            var msg = 'No se pudo actualizar.';
            try {
              var j = JSON.parse(xhr.responseText);
              if (j.error) msg = j.error;
            } catch (e) {}
            SOV.warnModal(msg);
          });
      };

      if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({
            icon: 'warning',
            title: 'Actualizar estado',
            text: '¿Cambiar estado de ' + rol + ' a ' + next + '?',
            showCancelButton: true,
            confirmButtonText: 'Sí',
            cancelButtonText: 'Cancelar'
          })
          .then((r) => {
            if (r.isConfirmed) go();
          });
      } else {
        if (confirm('¿Cambiar estado de ' + rol + ' a ' + next + '?')) go();
      }
    });

    // Submit create/edit via AJAX (CSRF ok por FormData)
    $('#form-role-create, #form-role-edit').on('submit', function(e) {
      if (!(window.SOV && SOV.ajaxJson)) return;
      e.preventDefault();
      var $f = $(this);
      var $btn = $f.find('button[type="submit"]');
      $btn.prop('disabled', true);

      var fd = new FormData(this);
      SOV.ajaxJson({
          url: $f.attr('action'),
          method: 'POST',
          data: fd
        })
        .done(function(resp) {
          if (resp && resp.ok) {
            location.reload();
          } else {
            $btn.prop('disabled', false);
            SOV.warnModal(resp.error || 'No se pudo guardar.');
          }
        })
        .fail(function(xhr) {
          $btn.prop('disabled', false);
          var msg = 'No se pudo guardar.';
          try {
            var j = JSON.parse(xhr.responseText);
            if (j.error) msg = j.error;
          } catch (e) {}
          SOV.warnModal(msg);
        });
    });

    $(document).on('click', '.btn-role-perms', function() {
      var $tr = $(this).closest('tr');
      var idRol = $tr.data('id');
      var rolName = $tr.data('rol');
      $('#perms_role_name').text(rolName);
      $('#modal-role-perms').data('idrol', idRol);
      $('#perm_container').html('<div class="text-muted">Cargando...</div>');

      SOV.ajaxJson({
          url: '../app/controllers/roles/permisos_list.php',
          method: 'GET',
          data: {
            id_rol: idRol
          }
        })
        .done(function(resp) {
          if (!resp || !resp.ok) {
            $('#perm_container').html('<div class="text-danger">No se pudo cargar.</div>');
            return;
          }
          var groups = (resp.data && resp.data.groups) ? resp.data.groups : {};
          var html = '';
          Object.keys(groups).forEach(function(mod) {
            html += '<div class="card card-outline card-secondary mb-2">';
            html += '<div class="card-header py-2"><b>' + mod + '</b></div>';
            html += '<div class="card-body py-2">';
            groups[mod].forEach(function(p) {
              var chk = p.checked ? 'checked' : '';
              html += '<div class="custom-control custom-checkbox">';
              html += '  <input class="custom-control-input perm-check" type="checkbox" id="perm_' + p.id_permiso + '" value="' + p.id_permiso + '" ' + chk + '>';
              html += '  <label class="custom-control-label" for="perm_' + p.id_permiso + '"><span class="badge badge-dark mr-2">' + p.clave + '</span> ' + (p.descripcion || '') + '</label>';
              html += '</div>';
            });
            html += '</div></div>';
          });
          $('#perm_container').html(html || '<div class="text-muted">Sin permisos.</div>');
          $('#btn-save-perms').prop('disabled', false);
        })
        .fail(function() {
          $('#perm_container').html('<div class="text-danger">Error cargando permisos.</div>');
        });
    });

    $('#btn-save-perms').on('click', function() {
      var idRol = $('#modal-role-perms').data('idrol');
      var ids = [];
      $('.perm-check:checked').each(function() {
        ids.push($(this).val());
      });

      var fd = new FormData();
      fd.append('_csrf', window.SOV_CSRF);
      fd.append('id_rol', idRol);
      ids.forEach(function(v) {
        fd.append('permisos[]', v);
      });

      SOV.ajaxJson({
          url: '../app/controllers/roles/permisos_save.php',
          method: 'POST',
          data: fd
        })
        .done(function(resp) {
          if (resp && resp.ok) {
            location.reload();
          } else SOV.warnModal(resp.error || 'No se pudo guardar.');
        })
        .fail(function(xhr) {
          var msg = 'No se pudo guardar.';
          try {
            var j = JSON.parse(xhr.responseText);
            if (j.error) msg = j.error;
          } catch (e) {}
          SOV.warnModal(msg);
        });
    });


  });
</script>