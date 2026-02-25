<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'cajas.ver', $URL . '/');
require_once __DIR__ . '/../layout/parte1.php';
require_once __DIR__ . '/../app/controllers/cajas/_caja_lib.php';

$caja = caja_abierta_actual($pdo);
$tot  = $caja ? caja_calcular_totales($pdo, (int)$caja['id_caja']) : null;
$abiertasCount = function_exists('caja_count_abiertas') ? caja_count_abiertas($pdo) : 0;

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v): string { return number_format((float)$v, 2); }

$montoInicial = (float)($caja['monto_inicial'] ?? 0);
$ventasEf = (float)($tot['ventas_efectivo'] ?? 0);
$ventasDep = (float)($tot['ventas_deposito'] ?? 0);
$abonosEf = (float)($tot['abonos_efectivo'] ?? 0);
$movIngEf = (float)($tot['mov_ingresos_efectivo'] ?? 0);
$movEgrEf = (float)($tot['mov_egresos_efectivo'] ?? 0);
$efectivoEsperado = $caja ? round($montoInicial + $ventasEf + $abonosEf + $movIngEf - $movEgrEf, 2) : 0;
?>

<style>
  .kpi{border:1px solid rgba(0,0,0,.06);border-radius:.75rem;}
  .kpi .label{color:#6c757d;font-size:.86rem;}
  .kpi .value{font-weight:800;font-size:1.2rem;margin:0;}
  .mono{font-variant-numeric:tabular-nums;}
  .btn-row{display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;}
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-7">
          <h1 class="m-0">Caja</h1>
          <div class="text-muted">Apertura, movimientos y cierre.</div>
        </div>
        <div class="col-sm-5">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?php echo $URL; ?>/">Inicio</a></li>
            <li class="breadcrumb-item active">Caja</li>
          </ol>
        </div>
      </div>

      <div class="row mb-2">
        <div class="col-12">
          <?php if ($caja): ?>
            <span class="badge badge-success" style="font-size:.95rem;">Estado: Abierta</span>
            <span class="badge badge-light ml-2" style="font-size:.95rem;">ID Caja: <?php echo (int)$caja['id_caja']; ?></span>
            <a class="btn btn-sm btn-outline-secondary ml-2" href="<?php echo $URL; ?>/cajas/movimientos.php"><i class="fas fa-list mr-1"></i>Movimientos</a>
          <?php else: ?>
            <span class="badge badge-secondary" style="font-size:.95rem;">Estado: Cerrada</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php flash_render(); ?>

      <?php if ($abiertasCount > 1): ?>
        <div class="alert alert-warning"><b>Atención:</b> Se detectaron <b><?php echo (int)$abiertasCount; ?></b> cajas abiertas. Recomendación: cerrar cajas adicionales o corregir en BD.</div>
      <?php endif; ?>

      <?php if (!$caja): ?>
        <div class="row">
          <div class="col-lg-7">
            <div class="card kpi">
              <div class="card-header"><h3 class="card-title"><strong>Aperturar caja</strong></h3></div>
              <div class="card-body">
                <form id="form-apertura" action="<?php echo $URL; ?>/app/controllers/cajas/apertura.php" method="post" novalidate>
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id_usuario" value="<?php echo (int)$id_usuario_sesion; ?>">
                  <div class="form-group">
                    <label>Monto inicial <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend"><span class="input-group-text">C$</span></div>
                      <input type="number" step="0.01" min="0" name="monto_inicial" class="form-control" required inputmode="decimal" placeholder="0.00">
                      <div class="invalid-feedback">Ingrese un monto válido.</div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Nota</label>
                    <input type="text" name="nota" class="form-control" maxlength="255" placeholder="Opcional (ej: Turno mañana)">
                  </div>
                  <div class="btn-row">
                    <a href="<?php echo $URL; ?>/" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                    <button class="btn btn-primary" type="submit"><i class="fas fa-door-open mr-1"></i>Aperturar</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="card kpi">
              <div class="card-header"><h3 class="card-title"><strong>Checklist</strong></h3></div>
              <div class="card-body">
                <ul class="mb-0">
                  <li>Cuenta el efectivo antes de abrir.</li>
                  <li>Registra egresos/retiros como movimiento.</li>
                  <li>Al cierre concilia efectivo esperado vs contado.</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

      <?php else: ?>

        <div class="row">
          <div class="col-md-3">
            <div class="card kpi"><div class="card-body">
              <div class="label">Monto inicial</div>
              <p class="value mono">C$<?php echo money($montoInicial); ?></p>
              <div class="text-muted" style="font-size:.82rem;">Apertura: <?php echo h((string)($caja['fecha_apertura'] ?? '')); ?></div>
            </div></div>
          </div>
          <div class="col-md-3">
            <div class="card kpi"><div class="card-body">
              <div class="label">Ventas efectivo</div>
              <p class="value mono">C$<?php echo money($ventasEf); ?></p>
              <div class="text-muted" style="font-size:.82rem;">Contado</div>
            </div></div>
          </div>
          <div class="col-md-3">
            <div class="card kpi"><div class="card-body">
              <div class="label">Ventas depósito</div>
              <p class="value mono">C$<?php echo money($ventasDep); ?></p>
              <div class="text-muted" style="font-size:.82rem;">Transferencias</div>
            </div></div>
          </div>
          <div class="col-md-3">
            <div class="card kpi"><div class="card-body">
              <div class="label">Efectivo esperado</div>
              <p class="value mono">C$<?php echo money($efectivoEsperado); ?></p>
              <div class="text-muted" style="font-size:.82rem;">Para cierre</div>
            </div></div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-6">
            <div class="card card-success">
              <div class="card-header"><h3 class="card-title"><strong>Resumen</strong></h3></div>
              <div class="card-body">
                <div class="row">
                  <div class="col-6 text-muted">Crédito (saldo)</div>
                  <div class="col-6 text-right mono"><strong>C$<?php echo money($tot['ventas_credito'] ?? 0); ?></strong></div>
                  <div class="col-6 text-muted">Abonos total</div>
                  <div class="col-6 text-right mono"><strong>C$<?php echo money($tot['abonos_total'] ?? 0); ?></strong></div>
                  <div class="col-6 text-muted">Mov. ingresos</div>
                  <div class="col-6 text-right mono"><strong>C$<?php echo money($tot['mov_ingresos'] ?? 0); ?></strong></div>
                  <div class="col-6 text-muted">Mov. egresos</div>
                  <div class="col-6 text-right mono"><strong>C$<?php echo money($tot['mov_egresos'] ?? 0); ?></strong></div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header"><h3 class="card-title"><strong>Registrar movimiento</strong></h3></div>
              <div class="card-body">
                <?php if (!ui_can('cajas.movimiento.crear')): ?>
                  <div class="alert alert-warning mb-0">No tiene permisos para registrar movimientos.</div>
                <?php else: ?>
                  <form action="<?php echo $URL; ?>/app/controllers/cajas/movimiento.php" method="post" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id_usuario" value="<?php echo (int)$id_usuario_sesion; ?>">
                    <div class="form-row">
                      <div class="form-group col-md-4">
                        <label>Tipo *</label>
                        <select name="tipo" class="form-control" required>
                          <option value="ingreso">Ingreso</option>
                          <option value="egreso">Egreso</option>
                        </select>
                      </div>
                      <div class="form-group col-md-4">
                        <label>Método *</label>
                        <select name="metodo_pago" class="form-control" required>
                          <option value="efectivo">Efectivo</option>
                          <option value="deposito">Depósito</option>
                        </select>
                      </div>
                      <div class="form-group col-md-4">
                        <label>Monto *</label>
                        <div class="input-group">
                          <div class="input-group-prepend"><span class="input-group-text">C$</span></div>
                          <input type="number" step="0.01" min="0.01" name="monto" class="form-control" required inputmode="decimal" placeholder="0.00">
                        </div>
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="form-group col-md-8">
                        <label>Concepto *</label>
                        <input type="text" name="concepto" class="form-control" required maxlength="150" placeholder="Ej: Retiro banco, Pago proveedor">
                      </div>
                      <div class="form-group col-md-4">
                        <label>Referencia</label>
                        <input type="text" name="referencia" class="form-control" maxlength="100" placeholder="Opcional">
                      </div>
                    </div>
                    <div class="btn-row">
                      <button class="btn btn-secondary" type="submit"><i class="fas fa-save mr-1"></i>Guardar</button>
                    </div>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card card-danger">
              <div class="card-header"><h3 class="card-title"><strong>Cerrar caja</strong></h3></div>
              <div class="card-body">
                <?php if (!ui_can('cajas.cerrar')): ?>
                  <div class="alert alert-warning mb-0">No tiene permisos para cerrar caja.</div>
                <?php else: ?>
                  <div class="alert alert-light">
                    <div class="d-flex justify-content-between"><span class="text-muted">Efectivo esperado</span><strong class="mono">$<?php echo money($efectivoEsperado); ?></strong></div>
                  </div>
                  <form id="form-cierre" action="<?php echo $URL; ?>/app/controllers/cajas/cierre.php" method="post" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id_caja" value="<?php echo (int)$caja['id_caja']; ?>">
                    <div class="form-row">
                      <div class="form-group col-md-4">
                        <label>Efectivo contado *</label>
                        <div class="input-group">
                          <div class="input-group-prepend"><span class="input-group-text">C$</span></div>
                          <input id="monto_cierre_efectivo" name="monto_cierre_efectivo" type="number" step="0.01" min="0" class="form-control" required inputmode="decimal" placeholder="0.00">
                        </div>
                      </div>
                      <div class="form-group col-md-4">
                        <label>Diferencia</label>
                        <input id="dif_cierre" type="text" class="form-control mono" value="$0.00" readonly>
                        <small id="dif_hint" class="text-muted">Cuadre</small>
                      </div>
                      <div class="form-group col-md-4">
                        <label>Observación</label>
                        <input name="observacion_cierre" type="text" class="form-control" maxlength="255" placeholder="Opcional">
                      </div>
                    </div>
                    <div class="btn-row">
                      <button id="btn-cierre" class="btn btn-danger" type="button"><i class="fas fa-lock mr-1"></i>Cerrar</button>
                    </div>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      <?php endif; ?>

    </div>
  </section>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
(function(){
  const esperado = <?php echo json_encode((float)$efectivoEsperado, JSON_UNESCAPED_UNICODE); ?>;
  const inp = document.getElementById('monto_cierre_efectivo');
  const dif = document.getElementById('dif_cierre');
  const hint = document.getElementById('dif_hint');
  const btn = document.getElementById('btn-cierre');
  const form = document.getElementById('form-cierre');

  function money(n){
    return (Number(n)||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  }
  function update(){
    if (!inp || !dif) return;
    const contado = Number(inp.value || 0);
    const d = contado - esperado;
    dif.value = 'C$' + money(d);
    if (!hint) return;
    if (d < 0) hint.textContent = 'Faltante de efectivo.';
    else if (d > 0) hint.textContent = 'Sobrante de efectivo.';
    else hint.textContent = 'Cuadra exacto.';
  }
  if (inp) inp.addEventListener('input', update);
  update();

  async function confirmClose(){
    if (typeof window.Swal !== 'undefined'){
      const res = await Swal.fire({
        title:'Cerrar caja',
        text:'Esta acción no se puede deshacer. ¿Confirmas el cierre?',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Sí, cerrar',
        cancelButtonText:'Cancelar'
      });
      return !!res.isConfirmed;
    }
    return confirm('¿Cerrar la caja? Esta acción no se puede deshacer.');
  }

  if (btn && form){
    btn.addEventListener('click', async function(){
      if (!form.checkValidity()){
        form.classList.add('was-validated');
        return;
      }
      const ok = await confirmClose();
      if (!ok) return;
      btn.disabled = true;
      form.submit();
    });
  }
})();
</script>
