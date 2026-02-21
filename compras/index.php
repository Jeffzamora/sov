<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';
require_once __DIR__ . '/../layout/parte1.php';

require_once __DIR__ . '/../app/controllers/compras/listado_de_compras.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<script>
  // CSRF para acciones AJAX (anular desde modal)
  const CSRF = <?php echo json_encode(csrf_token()); ?>;
</script>

<div class="content-wrapper">

  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-8">
          <h1 class="m-0">
            <i class="fas fa-shopping-cart mr-2 text-primary"></i>
            Compras
          </h1>
          <small class="text-muted">Listado de compras, detalle de producto/proveedor y acciones.</small>
        </div>
        <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
          <a href="create.php" class="btn btn-primary">
            <i class="fa fa-plus"></i> Nueva compra
          </a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <!-- Filtros + KPIs (UI/UX) -->
      <div class="card card-outline card-secondary">
        <div class="card-header">
          <h3 class="card-title mb-0"><i class="fas fa-filter mr-1"></i> Filtros</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group mb-2">
                <label class="mb-1">Estado</label>
                <select id="f_estado" class="form-control form-control-sm">
                  <option value="">Todos</option>
                  <option value="ACTIVO" selected>Activos</option>
                  <option value="ANULADO">Anulados</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mb-2">
                <label class="mb-1">Desde</label>
                <input type="date" id="f_desde" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mb-2">
                <label class="mb-1">Hasta</label>
                <input type="date" id="f_hasta" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mb-2">
                <label class="mb-1">Resumen (filtrado)</label>
                <div class="d-flex flex-column">
                  <div class="small text-muted">Compras: <strong id="kpi_count">0</strong></div>
                  <div class="small text-muted">Total: <strong id="kpi_total">0.00</strong></div>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between">
            <small class="text-muted">Tip: usa el buscador de la tabla + estos filtros para reportes rápidos.</small>
            <div>
              <button type="button" id="btnResetFiltros" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-undo"></i> Limpiar
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="card card-outline card-primary">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h3 class="card-title mb-0">
            <i class="fas fa-list mr-1"></i> Listado
          </h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
              <i class="fas fa-minus"></i>
            </button>
          </div>
        </div>

        <div class="card-body">

          <div class="table-responsive">
            <table id="tblCompras" class="table table-bordered table-striped table-sm">
              <thead>
                <tr>
                  <th class="text-center" style="width:60px;">Nro</th>
                  <th class="text-center" style="width:140px;">Nro compra</th>
                  <th>Producto</th>
                  <th class="text-center" style="width:140px;">Fecha</th>
                  <th>Proveedor</th>
                  <th class="text-center" style="width:140px;">Comprobante</th>
                  <th>Usuario</th>
                  <th class="text-right" style="width:130px;">Precio</th>
                  <th class="text-center" style="width:90px;">Cant.</th>
                  <th class="text-center" style="width:160px;">Acciones</th>
                </tr>
              </thead>

              <tbody>
              <?php
              $contador = 0;
              foreach ($compras_datos as $c){
                $contador++;
                $id = (int)($c['id_compra'] ?? 0);

                // IMPORTANTE: consistencia con tu SELECT
                $nro   = (string)($c['nro_compra'] ?? '');
                $fecha = (string)($c['fecha_compra'] ?? '');
                $comp  = (string)($c['comprobante'] ?? '');
                $usr   = (string)($c['nombres_usuario'] ?? '');

                $producto = (string)($c['nombre_producto'] ?? ''); // <<--- antes estabas usando nombre (mal)
                $proveedor = (string)($c['nombre_proveedor'] ?? '');

                $precio = (string)($c['precio_compra'] ?? '');
                $cant   = (string)($c['cantidad'] ?? '');

                // Si luego agregas anulado en SQL:
                $anulado = (int)($c['anulado'] ?? 0);

                // Para filtros y totales
                $estado = $anulado ? 'ANULADO' : 'ACTIVO';
                $precioNum = (float)preg_replace('/[^0-9.\-]/', '', (string)$precio);
                $cantNum   = (float)preg_replace('/[^0-9.\-]/', '', (string)$cant);
                $lineTotal = $precioNum * $cantNum;
              ?>
                <tr class="<?php echo $anulado ? 'text-muted' : ''; ?>"
                    data-estado="<?php echo h($estado); ?>"
                    data-fecha="<?php echo h($fecha); ?>"
                    data-total="<?php echo h(number_format($lineTotal, 2, '.', '')); ?>">
                  <td class="text-center"><?php echo $contador; ?></td>

                  <td class="text-center">
                    <span class="badge badge-<?php echo $anulado ? 'secondary' : 'primary'; ?>">
                      <?php echo h($nro); ?>
                    </span>
                    <?php if ($anulado): ?>
                      <div><small class="badge badge-danger mt-1">ANULADA</small></div>
                    <?php endif; ?>
                  </td>

                  <td>
                    <button
                      type="button"
                      class="btn btn-warning btn-sm btnProducto"
                      data-toggle="modal"
                      data-target="#modalProducto"
                      data-codigo="<?php echo h($c['codigo'] ?? ''); ?>"
                      data-nombre="<?php echo h($c['nombre_producto'] ?? ''); ?>"
                      data-descripcion="<?php echo h($c['descripcion'] ?? ''); ?>"
                      data-stock="<?php echo h($c['stock'] ?? ''); ?>"
                      data-stockmin="<?php echo h($c['stock_minimo'] ?? ''); ?>"
                      data-stockmax="<?php echo h($c['stock_maximo'] ?? ''); ?>"
                      data-fechaing="<?php echo h($c['fecha_ingreso'] ?? ''); ?>"
                      data-pcompra="<?php echo h($c['precio_compra_producto'] ?? ''); ?>"
                      data-pventa="<?php echo h($c['precio_venta_producto'] ?? ''); ?>"
                      data-categoria="<?php echo h($c['nombre_categoria'] ?? ''); ?>"
                      data-usuario="<?php echo h($c['nombres_usuario'] ?? ''); ?>"
                      data-imagen="<?php echo h(product_image_url(($c['imagen'] ?? ''), (int)($c['id_categoria'] ?? 0), true)); ?>"
                    >
                      <i class="fa fa-box"></i> <?php echo h($producto); ?>
                    </button>
                  </td>

                  <td class="text-center"><?php echo h($fecha); ?></td>

                  <td>
                    <button
                      type="button"
                      class="btn btn-warning btn-sm btnProveedor"
                      data-toggle="modal"
                      data-target="#modalProveedor"
                      data-nombre="<?php echo h($c['nombre_proveedor'] ?? ''); ?>"
                      data-celular="<?php echo h($c['celular_proveedor'] ?? ''); ?>"
                      data-telefono="<?php echo h($c['telefono_proveedor'] ?? ''); ?>"
                      data-empresa="<?php echo h($c['empresa'] ?? ''); ?>"
                      data-email="<?php echo h($c['email_proveedor'] ?? ''); ?>"
                      data-direccion="<?php echo h($c['direccion_proveedor'] ?? ''); ?>"
                    >
                      <i class="fa fa-truck"></i> <?php echo h($proveedor); ?>
                    </button>
                  </td>

                  <td class="text-center"><?php echo $comp !== '' ? h($comp) : '<span class="text-muted">—</span>'; ?></td>
                  <td><?php echo $usr !== '' ? h($usr) : '<span class="text-muted">—</span>'; ?></td>

                  <td class="text-right"><?php echo $precio !== '' ? h($precio) : '0.00'; ?></td>
                  <td class="text-center"><?php echo $cant !== '' ? h($cant) : '0'; ?></td>

                  <td class="text-center">
                    <div class="btn-group">
                      <a href="show.php?id=<?php echo $id; ?>" class="btn btn-info btn-sm" title="Ver">
                        <i class="fa fa-eye"></i>
                      </a>

                      <a href="update.php?id=<?php echo $id; ?>"
                         class="btn btn-success btn-sm <?php echo $anulado ? 'disabled' : ''; ?>"
                         title="Editar">
                        <i class="fa fa-pencil-alt"></i>
                      </a>

                      <!-- Anular (modal con motivo) -->
                      <button type="button"
                              class="btn btn-danger btn-sm btnAnular <?php echo $anulado ? 'disabled' : ''; ?>"
                              title="Anular"
                              data-id="<?php echo $id; ?>"
                              data-nro="<?php echo h($nro); ?>"
                              data-producto="<?php echo h($producto); ?>"
                              data-proveedor="<?php echo h($proveedor); ?>"
                              data-fecha="<?php echo h($fecha); ?>"
                              data-precio="<?php echo h($precio); ?>"
                              data-cantidad="<?php echo h($cant); ?>"
                              <?php echo $anulado ? 'disabled' : ''; ?>
                      >
                        <i class="fa fa-ban"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php } ?>
              </tbody>

            </table>
          </div>

        </div>
      </div>

    </div>
  </section>
