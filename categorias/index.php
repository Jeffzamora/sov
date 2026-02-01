<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

// (Opcional) Permiso
// require_perm($pdo, 'categorias.ver', $URL . '/index.php');

require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/controllers/categorias/listado_de_categoria.php';

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
                    <h1 class="m-0"><i class="fas fa-tags mr-2 text-primary"></i>Categorías</h1>
                    <small class="text-muted">Administra categorías (recomendado: desactivar en lugar de eliminar).</small>
                </div>
                <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-cat-create">
                        <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Nueva categoría</span>
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
                            <h3><?php echo count($categorias_datos ?? []); ?></h3>
                            <p>Categorías registradas</p>
                        </div>
                        <div class="icon"><i class="fas fa-layer-group"></i></div>
                    </div>

                    <div class="card card-outline card-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle text-info mr-2"></i>
                                <small class="text-muted">
                                    Si tu tabla tiene <b>estado</b>, lo correcto es <b>desactivar</b> (INACTIVO) para no perder historial.
                                </small>
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
                                <table id="catsTable" class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width:70px" class="text-center">#</th>
                                            <th>Nombre</th>
                                            <th style="width:200px" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 0;
                                        foreach (($categorias_datos ?? []) as $c): $i++; ?>
                                            <?php
                                            $id = (int)($c['id_categoria'] ?? 0);
                                            $nombre = (string)($c['nombre_categoria'] ?? '');
                                            $estado = strtoupper((string)($c['estado'] ?? 'ACTIVO'));
                                            $isInactivo = ($estado === 'INACTIVO');
                                            ?>
                                            <tr
                                                data-id="<?php echo $id; ?>"
                                                data-nombre="<?php echo e($nombre); ?>"
                                                data-estado="<?php echo e($estado); ?>">
                                                <td class="text-center"><?php echo $i; ?></td>
                                                <td>
                                                    <?php if ($isInactivo): ?>
                                                        <span class="badge badge-secondary mr-2">INACTIVO</span>
                                                    <?php endif; ?>
                                                    <?php echo e($nombre); ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group sov-btn-group">
                                                        <button type="button" class="btn btn-success btn-sm btn-cat-edit" title="Editar">
                                                            <i class="fas fa-edit"></i> <span class="d-none d-md-inline">Editar</span>
                                                        </button>

                                                        <?php if (!$isInactivo): ?>
                                                            <button type="button" class="btn btn-warning btn-sm btn-cat-toggle" data-next="INACTIVO" title="Desactivar">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-info btn-sm btn-cat-toggle" data-next="ACTIVO" title="Activar">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <button type="button" class="btn btn-danger btn-sm btn-cat-del" title="Eliminar (no recomendado)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <small class="text-muted d-block mt-2">
                                Recomendación: usa activar/desactivar para no romper referencias con productos.
                            </small>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="modal-cat-create" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Nueva categoría</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="form-cat-create" action="../app/controllers/categorias/registro_de_categorias.php" method="POST">
                <div class="modal-body">
                    <?php echo csrf_field(); ?>
                    <div class="form-group mb-0">
                        <label>Nombre</label>
                        <input type="text" name="nombre_categoria" class="form-control" maxlength="100" required autocomplete="off" placeholder="Ej: Lentes">
                        <small class="text-muted">Evita duplicados. Ej: “ARMAZONES”, “LENTES”, “ACC. LIMPIEZA”.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar (reusable) -->
