<?php
declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';

// Seguridad: solo admin o permiso explícito
if (function_exists('ui_can') && (ui_can('configuracion.ver') || ui_can('configuracion.editar'))) {
  // ok
} else {
  // fallback: solo ADMINISTRADOR
  require_admin($pdo, $URL . '/');
}

require_once $BASE_DIR . '/app/Security/Upload.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Defaults (fallback)
$defaults = [
  'brand.name' => 'Óptica Alta Vision',
  'brand.slogan' => '',
  'brand.phone' => '+505 8173 1664',
  'brand.address' => '',
  'brand.web' => '',
  'brand.ruc' => '',
  'brand.primary_color' => '#0b2a4a',
  'brand.secondary_color' => '#1c4b82',
  'brand.accent_color' => '#17a2b8',
  'brand.logo_path' => 'public/images/optica/logo_bajo.png',
  'brand.favicon_path' => 'public/images/optica/favicon.png',
  'brand.app_icon_path' => 'public/images/optica/icon_alto.png',

  'currency.code' => 'NIO',
  'currency.symbol' => 'C$',
  'currency.decimals' => 2,
  'currency.thousands' => ',',
  'currency.decimal_sep' => '.',
  'currency.symbol_pos' => 'before', // before|after

  'print.paper' => 'letter',
  'print.orientation' => 'portrait',
  'print.show_logo' => true,
  'print.template' => 'detallada', // compacta|detallada
  'print.footer_note' => '',

  'whatsapp.cc' => APP_WHATSAPP_CC,
];

// Handle POST
csrf_verify();

