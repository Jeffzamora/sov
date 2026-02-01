<?php
// Normaliza URL base (evita //public/...)
$URL = rtrim((string)($URL ?? ''), '/');
$year = date('Y');
?>

<!-- Main Footer -->
<footer class="main-footer">
  <div class="float-right d-none d-sm-inline">
    Óptica Alta Visión
  </div>

  <strong>
    Copyright &copy; <?php echo $year; ?>
    <a target="_blank" rel="noopener noreferrer" href="https://devzamora.com">devzamora</a>.
  </strong>
  Todos los derechos reservados.
</footer>

</div>
<!-- ./wrapper -->

<!-- Global Loader (Óptica) -->
<div id="sov-loader" class="sov-loader" aria-hidden="true">
  <div class="sov-loader-card" role="status" aria-live="polite" aria-atomic="true">
    <p class="sov-loader-title mb-1">Procesando...</p>
    <p class="sov-loader-sub mb-3">
      <span data-sov-loader-msg>Espere un momento</span>
      <span class="sov-loader-dots" aria-hidden="true"><span></span><span></span><span></span></span>
    </p>
    <div class="sov-loader-spin" aria-hidden="true"></div>
    <div class="sov-loader-glasses" aria-hidden="true"><i class="fas fa-glasses"></i></div>
  </div>
</div>

<!-- Global Warning Modal (reusable) -->
<div class="modal fade" id="sov-warn-modal" tabindex="-1" role="dialog" aria-labelledby="sovWarnTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="sovWarnTitle" data-sov-warn-title>Validación</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="mb-0" data-sov-warn-msg>Revise los datos e intente de nuevo.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap 4 -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Select2 -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/dist/js/adminlte.min.js"></script>

<!-- DataTables & Plugins -->
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>

<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/jszip/jszip.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/pdfmake/pdfmake.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/pdfmake/vfs_fonts.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="<?php echo $URL; ?>/public/templeates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

<!-- Helpers AJAX/Modales (SOV) -->
<script src="<?php echo $URL; ?>/public/js/sov.ajax.js"></script>

</body>
</html>