<div class="modal fade" id="modal-cat-edit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar categoría</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="form-cat-edit" action="../app/controllers/categorias/update_de_categorias.php" method="POST">
                <div class="modal-body">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id_categoria" id="edit_id_categoria">
                    <div class="form-group mb-0">
                        <label>Nombre</label>
                        <input type="text" name="nombre_categoria" id="edit_nombre_categoria" class="form-control" maxlength="100" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
    $(function() {

        // DataTable
        if ($.fn.DataTable) {
            $('#catsTable').DataTable({
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

        // Create (AJAX)
        $('#form-cat-create').on('submit', function(e) {
            if (!(window.SOV && SOV.ajaxJson)) return;
            e.preventDefault();
            var $f = $(this);
            var $btn = $f.find('button[type="submit"]').prop('disabled', true);
            var fd = new FormData(this);

            SOV.ajaxJson({
                    url: $f.attr('action'),
                    method: 'POST',
                    data: fd
                })
                .done(function(resp) {
                    if (resp && resp.ok) {
                        SOV.closeModalAndReload('#modal-cat-create');
                    } else {
                        $btn.prop('disabled', false);
                        SOV.warnModal((resp && resp.error) ? resp.error : 'No se pudo guardar.');
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

        // Open edit modal
        $(document).on('click', '.btn-cat-edit', function() {
            var $tr = $(this).closest('tr');
            $('#edit_id_categoria').val($tr.data('id'));
            $('#edit_nombre_categoria').val($tr.data('nombre'));
            $('#modal-cat-edit').modal('show');
        });

        // Update (AJAX)
        $('#form-cat-edit').on('submit', function(e) {
            if (!(window.SOV && SOV.ajaxJson)) return;
            e.preventDefault();
            var $f = $(this);
            var $btn = $f.find('button[type="submit"]').prop('disabled', true);
            var fd = new FormData(this);

            SOV.ajaxJson({
                    url: $f.attr('action'),
                    method: 'POST',
                    data: fd
                })
                .done(function(resp) {
                    if (resp && resp.ok) {
                        SOV.closeModalAndReload('#modal-cat-edit');
                    } else {
                        $btn.prop('disabled', false);
                        SOV.warnModal((resp && resp.error) ? resp.error : 'No se pudo actualizar.');
                    }
                })
                .fail(function(xhr) {
                    $btn.prop('disabled', false);
                    var msg = 'No se pudo actualizar.';
                    try {
                        var j = JSON.parse(xhr.responseText);
                        if (j.error) msg = j.error;
                    } catch (e) {}
                    SOV.warnModal(msg);
                });
        });

        // Toggle estado (requiere controller toggle_estado.php)
        $(document).on('click', '.btn-cat-toggle', function() {
            if (!(window.SOV && SOV.ajaxJson)) return;
            var $tr = $(this).closest('tr');
            var id = $tr.data('id');
            var nombre = $tr.data('nombre');
            var next = $(this).data('next');

            var go = function() {
                SOV.ajaxJson({
                    url: '../app/controllers/categorias/toggle_estado.php',
                    method: 'POST',
                    data: {
                        id_categoria: id,
                        estado: next
                    }
                }).done(function(resp) {
                    if (resp && resp.ok) location.reload();
                    else SOV.warnModal(resp && resp.error ? resp.error : 'No se pudo actualizar el estado.');
                }).fail(function(xhr) {
                    var msg = 'No se pudo actualizar el estado.';
                    try {
                        var j = JSON.parse(xhr.responseText);
                        if (j.error) msg = j.error;
                    } catch (e) {}
                    SOV.warnModal(msg);
                });
            };

            if (typeof Swal !== 'undefined' && Swal.fire) {
                Swal.fire({
                    icon: 'question',
                    title: (next === 'INACTIVO' ? 'Desactivar' : 'Activar') + ' categoría',
                    text: nombre,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then(r => {
                    if (r.isConfirmed) go();
                });
            } else {
                if (confirm('¿Cambiar estado de: ' + nombre + '?')) go();
            }
        });

        // Delete (si aún lo quieres, pero NO recomendado)
        $(document).on('click', '.btn-cat-del', function() {
            if (!(window.SOV && SOV.ajaxJson)) return;
            var $tr = $(this).closest('tr');
            var id = $tr.data('id');
            var nombre = $tr.data('nombre');

            var go = function() {
                SOV.ajaxJson({
                    url: '../app/controllers/categorias/delete.php',
                    method: 'POST',
                    data: {
                        id_categoria: id
                    }
                }).done(function(resp) {
                    if (resp && resp.ok) location.reload();
                    else SOV.warnModal(resp && resp.error ? resp.error : 'No se pudo procesar.');
                }).fail(function(xhr) {
                    var msg = 'No se pudo procesar.';
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
                    title: 'Eliminar/Desactivar',
                    text: 'Acción sobre: ' + nombre,
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar'
                }).then(r => {
                    if (r.isConfirmed) go();
                });
            } else {
                if (confirm('¿Continuar con: ' + nombre + '?')) go();
            }
        });

    });
</script>