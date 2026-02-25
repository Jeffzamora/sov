<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'ventas.crear', $URL . '/');
require_once __DIR__ . '/../layout/parte1.php';

require_once __DIR__ . '/../app/controllers/cajas/_caja_lib.php';
$caja = caja_abierta_actual($pdo);

function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h1 class="m-0">Nueva venta</h1>
          <div class="text-muted">Agrega productos, define pago y guarda</div>
        </div>
        <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
          <a class="btn btn-outline-secondary" href="<?php echo $URL; ?>/ventas">
            <i class="fas fa-arrow-left"></i> Volver
          </a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php flash_render(); ?>

      <?php if (!$caja): ?>
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i>
          No hay caja abierta. Debes <a href="<?php echo $URL; ?>/cajas">aperturar caja</a> antes de vender.
        </div>
      <?php endif; ?>

      <form action="<?php echo $URL; ?>/app/controllers/ventas/create.php" method="post" id="ventaForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id_usuario" value="<?php echo (int)$id_usuario_sesion; ?>">

        <div class="row">
          <!-- Col principal -->
          <div class="col-lg-8">
            <div class="card card-outline card-primary">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user"></i> Cliente y pago</h3>
              </div>
              <div class="card-body">
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="clienteSel" class="mb-1">Cliente</label>
                    <!-- Select2 AJAX -->
                    <select name="id_cliente" id="clienteSel" class="form-control"></select>
                    <small class="text-muted">Busca por nombre, apellido o documento.</small>
                  </div>

                  <div class="form-group col-md-3">
                    <label class="mb-1">Método</label>
                    <select name="metodo_pago" class="form-control" id="metodoPago" required>
                      <option value="efectivo">Efectivo</option>
                      <option value="deposito">Depósito</option>
                      <option value="credito">Crédito</option>
                      <option value="mixto">Mixto</option>
                    </select>
                  </div>

                  <div class="form-group col-md-3">
                    <label class="mb-1">Pagado inicial</label>
                    <input type="number" step="0.01" min="0" name="pagado_inicial" id="pagadoInicial" class="form-control" value="0">
                    <small class="text-muted" id="payHint">En efectivo/depósito se paga total.</small>
                  </div>
                </div>
              </div>
            </div>

            <div class="card card-outline card-success">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-box"></i> Productos</h3>
              </div>
              <div class="card-body">
                <div class="form-row align-items-end">
                  <div class="form-group col-md-6">
                    <label for="productoSel" class="mb-1">Producto</label>
                    <!-- Select2 AJAX -->
                    <select class="form-control" id="productoSel"></select>
                    <small class="text-muted">Busca por nombre o código.</small>
                  </div>

                  <div class="form-group col-md-2">
                    <label class="mb-1">Cantidad</label>
                    <input type="number" min="1" value="1" class="form-control" id="cantInp">
                  </div>

                  <div class="form-group col-md-2">
                    <label class="mb-1">Precio</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="precioInp">
                  </div>

                  <div class="form-group col-md-2">
                    <button type="button" class="btn btn-success btn-block" id="addItem" <?php echo !$caja ? 'disabled' : ''; ?>>
                      <i class="fas fa-plus"></i> Agregar
                    </button>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-hover table-sm" id="itemsTable">
                    <thead class="thead-light">
                      <tr>
                        <th>Producto</th>
                        <th style="width:110px">Cant.</th>
                        <th style="width:140px">Precio</th>
                        <th style="width:140px" class="text-right">Subtotal</th>
                        <th style="width:60px"></th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>

                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label class="mb-1">Descuento</label>
                    <input type="number" step="0.01" min="0" name="descuento" id="descuentoInp" class="form-control" value="0">
                  </div>
                  <div class="form-group col-md-4">
                    <label class="mb-1">Impuesto</label>
                    <input type="number" step="0.01" min="0" name="impuesto" id="impuestoInp" class="form-control" value="0">
                  </div>
                  <div class="form-group col-md-4">
                    <label class="mb-1">Nota</label>
                    <input type="text" name="nota" class="form-control" maxlength="255" placeholder="Opcional">
                  </div>
                </div>
              </div>

              <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                  <i class="fas fa-info-circle"></i> Tip: Enter para agregar rápido.
                </div>
                <div>
                  <button class="btn btn-primary" id="btnGuardar" <?php echo !$caja ? 'disabled' : ''; ?>>
                    <i class="fas fa-save"></i> Guardar venta
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar resumen -->
          <div class="col-lg-4">
            <div class="card card-outline card-dark">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calculator"></i> Resumen</h3>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Subtotal</span>
                  <strong>C$ <span id="subSpan">0.00</span></strong>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Descuento</span>
                  <strong>- C$ <span id="descSpan">0.00</span></strong>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Impuesto</span>
                  <strong>+ C$ <span id="impSpan">0.00</span></strong>
                </div>

                <hr class="my-2">

                <div class="d-flex justify-content-between align-items-center">
                  <span class="text-muted">Total</span>
                  <span class="badge badge-primary" style="font-size:1rem;">
                    C$ <span id="totalSpan">0.00</span>
                  </span>
                </div>

                <div class="d-flex justify-content-between mt-2">
                  <span class="text-muted">Pagado</span>
                  <strong>C$ <span id="pagadoSpan">0.00</span></strong>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Saldo</span>
                  <strong><span class="badge badge-warning">C$ <span id="saldoSpan">0.00</span></span></strong>
                </div>

                <hr class="my-3">

                <div class="small text-muted" id="warnBox" style="display:none;">
                  <i class="fas fa-exclamation-circle"></i>
                  <span id="warnText"></span>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-body">
                <a class="btn btn-outline-secondary btn-block" href="<?php echo $URL; ?>/ventas">
                  <i class="fas fa-times"></i> Cancelar
                </a>
              </div>
            </div>

          </div>
        </div>
      </form>

    </div>
  </section>
