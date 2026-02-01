<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/controllers/proveedores/listado_de_proveedores.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$showAll = (int)($_GET['all'] ?? 0) === 1;
?>

<script>
  const CSRF = <?php echo json_encode(csrf_token()); ?>;
</script>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-8">
          <h1 class="m-0">
            <i class="fas fa-truck-loading mr-2 text-primary"></i>
            Proveedores
          </h1>
          <small class="text-muted">Gestiona proveedores, contactos y estado.</small>
        </div>
        <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
          <div class="btn-group">
            <a class="btn btn-outline-secondary" href="<?php echo $URL; ?>/proveedores<?php echo $showAll ? '' : '/?all=1'; ?>">
              <i class="fas fa-eye<?php echo $showAll ? '-slash' : ''; ?>"></i>
              <?php echo $showAll ? 'Solo activos' : 'Ver inactivos'; ?>
            </a>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-create">
              <i class="fa fa-plus"></i> Nuevo proveedor
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php if (function_exists('flash_render')) { flash_render(); } ?>

      <div class="card card-outline card-primary">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h3 class="card-title mb-0"><i class="fas fa-list mr-1"></i> Listado</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
          </div>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="tblProveedores" class="table table-bordered table-striped table-sm">
              <thead>
              <tr>
                <th class="text-center" style="width:60px;">Nro</th>
                <th>Proveedor</th>
                <th class="text-center" style="width:140px;">Celular</th>
                <th class="text-center" style="width:120px;">Teléfono</th>
                <th>Empresa</th>
                <th>Email</th>
                <th>Dirección</th>
                <th class="text-center" style="width:170px;">Acciones</th>
              </tr>
              </thead>
              <tbody>
              <?php
              $contador = 0;
              foreach ($proveedores_datos as $p) {
                $contador++;
                $id     = (int)($p['id_proveedor'] ?? 0);
                $nom    = (string)($p['nombre_proveedor'] ?? '');
                $cel    = (string)($p['celular'] ?? '');
                $tel    = (string)($p['telefono'] ?? '');
                $emp    = (string)($p['empresa'] ?? '');
                $mail   = (string)($p['email'] ?? '');
                $dir    = (string)($p['direccion'] ?? '');
                $estado = (int)($p['estado'] ?? 1);
                $digits = preg_replace('/\D+/', '', $cel);
              ?>
                <tr class="<?php echo $estado ? '' : 'text-muted'; ?>">
                  <td class="text-center"><?php echo $contador; ?></td>
                  <td>
                    <strong><?php echo h($nom); ?></strong>
                    <?php if(!$estado): ?>
                      <span class="badge badge-secondary ml-1">Inactivo</span>
                    <?php else: ?>
                      <span class="badge badge-success ml-1">Activo</span>
                    <?php endif; ?>
                    <br>
                    <small class="text-muted">ID: <?php echo $id; ?></small>
                  </td>

                  <td class="text-center">
                    <?php if (trim($digits) !== ''): ?>
                      <a class="btn btn-success btn-sm" target="_blank" rel="noopener"
                         href="<?php echo 'https://wa.me/' . rawurlencode($digits); ?>">
                        <i class="fa fa-whatsapp"></i>
                        <span class="ml-1"><?php echo h($cel); ?></span>
                      </a>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>

                  <td class="text-center"><?php echo $tel !== '' ? h($tel) : '<span class="text-muted">—</span>'; ?></td>
                  <td><?php echo $emp !== '' ? h($emp) : '<span class="text-muted">—</span>'; ?></td>
                  <td><?php echo $mail !== '' ? h($mail) : '<span class="text-muted">—</span>'; ?></td>
                  <td><?php echo $dir !== '' ? h($dir) : '<span class="text-muted">—</span>'; ?></td>

                  <td class="text-center">
                    <div class="btn-group">
                      <button
                        type="button"
                        class="btn btn-success btn-sm btn-edit"
                        data-toggle="modal"
                        data-target="#modal-edit"
                        data-id="<?php echo $id; ?>"
                        data-estado="<?php echo $estado; ?>"
                        data-nombre="<?php echo h($nom); ?>"
                        data-celular="<?php echo h($cel); ?>"
                        data-telefono="<?php echo h($tel); ?>"
                        data-empresa="<?php echo h($emp); ?>"
                        data-email="<?php echo h($mail); ?>"
                        data-direccion="<?php echo h($dir); ?>">
                        <i class="fa fa-pencil-alt"></i> <span class="d-none d-md-inline">Editar</span>
                      </button>

                      <?php if($estado): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-desactivar" data-id="<?php echo $id; ?>" data-nombre="<?php echo h($nom); ?>">
                          <i class="fa fa-ban"></i>
                        </button>
                      <?php else: ?>
                        <button type="button" class="btn btn-outline-primary btn-sm btn-activar" data-id="<?php echo $id; ?>" data-nombre="<?php echo h($nom); ?>">
                          <i class="fa fa-check"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
  $(function () {
    // DataTable
    $('#tblProveedores').DataTable({
      pageLength: 10,
      responsive: true,
      lengthChange: true,
      autoWidth: false,
      language: {
        emptyTable: 'No hay información',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ Proveedores',
        infoEmpty: 'Mostrando 0 a 0 de 0 Proveedores',
        infoFiltered: '(Filtrado de _MAX_ total Proveedores)',
        lengthMenu: 'Mostrar _MENU_ Proveedores',
        loadingRecords: 'Cargando...',
        processing: 'Procesando...',
        search: 'Buscar:',
        zeroRecords: 'Sin resultados encontrados',
        paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
      },
      buttons: [
        { extend: 'collection', text: 'Reportes', buttons: ['copy','csv','excel','pdf','print'] },
        { extend: 'colvis', text: 'Columnas' }
      ]
    }).buttons().container().appendTo('#tblProveedores_wrapper .col-md-6:eq(0)');

    // Modal edición (rellenar)
    $(document).on('click', '.btn-edit', function(){
      var $b = $(this);
      $('#edit_id_proveedor').val($b.data('id'));
      $('#edit_nombre_proveedor').val($b.data('nombre') || '');
      $('#edit_celular').val($b.data('celular') || '');
      $('#edit_telefono').val($b.data('telefono') || '');
      $('#edit_empresa').val($b.data('empresa') || '');
      $('#edit_email').val($b.data('email') || '');
      $('#edit_direccion').val($b.data('direccion') || '');
    });

    // Guardar edición
    $('#btnSaveEdit').on('click', function(){
      var payload = {
        _csrf: CSRF,
        id_proveedor: $('#edit_id_proveedor').val(),
        nombre_proveedor: $('#edit_nombre_proveedor').val(),
        celular: $('#edit_celular').val(),
        telefono: $('#edit_telefono').val(),
        empresa: $('#edit_empresa').val(),
        email: $('#edit_email').val(),
        direccion: $('#edit_direccion').val()
      };

      if (!payload.nombre_proveedor || !payload.celular || !payload.empresa || !payload.direccion) {
        return (window.SOV && SOV.warnModal) ? SOV.warnModal('Completa los campos obligatorios (Nombre, Celular, Empresa, Dirección).') : alert('Completa los campos obligatorios.');
      }

      var doReq = (window.SOV && SOV.ajaxJson) ? SOV.ajaxJson : function(o){ return $.ajax(o); };

      doReq({
        url: "../app/controllers/proveedores/update.php",
        method: 'POST',
        dataType: 'json',
        data: payload,
        headers: { 'X-Requested-With':'XMLHttpRequest' }
      }).done(function(resp){
        if(resp && resp.ok){
          $('#modal-edit').one('hidden.bs.modal', function(){ location.reload(); });
          $('#modal-edit').modal('hide');
        }else{
          var msg = (resp && resp.error) ? resp.error : 'No se pudo actualizar.';
          (window.SOV && SOV.warnModal) ? SOV.warnModal(msg) : alert(msg);
        }
      }).fail(function(xhr){
        var msg = xhr.responseText || ('HTTP ' + xhr.status);
        (window.SOV && SOV.warnModal) ? SOV.warnModal(msg) : alert(msg);
      });
    });

    function confirmAndPost(opts){
      var go = function(){
        var doReq = (window.SOV && SOV.ajaxJson) ? SOV.ajaxJson : function(o){ return $.ajax(o); };
        return doReq({
          url: opts.url,
          method: 'POST',
          dataType: 'json',
          data: opts.data,
          headers: { 'X-Requested-With':'XMLHttpRequest' }
        }).done(function(resp){
          if(resp && resp.ok){
            location.reload();
          } else {
            var msg = (resp && resp.error) ? resp.error : 'Operación no realizada.';
            (window.SOV && SOV.warnModal) ? SOV.warnModal(msg) : alert(msg);
          }
        }).fail(function(xhr){
          var msg = xhr.responseText || ('HTTP ' + xhr.status);
          (window.SOV && SOV.warnModal) ? SOV.warnModal(msg) : alert(msg);
        });
      };

      if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({
          icon: opts.icon || 'warning',
          title: opts.title,
          text: opts.text,
          showCancelButton: true,
          confirmButtonText: opts.confirmText || 'Confirmar',
          cancelButtonText: 'Cancelar'
        }).then(function(r){ if(r.isConfirmed) go(); });
      } else {
        if (confirm(opts.text)) go();
      }
    }

    // Desactivar (soft delete)
    $(document).on('click', '.btn-desactivar', function(){
      var id = $(this).data('id');
      var nombre = $(this).data('nombre');
      confirmAndPost({
        title: 'Desactivar proveedor',
        text: '¿Desactivar: ' + nombre + '?',
        confirmText: 'Sí, desactivar',
        url: "../app/controllers/proveedores/delete.php",
        data: { _csrf: CSRF, id_proveedor: id }
      });
    });

    // Activar
    $(document).on('click', '.btn-activar', function(){
      var id = $(this).data('id');
      var nombre = $(this).data('nombre');
      confirmAndPost({
        icon: 'question',
        title: 'Activar proveedor',
        text: '¿Activar: ' + nombre + '?',
        confirmText: 'Sí, activar',
        url: "../app/controllers/proveedores/toggle_estado.php",
        data: { _csrf: CSRF, id_proveedor: id, accion: 'activar' }
      });
    });

    // Crear
    $('#btnCreateProveedor').on('click', function(){
      var payload = {
        _csrf: CSRF,
        nombre_proveedor: $('#c_nombre_proveedor').val(),
        celular: $('#c_celular').val(),
        telefono: $('#c_telefono').val(),
        empresa: $('#c_empresa').val(),
        email: $('#c_email').val(),
        direccion: $('#c_direccion').val()
      };

      if (!payload.nombre_proveedor || !payload.celular || !payload.empresa || !payload.direccion) {
        return (window.SOV && SOV.warnModal) ? SOV.warnModal('Completa los campos obligatorios (Nombre, Celular, Empresa, Dirección).') : alert('Completa los campos obligatorios.');
      }

      var doReq = (window.SOV && SOV.ajaxJson) ? SOV.ajaxJson : function(o){ return $.ajax(o); };

      doReq({
        url: "../app/controllers/proveedores/create.php",
        method: 'POST',
        dataType: 'json',
        data: payload,
        headers: { 'X-Requested-With':'XMLHttpRequest' }
      }).done(function(resp){
        if(resp && resp.ok){
          $('#modal-create').one('hidden.bs.modal', function(){ location.reload(); });
          $('#modal-create').modal('hide');
        }else{
          var msg = (resp && resp.error) ? resp.error : 'No se pudo guardar.';
          (window.SOV && SOV.warnModal) ? SOV.warnModal(msg) : alert(msg);
        }
      }).fail(function(xhr){
        var msg = xhr.responseText || ('HTTP ' + xhr.status);
        (window.SOV && SOV.warnModal) ? SOV.warnModal(msg) : alert(msg);
      });
    });
  });
