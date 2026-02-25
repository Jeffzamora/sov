<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'ventas.ver', $URL . '/');
require_once __DIR__ . '/../layout/parte1.php';

function h($v): string
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function money($v): string
{
  return number_format((float)$v, 2);
}

// Filtros (GET)
$filter = strtolower((string)($_GET['f'] ?? '')); // hoy|pendientes|credito|anuladas|''
$q = trim((string)($_GET['q'] ?? ''));

$where = [];
$params = [];

if ($filter === 'hoy') {
  $where[] = "DATE(v.fecha_venta) = CURDATE()";
}
if ($filter === 'pendientes') {
  $where[] = "v.saldo_pendiente > 0";
}
if ($filter === 'credito') {
  // ajusta a tus valores reales
  $where[] = "UPPER(v.metodo_pago) IN ('CREDITO','MIXTO')";
}
if ($filter === 'anuladas') {
  $where[] = "LOWER(v.estado) = 'anulada'";
} elseif ($filter !== '') {
  // por defecto, no filtra estado si no se pide
}

// Búsqueda
if ($q !== '') {
  $needle = '%' . $q . '%';
  $where[] = "(v.nro_venta LIKE :q1
            OR CONCAT(c.nombre,' ',c.apellido) LIKE :q2
            OR v.metodo_pago LIKE :q3)";
  $params[':q1'] = $needle;
  $params[':q2'] = $needle;
  $params[':q3'] = $needle;
}


$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
  SELECT v.id_venta, v.nro_venta, v.fecha_venta, v.total, v.metodo_pago, v.saldo_pendiente, v.estado,
         c.nombre, c.apellido
    FROM tb_ventas v
    INNER JOIN tb_clientes c ON c.id_cliente = v.id_cliente
  $whereSql
   ORDER BY v.id_venta DESC
   LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Resumen (KPI)
$kpi = [
  'total_ventas' => 0,
  'total_hoy' => 0.0,
  'pendiente_total' => 0.0,
  'creditos_activos' => 0,
];
try {
  $k = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM tb_ventas WHERE estado='activa') AS total_ventas,
      (SELECT COALESCE(SUM(total),0) FROM tb_ventas WHERE estado='activa' AND DATE(fecha_venta)=CURDATE()) AS total_hoy,
      (SELECT COALESCE(SUM(saldo_pendiente),0) FROM tb_ventas WHERE estado='activa' AND saldo_pendiente>0) AS pendiente_total,
      (SELECT COUNT(*) FROM tb_ventas WHERE estado='activa' AND saldo_pendiente>0 AND UPPER(metodo_pago) IN ('CREDITO','MIXTO')) AS creditos_activos
  ");
  $kpi = $k->fetch(PDO::FETCH_ASSOC) ?: $kpi;
} catch (Throwable $e) {
  // no romper la vista
}