</div>

<!-- Modal Anular Compra -->
<div class="modal fade" id="modalAnular" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title"><i class="fa fa-ban mr-2"></i> Anular compra</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="an_id_compra" value="">

        <div class="row">
          <div class="col-md-4"><div class="small text-muted">Nro compra</div><div id="an_nro" class="font-weight-bold">—</div></div>
          <div class="col-md-4"><div class="small text-muted">Fecha</div><div id="an_fecha" class="font-weight-bold">—</div></div>
          <div class="col-md-4"><div class="small text-muted">Proveedor</div><div id="an_proveedor" class="font-weight-bold">—</div></div>
        </div>
        <div class="row mt-2">
          <div class="col-md-8"><div class="small text-muted">Producto</div><div id="an_producto" class="font-weight-bold">—</div></div>
          <div class="col-md-4"><div class="small text-muted">Monto</div><div id="an_monto" class="font-weight-bold">0.00</div></div>
        </div>

        <hr>

        <div class="form-group mb-0">
          <label class="mb-1">Motivo de anulación <span class="text-danger">*</span></label>
          <textarea id="an_motivo" class="form-control" rows="3" maxlength="255" placeholder="Ej: Compra duplicada, error de proveedor, error de precio..."></textarea>
          <small class="text-muted">Se guardará en la compra como historial.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmAnular"><i class="fa fa-ban"></i> Anular</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/mensajes.php'; ?>