</div>

<script>
  
window.addEventListener('load', function() {
    const URL_BASE = <?php echo json_encode($URL); ?>;

    const tbody = document.querySelector('#itemsTable tbody');
    const selProd = document.getElementById('productoSel');
    const cant = document.getElementById('cantInp');
    const precio = document.getElementById('precioInp');
    const addBtn = document.getElementById('addItem');

    const descInp = document.getElementById('descuentoInp');
    const impInp = document.getElementById('impuestoInp');
    const metodoPago = document.getElementById('metodoPago');
    const pagadoInicial = document.getElementById('pagadoInicial');
    const payHint = document.getElementById('payHint');

    const subSpan = document.getElementById('subSpan');
    const descSpan = document.getElementById('descSpan');
    const impSpan = document.getElementById('impSpan');
    const totalSpan = document.getElementById('totalSpan');
    const pagadoSpan = document.getElementById('pagadoSpan');
    const saldoSpan = document.getElementById('saldoSpan');

    const warnBox = document.getElementById('warnBox');
    const warnText = document.getElementById('warnText');
    const btnGuardar = document.getElementById('btnGuardar');
    const form = document.getElementById('ventaForm');

    function toNum(v) {
      v = (v ?? '').toString().trim();
      return v === '' ? 0 : (parseFloat(v) || 0);
    }

    function showWarn(msg) {
      if (!msg) {
        warnBox.style.display = 'none';
        warnText.textContent = '';
        return;
      }
      warnBox.style.display = 'block';
      warnText.textContent = msg;
    }

    function recalc() {
      let subtotal = 0;
      tbody.querySelectorAll('tr').forEach(tr => subtotal += toNum(tr.getAttribute('data-line-total')));
      const descuento = Math.max(0, toNum(descInp.value));
      const impuesto = Math.max(0, toNum(impInp.value));
      const total = Math.max(0, subtotal - descuento + impuesto);

      subSpan.textContent = subtotal.toFixed(2);
      descSpan.textContent = descuento.toFixed(2);
      impSpan.textContent = impuesto.toFixed(2);
      totalSpan.textContent = total.toFixed(2);

      // UX método pago
      const mp = (metodoPago.value || '').toLowerCase();
      if (mp === 'efectivo' || mp === 'deposito') {
        pagadoInicial.value = total.toFixed(2);
        pagadoInicial.readOnly = true;
        payHint.textContent = 'En efectivo/depósito se paga el total.';
      } else if (mp === 'credito') {
        pagadoInicial.readOnly = false;
        payHint.textContent = 'Crédito: pagado inicial puede ser 0.';
      } else {
        pagadoInicial.readOnly = false;
        payHint.textContent = 'Mixto: abono inicial + saldo pendiente.';
      }

      let pagado = Math.max(0, toNum(pagadoInicial.value));
      if (pagado > total) pagado = total; // no permitir excedente
      pagadoSpan.textContent = pagado.toFixed(2);

      const saldo = Math.max(0, total - pagado);
      saldoSpan.textContent = saldo.toFixed(2);

      // Validaciones UI
      const itemsCount = tbody.querySelectorAll('tr').length;
      if (itemsCount === 0) showWarn('Agrega al menos un producto.');
      else if (total <= 0) showWarn('Total inválido.');
      else if ((mp === 'credito' || mp === 'mixto') && pagado < 0) showWarn('Pagado inicial inválido.');
      else showWarn('');

      btnGuardar.disabled = (itemsCount === 0 || total <= 0);
    }

    function addRow({
      id,
      text,
      stock,
      precioVenta
    }, cantidad, precioUnit) {
      const c = Math.max(1, parseInt(cantidad || '1', 10));
      const p = Math.max(0, parseFloat(precioUnit || precioVenta || '0'));
      const st = parseInt(stock || '0', 10);

      if (st >= 0 && c > st) {
        alert('Stock insuficiente.');
        return;
      }

      // Si ya existe el producto, suma cantidad (UX)
      const existing = tbody.querySelector(`tr[data-id="${id}"]`);
      if (existing) {
        const qtyInp = existing.querySelector('input[name="cantidad[]"]');
        qtyInp.value = (parseInt(qtyInp.value || '0', 10) + c);
        qtyInp.dispatchEvent(new Event('input'));
        return;
      }

      const line = c * p;
      const tr = document.createElement('tr');
      tr.dataset.id = id;
      tr.setAttribute('data-line-total', line.toFixed(2));

      const stockBadge = (st <= 5) ? '<span class="badge badge-warning ml-1">Stock bajo</span>' : '';
      tr.innerHTML = `
      <td>
        <div class="font-weight-semibold">${text} ${stockBadge}</div>
        <div class="text-muted small">Stock: ${st}</div>
        <input type="hidden" name="id_producto[]" value="${id}">
      </td>
      <td><input type="number" name="cantidad[]" class="form-control form-control-sm" min="1" value="${c}"></td>
      <td><input type="number" name="precio_unitario[]" class="form-control form-control-sm" step="0.01" min="0" value="${p.toFixed(2)}"></td>
      <td class="text-right">C$ <span class="lineTotal">${line.toFixed(2)}</span></td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger" title="Quitar">
          <i class="fas fa-times"></i>
        </button>
      </td>
    `;
      tbody.appendChild(tr);

      tr.querySelector('button').addEventListener('click', () => {
        tr.remove();
        recalc();
      });

      tr.querySelectorAll('input').forEach(inp => inp.addEventListener('input', () => {
        const c2 = Math.max(0, parseInt(tr.querySelector('input[name="cantidad[]"]').value || '0', 10));
        const p2 = Math.max(0, parseFloat(tr.querySelector('input[name="precio_unitario[]"]').value || '0'));
        if (st >= 0 && c2 > st) {
          showWarn('Hay una línea con cantidad mayor al stock.');
          btnGuardar.disabled = true;
        }
        const l2 = c2 * p2;
        tr.setAttribute('data-line-total', l2.toFixed(2));
        tr.querySelector('.lineTotal').textContent = l2.toFixed(2);
        recalc();
      }));

      recalc();
    }

    // Enter para agregar rápido
    [cant, precio].forEach(el => el.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        addBtn.click();
      }
    }));

    // Guardar: bloquear doble submit
    form.addEventListener('submit', (e) => {
      recalc();
      if (btnGuardar.disabled) {
        e.preventDefault();
        return;
      }
      btnGuardar.disabled = true;
      btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    });

    [descInp, impInp, metodoPago, pagadoInicial].forEach(el => {
      el.addEventListener('input', recalc);
      el.addEventListener('change', recalc);
    });

    // --- Select2 AJAX (Cliente + Producto) ---
    // Requiere que tengas Select2 cargado en tu layout.
    if (window.jQuery && jQuery.fn.select2) {
      $('#clienteSel').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Buscar cliente...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
          url: URL_BASE + '/app/controllers/citas/search_clientes_select2.php',
          dataType: 'json',
          delay: 250,
          data: params => ({
            q: params.term || '',
            page: params.page || 1
          }),
          processResults: (data, params) => ({
            results: (data.results || []).map(x => ({
              id: x.id,
              text: x.text + (x.doc ? ' - ' + x.doc : '')
            })),
            pagination: {
              more: !!(data.pagination && data.pagination.more)
            }
          })
        }
      });

      $('#productoSel').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Buscar producto...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
          url: URL_BASE + '/app/controllers/ventas/search_productos_select2.php',
          dataType: 'json',
          delay: 200,
          data: params => ({
            q: params.term || '',
            page: params.page || 1
          }),
          processResults: (data, params) => ({
            results: (data.results || []),
            pagination: {
              more: !!(data.pagination && data.pagination.more)
            }
          })
        },
        templateResult: (item) => {
          if (!item.id) return item.text;
          const st = item.stock ?? '';
          const pr = item.precio ?? '';
          return $(
            `<div>
            <div class="font-weight-semibold">${item.text}</div>
            <div class="text-muted small">Stock: ${st} | Precio: C$ ${pr}</div>
          </div>`
          );
        }
      });

      $('#productoSel').on('select2:select', function(e) {
        const d = e.params.data || {};
        precio.value = (d.precio ?? '');
        cant.value = 1;
        // focus cantidad
        setTimeout(() => cant.focus(), 0);
      });
    }

    // Botón agregar producto (usa datos del select2)
    addBtn.addEventListener('click', () => {
      // Si usamos select2, el valor y data vienen por jQuery
      let id = parseInt((selProd.value || '0'), 10);
      if (!id) return alert('Seleccione un producto');

      let text = selProd.options[selProd.selectedIndex]?.textContent || 'Producto';
      let stock = selProd.options[selProd.selectedIndex]?.getAttribute?.('data-stock');
      let precioVenta = selProd.options[selProd.selectedIndex]?.getAttribute?.('data-precio');

      // Si hay select2, intentamos recuperar data del item seleccionado
      if (window.jQuery && jQuery.fn.select2) {
        const data = $('#productoSel').select2('data');
        if (data && data[0]) {
          text = data[0].text || text;
          stock = data[0].stock ?? stock;
          precioVenta = data[0].precio ?? precioVenta;
        }
      }

      addRow({
        id,
        text,
        stock,
        precioVenta
      }, cant.value, precio.value);

      // limpiar selección para flujo rápido
      if (window.jQuery && jQuery.fn.select2) {
        $('#productoSel').val(null).trigger('change');
      } else {
        selProd.value = '';
      }
      precio.value = '';
      cant.value = 1;
      recalc();
    });

    recalc();
});
</script>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>