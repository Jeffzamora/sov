<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

// Seguridad: solo ADMIN o permiso roles.actualizar
if (function_exists('require_admin')) {
    require_admin($pdo, $URL . '/index.php');
} elseif (function_exists('require_perm')) {
    require_perm($pdo, 'roles.actualizar', $URL . '/index.php');
}

require_once __DIR__ . '/../app/controllers/roles/update_roles.php'; // IMPORTANTE: este archivo debe ser SOLO SELECT por id
require_once __DIR__ . '/../layout/parte1.php';

$id_rol_get = isset($id_rol_get) ? (int)$id_rol_get : 0;
$rol_value  = isset($rol) ? (string)$rol : '';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-8">
                    <h1 class="m-0"><i class="fas fa-edit mr-2 text-success"></i>Editar rol</h1>
                    <small class="text-muted">Actualiza el nombre del rol. Recomendación: usa MAYÚSCULAS para consistencia.</small>
                </div>
                <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                    <a class="btn btn-outline-secondary" href="<?php echo $URL; ?>/roles">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <?php if (function_exists('flash_render')) {
                flash_render();
            } ?>

            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">

                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-id-badge mr-1"></i>Datos del rol</h3>
                        </div>

                        <form id="form-role-update-page" action="../app/controllers/roles/update.php" method="post" novalidate>
                            <div class="card-body">
                                <?php echo csrf_field(); ?>

                                <input type="hidden" name="id_rol" value="<?php echo $id_rol_get; ?>">

                                <div class="form-group">
                                    <label>Nombre del rol</label>
                                    <input
                                        type="text"
                                        name="rol"
                                        class="form-control"
                                        maxlength="50"
                                        required
                                        autocomplete="off"
                                        value="<?php echo htmlspecialchars($rol_value, ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="Ej: ADMINISTRADOR"
                                        pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9 _-]{2,50}$">
                                    <small class="text-muted">
                                        Evita duplicados. Ej: “ADMINISTRADOR”, “CAJERO”, “ALMACEN”.
                                    </small>
                                </div>

                                <div class="alert alert-warning py-2 mb-0">
                                    <small>
                                        Si el rol ya tiene permisos asignados, el cambio de nombre no afecta las asignaciones (depende de tu BD).
                                    </small>
                                </div>

                            </div>

                            <div class="card-footer d-flex justify-content-between">
                                <a href="<?php echo $URL; ?>/roles" class="btn btn-outline-secondary">Cancelar</a>
                                <button id="btn-role-update" type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Actualizar
                                </button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
    (function() {
        var form = document.getElementById('form-role-update-page');
        var btn = document.getElementById('btn-role-update');
        if (!form || !btn) return;

        form.addEventListener('submit', function(e) {
            // Evitar doble submit
            btn.disabled = true;
        });
    })();
</script>