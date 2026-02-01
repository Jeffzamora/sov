(function (window, $) {
  'use strict';

  if (!$) {
    // jQuery es requerido por Bootstrap 4 / AdminLTE en este proyecto
    return;
  }

  function toast(icon, title) {
    if (typeof Swal !== 'undefined' && Swal.fire) {
      Swal.fire({
        position: 'top-end',
        icon: icon || 'info',
        title: title || '',
        showConfirmButton: false,
        timer: 2200
      });
      return;
    }
    alert(title || '');
  }

  // Warning modal (Bootstrap). Fallback to toast if modal not present.
  function warnModal(message, title) {
    message = message || 'Ocurrió un problema. Revise los datos e intente de nuevo.';
    title = title || 'Validación';

    var $m = $('#sov-warn-modal');
    if (!$m.length) {
      toast('warning', (title ? (title + ': ') : '') + message);
      return;
    }

    // Guardar el elemento con foco para restaurarlo al cerrar
    try { $m.data('sov-return-focus', document.activeElement || null); } catch (e) {}

    $m.find('[data-sov-warn-title]').text(title);
    $m.find('[data-sov-warn-msg]').text(message);
    $m.modal('show');
  }

  // Enfocar botón de cierre en modal warning (corrige selector)
  $(document).on('shown.bs.modal', '#sov-warn-modal', function () {
    var $m = $(this);
    // Soporta: data-sov-warn-ok o cualquier botón de dismiss
    var $btn = $m.find('[data-sov-warn-ok], [data-dismiss="modal"]').first();
    if ($btn.length) $btn.trigger('focus');
  });

  // Evitar warning aria-hidden: blur si el foco queda dentro
  $(document).on('hide.bs.modal', '#sov-warn-modal', function () {
    var m = this;
    var ae = document.activeElement;
    if (ae && m.contains(ae)) {
      try { ae.blur(); } catch (e) {}
    }
  });

  // Restaurar foco al cerrar modal warning
  $(document).on('hidden.bs.modal', '#sov-warn-modal', function () {
    var $m = $(this);
    var el = $m.data('sov-return-focus');
    if (el && el.focus) {
      try { el.focus(); } catch (e) {}
    }
  });

  function hasOpenModals() {
    return $('.modal.show').length > 0;
  }

  function cleanModalArtifacts() {
    // Delay corto para no interferir con transiciones
    setTimeout(function () {
      if (!hasOpenModals()) {
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
      }
    }, 120);
  }

  function showLoader(message) {
    if (window.__sovLoaderTimer) {
      clearTimeout(window.__sovLoaderTimer);
      window.__sovLoaderTimer = null;
    }
    var $l = $('#sov-loader');
    if (!$l.length) return;

    if (message) {
      $l.find('[data-sov-loader-msg]').text(message);
    }

    window.__sovLoaderTimer = setTimeout(function () {
      // Usar aria-hidden para accesibilidad
      $l.attr('aria-hidden', 'false');
      $l.css('display', 'flex');
    }, 180);
  }

  function hideLoader() {
    var $l = $('#sov-loader');
    if (!$l.length) return;

    if (window.__sovLoaderTimer) {
      clearTimeout(window.__sovLoaderTimer);
      window.__sovLoaderTimer = null;
    }

    $l.attr('aria-hidden', 'true');
    $l.hide();
  }

  // Inject data-label attributes based on thead headers (for stacked tables on XS)
  function applyDataLabels(table) {
    var $t = $(table);
    var headers = [];
    $t.find('thead th').each(function () {
      headers.push($(this).text().trim());
    });
    if (!headers.length) return;

    $t.find('tbody tr').each(function () {
      $(this).find('td').each(function (i) {
        if (!$(this).attr('data-label')) {
          $(this).attr('data-label', headers[i] || '');
        }
      });
    });
  }

  function stackifyTables(ctx) {
    var $ctx = ctx ? $(ctx) : $(document);
    $ctx.find('.modal table.table').each(function () {
      var $t = $(this);
      if (!$t.hasClass('sov-stack')) $t.addClass('sov-stack');
      applyDataLabels(this);
    });
  }

  /**
   * AJAX helper.
   * - Soporta FormData, querystring y objeto.
   * - CSRF solo para métodos mutadores.
   * - Opcional: opts.json=true para enviar JSON real.
   */
  function ajaxJson(opts) {
    opts = opts || {};
    var url = opts.url;
    var method = (opts.method || 'POST').toUpperCase();
    var data = opts.data || {};

    var isStringData = (typeof data === 'string');
    var isFormData = (typeof FormData !== 'undefined' && data instanceof FormData);
    var needsCsrf = (method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE');

    if (window.SOV_CSRF && needsCsrf) {
      if (isFormData) {
        if (!data.has('_csrf')) data.append('_csrf', window.SOV_CSRF);
      } else if (isStringData) {
        if (!/(^|&)_csrf=/.test(data)) {
          data = data + (data.length ? '&' : '') + '_csrf=' + encodeURIComponent(window.SOV_CSRF);
        }
      } else {
        if (!data._csrf) data._csrf = window.SOV_CSRF;
      }
    }

    if (opts.showLoader !== false) {
      showLoader(opts.loaderMessage || 'Procesando...');
    }

    var ajaxOpts = {
      url: url,
      method: method,
      headers: Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, (opts.headers || {})),
      dataType: opts.dataType || 'json'
    };

    if (opts.json === true && !isFormData && !isStringData) {
      ajaxOpts.contentType = 'application/json; charset=UTF-8';
      ajaxOpts.processData = false;
      ajaxOpts.data = JSON.stringify(data);
    } else {
      ajaxOpts.data = data;
      ajaxOpts.processData = !isFormData;
      ajaxOpts.contentType = isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8';
    }

    return $.ajax(ajaxOpts).always(function () {
      if (opts.showLoader !== false) hideLoader();
    });
  }

  function closeModalAndReload(modalSelector, reloadUrl) {
    $(modalSelector).one('hidden.bs.modal', function () {
      if (reloadUrl) window.location.href = reloadUrl;
      else window.location.reload();
    });
    $(modalSelector).modal('hide');
    cleanModalArtifacts();
  }

  function requireValue(value, inputSelector, message) {
    if (value === undefined || value === null || String(value).trim() === '') {
      if (inputSelector) {
        try { $(inputSelector).focus(); } catch (e) {}
      }
      toast('warning', message || 'Debe llenar todos los campos');
      return false;
    }
    return true;
  }

  // Global safety: if Bootstrap leaves artifacts after modal close
  $(document).on('hidden.bs.modal', '.modal', function () {
    cleanModalArtifacts();
  });

  // When any modal opens, prep stacked tables
  $(document).on('shown.bs.modal', '.modal', function () {
    stackifyTables(this);
  });

  // Re-apply labels on DataTables redraw (if present)
  $(document).on('draw.dt', function (e, settings) {
    try {
      var table = settings && settings.nTable ? settings.nTable : null;
      if (table) applyDataLabels(table);
    } catch (err) {}
  });

  // Show loader for classic form submits (non-AJAX)
  $(document).on('submit', 'form', function () {
    try {
      if ($(this).data('sov-noloader') === true) return;
    } catch (e) {}
    showLoader('Enviando...');
  });

  // Red de seguridad: ocultar loader al volver del cache (bfcache) o al finalizar todas las llamadas ajax
  window.addEventListener('pageshow', function () { hideLoader(); });
  $(document).ajaxStop(function () { hideLoader(); });

  window.SOV = window.SOV || {};
  window.SOV.toast = toast;
  window.SOV.warnModal = warnModal;
  window.SOV.ajaxJson = ajaxJson;
  window.SOV.closeModalAndReload = closeModalAndReload;
  window.SOV.cleanModalArtifacts = cleanModalArtifacts;
  window.SOV.requireValue = requireValue;
  window.SOV.showLoader = showLoader;
  window.SOV.hideLoader = hideLoader;
  window.SOV.stackifyTables = stackifyTables;

})(window, window.jQuery);