</script>

<!-- Modal EDIT (único) -->
<div class="modal fade" id="modal-edit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h4 class="modal-title"><i class="fa fa-pencil-alt mr-1"></i> Editar proveedor</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_id_proveedor">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Nombre <b class="text-danger">*</b></label>
              <input type="text" id="edit_nombre_proveedor" class="form-control" maxlength="150">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Celular <b class="text-danger">*</b></label>
              <input type="text" id="edit_celular" class="form-control" maxlength="30">
              <small class="text-muted">Solo números recomendado (para WhatsApp).</small>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Teléfono</label>
              <input type="text" id="edit_telefono" class="form-control" maxlength="30">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Empresa <b class="text-danger">*</b></label>
              <input type="text" id="edit_empresa" class="form-control" maxlength="150">
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Email</label>
              <input type="email" id="edit_email" class="form-control" maxlength="320">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Dirección <b class="text-danger">*</b></label>
              <textarea id="edit_direccion" class="form-control" rows="3" maxlength="255"></textarea>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnSaveEdit"><i class="fa fa-save"></i> Guardar cambios</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal CREATE (único) -->
<div class="modal fade" id="modal-create" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h4 class="modal-title"><i class="fa fa-plus mr-1"></i> Nuevo proveedor</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Nombre <b class="text-danger">*</b></label>
              <input type="text" id="c_nombre_proveedor" class="form-control" maxlength="150">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Celular <b class="text-danger">*</b></label>
              <input type="text" id="c_celular" class="form-control" maxlength="30">
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Teléfono</label>
              <input type="text" id="c_telefono" class="form-control" maxlength="30">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Empresa <b class="text-danger">*</b></label>
              <input type="text" id="c_empresa" class="form-control" maxlength="150">
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Email</label>
              <input type="email" id="c_email" class="form-control" maxlength="320">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Dirección <b class="text-danger">*</b></label>
              <textarea id="c_direccion" class="form-control" rows="3" maxlength="255"></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnCreateProveedor"><i class="fa fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>
