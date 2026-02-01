<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

// Seguridad: solo ADMINISTRADOR para gestión de roles/permisos
if (function_exists('require_admin')) {
    require_admin($pdo, $URL . '/index.php');
} else {
    if (function_exists('require_perm')) {
        require_perm($pdo, 'roles.crear', $URL . '/index.php');
    }
}

require_once __DIR__ . '/../layout/parte1.php';

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-8">
                    <h1 class="m-0">
                        <i class="fas fa-plus mr-2 text-primary"></i>Crear rol
                    </h1>
                    <small class="text-muted">Define un perfil de acceso (ej: CAJERO, VENDEDOR, ALMACENISTA).</small>
                </div>
                <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                    <a href="<?php echo e($URL); ?>/roles" class="btn btn-outline-secondary">
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

                    <div class="card card-outline card-primary shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-id-badge mr-1"></i>Datos del rol</h3>
                        </div>

                        <form id="form-role-create" action="../app/controllers/roles/create.php" method="post" novalidate>
                            <div class="card-body">
                                <?php echo csrf_field(); ?>

                                <div class="form-group">
                                    <label>Nombre del rol</label>
                                    <input
                                        type="text"
                                        name="rol"
                                        class="form-control"
                                        maxlength="50"
                                        required
                                        autocomplete="off"
                                        placeholder="Ej: CAJERO"
                                        pattern="^[A-Za-z0-9 _-]{2,50}$">
                                    <small class="text-muted">
                                        Recomendado: usar MAYÚSCULAS y nombres claros. Ej: <b>ADMINISTRADOR</b>, <b>CAJERO</b>.
                                    </small>
                                </div>

                                <div class="alert alert-info py-2 mb-0">
                                    <small>
                                        Después de crear el rol, asígnale permisos desde <b>Roles → Permisos</b>.
                                    </small>
                                </div>
                            </div>

                            <div class="card-footer d-flex justify-content-between">
                                <a href="<?php echo e($URL); ?>/roles" class="btn btn-outline-secondary">
                                    Cancelar
                                </a>
                                <button id="btn-save-role" type="submit" class="btn btn-primary">
                                    <span class="btn-label"><i class="fas fa-save"></i> Guardar rol</span>
                                    <span class="btn-loading d-none"><i class="fas fa-spinner fa-spin"></i> Guardando...</span>
                                </button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
    (function() {
        var form = document.getElementById('form-role-create');
        var btn = document.getElementById('btn-save-role');
        if (!form || !btn) return;

        form.addEventListener('submit', function(e) {
            // Validación simple HTML5
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                if (window.SOV && SOV.warnModal) SOV.warnModal('Verifica el nombre del rol.', 'Validación');
                return;
            }

            btn.disabled = true;
            var a = btn.querySelector('.btn-label');
            var b = btn.querySelector('.btn-loading');
            if (a) a.classList.add('d-none');
            if (b) b.classList.remove('d-none');
        });
    })();
</script>