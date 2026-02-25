<?php
declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';

// Solo ADMIN.
require_admin($pdo, $URL . '/index.php');

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/**
 * Escanea el proyecto buscando usos de permisos en:
 * - ui_can('perm')
 * - require_perm($pdo, 'perm', ...)
 */
function scan_perm_usages(string $root): array {
  $perms = [];
  $rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
  );

  foreach ($rii as $file) {
    /** @var SplFileInfo $file */
    $path = $file->getPathname();
    if (!preg_match('/\\.php$/i', $path)) continue;

    // Excluir vendor/cache o dumps si existieran
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) continue;

    $txt = @file_get_contents($path);
    if ($txt === false) continue;

    // ui_can('xxx')
    if (preg_match_all("/ui_can\(\s*['\"]([^'\"]+)['\"]\s*\)/", $txt, $m)) {
      foreach ($m[1] as $p) {
        $p = trim($p);
        if ($p !== '') $perms[$p]['files'][$path] = true;
      }
    }

    // require_perm($pdo, 'xxx', ...)
    if (preg_match_all("/require_perm\(\s*\$pdo\s*,\s*['\"]([^'\"]+)['\"]\s*,/", $txt, $m2)) {
      foreach ($m2[1] as $p) {
        $p = trim($p);
        if ($p !== '') $perms[$p]['files'][$path] = true;
      }
    }
  }

  // Normaliza
  $out = [];
  foreach ($perms as $perm => $meta) {
    $out[$perm] = array_keys($meta['files'] ?? []);
    sort($out[$perm]);
  }
  ksort($out);
  return $out;
}

// 1) Permisos declarados en BD
$permsDb = [];
try {
  $st = $pdo->query('SELECT id_permiso, clave, descripcion FROM tb_permisos');
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $k = trim((string)($r['clave'] ?? ''));
    if ($k === '') continue;
    $permsDb[$k] = [
      'id' => (int)($r['id_permiso'] ?? 0),
      'desc' => (string)($r['descripcion'] ?? ''),
    ];
  }
} catch (Throwable $e) {
  $permsDb = [];
}

// 2) Permisos usados en código
$used = scan_perm_usages($BASE_DIR);

// 3) Permisos faltantes en BD
$missingInDb = [];
foreach ($used as $perm => $files) {
  if (!isset($permsDb[$perm])) $missingInDb[$perm] = $files;
}

// 4) Asignaciones del rol actual
$idRol = (int)($_SESSION['sesion_id_rol'] ?? 0);
$roleRow = null;
try {
  $rs = $pdo->prepare('SELECT id_rol, rol, estado FROM tb_roles WHERE id_rol = ? LIMIT 1');
  $rs->execute([$idRol]);
  $roleRow = $rs->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {}

$assigned = [];
try {
  $st = $pdo->prepare('SELECT p.clave FROM tb_roles_permisos rp INNER JOIN tb_permisos p ON p.id_permiso = rp.id_permiso WHERE rp.id_rol = ?');
  $st->execute([$idRol]);
  $assigned = $st->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
  $assigned = array_values(array_filter(array_map('trim', $assigned), fn($v) => $v !== ''));
  sort($assigned);
} catch (Throwable $e) {
  $assigned = [];
}

$sessionPerms = $_SESSION['_perms'] ?? [];

require_once $BASE_DIR . '/layout/parte1.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">RBAC Audit</h1>
          <div class="text-muted">Diagnóstico de permisos (código vs BD) y asignaciones por rol</div>
        </div>
        <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo h($URL); ?>/index.php"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="card">
        <div class="card-body">
          <div><b>Rol sesión:</b> <?php echo h($roleRow['rol'] ?? ''); ?> (id_rol=<?php echo (int)$idRol; ?>) — <b>estado:</b> <?php echo h($roleRow['estado'] ?? ''); ?></div>
          <div class="small text-muted mt-2">
            Permisos en sesión: <?php echo h(is_array($sessionPerms) ? (string)count($sessionPerms) : '0'); ?>
            <?php if (is_array($sessionPerms) && !empty($sessionPerms['*'])): ?>
              <span class="badge badge-success ml-2">Wildcard *</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-code"></i> Permisos usados en el código</h3></div>
            <div class="card-body" style="max-height:420px; overflow:auto;">
              <table class="table table-sm table-striped">
                <thead><tr><th>Permiso</th><th>Archivos</th></tr></thead>
                <tbody>
                <?php foreach ($used as $perm => $files): ?>
                  <tr>
                    <td><code><?php echo h($perm); ?></code></td>
                    <td class="small">
                      <?php foreach ($files as $fp): ?>
                        <div><?php echo h(str_replace($BASE_DIR . DIRECTORY_SEPARATOR, '', $fp)); ?></div>
                      <?php endforeach; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-database"></i> Permisos faltantes en BD</h3></div>
            <div class="card-body" style="max-height:420px; overflow:auto;">
              <?php if (count($missingInDb) === 0): ?>
                <div class="alert alert-success mb-0">No se detectaron permisos faltantes en <code>tb_permisos</code>.</div>
              <?php else: ?>
                <div class="alert alert-warning">Se detectaron permisos usados en el código que NO existen en <code>tb_permisos</code>.</div>
                <table class="table table-sm table-striped">
                  <thead><tr><th>Permiso</th><th>Archivos</th></tr></thead>
                  <tbody>
                  <?php foreach ($missingInDb as $perm => $files): ?>
                    <tr>
                      <td><code><?php echo h($perm); ?></code></td>
                      <td class="small">
                        <?php foreach ($files as $fp): ?>
                          <div><?php echo h(str_replace($BASE_DIR . DIRECTORY_SEPARATOR, '', $fp)); ?></div>
                        <?php endforeach; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-user-shield"></i> Permisos asignados al rol actual (BD)</h3></div>
        <div class="card-body" style="max-height:320px; overflow:auto;">
          <?php if (count($assigned) === 0): ?>
            <div class="alert alert-warning mb-0">No hay permisos asignados en <code>tb_roles_permisos</code> para este rol.</div>
          <?php else: ?>
            <div class="row">
              <?php foreach ($assigned as $p): ?>
                <div class="col-md-3 col-sm-4 col-6 mb-2"><code><?php echo h($p); ?></code></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </section>
</div>

<?php require_once $BASE_DIR . '/layout/parte2.php'; ?>
