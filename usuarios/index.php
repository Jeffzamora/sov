<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

require_admin($pdo, $URL . '/index.php');


require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/controllers/usuarios/listado_de_usuarios.php';
require_once __DIR__ . '/../app/controllers/roles/listado_de_roles.php';

function e_attr($v): string
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Métricas
$usuarios = $usuarios_datos ?? [];
$totalUsuarios = is_array($usuarios) ? count($usuarios) : 0;
$activos = 0;
$inactivos = 0;
if (is_array($usuarios)) {
  foreach ($usuarios as $u) {
    $st = strtoupper((string)($u['estado'] ?? 'ACTIVO'));
    if ($st === 'ACTIVO') $activos++;
    else $inactivos++;
  }
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
          <h1 class="m-0"><i class="fas fa-users-cog mr-2 text-success"></i>Usuarios</h1>
          <small class="text-muted">Gestión de usuarios, roles, estado y contraseña.</small>
        </div>
        <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
          <?php if (ui_can('usuarios.crear')): ?>
            <button class="btn btn-success" data-toggle="modal" data-target="#modal-user-create">
              <i class="fas fa-user-plus"></i> <span class="d-none d-md-inline">Nuevo usuario</span>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php if (function_exists('flash_render')) flash_render(); ?>

      <div class="row">
        <div class="col-lg-4 col-md-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?php echo (int)$activos; ?></h3>
              <p>Usuarios activos</p>
            </div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="small-box bg-secondary">
            <div class="inner">
              <h3><?php echo (int)$inactivos; ?></h3>
              <p>Usuarios inactivos</p>
            </div>
            <div class="icon"><i class="fas fa-user-slash"></i></div>
          </div>
        </div>
        <div class="col-lg-4 d-none d-lg-block">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo (int)$totalUsuarios; ?></h3>
              <p>Total usuarios</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
          </div>
        </div>

        <div class="col-12">
          <div class="card card-outline card-success">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-list mr-1"></i>Listado</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table id="usuariosTable" class="table table-bordered table-striped table-hover">
                  <thead>
                    <tr>
                      <th style="width:70px">
                        <center>#</center>
                      </th>
                      <th>Nombre</th>
                      <th>Email</th>
                      <th>Rol</th>
                      <th style="width:110px">
                        <center>Estado</center>
                      </th>
                      <th style="width:220px">
                        <center>Acciones</center>
                      </th>
                    </tr>
                  </thead>

                  <tbody>
                    <?php $i = 0;
                    foreach (($usuarios ?? []) as $u): $i++; ?>
                      <?php
                      $id = (int)($u['id_usuario'] ?? 0);
                      $nombres = (string)($u['nombres'] ?? '');
                      $email = (string)($u['email'] ?? '');
                      $rol = (string)($u['rol'] ?? '');
                      $idRol = (int)($u['id_rol'] ?? 0);
                      $estado = strtoupper((string)($u['estado'] ?? 'ACTIVO'));
                      ?>
                      <tr
                        data-id="<?php echo $id; ?>"
                        data-nombres="<?php echo e_attr($nombres); ?>"
                        data-email="<?php echo e_attr($email); ?>"
                        data-rol="<?php echo e_attr($rol); ?>"
                        data-id_rol="<?php echo $idRol; ?>"
                        data-estado="<?php echo e_attr($estado); ?>">
                        <td>
                          <center><?php echo $i; ?></center>
                        </td>

                        <td>
                          <strong><?php echo htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8'); ?></strong>
                          <?php if ($id === (int)$id_usuario_sesion): ?>
                            <span class="badge badge-light ml-1">Tú</span>
                          <?php endif; ?>
                        </td>

                        <td>
                          <span class="badge badge-light">
                            <i class="far fa-envelope mr-1"></i><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        </td>

                        <td><span class="badge badge-info"><?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?></span></td>

                        <td class="text-center">
                          <?php if ($estado === 'ACTIVO'): ?>
                            <span class="badge badge-success">ACTIVO</span>
                          <?php else: ?>
                            <span class="badge badge-secondary">INACTIVO</span>
                          <?php endif; ?>
                        </td>

                        <td>
                          <center>
                            <div class="btn-group sov-btn-group">
                              <?php if (ui_can('usuarios.actualizar')): ?>
                                <button class="btn btn-outline-success btn-sm btn-user-edit" type="button" title="Editar">
                                  <i class="fas fa-edit"></i> <span class="d-none d-md-inline">Editar</span>
                                </button>
                                <button class="btn btn-outline-primary btn-sm btn-user-pass" type="button" title="Cambiar contraseña">
                                  <i class="fas fa-key"></i>
                                </button>
                              <?php endif; ?>

                              <?php if (ui_can('usuarios.eliminar')): ?>
                                <button class="btn btn-outline-warning btn-sm btn-user-toggle" type="button" title="Activar/Desactivar">
                                  <i class="fas fa-toggle-on"></i>
                                </button>
                              <?php endif; ?>
                            </div>
                          </center>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <small class="text-muted d-block mt-2">
                Tip: “Clave” actualiza solo la contraseña. “Activar/Desactivar” es soft-delete (no se borra).
              </small>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<!-- Modal: Crear usuario -->
<div class="modal fade" id="modal-user-create" tabindex="-1" aria-hidden="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Nuevo usuario</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <form id="form-user-create" action="../app/controllers/usuarios/create.php" method="POST" novalidate>
        <div class="modal-body">
          <?php echo csrf_field(); ?>

          <div class="form-group">
            <label>Nombres</label>
            <input type="text" name="nombres" class="form-control" maxlength="120" required autocomplete="off">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required autocomplete="off">
          </div>

          <div class="form-group">
            <label>Rol</label>
            <!-- Compat: enviamos id_rol y rol (por si tu controller aún usa "rol") -->
            <select name="id_rol" class="form-control" required>
              <option value="">Seleccione...</option>
              <?php foreach (($roles_datos ?? []) as $r): ?>
                <option value="<?php echo (int)$r['id_rol']; ?>"><?php echo htmlspecialchars((string)$r['rol'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="rol" value="">
          </div>

          <div class="form-row">
            <div class="form-group col-12 col-md-6">
              <label>Contraseña</label>
              <input type="password" name="password_user" class="form-control" minlength="8" required autocomplete="new-password">
            </div>
            <div class="form-group col-12 col-md-6">
              <label>Repetir</label>
              <input type="password" name="password_repeat" class="form-control" minlength="8" required autocomplete="new-password">
            </div>
          </div>

          <small class="text-muted">Mínimo 8 caracteres.</small>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Editar usuario -->
<div class="modal fade" id="modal-user-edit" tabindex="-1" aria-hidden="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar usuario</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <form id="form-user-edit" action="../app/controllers/usuarios/update_usuario.php" method="POST" novalidate>
        <div class="modal-body">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id_usuario" id="edit_id_usuario">

          <div class="form-group">
            <label>Nombres</label>
            <input type="text" name="nombres" id="edit_nombres" class="form-control" maxlength="120" required>
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="edit_email" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Rol</label>
            <select name="id_rol" id="edit_id_rol" class="form-control" required>
              <option value="">Seleccione...</option>
              <?php foreach (($roles_datos ?? []) as $r): ?>
                <option value="<?php echo (int)$r['id_rol']; ?>"><?php echo htmlspecialchars((string)$r['rol'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="rol" id="edit_rol_compat" value="">
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

<!-- Modal: Cambiar contraseña -->
<div class="modal fade" id="modal-user-pass" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fas fa-key mr-2"></i>Cambiar contraseña</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <form id="form-user-pass" action="../app/controllers/usuarios/update.php" method="POST" novalidate>
        <div class="modal-body">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id_usuario" id="pass_id_usuario">

          <div class="alert alert-warning py-2">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Se actualizará únicamente la contraseña del usuario seleccionado.
          </div>

          <div class="form-row">
            <div class="form-group col-12 col-md-6">
              <label>Nueva contraseña</label>
              <input type="password" name="password_user" class="form-control" minlength="8" required autocomplete="new-password">
            </div>
            <div class="form-group col-12 col-md-6">
              <label>Repetir</label>
              <input type="password" name="password_repeat" class="form-control" minlength="8" required autocomplete="new-password">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
  $(function() {
    if ($.fn.DataTable) {
      $('#usuariosTable').DataTable({
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

    // Helper: set compat "rol" hidden from selected text (si tu backend aún lo usa)
    function syncRolCompat($form) {
      var $sel = $form.find('select[name="id_rol"]');
      if (!$sel.length) return;
      var txt = $sel.find('option:selected').text().trim();
      $form.find('input[name="rol"]').val(txt);
    }

    $('#form-user-create select[name="id_rol"]').on('change', function() {
      syncRolCompat($('#form-user-create'));
    });
    $('#form-user-edit select[name="id_rol"]').on('change', function() {
      syncRolCompat($('#form-user-edit'));
    });

    // Abrir editar
    $(document).on('click', '.btn-user-edit', function() {
      var $tr = $(this).closest('tr');
      $('#edit_id_usuario').val($tr.data('id'));
      $('#edit_nombres').val($tr.data('nombres'));
      $('#edit_email').val($tr.data('email'));

      var idRol = $tr.data('id_rol');
      $('#edit_id_rol').val(idRol ? String(idRol) : '');
      syncRolCompat($('#form-user-edit'));

      $('#modal-user-edit').modal('show');
    });

    // Abrir pass
    $(document).on('click', '.btn-user-pass', function() {
      var $tr = $(this).closest('tr');
      $('#pass_id_usuario').val($tr.data('id'));
      $('#modal-user-pass').modal('show');
    });

    // Activar/Desactivar
    $(document).on('click', '.btn-user-toggle', function() {
      var $tr = $(this).closest('tr');
      var id = $tr.data('id');
      var n = $tr.data('nombres');
      var st = String($tr.data('estado') || 'ACTIVO').toUpperCase();
      var next = (st === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

      var go = function() {
        SOV.ajaxJson({
            url: '../app/controllers/usuarios/toggle_estado.php',
            method: 'POST',
            data: {
              _csrf: window.SOV_CSRF,
              id_usuario: id,
              estado: next
            }
          })
          .done(function(resp) {
            if (resp && resp.ok) {
              location.reload();
            } else SOV.warnModal((resp && resp.error) ? resp.error : 'No se pudo actualizar el estado.');
          })
          .fail(function(xhr) {
            var msg = 'No se pudo actualizar el estado.';
            try {
              var j = JSON.parse(xhr.responseText);
              if (j.error) msg = j.error;
            } catch (e) {}
            SOV.warnModal(msg);
          });
      };

      if (typeof Swal !== 'undefined' && Swal.fire) {
        var titulo = (next === 'INACTIVO') ? 'Desactivar usuario' : 'Activar usuario';
        var texto = (next === 'INACTIVO') ? ('¿Desactivar: ' + n + '?') : ('¿Activar: ' + n + '?');
        Swal.fire({
          icon: 'warning',
          title: titulo,
          text: texto,
          showCancelButton: true,
          confirmButtonText: 'Sí, continuar',
          cancelButtonText: 'Cancelar'
        }).then((r) => {
          if (r.isConfirmed) go();
        });
      } else {
        if (confirm('¿Continuar con la acción para: ' + n + '?')) go();
      }
    });

    // Submit AJAX (con fallback seguro para FormData)
    $('#form-user-create, #form-user-edit, #form-user-pass').on('submit', function(e) {
      if (!(window.SOV && SOV.ajaxJson)) return; // fallback a submit normal
      e.preventDefault();

      var $f = $(this);
      syncRolCompat($f);

      // Validación local contraseñas
      if ($f.is('#form-user-pass') || $f.is('#form-user-create')) {
        var p1 = $f.find('input[name="password_user"]').val();
        var p2 = $f.find('input[name="password_repeat"]').val();
        if (!p1 || !p2) {
          SOV.warnModal('Debe completar la contraseña y confirmación.');
          return;
        }
        if (String(p1) !== String(p2)) {
          SOV.warnModal('Las contraseñas no coinciden.');
          return;
        }
        if (String(p1).length < 8) {
          SOV.warnModal('La contraseña debe tener mínimo 8 caracteres.');
          return;
        }
      }

      var fd = new FormData(this);

      // Intento por SOV.ajaxJson (si soporta FormData)
      var req = SOV.ajaxJson({
        url: $f.attr('action'),
        method: 'POST',
        data: fd
      });

      // Si tu helper no soporta FormData, esto suele fallar. Fallback:
      if (!req || !req.done) {
        $.ajax({
          url: $f.attr('action'),
          method: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          dataType: 'json',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).done(function(resp) {
          if (resp && resp.ok) {
            location.reload();
          } else SOV.warnModal((resp && resp.error) ? resp.error : 'No se pudo guardar.');
        }).fail(function(xhr) {
          var msg = 'No se pudo guardar.';
          try {
            var j = JSON.parse(xhr.responseText);
            if (j.error) msg = j.error;
          } catch (e) {}
          SOV.warnModal(msg);
        });
        return;
      }

      req.done(function(resp) {
        if (resp && resp.ok) {
          location.reload();
        } else SOV.warnModal((resp && resp.error) ? resp.error : 'No se pudo guardar.');
      }).fail(function(xhr) {
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