<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

require_perm($pdo, 'usuarios.crear', $URL . '/usuarios');

require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/controllers/roles/listado_de_roles.php';

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
                    <h1 class="m-0"><i class="fas fa-user-plus mr-2 text-primary"></i>Registro de un nuevo usuario</h1>
                    <small class="text-muted">Crea usuarios y asigna roles. La eliminación es por desactivación.</small>
                </div>
                <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <?php if (function_exists('flash_render')) flash_render(); ?>

            <div class="row">
                <div class="col-lg-6 col-md-8">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-id-card mr-1"></i>Datos del usuario</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>

                        <form id="form-user-create" action="../app/controllers/usuarios/create.php" method="post" novalidate>
                            <div class="card-body">
                                <?php echo csrf_field(); ?>

                                <div class="form-group">
                                    <label>Nombres</label>
                                    <input
                                        type="text"
                                        name="nombres"
                                        class="form-control"
                                        placeholder="Ej: Juan Pérez"
                                        maxlength="120"
                                        required
                                        autocomplete="name">
                                </div>

                                <div class="form-group">
                                    <label>Email</label>
                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control"
                                        placeholder="correo@empresa.com"
                                        required
                                        autocomplete="email">
                                </div>

                                <div class="form-group">
                                    <label>Rol del usuario</label>

                                    <!-- Recomendado: enviar id_rol -->
                                    <select name="id_rol" id="id_rol" class="form-control" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <?php foreach (($roles_datos ?? []) as $r): ?>
                                            <option value="<?php echo (int)$r['id_rol']; ?>"><?php echo e($r['rol']); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <!-- Compatibilidad: si tu controller antiguo usa "rol", se envía también -->
                                    <input type="hidden" name="rol" id="rol_compat" value="">
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-12 col-md-6">
                                        <label>Contraseña</label>
                                        <input
                                            type="password"
                                            name="password_user"
                                            class="form-control"
                                            minlength="8"
                                            required
                                            autocomplete="new-password"
                                            placeholder="Mínimo 8 caracteres">
                                        <small class="text-muted">Recomendado: letras, números y un símbolo.</small>
                                    </div>

                                    <div class="form-group col-12 col-md-6">
                                        <label>Repetir contraseña</label>
                                        <input
                                            type="password"
                                            name="password_repeat"
                                            class="form-control"
                                            minlength="8"
                                            required
                                            autocomplete="new-password"
                                            placeholder="Repita la contraseña">
                                        <small id="pwdHelp" class="text-muted"></small>
                                    </div>
                                </div>

                                <div class="alert alert-light border mt-2 mb-0">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    El usuario podrá iniciar sesión inmediatamente. Para “eliminar”, se desactiva (no se borra).
                                </div>
                            </div>

                            <div class="card-footer d-flex justify-content-between">
                                <a href="index.php" class="btn btn-outline-secondary">
                                    Cancelar
                                </a>
                                <button id="btn-guardar" type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
    (function() {
        var form = document.getElementById('form-user-create');
        if (!form) return;

        var selRol = document.getElementById('id_rol');
        var rolCompat = document.getElementById('rol_compat');

        function syncRolText() {
            if (!selRol || !rolCompat) return;
            var opt = selRol.options[selRol.selectedIndex];
            rolCompat.value = opt ? (opt.text || '').trim() : '';
        }
        if (selRol) selRol.addEventListener('change', syncRolText);

        var p1 = form.querySelector('input[name="password_user"]');
        var p2 = form.querySelector('input[name="password_repeat"]');
        var help = document.getElementById('pwdHelp');
        var btn = document.getElementById('btn-guardar');

        function validatePwd() {
            if (!p1 || !p2) return true;
            var a = (p1.value || '');
            var b = (p2.value || '');
            if (help) help.textContent = '';

            if (a.length && a.length < 8) {
                if (help) help.textContent = 'La contraseña debe tener mínimo 8 caracteres.';
                return false;
            }
            if (a && b && a !== b) {
                if (help) help.textContent = 'Las contraseñas no coinciden.';
                return false;
            }
            if (a && b && a === b) {
                if (help) help.textContent = 'Contraseñas coinciden.';
            }
            return true;
        }

        if (p1) p1.addEventListener('input', validatePwd);
        if (p2) p2.addEventListener('input', validatePwd);

        form.addEventListener('submit', function(e) {
            syncRolText();

            if (!validatePwd()) {
                e.preventDefault();
                if (window.SOV && SOV.warnModal) SOV.warnModal('Verifique la contraseña y confirmación.', 'Validación');
                return;
            }

            // Evitar doble envío (UX)
            if (btn) btn.disabled = true;
        });
    })();
</script>