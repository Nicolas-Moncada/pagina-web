/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: assets/js/theme.js
 * Propósito: Motor de experiencia PREMIUM:
 *            - Control de tamaño de letra (A, A+, A++) persistente en localStorage.
 *            - Modo claro forzado (color-scheme: light).
 *            - Reveals al hacer scroll (IntersectionObserver).
 *            - Contadores animados reales (requestAnimationFrame).
 *            - ScrollSpy para el navbar del sitio público.
 *            - Navbar con sombra dinámica + ondas (ripple) en botones.
 *            - SweetAlert2 adaptado a la paleta azul marino/oro.
 *            - Helpers de formato COP y Litros + diccionario DataTables.
 * =========================================================================================
 */

(function () {
  'use strict';

  // Respecto a preferencias de movimiento reducido del sistema
  const REDUCE_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

  // 3. CONTADOR ANIMADO REAL (rAF con easing easeOutCubic)
  function animateCounter(el) {
    const raw = el.getAttribute('data-target') ?? el.textContent;
    const target = parseFloat(String(raw).replace(/[^\d.,-]/g, '').replace(',', '.'));

    if (isNaN(target)) {
      return; // No es numérico: dejar el texto original
    }

    const decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
    const prefix = el.getAttribute('data-prefix') || '';
    const suffix = el.getAttribute('data-suffix') || '';
    const separator = el.getAttribute('data-separator') !== 'off';
    const duration = REDUCE_MOTION ? 0 : (parseInt(el.getAttribute('data-duration') || '1400', 10) || 1400);

    const formatter = new Intl.NumberFormat('es-CO', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
      useGrouping: separator
    });

    function render(value) {
      el.textContent = prefix + formatter.format(value) + suffix;
    }

    if (duration === 0) {
      render(target);
      return;
    }

    const startTime = performance.now();

    function step(now) {
      const progress = Math.min((now - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
      render(target * eased);
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        render(target);
      }
    }

    requestAnimationFrame(step);
  }

  function setupCounters() {
    const counters = document.querySelectorAll('.counter-animated');
    if (counters.length === 0) return;

    if (!('IntersectionObserver' in window) || REDUCE_MOTION) {
      counters.forEach(animateCounter);
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });

    counters.forEach((c) => observer.observe(c));
  }

  // 4. REVEALS AL HACER SCROLL
  function setupReveals() {
    // Auto-etiquetado de bloqueos visuales característicos del sistema
    document.querySelectorAll(
      'main .card, main .card-solid, main .kpi-card, main .section-eyebrow, main .section-title'
    ).forEach((el) => {
      if (!el.classList.contains('reveal')) {
        el.classList.add('reveal');
      }
    });

    const items = document.querySelectorAll('.reveal, [data-reveal]');
    if (items.length === 0) return;

    // Converter data-reveal a clase para unificar
    items.forEach((el) => {
      if (!el.classList.contains('reveal')) el.classList.add('reveal');
      const delay = el.getAttribute('data-reveal-delay');
      if (delay) el.style.setProperty('--reveal-delay', delay + 'ms');
    });

    if (!('IntersectionObserver' in window) || REDUCE_MOTION) {
      items.forEach((el) => el.classList.add('reveal-visible'));
      return;
    }

    // Escalonar tarjetas dentro de filas Bootstrap para efecto cascada
    document.querySelectorAll('.row').forEach((row) => {
      const kids = row.querySelectorAll(':scope > .col [class*="card"], :scope > * > .reveal');
      let i = 0;
      kids.forEach((kid) => {
        if (kid.classList.contains('reveal')) {
          kid.style.setProperty('--reveal-delay', Math.min(i * 90, 450) + 'ms');
          i++;
        }
      });
    });

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    items.forEach((el) => observer.observe(el));
  }

  // 5. SCROLLSPY DEL NAVBAR (solo index con anclas)
  function setupScrollSpy() {
    const links = document.querySelectorAll('.nav-link-custom[href^="#"]');
    if (links.length === 0) return;

    const sections = [];
    links.forEach((link) => {
      const id = link.getAttribute('href').slice(1);
      const section = document.getElementById(id);
      if (section) sections.push({ id, link, section });
    });
    if (sections.length === 0) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          sections.forEach((s) => s.link.classList.toggle('active', s.id === entry.target.id));
        }
      });
    }, { rootMargin: '-35% 0px -55% 0px', threshold: 0 });

    sections.forEach((s) => observer.observe(s.section));

    // Cierre del menú móvil al pulsar un ancla
    links.forEach((link) => {
      link.addEventListener('click', () => {
        const collapse = document.getElementById('navbarContenido');
        if (collapse && collapse.classList.contains('show')) {
          bootstrap.Collapse.getOrCreateInstance(collapse).hide();
        }
      });
    });
  }

  // 6. NAVBAR CON SOMBRA DINÁMICA AL HACER SCROLL
  function setupNavbarScroll() {
    const navbar = document.querySelector('.navbar-asoagro');
    if (!navbar) return;

    const update = () => {
      navbar.classList.toggle('navbar-scrolled', window.scrollY > 10);
    };

    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  // 7. ONDA (RIPPLE) EN BOTONES GRANDES
  function setupRipple() {
    if (REDUCE_MOTION) return;

    document.querySelectorAll('.btn-touch, .btn-aso-primary, .btn-aso-accent').forEach((btn) => {
      btn.addEventListener('click', function (e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX ? e.clientX - rect.left : rect.width / 2;
        const y = e.clientY ? e.clientY - rect.top : rect.height / 2;
        const wave = document.createElement('span');

        wave.className = 'ripple';
        wave.style.setProperty('--ripple-x', x + 'px');
        wave.style.setProperty('--ripple-y', y + 'px');

        this.appendChild(wave);
        setTimeout(() => wave.remove(), 650);
      });
    });
  }

  // 8. INICIALIZAR AL CARGAR EL DOM
  document.addEventListener('DOMContentLoaded', () => {
    // Preferencia guardada (por defecto 'normal' que ya es 20px)
    let savedSize = 'normal';
    try {
      savedSize = localStorage.getItem(FONT_STORAGE_KEY) || 'normal';
    } catch (e) {}
    applyFontSize(savedSize);

    // Listeners de los botones de tamaño de fuente
    document.querySelectorAll('.btn-font-size').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        applyFontSize(btn.getAttribute('data-size'));
      });
    });

    setupCounters();
    setupReveals();
    setupScrollSpy();
    setupNavbarScroll();
    setupRipple();
  });

  // 9. HELPERS GLOBALES EXPUESTOS EN WINDOW (AsoagroUI)
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
          confirmButtonColor: '#16375f',
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
          confirmButtonColor: '#16375f',
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
          confirmButtonColor: '#16375f',
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
      const input = inputSelector instanceof Element
        ? inputSelector
        : document.querySelector(inputSelector || '#captcha-input');
      if (img) {
        img.src = 'includes/captcha_gd.php?t=' + Date.now();
      }
      if (input) {
        input.value = '';
        input.focus();
      }
    },

    // Mostrar/Ocultar Contraseña (acepta selector o elemento como botón)
    togglePassword: function (inputSelector, buttonSelector) {
      const input = inputSelector instanceof Element
        ? inputSelector
        : document.querySelector(inputSelector);
      const btn = buttonSelector instanceof Element
        ? buttonSelector
        : document.querySelector(buttonSelector);
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