<?php require_once __DIR__ . '/../layout/parte2.php'; ?>

<script>
  $(function () {
    const WHATSAPP_CC = <?php echo json_encode(APP_WHATSAPP_CC); ?>;
    $("#tblCompras").DataTable({
      pageLength: 10,
      responsive: true,
      lengthChange: true,
      autoWidth: false,
      language: {
        emptyTable: "No hay información",
        info: "Mostrando _START_ a _END_ de _TOTAL_ Compras",
        infoEmpty: "Mostrando 0 a 0 de 0 Compras",
        infoFiltered: "(Filtrado de _MAX_ total Compras)",
        lengthMenu: "Mostrar _MENU_ Compras",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "Sin resultados encontrados",
        paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
      },
      buttons: [
        { extend: 'collection', text: 'Reportes', buttons: ['copy','csv','excel','pdf','print'] },
        { extend: 'colvis', text: 'Columnas' }
      ],
    }).buttons().container().appendTo('#tblCompras_wrapper .col-md-6:eq(0)');

    // Modal Producto (único)
    $(document).on('click', '.btnProducto', function(){
      var $b = $(this);
      $('#p_codigo').val($b.data('codigo') || '');
      $('#p_nombre').val($b.data('nombre') || '');
      $('#p_descripcion').val($b.data('descripcion') || '');
      $('#p_stock').val($b.data('stock') || '');
      $('#p_stockmin').val($b.data('stockmin') || '');
      $('#p_stockmax').val($b.data('stockmax') || '');
      $('#p_fechaing').val($b.data('fechaing') || '');
      $('#p_pcompra').val($b.data('pcompra') || '');
      $('#p_pventa').val($b.data('pventa') || '');
      $('#p_categoria').val($b.data('categoria') || '');
      $('#p_usuario').val($b.data('usuario') || '');
      $('#p_img').attr('src', $b.data('imagen') || '');
    });

    // Modal Proveedor (único)
    $(document).on('click', '.btnProveedor', function(){
      var $b = $(this);
      var cel = ($b.data('celular') || '').toString();
      var celDigits = cel.replace(/\D+/g,'');
      $('#prov_nombre').val($b.data('nombre') || '');
      $('#prov_telefono').val($b.data('telefono') || '');
      $('#prov_empresa').val($b.data('empresa') || '');
      $('#prov_email').val($b.data('email') || '');
      $('#prov_direccion').val($b.data('direccion') || '');

      if(celDigits){
        $('#prov_whats').attr('href', 'https://wa.me/' + encodeURIComponent(WHATSAPP_CC + celDigits)).removeClass('disabled');
        $('#prov_celular').val(cel);
      }else{
        $('#prov_whats').attr('href', '#').addClass('disabled');
        $('#prov_celular').val('');
      }
    });

    // Filtros DataTables (estado + rango fecha) usando data-attrs del <tr>
    function parseDateYMD(v){
      if(!v) return null;
      // Soporta YYYY-MM-DD o YYYY/MM/DD
      const s = String(v).trim().replace(/\//g,'-');
      const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
      if(!m) return null;
      return new Date(Number(m[1]), Number(m[2])-1, Number(m[3]));
    }

    const dt = $('#tblCompras').DataTable();

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
      if(settings.nTable.id !== 'tblCompras') return true;
      const row = dt.row(dataIndex).node();
      if(!row) return true;
      const $tr = $(row);

      const estado = ($tr.data('estado') || '').toString();
      const fecha  = ($tr.data('fecha') || '').toString();

      const fEstado = ($('#f_estado').val() || '').toString();
      if(fEstado && estado !== fEstado) return false;

      const dRow = parseDateYMD(fecha);
      const dDesde = parseDateYMD($('#f_desde').val());
      const dHasta = parseDateYMD($('#f_hasta').val());
      if(dRow){
        if(dDesde && dRow < dDesde) return false;
        if(dHasta){
          // incluir día completo
          const end = new Date(dHasta.getFullYear(), dHasta.getMonth(), dHasta.getDate(), 23, 59, 59);
          if(dRow > end) return false;
        }
      }

      return true;
    });

    function updateKPIs(){
      let count = 0;
      let sum = 0;
      dt.rows({ filter: 'applied' }).every(function(){
        const tr = this.node();
        const total = parseFloat($(tr).data('total') || 0);
        count++;
        sum += (isNaN(total) ? 0 : total);
      });
      $('#kpi_count').text(count);
      $('#kpi_total').text(sum.toFixed(2));
    }

    $('#f_estado,#f_desde,#f_hasta').on('change', function(){
      dt.draw();
      updateKPIs();
    });

    $('#btnResetFiltros').on('click', function(){
      $('#f_estado').val('ACTIVO');
      $('#f_desde').val('');
      $('#f_hasta').val('');
      dt.draw();
      updateKPIs();
    });

    dt.on('draw', function(){
      updateKPIs();
    });

    // Modal Anular
    $(document).on('click', '.btnAnular', function(){
      if($(this).hasClass('disabled')) return;
      const $b = $(this);
      const id = $b.data('id');
      const nro = $b.data('nro');
      const producto = $b.data('producto');
      const proveedor = $b.data('proveedor');
      const fecha = $b.data('fecha');
      const precio = String($b.data('precio') || '0');
      const cantidad = String($b.data('cantidad') || '0');

      // monto aproximado (precio*cantidad)
      const p = parseFloat(precio.toString().replace(/[^0-9.\-]/g,'')) || 0;
      const c = parseFloat(cantidad.toString().replace(/[^0-9.\-]/g,'')) || 0;
      const monto = (p*c).toFixed(2);

      $('#an_id_compra').val(id);
      $('#an_nro').text(nro || '—');
      $('#an_fecha').text(fecha || '—');
      $('#an_proveedor').text(proveedor || '—');
      $('#an_producto').text(producto || '—');
      $('#an_monto').text(monto);
      $('#an_motivo').val('');

      $('#modalAnular').modal('show');
    });

    $('#btnConfirmAnular').on('click', function(){
      if(!window.SOV){
        alert('Falta cargar helpers SOV (public/js/sov.ajax.js).');
        return;
      }
      const id = parseInt($('#an_id_compra').val() || '0', 10);
      const motivo = ($('#an_motivo').val() || '').toString().trim();
      if(!id){
        SOV.toast('error', 'ID inválido');
        return;
      }
      if(motivo.length < 3){
        SOV.toast('warning', 'Escribe un motivo (mínimo 3 caracteres).');
        return;
      }

      Swal.fire({
        title: 'Confirmar anulación',
        text: 'Esta acción revertirá el stock y marcará la compra como ANULADA.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, anular'
      }).then((r) => {
        if(!r.isConfirmed) return;
        SOV.ajaxJson({
          url: '../app/controllers/compras/delete.php',
          method: 'POST',
          data: {
            _csrf: CSRF,
            id_compra: id,
            motivo_anulacion: motivo
          }
        }).done(function(resp){
          if(resp && resp.ok){
            $('#modalAnular').modal('hide');
            SOV.toast('success', 'Compra anulada');
            window.location.reload();
          }else{
            SOV.toast('error', (resp && resp.error) ? resp.error : 'No se pudo anular');
          }
        }).fail(function(xhr){
          let msg = 'Error al anular';
          try{ const r = JSON.parse(xhr.responseText); if(r && r.error) msg = r.error; }catch(e){}
          SOV.toast('error', msg);
        });
      });
    });

    // Inicial
    dt.draw();
    updateKPIs();
  });
