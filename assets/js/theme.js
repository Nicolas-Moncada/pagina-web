/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: assets/js/theme.js
 * Propósito: Sistema accesible para adultos mayores rurales:
 *            - Control de tamaño de letra (A, A+, A++) persistente en localStorage.
 *            - Modo claro forzado (color-scheme: light).
 *            - SweetAlert2 adaptado a la paleta azul marino/crema.
 *            - Helpers de formato COP y Litros.
 *            - Diccionario en español para DataTables.
 * =========================================================================================
 */

(function () {
  'use strict';

  // 1. FORZADO DE TEMA CLARO ÚNICO (PREVIENE MODO OSCURO DE CELULARES)
  document.documentElement.setAttribute('data-bs-theme', 'light');
  document.documentElement.style.colorScheme = 'light';

  // 2. CONTROL ACCESIBLE DE TAMAÑO DE FUENTE: A  A+  A++
  const FONT_STORAGE_KEY = 'asoagro_font_size';

  function applyFontSize(size) {
    document.documentElement.classList.remove('font-size-normal', 'font-size-large', 'font-size-xlarge');
    
    if (size === 'large') {
      document.documentElement.classList.add('font-size-large');
    } else if (size === 'xlarge') {
      document.documentElement.classList.add('font-size-xlarge');
    } else {
      document.documentElement.classList.add('font-size-normal');
      size = 'normal';
    }

    try {
      localStorage.setItem(FONT_STORAGE_KEY, size);
    } catch (e) {}

    // Actualizar botones en la interfaz
    document.querySelectorAll('.btn-font-size').forEach((btn) => {
      const btnSize = btn.getAttribute('data-size');
      if (btnSize === size) {
        btn.classList.add('active');
        btn.setAttribute('aria-pressed', 'true');
      } else {
        btn.classList.remove('active');
        btn.setAttribute('aria-pressed', 'false');
      }
    });
  }

  // Inicializar al cargar el DOM
  document.addEventListener('DOMContentLoaded', () => {
    // Leer preferencia guardada (por defecto 'normal' que ya es 20px)
    let savedSize = 'normal';
    try {
      savedSize = localStorage.getItem(FONT_STORAGE_KEY) || 'normal';
    } catch (e) {}
    applyFontSize(savedSize);

    // Asignar listeners a los botones de tamaño de fuente
    document.querySelectorAll('.btn-font-size').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const targetSize = btn.getAttribute('data-size');
        applyFontSize(targetSize);
      });
    });

    // Contador animado accesible
    const counters = document.querySelectorAll('.counter-animated');
    if (counters.length > 0) {
      counters.forEach((c) => {
        c.textContent = c.getAttribute('data-target') || '32';
      });
    }
  });

  // 3. HELPERS GLOBALES EXPUESTOS EN WINDOW (AsoagroUI)
  window.AsoagroUI = {
    // Formato de moneda colombiana COP
    formatCOP: function (val) {
      if (val === null || val === undefined || isNaN(val)) return '$ 0';
      return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0
      }).format(val);
    },

    // Formato de Litros
    formatLitros: function (val) {
      if (val === null || val === undefined || isNaN(val)) return '0,0 L';
      return new Intl.NumberFormat('es-CO', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
      }).format(val) + ' L';
    },

    // Notificaciones SweetAlert2 Adaptadas a Paleta Azul Marino / Oro
    showSuccess: function (title, text = '') {
      if (typeof Swal !== 'undefined') {
        return Swal.fire({
          icon: 'success',
          title: title,
          text: text,
          confirmButtonColor: '#153250',
          confirmButtonText: 'Aceptar',
          customClass: { popup: 'rounded-4 shadow-lg border border-2' }
        });
      }
      alert(title + (text ? "\n" + text : ''));
    },

    showError: function (title, text = '') {
      if (typeof Swal !== 'undefined') {
        return Swal.fire({
          icon: 'error',
          title: title,
          text: text,
          confirmButtonColor: '#153250',
          confirmButtonText: 'Entendido',
          customClass: { popup: 'rounded-4 shadow-lg border border-2' }
        });
      }
      alert(title + (text ? "\n" + text : ''));
    },

    showWarning: function (title, text = '') {
      if (typeof Swal !== 'undefined') {
        return Swal.fire({
          icon: 'warning',
          title: title,
          text: text,
          confirmButtonColor: '#b56f10',
          confirmButtonText: 'Aceptar',
          customClass: { popup: 'rounded-4 shadow-lg border border-2' }
        });
      }
      alert(title + (text ? "\n" + text : ''));
    },

    confirmAction: async function (title, text, confirmBtnText = 'Sí, continuar') {
      if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
          title: title,
          text: text,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#153250',
          cancelButtonColor: '#6c757d',
          confirmButtonText: confirmBtnText,
          cancelButtonText: 'Cancelar',
          customClass: { popup: 'rounded-4 shadow-lg border border-2' }
        });
        return result.isConfirmed;
      }
      return confirm(title + (text ? "\n" + text : ''));
    },

    // Refrescar CAPTCHA GD
    refreshCaptcha: function (imgSelector = '#captcha-img', inputSelector = '#captcha-input') {
      const img = document.querySelector(imgSelector);
      const input = document.querySelector(inputSelector);
      if (img) {
        img.src = 'includes/captcha_gd.php?t=' + Date.now();
      }
      if (input) {
        input.value = '';
        input.focus();
      }
    },

    // Mostrar/Ocultar Contraseña
    togglePassword: function (inputSelector, buttonSelector) {
      const input = document.querySelector(inputSelector);
      const btn = document.querySelector(buttonSelector);
      if (input && btn) {
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
          input.type = 'text';
          if (icon) icon.className = 'bi bi-eye-slash-fill';
          btn.setAttribute('title', 'Ocultar contraseña');
        } else {
          input.type = 'password';
          if (icon) icon.className = 'bi bi-eye-fill';
          btn.setAttribute('title', 'Mostrar contraseña');
        }
      }
    },

    // Diccionario en español para DataTables
    dataTablesSpanish: {
      processing: "Procesando registros...",
      search: "Buscar en tabla:",
      lengthMenu: "Mostrar _MENU_ registros",
      info: "Mostrando de _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 registros",
      infoFiltered: "(filtrado de _MAX_ totales)",
      loadingRecords: "Cargando datos...",
      zeroRecords: "No se encontraron registros",
      emptyTable: "No hay registros disponibles",
      paginate: {
        first: "«",
        previous: "‹ Anterior",
        next: "Siguiente ›",
        last: "»"
      }
    }
  };
})();