function badge_estado(string $estado): string
{
  $e = strtolower(trim($estado));
  if ($e === 'activa' || $e === 'activo') return 'success';
  if ($e === 'anulada' || $e === 'anulado') return 'danger';
  if ($e === 'pendiente') return 'warning';
  return 'secondary';
}
function badge_metodo(string $m): string
{
  $x = strtoupper(trim($m));
  return match ($x) {
    'EFECTIVO' => 'success',
    'TARJETA' => 'info',
    'TRANSFERENCIA' => 'primary',
    'CREDITO' => 'warning',
    'MIXTO' => 'warning',
    default => 'secondary',
  };
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h1 class="m-0">Ventas</h1>
          <div class="text-muted">Listado y accesos rápidos</div>
        </div>
        <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-primary" href="<?php echo $URL; ?>/ventas/create.php">
            <i class="fas fa-plus"></i> Nueva venta
          </a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php flash_render(); ?>

      <!-- KPI -->
      <div class="row">
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-shopping-cart"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Ventas activas</span>
              <span class="info-box-number"><?php echo (int)($kpi['total_ventas'] ?? 0); ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-calendar-day"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total hoy</span>
              <span class="info-box-number">C$<?php echo money($kpi['total_hoy'] ?? 0); ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Pendiente total</span>
              <span class="info-box-number">C$<?php echo money($kpi['pendiente_total'] ?? 0); ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-file-invoice-dollar"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Créditos activos</span>
              <span class="info-box-number"><?php echo (int)($kpi['creditos_activos'] ?? 0); ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
          <div class="card-tools">
            <a class="btn btn-xs btn-outline-secondary" href="<?php echo $URL; ?>/ventas/">
              <i class="fas fa-undo"></i> Reset
            </a>
          </div>
        </div>
        <div class="card-body">
          <form class="form-row align-items-end" method="GET">
            <div class="col-md-4 mb-2">
              <label class="mb-1">Buscar</label>
              <input class="form-control form-control-sm" name="q" value="<?php echo h($q); ?>"
                placeholder="Cliente, # venta, método...">
            </div>
            <div class="col-md-3 mb-2">
              <label class="mb-1">Filtro rápido</label>
              <select class="form-control form-control-sm" name="f">
                <option value="" <?php echo $filter === '' ? 'selected' : ''; ?>>Todas</option>
                <option value="hoy" <?php echo $filter === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                <option value="pendientes" <?php echo $filter === 'pendientes' ? 'selected' : ''; ?>>Pendientes</option>
                <option value="credito" <?php echo $filter === 'credito' ? 'selected' : ''; ?>>Crédito / Mixto</option>
                <option value="anuladas" <?php echo $filter === 'anuladas' ? 'selected' : ''; ?>>Anuladas</option>
              </select>
            </div>
            <div class="col-md-5 mb-2">
              <button class="btn btn-sm btn-primary mr-2"><i class="fas fa-search"></i> Aplicar</button>
              <a class="btn btn-sm btn-outline-secondary" href="<?php echo $URL; ?>/ventas/">
                <i class="fas fa-times"></i> Limpiar
              </a>
            </div>
          </form>
        </div>
      </div>

      <!-- Tabla -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Últimas ventas</h3>
          <div class="card-tools text-muted">Mostrando hasta 200</div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-hover table-striped table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:90px;">#</th>
                <th style="width:150px;">Fecha</th>
                <th>Cliente</th>
                <th style="width:120px;">Método</th>
                <th class="text-right" style="width:120px;">Total</th>
                <th class="text-right" style="width:120px;">Saldo</th>
                <th style="width:110px;">Estado</th>
                <th style="width:90px;"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($ventas)): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted p-4">No hay ventas para los filtros seleccionados.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($ventas as $v): ?>
                  <?php
                  $cliente = trim(($v['nombre'] ?? '') . ' ' . ($v['apellido'] ?? ''));
                  $estado = (string)($v['estado'] ?? '');
                  $metodo = (string)($v['metodo_pago'] ?? '');
                  $saldo  = (float)($v['saldo_pendiente'] ?? 0);
                  $saldoBadge = $saldo > 0 ? 'warning' : 'success';
                  ?>
                  <tr>
                    <td class="font-weight-bold"><?php echo (int)($v['nro_venta'] ?? 0); ?></td>
                    <td class="text-muted"><?php echo h($v['fecha_venta'] ?? ''); ?></td>
                    <td>
                      <div class="font-weight-semibold"><?php echo h($cliente); ?></div>
                      <?php if ($saldo > 0): ?>
                        <span class="badge badge-<?php echo $saldoBadge; ?>">Saldo pendiente</span>
                      <?php else: ?>
                        <span class="badge badge-<?php echo $saldoBadge; ?>">Pagado</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge badge-<?php echo badge_metodo($metodo); ?>">
                        <?php echo h($metodo); ?>
                      </span>
                    </td>
                    <td class="text-right">C$<?php echo money($v['total'] ?? 0); ?></td>
                    <td class="text-right">C$<?php echo money($v['saldo_pendiente'] ?? 0); ?></td>
                    <td>
                      <span class="badge badge-<?php echo badge_estado($estado); ?>">
                        <?php echo h($estado); ?>
                      </span>
                    </td>
                    <td class="text-right">
                      <div class="btn-group btn-group-sm">
                        <a class="btn btn-info"
                          href="<?php echo $URL; ?>/ventas/ver.php?id=<?php echo (int)$v['id_venta']; ?>"
                          title="Ver">
                          <i class="fas fa-eye"></i>
                        </a>

                        <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split"
                          data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <span class="sr-only">Más</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                          <a class="dropdown-item" target="_blank"
                            href="<?php echo $URL; ?>/ventas/voucher.php?id=<?php echo (int)$v['id_venta']; ?>">
                            <i class="fas fa-receipt mr-2"></i> Voucher
                          </a>
                          <a class="dropdown-item" target="_blank"
                            href="<?php echo $URL; ?>/ventas/voucher_pdf.php?id=<?php echo (int)$v['id_venta']; ?>">
                            <i class="far fa-file-pdf mr-2"></i> Voucher PDF
                          </a>
                          <div class="dropdown-divider"></div>
                          <a class="dropdown-item" href="<?php echo $URL; ?>/ventas/ver.php?id=<?php echo (int)$v['id_venta']; ?>#pagos">
                            <i class="fas fa-hand-holding-usd mr-2"></i> Abonos / Pagos
                          </a>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="card-footer text-muted small">
          Tip: usa “Crédito / Mixto” para ver solo ventas a crédito y gestionar abonos.
        </div>
      </div>

    </div>
  </section>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>