</script>

<!-- Modal PRODUCTO (único) -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info">
        <h4 class="modal-title"><i class="fa fa-box mr-1"></i> Datos del producto</h4>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-9">
            <div class="row">
              <div class="col-md-3"><div class="form-group"><label>Código</label><input id="p_codigo" class="form-control" disabled></div></div>
              <div class="col-md-5"><div class="form-group"><label>Nombre</label><input id="p_nombre" class="form-control" disabled></div></div>
              <div class="col-md-4"><div class="form-group"><label>Categoría</label><input id="p_categoria" class="form-control" disabled></div></div>
            </div>
            <div class="form-group"><label>Descripción</label><input id="p_descripcion" class="form-control" disabled></div>

            <div class="row">
              <div class="col-md-3"><div class="form-group"><label>Stock</label><input id="p_stock" class="form-control" disabled></div></div>
              <div class="col-md-3"><div class="form-group"><label>Min</label><input id="p_stockmin" class="form-control" disabled></div></div>
              <div class="col-md-3"><div class="form-group"><label>Max</label><input id="p_stockmax" class="form-control" disabled></div></div>
              <div class="col-md-3"><div class="form-group"><label>Ingreso</label><input id="p_fechaing" class="form-control" disabled></div></div>
            </div>

            <div class="row">
              <div class="col-md-4"><div class="form-group"><label>Precio compra</label><input id="p_pcompra" class="form-control" disabled></div></div>
              <div class="col-md-4"><div class="form-group"><label>Precio venta</label><input id="p_pventa" class="form-control" disabled></div></div>
              <div class="col-md-4"><div class="form-group"><label>Usuario</label><input id="p_usuario" class="form-control" disabled></div></div>
            </div>
          </div>
          <div class="col-md-3">
            <label>Imagen</label>
            <img id="p_img" src="" class="img-fluid rounded border" alt="Producto">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal PROVEEDOR (único) -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-info">
        <h4 class="modal-title"><i class="fa fa-truck mr-1"></i> Datos del proveedor</h4>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Proveedor</label>
          <input id="prov_nombre" class="form-control" disabled>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Celular</label>
              <input id="prov_celular" class="form-control" disabled>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <a id="prov_whats" href="#" target="_blank" rel="noopener" class="btn btn-success btn-block">
              <i class="fa fa-whatsapp"></i> WhatsApp
            </a>
          </div>
        </div>

        <div class="form-group"><label>Teléfono</label><input id="prov_telefono" class="form-control" disabled></div>
        <div class="form-group"><label>Empresa</label><input id="prov_empresa" class="form-control" disabled></div>
        <div class="form-group"><label>Email</label><input id="prov_email" class="form-control" disabled></div>
        <div class="form-group"><label>Dirección</label><input id="prov_direccion" class="form-control" disabled></div>
      </div>
    </div>
  </div>
</div>
