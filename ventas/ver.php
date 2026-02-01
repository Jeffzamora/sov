<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect($URL . '/ventas', 'Venta inválida.', 'danger');

$stmt = $pdo->prepare("
  SELECT v.*, c.nombre, c.apellido, c.numero_documento
  FROM tb_ventas v
  INNER JOIN tb_clientes c ON c.id_cliente = v.id_cliente
  WHERE v.id_venta = ?
  LIMIT 1
");
$stmt->execute([$id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venta) redirect($URL . '/ventas', 'Venta no encontrada.', 'danger');

$det = $pdo->prepare("
  SELECT d.*, a.nombre AS producto
  FROM tb_ventas_detalle d
  INNER JOIN tb_almacen a ON a.id_producto = d.id_producto
  WHERE d.id_venta = ?
");
$det->execute([$id]);
$items = $det->fetchAll(PDO::FETCH_ASSOC) ?: [];

$pag = $pdo->prepare("SELECT * FROM tb_ventas_pagos WHERE id_venta=? ORDER BY id_pago ASC");
$pag->execute([$id]);
$pagos = $pag->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Devoluciones (si el módulo está instalado)
$devoluciones = [];
if (function_exists('db_table_exists') && db_table_exists($pdo, 'tb_devoluciones')) {
  $dv = $pdo->prepare("SELECT * FROM tb_devoluciones WHERE id_venta=? ORDER BY id_devolucion DESC");
  $dv->execute([$id]);
  $devoluciones = $dv->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function money($v)
{
  return number_format((float)$v, 2, '.', ',');
}
function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$saldoPendiente = (float)($venta['saldo_pendiente'] ?? 0);
$totalVenta     = (float)($venta['total'] ?? 0);
$pagadoInicial  = (float)($venta['pagado_inicial'] ?? 0);

$pagadoExtra = 0.0;
foreach ($pagos as $p) $pagadoExtra += (float)($p['monto'] ?? 0);
$totalPagado = max(0, $pagadoInicial + $pagadoExtra);

$ventaActiva = (($venta['estado'] ?? '') === 'activa');
$deudaCancelada = ($saldoPendiente <= 0.00001);

$cliente = trim(($venta['apellido'] ?? '') . ' ' . ($venta['nombre'] ?? ''));
$doc = (string)($venta['numero_documento'] ?? '');
$metodo = strtolower(trim((string)($venta['metodo_pago'] ?? '')));
$estado = strtolower(trim((string)($venta['estado'] ?? '')));

function badge_estado(string $estado): string
{
  $e = strtolower(trim($estado));
  if ($e === 'activa') return 'success';
  if ($e === 'anulada') return 'danger';
  return 'secondary';
}
function badge_metodo(string $m): string
{
  $x = strtoupper(trim($m));
  return match ($x) {
    'EFECTIVO' => 'success',
    'DEPOSITO', 'DEPÓSITO' => 'primary',
    'CREDITO', 'CRÉDITO' => 'warning',
    'MIXTO' => 'warning',
    default => 'secondary',
  };
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">

      <div class="row mb-2 align-items-center">
        <div class="col-lg-7">
          <h1 class="m-0">
            Venta #<?php echo (int)$venta['nro_venta']; ?>
            <span class="badge badge-<?php echo badge_estado($estado); ?> ml-2">
              <?php echo $estado === 'activa' ? 'Activa' : 'Anulada'; ?>
            </span>
            <span class="badge badge-<?php echo badge_metodo($metodo); ?> ml-1">
              <?php echo h($venta['metodo_pago'] ?? ''); ?>
            </span>
            <?php if ($deudaCancelada): ?>
              <span class="badge badge-success ml-1">Deuda cancelada</span>
            <?php elseif ($saldoPendiente > 0): ?>
              <span class="badge badge-warning ml-1">Saldo pendiente</span>
            <?php endif; ?>
          </h1>

          <div class="text-muted mt-1">
            <i class="fas fa-user"></i> <?php echo h($cliente); ?>
            <?php if ($doc !== ''): ?>
              <span class="ml-2"><i class="far fa-id-card"></i> <?php echo h($doc); ?></span>
            <?php endif; ?>
            <span class="ml-2"><i class="far fa-calendar"></i> <?php echo h($venta['fecha_venta'] ?? ''); ?></span>
          </div>
        </div>

        <div class="col-lg-5 text-lg-right mt-3 mt-lg-0">
          <div class="btn-group" role="group">
            <a class="btn btn-secondary" target="_blank"
              href="<?php echo $URL; ?>/ventas/voucher.php?id=<?php echo (int)$venta['id_venta']; ?>">
              <i class="fas fa-receipt"></i> Voucher
            </a>
            <a class="btn btn-outline-secondary" target="_blank"
              href="<?php echo $URL; ?>/ventas/voucher_pdf.php?id=<?php echo (int)$venta['id_venta']; ?>">
              <i class="far fa-file-pdf"></i> PDF
            </a>
            <a class="btn btn-outline-secondary" href="<?php echo $URL; ?>/ventas">
              <i class="fas fa-arrow-left"></i> Volver
            </a>
            <?php if ($ventaActiva): ?>
              <?php if (!empty($devoluciones) || (function_exists('ui_can') && ui_can('ventas.devolver'))): ?>
                <button type="button" class="btn btn-outline-warning" data-toggle="modal" data-target="#modalDevolucion">
                  <i class="fas fa-undo"></i> Devolver
                </button>
              <?php endif; ?>
              <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#modalAnularVenta">
                <i class="fas fa-ban"></i> Anular
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php flash_render(); ?>

      <?php if (!$ventaActiva): ?>
        <div class="alert alert-danger">
          <i class="fas fa-ban"></i>
          Esta venta está <b>ANULADA</b>. No se permiten abonos ni modificaciones.
        </div>
      <?php elseif ($deudaCancelada): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          Deuda cancelada. Ya no se permiten nuevos abonos.
        </div>
      <?php endif; ?>

      <!-- KPI -->
      <div class="row">
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-money-bill-wave"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total</span>
              <span class="info-box-number">C$<?php echo money($totalVenta); ?></span>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Pagado</span>
              <span class="info-box-number">C$<?php echo money($totalPagado); ?></span>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-file-invoice-dollar"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Saldo</span>
              <span class="info-box-number">C$<?php echo money($saldoPendiente); ?></span>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Items</span>
              <span class="info-box-number"><?php echo count($items); ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="card card-outline card-primary">
        <div class="card-header p-0 pt-1">
          <ul class="nav nav-tabs" id="ventaTabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="tab-resumen" data-toggle="pill" href="#pane-resumen" role="tab">
                <i class="fas fa-info-circle"></i> Resumen
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="tab-detalle" data-toggle="pill" href="#pane-detalle" role="tab">
                <i class="fas fa-shopping-basket"></i> Detalle
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="tab-pagos" data-toggle="pill" href="#pane-pagos" role="tab">
                <i class="fas fa-coins"></i> Pagos
              </a>
            </li>
            <?php if (!empty($devoluciones)): ?>
              <li class="nav-item">
                <a class="nav-link" id="tab-devol" data-toggle="pill" href="#pane-devol" role="tab">
                  <i class="fas fa-undo"></i> Devoluciones
                </a>
              </li>
            <?php endif; ?>
            <li class="nav-item">
              <a class="nav-link" id="tab-print" data-toggle="pill" href="#pane-print" role="tab">
                <i class="fas fa-print"></i> Impresión
              </a>
            </li>
          </ul>
        </div>

        <div class="card-body">
          <div class="tab-content" id="ventaTabsContent">

            <!-- Resumen -->
            <div class="tab-pane fade show active" id="pane-resumen" role="tabpanel">
              <div class="row">
                <div class="col-md-6">
                  <div class="card card-outline card-secondary">
                    <div class="card-header">
                      <h3 class="card-title">Cliente</h3>
                    </div>
                    <div class="card-body">
                      <div><b><?php echo h($cliente); ?></b></div>
                      <?php if ($doc !== ''): ?><div class="text-muted">Documento: <?php echo h($doc); ?></div><?php endif; ?>
                      <div class="text-muted">Fecha: <?php echo h($venta['fecha_venta'] ?? ''); ?></div>
                      <div>Método: <span class="badge badge-<?php echo badge_metodo($metodo); ?>"><?php echo h($venta['metodo_pago'] ?? ''); ?></span></div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="card card-outline card-secondary">
                    <div class="card-header">
                      <h3 class="card-title">Montos</h3>
                    </div>
                    <div class="card-body">
                      <div class="d-flex justify-content-between"><span>Total</span><b>C$<?php echo money($totalVenta); ?></b></div>
                      <div class="d-flex justify-content-between"><span>Pagado inicial</span><b>C$<?php echo money($pagadoInicial); ?></b></div>
                      <div class="d-flex justify-content-between"><span>Abonos</span><b>C$<?php echo money($pagadoExtra); ?></b></div>
                      <hr class="my-2">
                      <div class="d-flex justify-content-between"><span>Pagado total</span><b>C$<?php echo money($totalPagado); ?></b></div>
                      <div class="d-flex justify-content-between"><span>Saldo</span><b>C$<?php echo money($saldoPendiente); ?></b></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Detalle -->
            <div class="tab-pane fade" id="pane-detalle" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-hover table-sm">
                  <thead class="thead-light">
                    <tr>
                      <th>Producto</th>
                      <th class="text-right" style="width:90px;">Cant</th>
                      <th class="text-right" style="width:130px;">Precio</th>
                      <th class="text-right" style="width:140px;">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!$items): ?>
                      <tr>
                        <td colspan="4" class="text-center text-muted p-3">Sin detalle.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($items as $it): ?>
                        <tr>
                          <td><?php echo h($it['producto'] ?? ''); ?></td>
                          <td class="text-right"><?php echo (int)($it['cantidad'] ?? 0); ?></td>
                          <td class="text-right">C$<?php echo money($it['precio_unitario'] ?? 0); ?></td>
                          <td class="text-right font-weight-bold">C$<?php echo money($it['total_linea'] ?? 0); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <div class="row mt-2">
                <div class="col-md-6 text-muted">Descuento: <b>C$<?php echo money($venta['descuento'] ?? 0); ?></b></div>
                <div class="col-md-6 text-muted text-right">Impuesto: <b>C$<?php echo money($venta['impuesto'] ?? 0); ?></b></div>
              </div>
            </div>

            <!-- Pagos -->
            <div class="tab-pane fade" id="pane-pagos" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-sm table-hover">
                  <thead class="thead-light">
                    <tr>
                      <th>Fecha</th>
                      <th>Método</th>
                      <th class="text-right">Monto</th>
                      <th>Ref</th>
                      <th style="width:160px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!$pagos): ?>
                      <tr>
                        <td colspan="5" class="text-center text-muted p-3">Sin pagos registrados.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($pagos as $p): ?>
                        <tr>
                          <td class="text-muted"><?php echo h($p['fecha_pago'] ?? ''); ?></td>
                          <td><span class="badge badge-light"><?php echo h($p['metodo_pago'] ?? ''); ?></span></td>
                          <td class="text-right font-weight-bold">C$<?php echo money($p['monto'] ?? 0); ?></td>
                          <td class="text-muted"><?php echo h($p['referencia'] ?? ''); ?></td>
                          <td class="text-right">
                            <div class="btn-group btn-group-sm">
                              <a class="btn btn-outline-secondary" target="_blank"
                                href="<?php echo $URL; ?>/ventas/abono_voucher.php?id_pago=<?php echo (int)$p['id_pago']; ?>">
                                <i class="fas fa-receipt"></i>
                              </a>
                              <a class="btn btn-outline-primary" target="_blank"
                                href="<?php echo $URL; ?>/ventas/abono_voucher_pdf.php?id_pago=<?php echo (int)$p['id_pago']; ?>">
                                <i class="far fa-file-pdf"></i>
                              </a>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <?php if ($deudaCancelada): ?>
                <div class="alert alert-success mb-0">Deuda cancelada. No se permiten nuevos abonos.</div>
              <?php elseif (!$ventaActiva): ?>
                <div class="alert alert-warning mb-0">Venta anulada. No se pueden registrar abonos.</div>
              <?php else: ?>
                <hr>
                <div class="card card-outline card-success">
                  <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle"></i> Registrar abono</h3>
                  </div>
                  <div class="card-body">
                    <form action="<?php echo $URL; ?>/app/controllers/ventas/abonar.php" method="post" id="abonoForm">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id_usuario" value="<?php echo (int)$id_usuario_sesion; ?>">
                      <input type="hidden" name="id_venta" value="<?php echo (int)$venta['id_venta']; ?>">

                      <div class="form-row">
                        <div class="form-group col-md-3">
                          <label class="mb-1">Método</label>
                          <select name="metodo_pago" class="form-control form-control-sm" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="deposito">Depósito</option>
                          </select>
                        </div>
                        <div class="form-group col-md-3">
                          <label class="mb-1">Monto</label>
                          <input type="number" step="0.01" min="0" name="monto" class="form-control form-control-sm" required id="montoAbono">
                          <small class="text-muted">Máximo: C$<?php echo money($saldoPendiente); ?></small>
                        </div>
                        <div class="form-group col-md-6">
                          <label class="mb-1">Referencia</label>
                          <input type="text" name="referencia" class="form-control form-control-sm" maxlength="100" placeholder="Opcional">
                        </div>
                      </div>

                      <button class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar abono
                      </button>

                      <div class="small text-muted mt-2" id="abonoWarn" style="display:none;">
                        <i class="fas fa-exclamation-circle"></i> <span id="abonoWarnText"></span>
                      </div>
                    </form>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Impresión -->
            <?php if (!empty($devoluciones)): ?>
              <div class="tab-pane fade" id="pane-devol" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead class="thead-light">
                      <tr>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th class="text-right">Monto</th>
                        <th>Referencia</th>
                        <th>Motivo</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($devoluciones as $d): ?>
                        <tr>
                          <td class="text-muted"><?php echo h($d['fecha'] ?? ''); ?></td>
                          <td><span class="badge badge-light"><?php echo h($d['metodo_pago'] ?? ''); ?></span></td>
                          <td class="text-right font-weight-bold">C$<?php echo money($d['monto_total'] ?? 0); ?></td>
                          <td class="text-muted"><?php echo h($d['referencia'] ?? ''); ?></td>
                          <td class="text-muted"><?php echo h($d['motivo'] ?? ''); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>

            <div class="tab-pane fade" id="pane-print" role="tabpanel">
              <div class="row">
                <div class="col-md-6">
                  <div class="callout callout-info">
                    <h5><i class="fas fa-receipt"></i> Voucher</h5>
                    <p>Imprime el voucher en formato ticket.</p>
                    <a class="btn btn-secondary" target="_blank"
                      href="<?php echo $URL; ?>/ventas/voucher.php?id=<?php echo (int)$venta['id_venta']; ?>">
                      Abrir Voucher
                    </a>
                    <a class="btn btn-outline-info ml-2" target="_blank"
                      href="<?php echo $URL; ?>/ventas/voucher.php?id=<?php echo (int)$venta['id_venta']; ?>&formato=carta3">
                      <i class="fas fa-file-alt"></i> Carta (3 copias)
                    </a>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="callout callout-danger">
                    <h5><i class="far fa-file-pdf"></i> PDF</h5>
                    <p>Descarga e imprime el comprobante en PDF.</p>
                    <a class="btn btn-outline-secondary" target="_blank"
                      href="<?php echo $URL; ?>/ventas/voucher_pdf.php?id=<?php echo (int)$venta['id_venta']; ?>">
                      Abrir PDF
                    </a>
                    <a class="btn btn-outline-danger ml-2" target="_blank"
                      href="<?php echo $URL; ?>/ventas/voucher_pdf.php?id=<?php echo (int)$venta['id_venta']; ?>&formato=carta3">
                      <i class="far fa-file-pdf"></i> Carta PDF
                    </a>
                  </div>
                </div>
              </div>
            </div>

          </div><!-- tab-content -->
        </div><!-- card-body -->
      </div><!-- card -->

    </div>
  </section>
</div>

<?php if ($ventaActiva): ?>

  <!-- Modal Devolución -->
  <div class="modal fade" id="modalDevolucion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-warning">
          <h5 class="modal-title"><i class="fas fa-undo"></i> Devolución venta #<?php echo (int)$venta['nro_venta']; ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="<?php echo $URL; ?>/app/controllers/ventas/devolver.php" method="post">
          <div class="modal-body">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id_venta" value="<?php echo (int)$venta['id_venta']; ?>">

            <div class="alert alert-info">
              Selecciona los productos a devolver y la cantidad. El sistema registra un <b>egreso</b> en caja por el monto devuelto.
            </div>

            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th style="width:40px"></th>
                    <th>Producto</th>
                    <th class="text-right">Vendida</th>
                    <th class="text-right">Devolver</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td>
                      <input type="checkbox" class="js-dev-check">
                    </td>
                    <td><?php echo h($it['producto'] ?? ''); ?></td>
                    <td class="text-right"><?php echo (int)($it['cantidad'] ?? 0); ?></td>
                    <td class="text-right">
                      <input type="hidden" name="id_producto[]" value="<?php echo (int)($it['id_producto'] ?? 0); ?>">
                      <input type="number" class="form-control form-control-sm js-dev-qty" name="cantidad[]" min="0" step="1" value="0" style="max-width:120px; display:inline-block;" disabled>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="form-row">
              <div class="form-group col-md-3">
                <label class="mb-1">Método</label>
                <select name="metodo_pago" class="form-control form-control-sm" required>
                  <option value="efectivo">Efectivo</option>
                  <option value="deposito">Depósito</option>
                </select>
              </div>
              <div class="form-group col-md-5">
                <label class="mb-1">Referencia</label>
                <input type="text" name="referencia" class="form-control form-control-sm" maxlength="100" placeholder="Opcional">
              </div>
              <div class="form-group col-md-4">
                <label class="mb-1">Motivo</label>
                <input type="text" name="motivo" class="form-control form-control-sm" maxlength="255" placeholder="Opcional">
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning"><i class="fas fa-check"></i> Registrar devolución</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Anular Venta -->
  <div class="modal fade" id="modalAnularVenta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-ban"></i> Anular venta #<?php echo (int)$venta['nro_venta']; ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="<?php echo $URL; ?>/app/controllers/ventas/anular.php" method="post">
          <div class="modal-body">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id_venta" value="<?php echo (int)$venta['id_venta']; ?>">
            <div class="alert alert-warning mb-2">
              <b>Atención:</b> Esto revertirá el stock y eliminará el impacto de esta venta en la caja.
              Si la caja de la venta ya está cerrada, no se permitirá anular.
            </div>
            <div class="form-group mb-0">
              <label class="mb-1">Motivo (opcional)</label>
              <textarea name="motivo" class="form-control" rows="3" maxlength="255" placeholder="Ej: error de cobro, cliente devolvió, etc."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger"><i class="fas fa-check"></i> Confirmar anulación</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
  (function() {
    const saldo = <?php echo json_encode((float)$saldoPendiente); ?>;
    const monto = document.getElementById('montoAbono');
    const form = document.getElementById('abonoForm');
    const warn = document.getElementById('abonoWarn');
    const warnText = document.getElementById('abonoWarnText');

    if (!form || !monto) return;

    function show(msg) {
      if (!warn || !warnText) return;
      if (!msg) {
        warn.style.display = 'none';
        warnText.textContent = '';
        return;
      }
      warn.style.display = 'block';
      warnText.textContent = msg;
    }

    monto.addEventListener('input', () => {
      const v = parseFloat(monto.value || '0') || 0;
      if (v <= 0) show('El monto debe ser mayor que 0.');
      else if (v > (saldo + 0.00001)) show('El monto no puede ser mayor al saldo pendiente.');
      else show('');
    });

    form.addEventListener('submit', (e) => {
      const v = parseFloat(monto.value || '0') || 0;
      if (v <= 0 || v > (saldo + 0.00001)) {
        e.preventDefault();
        show('Revisa el monto del abono.');
      }
    });
  })();
</script>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>