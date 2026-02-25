<?php
declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'usuarios.password', $URL . '/');

require_once $BASE_DIR . '/layout/parte1.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Cambiar contraseña</h1>
          <div class="text-muted">Actualiza tu contraseña de acceso</div>
        </div>
        <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo $URL; ?>/">
            <i class="fas fa-home"></i> Dashboard
          </a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php if (function_exists('flash_render')) { flash_render(); } ?>

      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-lock"></i> Seguridad</h3>
            </div>
            <form method="POST" action="<?php echo $URL; ?>/app/controllers/usuarios/change_password_self.php" autocomplete="off">
              <div class="card-body">

                <?php echo csrf_field(); ?>

                <div class="form-group">
                  <label>Contraseña actual</label>
                  <input type="password" name="password_actual" class="form-control" required>
                </div>

                <div class="form-group">
                  <label>Nueva contraseña</label>
                  <input type="password" name="password_nueva" class="form-control" required minlength="8">
                  <small class="text-muted">Mínimo 8 caracteres.</small>
                </div>

                <div class="form-group mb-0">
                  <label>Repetir nueva contraseña</label>
                  <input type="password" name="password_repeat" class="form-control" required minlength="8">
                </div>

              </div>
              <div class="card-footer d-flex justify-content-between">
                <a class="btn btn-outline-secondary" href="<?php echo $URL; ?>/">
                  <i class="fas fa-arrow-left"></i> Volver
                </a>
                <button class="btn btn-primary" type="submit">
                  <i class="fas fa-save"></i> Guardar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<?php require_once $BASE_DIR . '/layout/parte2.php'; ?>