$tab = (string)($_GET['tab'] ?? 'general');
$tab = in_array($tab, ['general','marca','moneda','impresion','sucursales'], true) ? $tab : 'general';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  require_post();
  if (function_exists('ui_can') && !ui_can('configuracion.editar')) {
    // si no hay permiso explícito, exige admin
    require_admin($pdo, $URL . '/');
  }

  $action = (string)($_POST['action'] ?? '');
  $uid = (int)($id_usuario_sesion ?? 0);

  try {
    if ($action === 'save_general') {
      setting_set($pdo, 'brand.name', trim((string)($_POST['brand_name'] ?? '')), 'string', $uid);
      setting_set($pdo, 'brand.slogan', trim((string)($_POST['brand_slogan'] ?? '')), 'string', $uid);
      setting_set($pdo, 'brand.phone', trim((string)($_POST['brand_phone'] ?? '')), 'string', $uid);
      setting_set($pdo, 'brand.address', trim((string)($_POST['brand_address'] ?? '')), 'string', $uid);
      setting_set($pdo, 'brand.web', trim((string)($_POST['brand_web'] ?? '')), 'string', $uid);
      setting_set($pdo, 'brand.ruc', trim((string)($_POST['brand_ruc'] ?? '')), 'string', $uid);
      setting_set($pdo, 'whatsapp.cc', preg_replace('/\D+/', '', (string)($_POST['whatsapp_cc'] ?? '')), 'string', $uid);
      flash_set('Configuración general guardada.', 'success');
      redirect($URL . '/configuracion?tab=general');
    }

    if ($action === 'save_brand') {
      $p = trim((string)($_POST['primary_color'] ?? ''));
      $s = trim((string)($_POST['secondary_color'] ?? ''));
      $a = trim((string)($_POST['accent_color'] ?? ''));
      if ($p !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $p)) throw new RuntimeException('Color primario inválido.');
      if ($s !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $s)) throw new RuntimeException('Color secundario inválido.');
      if ($a !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $a)) throw new RuntimeException('Color acento inválido.');

      if ($p !== '') setting_set($pdo, 'brand.primary_color', $p, 'string', $uid);
      if ($s !== '') setting_set($pdo, 'brand.secondary_color', $s, 'string', $uid);
      if ($a !== '') setting_set($pdo, 'brand.accent_color', $a, 'string', $uid);

      // Uploads
      // Logo
      $logoRel = upload_image('brand_logo', 'uploads/brand', 3 * 1024 * 1024);
      if ($logoRel) setting_set($pdo, 'brand.logo_path', $logoRel, 'string', $uid);

      // Favicon
      $favRel = upload_image('brand_favicon', 'uploads/brand', 2 * 1024 * 1024);
      if ($favRel) setting_set($pdo, 'brand.favicon_path', $favRel, 'string', $uid);

      // App icon
      $iconRel = upload_image('brand_app_icon', 'uploads/brand', 3 * 1024 * 1024);
      if ($iconRel) setting_set($pdo, 'brand.app_icon_path', $iconRel, 'string', $uid);

      flash_set('Marca/colores actualizados.', 'success');
      redirect($URL . '/configuracion?tab=marca');
    }

    if ($action === 'save_currency') {
      $code = strtoupper(trim((string)($_POST['currency_code'] ?? '')));
      $symbol = trim((string)($_POST['currency_symbol'] ?? ''));
      $decimals = (int)($_POST['currency_decimals'] ?? 2);
      $th = (string)($_POST['currency_thousands'] ?? ',');
      $ds = (string)($_POST['currency_decimal_sep'] ?? '.');
      $pos = (string)($_POST['currency_symbol_pos'] ?? 'before');
      if ($code === '' || strlen($code) > 6) throw new RuntimeException('Código de moneda inválido.');
      if ($symbol === '' || mb_strlen($symbol) > 6) throw new RuntimeException('Símbolo de moneda inválido.');
      if ($decimals < 0 || $decimals > 4) throw new RuntimeException('Decimales inválidos (0-4).');
      if (!in_array($pos, ['before','after'], true)) $pos = 'before';
      if (!in_array($th, [',','.', ' '], true)) $th = ',';
      if (!in_array($ds, ['.',','], true)) $ds = '.';

      setting_set($pdo, 'currency.code', $code, 'string', $uid);
      setting_set($pdo, 'currency.symbol', $symbol, 'string', $uid);
      setting_set($pdo, 'currency.decimals', $decimals, 'int', $uid);
      setting_set($pdo, 'currency.thousands', $th, 'string', $uid);
      setting_set($pdo, 'currency.decimal_sep', $ds, 'string', $uid);
      setting_set($pdo, 'currency.symbol_pos', $pos, 'string', $uid);
      flash_set('Moneda y formato guardados.', 'success');
      redirect($URL . '/configuracion?tab=moneda');
    }

    if ($action === 'save_print') {
      $paper = (string)($_POST['print_paper'] ?? 'letter');
      $ori = (string)($_POST['print_orientation'] ?? 'portrait');
      $showLogo = !empty($_POST['print_show_logo']);
      $tpl = (string)($_POST['print_template'] ?? 'detallada');
      $footer = trim((string)($_POST['print_footer_note'] ?? ''));
      if (!in_array($paper, ['letter','a4'], true)) $paper = 'letter';
      if (!in_array($ori, ['portrait','landscape'], true)) $ori = 'portrait';
      if (!in_array($tpl, ['compacta','detallada'], true)) $tpl = 'detallada';
      setting_set($pdo, 'print.paper', $paper, 'string', $uid);
      setting_set($pdo, 'print.orientation', $ori, 'string', $uid);
      setting_set($pdo, 'print.show_logo', $showLogo, 'bool', $uid);
      setting_set($pdo, 'print.template', $tpl, 'string', $uid);
      setting_set($pdo, 'print.footer_note', $footer, 'string', $uid);
      flash_set('Configuración de impresión guardada.', 'success');
      redirect($URL . '/configuracion?tab=impresion');
    }

    // Sucursales: create/update/delete (soft)
    if ($action === 'sucursal_save') {
      // Crea tabla si no existe (opcional, pero recomendado ejecutar SQL)
      if (!function_exists('sov_table_exists') || !sov_table_exists($pdo, 'tb_sucursales')) {
        throw new RuntimeException('La tabla tb_sucursales no existe. Ejecuta el script SQL de migración.');
      }
      $id = (int)($_POST['id_sucursal'] ?? 0);
      $nombre = trim((string)($_POST['s_nombre'] ?? ''));
      $direccion = trim((string)($_POST['s_direccion'] ?? ''));
      $telefono = trim((string)($_POST['s_telefono'] ?? ''));
      $isDefault = !empty($_POST['s_default']);
      if ($nombre === '') throw new RuntimeException('Nombre de sucursal requerido.');

      if ($id > 0) {
        $st = $pdo->prepare("UPDATE tb_sucursales SET nombre=?, direccion=?, telefono=? WHERE id_sucursal=? LIMIT 1");
        $st->execute([$nombre, $direccion, $telefono, $id]);
      } else {
        $st = $pdo->prepare("INSERT INTO tb_sucursales (nombre,direccion,telefono,is_default,activo,created_at,updated_at) VALUES (?,?,?,?,1,NOW(),NOW())");
        $st->execute([$nombre, $direccion, $telefono, 0]);
        $id = (int)$pdo->lastInsertId();
      }

      if ($isDefault) {
        $pdo->exec("UPDATE tb_sucursales SET is_default=0");
        $st2 = $pdo->prepare("UPDATE tb_sucursales SET is_default=1 WHERE id_sucursal=? LIMIT 1");
        $st2->execute([$id]);
        setting_set($pdo, 'sucursal.default_id', $id, 'int', $uid);
      }

      flash_set('Sucursal guardada.', 'success');
      redirect($URL . '/configuracion?tab=sucursales');
    }

    if ($action === 'sucursal_disable') {
      if (!sov_table_exists($pdo, 'tb_sucursales')) {
        throw new RuntimeException('La tabla tb_sucursales no existe. Ejecuta el script SQL de migración.');
      }
      $id = (int)($_POST['id_sucursal'] ?? 0);
      if ($id > 0) {
        $st = $pdo->prepare("UPDATE tb_sucursales SET activo=0, is_default=0, updated_at=NOW() WHERE id_sucursal=? LIMIT 1");
        $st->execute([$id]);
      }
      flash_set('Sucursal desactivada.', 'success');
      redirect($URL . '/configuracion?tab=sucursales');
    }

    throw new RuntimeException('Acción inválida.');

  } catch (Throwable $e) {
    flash_set($e->getMessage(), 'danger');
    redirect($URL . '/configuracion?tab=' . urlencode($tab));
  }
}

