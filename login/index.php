<?php
// Config + sesión + CSRF
require_once __DIR__ . '/../app/config.php';
if (function_exists('ensure_session')) { ensure_session(); }

// Normaliza URL base (evita //)
$URL = rtrim((string)($URL ?? ''), '/');

// Mensaje flash seguro
$flashMsg = null;
$flashIcon = 'error';
if (!empty($_SESSION['mensaje'])) {
  $flashMsg = (string)$_SESSION['mensaje'];
  $flashIcon = (string)($_SESSION['icono'] ?? 'error');
  unset($_SESSION['mensaje'], $_SESSION['icono']);
}

// CSRF
$csrfToken = function_exists('csrf_token') ? csrf_token() : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Óptica Alta Visión | Acceso</title>

  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $URL; ?>/public/images/optica/icon_bajo.png">
  <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $URL; ?>/public/images/optica/icon_alto.png">
  <link rel="apple-touch-icon" href="<?php echo $URL; ?>/public/images/optica/icon_alto.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/css/sov.responsive.css">

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    .optica-login .optica-card{border-radius:16px; overflow:hidden; box-shadow:0 12px 30px rgba(0,0,0,.12)}
    .optica-login .optica-hero{background:linear-gradient(135deg, rgba(0,123,255,.08), rgba(40,167,69,.08));}
    .optica-login .optica-brand{font-weight:700;}
    .optica-login .optica-subtitle{color:#6c757d;}
    .optica-login .caps-hint{display:none; font-size:.85rem; color:#c0392b;}
    .optica-login .caps-hint.show{display:block;}
    .optica-login .btn-primary{border-radius:10px;}
    .optica-login .form-control{border-radius:10px;}
    .optica-login .input-group-text{border-radius:10px;}
    .optica-login .input-group .btn{border-radius:10px;}
  </style>
</head>

<body class="hold-transition login-page optica-login">

<div class="login-box">

  <?php if ($flashMsg !== null): ?>
    <script>
      (function(){
        var icon = <?php echo json_encode($flashIcon); ?>;
        var map = {success:'success', error:'error', danger:'error', warning:'warning', info:'info'};
        Swal.fire({
          position: 'top-end',
          icon: map[(icon||'error').toLowerCase()] || 'error',
          title: <?php echo json_encode($flashMsg); ?>,
          showConfirmButton: false,
          timer: 2000
        });
      })();
    </script>
  <?php endif; ?>

  <div class="card optica-card">
    <div class="row no-gutters">
      <div class="col-md-6 d-none d-md-flex optica-hero align-items-center justify-content-center p-4">
        <div class="text-center">
          <img src="<?php echo $URL; ?>/public/images/optica/logo_alto.png" alt="Óptica Alta Visión" style="max-width:220px;height:auto;">
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="card-body p-4">
          <div class="text-center mb-3 d-md-none">
            <img src="<?php echo $URL; ?>/public/images/optica/logo_bajo.png" alt="Óptica Alta Visión" style="max-width:220px;height:auto;">
          </div>

          <h4 class="optica-brand mb-1">Acceso al sistema</h4>
          <p class="optica-subtitle mb-3">Óptica • Ventas • Inventario</p>

          <form id="form-login" action="<?php echo $URL; ?>/app/controllers/login/ingreso.php" method="post" novalidate>
            <?php if (function_exists('csrf_field')) echo csrf_field(); ?>

            <div class="input-group mb-3">
              <input type="email" id="email" name="email" class="form-control" placeholder="Correo electrónico" autocomplete="username" required>
              <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-envelope"></span></div>
              </div>
            </div>

            <div class="input-group mb-2">
              <input type="password" id="password_user" name="password_user" class="form-control" placeholder="Contraseña" autocomplete="current-password" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="btn-toggle-pass" aria-label="Mostrar u ocultar contraseña" title="Mostrar/Ocultar">
                  <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
                <div class="input-group-text"><span class="fas fa-lock"></span></div>
              </div>
            </div>
            <div id="caps-hint" class="caps-hint mb-3">Bloq Mayús activado.</div>

            <div class="custom-control custom-checkbox mb-3">
              <input class="custom-control-input" type="checkbox" id="remember-email" checked>
              <label for="remember-email" class="custom-control-label">Recordar correo</label>
            </div>

            <div class="row">
              <div class="col-12">
                <button id="btn-login" type="submit" class="btn btn-primary btn-block">
                  <span class="btn-text">Ingresar</span>
                </button>
              </div>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/dist/js/adminlte.min.js"></script>
<!-- Helpers -->
<script src="<?php echo $URL; ?>/public/js/sov.ajax.js"></script>

<!-- Global Loader -->
<div id="sov-loader" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:2000; align-items:center; justify-content:center;">
  <div class="sov-loader-card bg-white p-4" role="status" aria-live="polite" aria-atomic="true" style="border-radius:16px; min-width:260px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,.2)">
    <img class="sov-loader-logo" src="<?php echo $URL; ?>/public/images/optica/logo_bajo.png" alt="Óptica Alta Visión" style="max-width:160px;height:auto;">
    <p class="sov-loader-title mt-3 mb-1" style="font-weight:700;">Procesando...</p>
    <p class="sov-loader-sub mb-3"><span data-sov-loader-msg>Espere un momento</span></p>
    <div class="spinner-border" role="status" aria-hidden="true"></div>
  </div>
</div>

<!-- Modal error -->
<div class="modal fade" id="modal-login-error" tabindex="-1" role="dialog" aria-labelledby="loginErrTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title" id="loginErrTitle">Error de inicio de sesión</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="login-error-text" class="mb-0">Correo o contraseña incorrectos.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<script>
  const SOV_CSRF = <?php echo json_encode($csrfToken); ?>;

  function sovShowLoader(msg) {
    const el = document.getElementById('sov-loader');
    if (!el) return;
    const msgEl = el.querySelector('[data-sov-loader-msg]');
    if (msgEl && msg) msgEl.textContent = msg;
    el.removeAttribute('aria-hidden');
    el.style.display = 'flex';
  }

  function sovHideLoader() {
    const el = document.getElementById('sov-loader');
    if (!el) return;
    el.setAttribute('aria-hidden', 'true');
    el.style.display = 'none';
  }

  // UX: recordar correo
  (function(){
    try {
      const saved = localStorage.getItem('sov_login_email') || '';
      if (saved) $('#email').val(saved);
      $('#remember-email').prop('checked', localStorage.getItem('sov_remember_email') !== '0');
      if (!$('#remember-email').is(':checked')) $('#email').val('');
    } catch(e) {}
  })();

  $('#remember-email').on('change', function(){
    try {
      localStorage.setItem('sov_remember_email', this.checked ? '1' : '0');
      if (!this.checked) localStorage.removeItem('sov_login_email');
    } catch(e) {}
  });

  // Mostrar/ocultar contraseña
  $('#btn-toggle-pass').on('click', function(){
    const i = document.getElementById('password_user');
    if (!i) return;
    const show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    $(this).find('i').toggleClass('fa-eye fa-eye-slash');
  });

  // Bloq Mayús
  $('#password_user').on('keyup', function(e){
    const caps = e.getModifierState && e.getModifierState('CapsLock');
    $('#caps-hint').toggleClass('show', !!caps);
  });

  $('#modal-login-error')
    .on('shown.bs.modal', function(){
      $(this).find('button.btn.btn-secondary,[data-dismiss="modal"]').first().trigger('focus');
    })
    .on('hide.bs.modal', function(){
      const m = this;
      const ae = document.activeElement;
      if (ae && m.contains(ae)) { try { ae.blur(); } catch (e) {} }
      setTimeout(function(){ $('#email').trigger('focus'); }, 0);
    });

  $('#form-login').on('submit', function(e){
    e.preventDefault();

    const $btn = $('#btn-login');
    const $btnText = $btn.find('.btn-text');
    const email = ($('#email').val() || '').trim();
    const password_user = $('#password_user').val() || '';

    if (!email || !password_user) {
      $('#login-error-text').text('Debe ingresar correo y contraseña.');
      $('#modal-login-error').modal('show');
      return;
    }

    if ($('#remember-email').is(':checked')) {
      try { localStorage.setItem('sov_login_email', email); } catch(e) {}
    }

    $btn.prop('disabled', true);
    $btnText.text('Ingresando...');
    sovShowLoader('Validando credenciales...');

    $.ajax({
      url: '<?php echo $URL; ?>/app/controllers/login/ingreso.php',
      method: 'POST',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      data: { _csrf: SOV_CSRF, email: email, password_user: password_user }
    })
    .done(function(resp){
      if (resp && resp.ok) {
        window.location.href = resp.redirect || '<?php echo $URL; ?>/index.php';
      } else {
        $('#login-error-text').text((resp && resp.error) ? resp.error : 'Correo o contraseña incorrectos.');
        $('#modal-login-error').modal('show');
      }
    })
    .fail(function(xhr){
      let msg = 'Correo o contraseña incorrectos.';
      try {
        const j = JSON.parse(xhr.responseText);
        if (j && j.error) msg = j.error;
      } catch (e) {}
      $('#login-error-text').text(msg);
      $('#modal-login-error').modal('show');
    })
    .always(function(){
      sovHideLoader();
      $btn.prop('disabled', false);
      $btnText.text('Ingresar');
    });
  });

  setTimeout(function(){ $('#email').trigger('focus'); }, 100);
</script>

</body>
</html>
