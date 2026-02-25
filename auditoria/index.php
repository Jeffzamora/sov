<?php
declare(strict_types=1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/app/config.php';
require_once $BASE_DIR . '/layout/sesion.php';

require_once $BASE_DIR . '/layout/parte1.php';


function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Bitácora / Auditoría</h1>
                    <div class="text-muted">Registro de cambios del sistema</div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card">
                <div class="card-body">
                    <form id="filtros" class="form-row">
                        <div class="form-group col-md-2">
                            <label>Desde</label>
                            <input type="date" class="form-control" id="desde">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Hasta</label>
                            <input type="date" class="form-control" id="hasta">
                        </div>

                        <div class="form-group col-md-2">
                            <label>Tabla</label>
                            <input type="text" class="form-control" id="tabla" placeholder="tb_ventas">
                        </div>

                        <div class="form-group col-md-2">
                            <label>Acción</label>
                            <select class="form-control" id="accion">
                                <option value="">Todas</option>
                                <option value="INSERT">INSERT</option>
                                <option value="UPDATE">UPDATE</option>
                                <option value="DELETE">DELETE</option>
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <label>Usuario</label>
                            <input type="text" class="form-control" id="usuario" placeholder="email o id">
                        </div>

                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-block" id="btnBuscar">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-responsive">
                    <table id="tablaAuditoria" class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tabla</th>
                                <th>Acción</th>
                                <th>Registro</th>
                                <th>Usuario</th>
                                <th>IP</th>
                                <th>User Agent</th>
                                <th>Antes</th>
                                <th>Después</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once $BASE_DIR . '/layout/parte2.php'; ?>

<script>
(function() {
    const $t = $('#tablaAuditoria');

    const dt = $t.DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        order: [
            [0, 'desc']
        ],
        ajax: {
            url: '<?= $URL ?>/auditoria/ajax_list.php',
            type: 'GET',
            xhrFields: { withCredentials: true },
            data: function(d) {
                d.desde = $('#desde').val();
                d.hasta = $('#hasta').val();
                d.tabla = $('#tabla').val();
                d.accion = $('#accion').val();
                d.usuario = $('#usuario').val();
            }
        },
        order: [
            [0, 'desc']
        ],
        columns: [{
                data: 'fecha'
            },
            {
                data: 'tabla'
            },
            {
                data: 'accion'
            },
            {
                data: 'pk'
            },
            {
                data: 'usuario'
            },
            {
                data: 'ip'
            },
            {
                data: 'ua'
            },
            {
                data: 'antes'
            },
            {
                data: 'despues'
            }
        ],
        columnDefs: [{
                targets: [7, 8],
                render: function(data) {
                    if (!data) return '';
                    return '<pre class="mb-0" style="max-height:140px;overflow:auto;">' + $(
                        '<div/>').text(data).html() + '</pre>';
                }
            },
            {
                targets: [6],
                render: function(data) {
                    if (!data) return '';
                    return '<span title="' + $('<div/>').text(data).html() + '">' + $('<div/>')
                        .text(data).text().slice(0, 60) + '…</span>';
                }
            }
        ]
    });

    $('#btnBuscar').on('click', function() {
        dt.ajax.reload();
    });
})();
</script>