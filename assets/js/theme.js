/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: assets/js/theme.js
 * Propósito: Modo oscuro anti-parpadeo, SweetAlert2 helpers, DataTables español y utilidades.
 * =========================================================================================
 */

(function () {
  'use strict';

  // 1. SISTEMA DE MODO OSCURO PERSISTENTE (Bootstrap 5.3 + LocalStorage)
  const THEME_STORAGE_KEY = 'asoagro_theme';

  function getPreferredTheme() {
    const storedTheme = localStorage.getItem(THEME_STORAGE_KEY);
    if (storedTheme) {
      return storedTheme;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function setTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem(THEME_STORAGE_KEY, theme);
    updateThemeToggleIcons(theme);
  }

  function updateThemeToggleIcons(theme) {
    const toggleBtns = document.querySelectorAll('.theme-toggle-btn');
    toggleBtns.forEach((btn) => {
      const icon = btn.querySelector('i');
      if (icon) {
        if (theme === 'dark') {
          icon.className = 'bi bi-sun-fill text-warning';
          btn.setAttribute('title', 'Cambiar a modo claro');
        } else {
          icon.className = 'bi bi-moon-stars-fill text-success';
          btn.setAttribute('title', 'Cambiar a modo oscuro');
        }
      }
    });
  }

  // Inicializar tema al cargar el DOM
  document.addEventListener('DOMContentLoaded', () => {
    const currentTheme = getPreferredTheme();
    setTheme(currentTheme);

    // Event listeners para los botones de alternar tema
    const toggleBtns = document.querySelectorAll('.theme-toggle-btn');
    toggleBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        const activeTheme = document.documentElement.getAttribute('data-bs-theme');
        const nextTheme = activeTheme === 'dark' ? 'light' : 'dark';
        setTheme(nextTheme);
      });
    });

    // Escuchar cambios automáticos del sistema operativo si el usuario no tiene preferencia manual
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      if (!localStorage.getItem(THEME_STORAGE_KEY)) {
        setTheme(getPreferredTheme());
      }
    });

    // 2. ANIMACIÓN SUAVE POR INTERSECTION OBSERVER (Scroll Reveal)
    const revealElements = document.querySelectorAll('.reveal-on-scroll');
    if ('IntersectionObserver' in window && revealElements.length > 0) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12 });

      revealElements.forEach((el) => observer.observe(el));
    } else {
      revealElements.forEach((el) => el.classList.add('is-visible'));
    }

    // 3. CONTADOR ANIMADO (Nuestra Familia: 32 Asociados)
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const counters = document.querySelectorAll('.counter-animated');
    if (counters.length > 0) {
      if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        counters.forEach((c) => {
          c.textContent = c.getAttribute('data-target') || '32';
        });
      } else {
        const counterObserver = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const target = parseInt(entry.target.getAttribute('data-target') || '32', 10);
              animateCounter(entry.target, target, 1600);
              counterObserver.unobserve(entry.target);
            }
          });
        }, { threshold: 0.3 });

        counters.forEach((c) => counterObserver.observe(c));
      }
    }

    // 4. NAVBAR TRANSPARENTE SOBRE HERO Y SÓLIDO EN SCROLL
    const nav = document.querySelector('.navbar-asoagro');
    const heroFull = document.querySelector('.hero-fullwidth');
    if (nav && heroFull) {
      const handleNavScroll = () => {
        if (window.scrollY > 40) {
          nav.classList.add('navbar-scrolled');
          nav.classList.remove('navbar-transparent');
        } else {
          nav.classList.add('navbar-transparent');
          nav.classList.remove('navbar-scrolled');
        }
      };
      window.addEventListener('scroll', handleNavScroll, { passive: true });
      handleNavScroll();
    }
  });

  function animateCounter(element, target, duration) {
    let start = 0;
    const stepTime = Math.max(10, Math.floor(duration / target));
    const timer = setInterval(() => {
      start += 1;
      element.textContent = start;
      if (start >= target) {
        element.textContent = target;
        clearInterval(timer);
      }
    }, stepTime);
  }

  // 4. HELPERS GLOBALES EXPUESTOS EN WINDOW (AsoagroUI)
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

    // Notificaciones SweetAlert2
    showSuccess: function (title, text = '') {
      if (typeof Swal !== 'undefined') {
        return Swal.fire({
          icon: 'success',
          title: title,
          text: text,
          confirmButtonColor: '#144722',
          confirmButtonText: 'Aceptar',
          customClass: { popup: 'rounded-4 shadow-lg' }
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
          confirmButtonColor: '#144722',
          confirmButtonText: 'Entendido',
          customClass: { popup: 'rounded-4 shadow-lg' }
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
          confirmButtonColor: '#f59e0b',
          confirmButtonText: 'Aceptar',
          customClass: { popup: 'rounded-4 shadow-lg' }
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
          confirmButtonColor: '#144722',
          cancelButtonColor: '#6c757d',
          confirmButtonText: confirmBtnText,
          cancelButtonText: 'Cancelar',
          customClass: { popup: 'rounded-4 shadow-lg' }
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

    // Diccionario español nativo para DataTables sin dependencias externas
    dataTablesSpanish: {
      processing: "Procesando registros...",
      search: "Buscar en tabla:",
      lengthMenu: "Mostrar _MENU_ por página",
      info: "Mostrando de _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 de 0 registros",
      infoFiltered: "(filtrado de un total de _MAX_)",
      infoPostFix: "",
      loadingRecords: "Cargando datos...",
      zeroRecords: "No se encontraron registros coincidentes",
      emptyTable: "No hay registros disponibles",
      paginate: {
        first: "«",
        previous: "‹",
        next: "›",
        last: "»"
      },
      aria: {
        sortAscending: ": activar para ordenar columna ascendente",
        sortDescending: ": activar para ordenar columna descendente"
      },
      buttons: {
        copy: "Copiar",
        colvis: "Columnas",
        csv: "CSV",
        excel: "Excel",
        pdf: "PDF",
        print: "Imprimir"
      }
    }
  };
})();

