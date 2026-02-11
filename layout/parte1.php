<?php
// Normaliza URL base
$URL = rtrim((string)($URL ?? ''), '/');

// Helpers para salida segura
if (!function_exists('e')) {
  function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

// Marca menú activo según ruta actual (simple y efectivo)
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
// Ajusta si tu app vive en subcarpeta: ej /optica/usuarios...
$activeMenu = [
  'dashboard'   => ($path === APP_BASE_PATH . '/' || $path === APP_BASE_PATH || str_ends_with($path, '/index.php')),
  'reportes'    => str_contains($path, '/reportes'),
  'usuarios'    => str_contains($path, '/usuarios'),
  'roles'       => str_contains($path, '/roles'),
  'categorias'  => str_contains($path, '/categorias'),
  'almacen'     => str_contains($path, '/almacen'),
  'compras'     => str_contains($path, '/compras'),
  'proveedores' => str_contains($path, '/proveedores'),
  'clientes'    => str_contains($path, '/clientes'),
  'citas'       => str_contains($path, '/citas'),
  'cajas'       => str_contains($path, '/cajas'),
  'ventas'      => str_contains($path, '/ventas'),
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0b2a4a">
  <title>Óptica Alta Vision | Sistema</title>

  <!-- PWA -->
  <link rel="manifest" href="<?php echo $URL; ?>/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <link rel="apple-touch-icon" href="<?php echo $URL; ?>/public/pwa/icon-192.png">

  <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $URL; ?>/public/images/optica/icon_bajo.png">
  <link rel="apple-touch-icon" href="<?php echo $URL; ?>/public/images/optica/icon_alto.png">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/dist/css/adminlte.min.css">
  <!-- Select2 (buscadores AJAX: clientes/productos/etc.) -->
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/css/sov.responsive.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- DataTables -->
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

  <!-- jQuery -->
  <script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>

  <?php if (function_exists('csrf_token')): ?>
    <script>
      window.SOV_CSRF = <?php echo json_encode(csrf_token()); ?>;
    </script>
  <?php endif; ?>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="<?php echo $URL; ?>/" class="nav-link">Óptica Alta Vision</a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?php echo $URL; ?>/" class="brand-link">
      <img src="<?php echo $URL; ?>/public/images/optica/logo_bajo.png"
           alt="Óptica"
           class="brand-image img-circle elevation-3"
           style="opacity:.85">
      <span class="brand-text font-weight-light">Óptica SIS</span>
    </a>

    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg"
               class="img-circle elevation-2"
               alt="Usuario">
        </div>
        <div class="info">
          <a href="<?php echo $URL; ?>/usuarios/password.php" class="d-block" title="Cambiar contraseña">
            <?php echo e($nombres_sesion ?? 'nombres'); ?>
          </a>
          <div class="small text-muted" style="margin-top:2px;">
            <a href="<?php echo $URL; ?>/usuarios/password.php" class="text-muted">Cambiar contraseña</a>
          </div>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

          <!-- Dashboard -->
          <li class="nav-item">
            <a href="<?php echo $URL; ?>/" class="nav-link <?php echo $activeMenu['dashboard'] ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-home"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <!-- Reportes -->
          <li class="nav-item">
            <a href="<?php echo $URL; ?>/reportes" class="nav-link <?php echo $activeMenu['reportes'] ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Reportes</p>
            </a>
          </li>

          <!-- Usuarios -->
          <?php if (function_exists('ui_can') ? ui_can('usuarios.ver') : true): ?>
          <li class="nav-item <?php echo $activeMenu['usuarios'] ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $activeMenu['usuarios'] ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-users"></i>
              <p>Usuarios <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL; ?>/usuarios" class="nav-link">
                  <i class="nav-icon fas fa-list"></i><p>Listado</p>
                </a>
              </li>
              <?php if (function_exists('ui_can') ? ui_can('usuarios.crear') : true): ?>
                <li class="nav-item">
                  <a href="<?php echo $URL; ?>/usuarios/create.php" class="nav-link">
                    <i class="nav-icon fas fa-plus"></i><p>Crear usuario</p>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- Roles -->
          <?php if (function_exists('ui_can') ? ui_can('roles.ver') : true): ?>
          <li class="nav-item <?php echo $activeMenu['roles'] ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $activeMenu['roles'] ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-address-card"></i>
              <p>Roles <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL; ?>/roles" class="nav-link">
                  <i class="nav-icon fas fa-list"></i><p>Listado</p>
                </a>
              </li>
              <?php if (function_exists('ui_can') ? ui_can('roles.crear') : true): ?>
                <li class="nav-item">
                  <a href="<?php echo $URL; ?>/roles/create.php" class="nav-link">
                    <i class="nav-icon fas fa-plus"></i><p>Crear rol</p>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- Categorías -->
          <?php if (function_exists('ui_can') ? ui_can('categorias.ver') : true): ?>
          <li class="nav-item <?php echo $activeMenu['categorias'] ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $activeMenu['categorias'] ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-tags"></i>
              <p>Categorías <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL; ?>/categorias" class="nav-link">
                  <i class="nav-icon fas fa-list"></i><p>Listado</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <!-- Almacén -->
          <?php if (function_exists('ui_can') ? ui_can('almacen.ver') : true): ?>
          <li class="nav-item <?php echo $activeMenu['almacen'] ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $activeMenu['almacen'] ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-warehouse"></i>
              <p>Almacén <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL; ?>/almacen" class="nav-link">
                  <i class="nav-icon fas fa-list"></i><p>Productos</p>
                </a>
              </li>
              <?php if (function_exists('ui_can') ? ui_can('almacen.crear') : true): ?>
                <li class="nav-item">
                  <a href="<?php echo $URL; ?>/almacen/create.php" class="nav-link">
                    <i class="nav-icon fas fa-plus"></i><p>Crear producto</p>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- Compras -->
          <?php if (function_exists('ui_can') ? ui_can('compras.ver') : true): ?>
          <li class="nav-item <?php echo $activeMenu['compras'] ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $activeMenu['compras'] ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-cart-plus"></i>
              <p>Compras <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL; ?>/compras" class="nav-link">
                  <i class="nav-icon fas fa-list"></i><p>Listado</p>
                </a>
              </li>
              <?php if (function_exists('ui_can') ? ui_can('compras.crear') : true): ?>
                <li class="nav-item">
                  <a href="<?php echo $URL; ?>/compras/create.php" class="nav-link">
                    <i class="nav-icon fas fa-plus"></i><p>Crear compra</p>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- Proveedores -->
          <?php if (function_exists('ui_can') ? ui_can('proveedores.ver') : true): ?>
          <li class="nav-item <?php echo $activeMenu['proveedores'] ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $activeMenu['proveedores'] ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-truck"></i>
              <p>Proveedores <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL; ?>/proveedores" class="nav-link">
                  <i class="nav-icon fas fa-list"></i><p>Listado</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <!-- Clientes -->
          <?php if (function_exists('ui_can') ? ui_can('clientes.ver') : true): ?>
          <li class="nav-item <?php echo $activeMenu['clientes'] ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $activeMenu['clientes'] ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-user-tag"></i>
              <p>Clientes <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL; ?>/clientes" class="nav-link">
                  <i class="nav-icon fas fa-list"></i><p>Listado</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <!-- Citas -->
          <?php if (function_exists('ui_can') ? ui_can('citas.ver') : true): ?>
          <li class="nav-item <?php echo $activeMenu['citas'] ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $activeMenu['citas'] ? 'active' : ''; ?>">
              <i class="nav-icon far fa-calendar-alt"></i>
              <p>Citas <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL; ?>/citas" class="nav-link">
                  <i class="nav-icon fas fa-calendar-day"></i><p>Calendario</p>
                </a>
              </li>
              <?php if (function_exists('ui_can') ? (ui_can('citas.actualizar') || ui_can('horario.actualizar') || ui_can('horario.ver')) : true): ?>
                <li class="nav-item">
                  <a href="<?php echo $URL; ?>/citas/config.php" class="nav-link">
                    <i class="nav-icon fas fa-business-time"></i><p>Configurar horario</p>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- Caja -->
          <?php if (function_exists('ui_can') ? ui_can('cajas.ver') : true): ?>
            <li class="nav-item">
              <a href="<?php echo $URL; ?>/cajas" class="nav-link <?php echo $activeMenu['cajas'] ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-cash-register"></i>
                <p>Caja (Apertura/Cierre)</p>
              </a>
            </li>
          <?php endif; ?>

          <!-- Ventas -->
          <?php if (function_exists('ui_can') ? ui_can('ventas.ver') : true): ?>
            <li class="nav-item">
              <a href="<?php echo $URL; ?>/ventas" class="nav-link <?php echo $activeMenu['ventas'] ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-receipt"></i>
                <p>Ventas / Voucher</p>
              </a>
            </li>
          <?php endif; ?>

          <li class="nav-header">SESIÓN</li>
          <li class="nav-item">
            <a href="<?php echo $URL; ?>/app/controllers/login/cerrar_sesion.php" class="nav-link" style="background-color:#ca0a0b">
              <i class="nav-icon fas fa-door-closed"></i>
              <p>Cerrar sesión</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>