// Load current values
$v = function (string $k) use ($defaults) {
  return setting($k, $defaults[$k] ?? '');
};

$opt = optica_info();

// Sucursales
$sucursales = [];
if (function_exists('sov_table_exists') && sov_table_exists($pdo, 'tb_sucursales')) {
  $st = $pdo->query("SELECT * FROM tb_sucursales ORDER BY is_default DESC, nombre ASC");
  $sucursales = $st->fetchAll(PDO::FETCH_ASSOC);
}

require_once $BASE_DIR . '/layout/parte1.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Configuración</h1>
          <div class="text-muted">Marca, moneda, impresión y sucursales</div>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php flash_render(); ?>

      <div class="card card-outline card-primary">
        <div class="card-header">
          <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link <?= $tab==='general'?'active':'' ?>" href="<?= h($URL) ?>/configuracion?tab=general">General</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab==='marca'?'active':'' ?>" href="<?= h($URL) ?>/configuracion?tab=marca">Marca</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab==='moneda'?'active':'' ?>" href="<?= h($URL) ?>/configuracion?tab=moneda">Moneda</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab==='impresion'?'active':'' ?>" href="<?= h($URL) ?>/configuracion?tab=impresion">Impresión</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab==='sucursales'?'active':'' ?>" href="<?= h($URL) ?>/configuracion?tab=sucursales">Sucursales</a></li>
          </ul>
        </div>

        <div class="card-body">

          <?php if ($tab === 'general'): ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_general">

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Nombre de la empresa</label>
                    <input class="form-control" name="brand_name" value="<?= h((string)$v('brand.name')) ?>" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Slogan (opcional)</label>
                    <input class="form-control" name="brand_slogan" value="<?= h((string)$v('brand.slogan')) ?>">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Teléfono</label>
                    <input class="form-control" name="brand_phone" value="<?= h((string)$v('brand.phone')) ?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>WhatsApp CC</label>
                    <input class="form-control" name="whatsapp_cc" inputmode="numeric" maxlength="4" value="<?= h((string)$v('whatsapp.cc')) ?>">
                    <small class="text-muted">Ej: 505 Nicaragua, 1 USA</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>RUC / NIT</label>
                    <input class="form-control" name="brand_ruc" value="<?= h((string)$v('brand.ruc')) ?>">
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Dirección</label>
                <input class="form-control" name="brand_address" value="<?= h((string)$v('brand.address')) ?>">
              </div>

              <div class="form-group">
                <label>Sitio web (opcional)</label>
                <input class="form-control" name="brand_web" value="<?= h((string)$v('brand.web')) ?>" placeholder="https://...">
              </div>

              <button class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
            </form>
          <?php endif; ?>

          <?php if ($tab === 'marca'): ?>
            <div class="row">
              <div class="col-lg-5">
                <form method="post" enctype="multipart/form-data">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="save_brand">

                  <div class="form-group">
                    <label>Color primario</label>
                    <input type="color" class="form-control" name="primary_color" value="<?= h((string)$v('brand.primary_color')) ?>">
                  </div>
                  <div class="form-group">
                    <label>Color secundario</label>
                    <input type="color" class="form-control" name="secondary_color" value="<?= h((string)$v('brand.secondary_color')) ?>">
                  </div>
                  <div class="form-group">
                    <label>Color acento</label>
                    <input type="color" class="form-control" name="accent_color" value="<?= h((string)$v('brand.accent_color')) ?>">
                  </div>

                  <div class="form-group">
                    <label>Logo</label>
                    <input type="file" class="form-control" name="brand_logo" accept="image/*">
                    <small class="text-muted">PNG/JPG/WEBP (máx 3MB)</small>
                  </div>
                  <div class="form-group">
                    <label>Favicon</label>
                    <input type="file" class="form-control" name="brand_favicon" accept="image/*">
                  </div>
                  <div class="form-group">
                    <label>Ícono de app (PWA)</label>
                    <input type="file" class="form-control" name="brand_app_icon" accept="image/*">
                  </div>

                  <button class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                </form>
              </div>
              <div class="col-lg-7">
                <div class="callout callout-info">
                  <div class="d-flex align-items-center" style="gap:12px;">
                    <img src="<?= h($opt['logo'] ?? '') ?>" alt="logo" style="height:56px;border-radius:8px;">
                    <div>
                      <div style="font-weight:700;font-size:18px;"><?= h($opt['nombre'] ?? '') ?></div>
                      <div class="text-muted">Previsualización (header)</div>
                    </div>
                  </div>
                  <hr>
                  <div class="d-flex" style="gap:12px;flex-wrap:wrap;">
                    <span class="badge" style="background:<?= h((string)$v('brand.primary_color')) ?>;color:#fff;padding:8px 10px;border-radius:8px;">Primario</span>
                    <span class="badge" style="background:<?= h((string)$v('brand.secondary_color')) ?>;color:#fff;padding:8px 10px;border-radius:8px;">Secundario</span>
                    <span class="badge" style="background:<?= h((string)$v('brand.accent_color')) ?>;color:#fff;padding:8px 10px;border-radius:8px;">Acento</span>
                  </div>
                  <div class="text-muted mt-2">Nota: para aplicar 100% el tema (sidebar/nav), podemos generar CSS dinámico con estas variables.</div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($tab === 'moneda'): ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_currency">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Código</label>
                    <input class="form-control" name="currency_code" value="<?= h((string)$v('currency.code')) ?>" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Símbolo</label>
                    <input class="form-control" name="currency_symbol" value="<?= h((string)$v('currency.symbol')) ?>" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Decimales</label>
                    <input type="number" class="form-control" name="currency_decimals" min="0" max="4" value="<?= h((string)$v('currency.decimals')) ?>">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Posición símbolo</label>
                    <select class="form-control" name="currency_symbol_pos">
                      <option value="before" <?= ((string)$v('currency.symbol_pos')==='before')?'selected':'' ?>>Antes</option>
                      <option value="after" <?= ((string)$v('currency.symbol_pos')==='after')?'selected':'' ?>>Después</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Separador miles</label>
                    <select class="form-control" name="currency_thousands">
                      <?php foreach ([',','.', ' '] as $sep): ?>
                        <option value="<?= h($sep) ?>" <?= ((string)$v('currency.thousands')===$sep)?'selected':'' ?>><?= $sep === ' ' ? '(espacio)' : h($sep) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Separador decimal</label>
                    <select class="form-control" name="currency_decimal_sep">
                      <option value="." <?= ((string)$v('currency.decimal_sep')==='.')?'selected':'' ?>>.</option>
                      <option value="," <?= ((string)$v('currency.decimal_sep')===',')?'selected':'' ?>>,</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Ejemplo</label>
                    <?php
                      $amount = 1234.5;
                      $dec = (int)$v('currency.decimals');
                      $fmt = number_format($amount, $dec, (string)$v('currency.decimal_sep'), (string)$v('currency.thousands'));
                      $sample = ((string)$v('currency.symbol_pos')==='after') ? ($fmt.' '.(string)$v('currency.symbol')) : ((string)$v('currency.symbol').' '.$fmt);
                    ?>
                    <input class="form-control" value="<?= h($sample) ?>" disabled>
                  </div>
                </div>
              </div>
              <button class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
            </form>
          <?php endif; ?>

          <?php if ($tab === 'impresion'): ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_print">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Papel</label>
                    <select class="form-control" name="print_paper">
                      <option value="letter" <?= ((string)$v('print.paper')==='letter')?'selected':'' ?>>Carta</option>
                      <option value="a4" <?= ((string)$v('print.paper')==='a4')?'selected':'' ?>>A4</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Orientación</label>
                    <select class="form-control" name="print_orientation">
                      <option value="portrait" <?= ((string)$v('print.orientation')==='portrait')?'selected':'' ?>>Vertical</option>
                      <option value="landscape" <?= ((string)$v('print.orientation')==='landscape')?'selected':'' ?>>Horizontal</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Plantilla</label>
                    <select class="form-control" name="print_template">
                      <option value="detallada" <?= ((string)$v('print.template')==='detallada')?'selected':'' ?>>Detallada</option>
                      <option value="compacta" <?= ((string)$v('print.template')==='compacta')?'selected':'' ?>>Compacta</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Logo en PDF</label>
                    <div class="custom-control custom-switch" style="margin-top:8px;">
                      <input type="checkbox" class="custom-control-input" id="print_show_logo" name="print_show_logo" <?= $v('print.show_logo') ? 'checked' : '' ?>>
                      <label class="custom-control-label" for="print_show_logo">Mostrar</label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Nota / pie de documento</label>
                <textarea class="form-control" name="print_footer_note" rows="3" placeholder="Ej: Garantía de 30 días..."><?= h((string)$v('print.footer_note')) ?></textarea>
              </div>
              <button class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
              <div class="text-muted mt-2">Tip: si Dompdf no tiene GD, desactiva “Logo en PDF” para evitar errores por imágenes.</div>
            </form>
          <?php endif; ?>

          <?php if ($tab === 'sucursales'): ?>
            <div class="row">
              <div class="col-lg-5">
                <div class="card card-outline card-secondary">
                  <div class="card-header"><strong>Nueva / Editar sucursal</strong></div>
                  <div class="card-body">
                    <form method="post" id="sucursalForm">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="sucursal_save">
                      <input type="hidden" name="id_sucursal" id="id_sucursal" value="0">
                      <div class="form-group">
                        <label>Nombre</label>
                        <input class="form-control" name="s_nombre" id="s_nombre" required>
                      </div>
                      <div class="form-group">
                        <label>Dirección</label>
                        <input class="form-control" name="s_direccion" id="s_direccion">
                      </div>
                      <div class="form-group">
                        <label>Teléfono</label>
                        <input class="form-control" name="s_telefono" id="s_telefono">
                      </div>
                      <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="s_default" name="s_default">
                        <label class="custom-control-label" for="s_default">Sucursal por defecto</label>
                      </div>
                      <div class="mt-3 d-flex" style="gap:8px;flex-wrap:wrap;">
                        <button class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetSucursalForm()">Limpiar</button>
                      </div>
                      <div class="text-muted mt-2">Nota: si aún no creaste la tabla, ejecuta el SQL de migración.</div>
                    </form>
                  </div>
                </div>
              </div>
              <div class="col-lg-7">
                <div class="card card-outline card-secondary">
                  <div class="card-header"><strong>Listado</strong></div>
                  <div class="card-body">
                    <?php if (empty($sucursales)): ?>
                      <div class="text-muted">No hay sucursales (o no existe la tabla).</div>
                    <?php else: ?>
                      <div class="table-responsive">
                        <table class="table table-sm table-hover">
                          <thead>
                            <tr>
                              <th>Nombre</th>
                              <th>Teléfono</th>
                              <th>Default</th>
                              <th>Estado</th>
                              <th class="text-right">Acciones</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($sucursales as $s): ?>
                              <tr>
                                <td><?= h($s['nombre'] ?? '') ?><div class="text-muted small"><?= h($s['direccion'] ?? '') ?></div></td>
                                <td><?= h($s['telefono'] ?? '') ?></td>
                                <td><?= !empty($s['is_default']) ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>' ?></td>
                                <td><?= !empty($s['activo']) ? '<span class="badge badge-primary">Activa</span>' : '<span class="badge badge-danger">Inactiva</span>' ?></td>
                                <td class="text-right">
                                  <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick='editSucursal(<?= (int)$s['id_sucursal'] ?>, <?= json_encode($s['nombre'] ?? '', JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($s['direccion'] ?? '', JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($s['telefono'] ?? '', JSON_UNESCAPED_UNICODE) ?>, <?= !empty($s['is_default']) ? 'true':'false' ?>)'>
                                    <i class="fas fa-edit"></i>
                                  </button>
                                  <?php if (!empty($s['activo'])): ?>
                                  <form method="post" style="display:inline" onsubmit="return confirm('¿Desactivar sucursal?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="sucursal_disable">
                                    <input type="hidden" name="id_sucursal" value="<?= (int)$s['id_sucursal'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-ban"></i></button>
                                  </form>
                                  <?php endif; ?>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <script>
              function editSucursal(id, nombre, dir, tel, isDef){
                document.getElementById('id_sucursal').value = id;
                document.getElementById('s_nombre').value = nombre || '';
                document.getElementById('s_direccion').value = dir || '';
                document.getElementById('s_telefono').value = tel || '';
                document.getElementById('s_default').checked = !!isDef;
                window.scrollTo({top:0, behavior:'smooth'});
              }
              function resetSucursalForm(){
                document.getElementById('id_sucursal').value = 0;
                document.getElementById('s_nombre').value = '';
                document.getElementById('s_direccion').value = '';
                document.getElementById('s_telefono').value = '';
                document.getElementById('s_default').checked = false;
              }
            </script>
          <?php endif; ?>

        </div>
      </div>

      <div class="card card-outline card-info">
        <div class="card-header"><strong>SQL de instalación</strong></div>
        <div class="card-body">
          <div class="text-muted">Ejecuta el script: <code>sql/20260223_settings_sucursales.sql</code> para crear <code>tb_settings</code> y <code>tb_sucursales</code>.</div>
        </div>
      </div>

    </div>
  </section>
</div>

<?php require_once $BASE_DIR . '/layout/parte2.php'; ?>
