/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: assets/js/theme.js
 * Propósito: Motor de experiencia visual y animaciones interactivas:
 *            - Modo claro forzado (color-scheme: light).
 *            - Reveals suaves al hacer scroll (IntersectionObserver con efecto fade-up).
 *            - Contadores animados reales con aceleración fluida (requestAnimationFrame).
 *            - ScrollSpy dinámico para resaltar sección activa en la barra de navegación.
 *            - Barra de navegación con elevación y desenfoque dinámico al deslizar.
 *            - Micro-interacciones táctiles (ripple effect en botones).
 *            - Helpers de formato COP y Litros + notificaciones SweetAlert2.
 * =========================================================================================
 */

(function () {
  'use strict';

  // Respeto a preferencias de movimiento reducido del sistema
  const REDUCE_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // 1. FORZADO DE TEMA CLARO ÚNICO (PREVIENE INVERSIONES INDESEADAS EN CELULARES)
  document.documentElement.setAttribute('data-bs-theme', 'light');
  document.documentElement.style.colorScheme = 'light';

  // 2. CONTADOR ANIMADO REAL (rAF con curva de desaceleración cúbica suave)
  function animateCounter(el) {
    const raw = el.getAttribute('data-target') || el.getAttribute('data-counter-target') || el.textContent;
    const target = parseFloat(String(raw).replace(/[^\d.,-]/g, '').replace(',', '.'));

    if (isNaN(target)) {
      return; // No es numérico: dejar el texto original
    }

    const decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
    const prefix = el.getAttribute('data-prefix') || el.getAttribute('data-counter-prefix') || '';
    const suffix = el.getAttribute('data-suffix') || el.getAttribute('data-counter-suffix') || '';
    const separator = el.getAttribute('data-separator') !== 'off';
    const duration = REDUCE_MOTION ? 0 : (parseInt(el.getAttribute('data-duration') || '1500', 10) || 1500);

    const formatter = new Intl.NumberFormat('es-CO', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
      useGrouping: separator
    });

    function render(value) {
      el.textContent = prefix + formatter.format(Math.round(value)) + suffix;
    }

    if (duration === 0) {
      render(target);
      return;
    }

    const startTime = performance.now();

    function step(now) {
      const progress = Math.min((now - startTime) / duration, 1);
      // Easing cúbico de desaceleración suave: easeOutCubic
      const eased = 1 - Math.pow(1 - progress, 3);
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
    const counters = document.querySelectorAll('.counter-animated, [data-counter-target]');
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
    }, { threshold: 0.35 });

    counters.forEach((c) => observer.observe(c));
  }

  // 3. REVEALS AL HACER SCROLL (APARICIÓN ESCALONADA Y FLUIDA)
  function setupReveals() {
    // Auto-etiquetado de elementos clave para animaciones suaves
    document.querySelectorAll(
      'main .card, .card-elevate, .kpi-card, .section-eyebrow, .section-title, .hero-badge, .workflow-step'
    ).forEach((el) => {
      if (!el.classList.contains('reveal')) {
        el.classList.add('reveal');
      }
    });

    const items = document.querySelectorAll('.reveal, [data-reveal]');
    if (items.length === 0) return;

    // Escalonar tarjetas dentro de filas Bootstrap para efecto cascada (stagger)
    document.querySelectorAll('.row').forEach((row) => {
      const kids = row.querySelectorAll(':scope > .col [class*="card"], :scope > * > .reveal, :scope > .col');
      let i = 0;
      kids.forEach((kid) => {
        if (kid.classList.contains('reveal')) {
          kid.style.setProperty('--reveal-delay', Math.min(i * 100, 500) + 'ms');
          i++;
        }
      });
    });

    if (!('IntersectionObserver' in window) || REDUCE_MOTION) {
      items.forEach((el) => el.classList.add('reveal-visible'));
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    items.forEach((el) => observer.observe(el));
  }

  // 4. SCROLLSPY DEL NAVBAR (RESALTADO AUTOMÁTICO DE SECCIÓN ACTIVA)
  function setupScrollSpy() {
    const links = document.querySelectorAll('.nav-link-custom[href^="#"]');
    if (links.length === 0) return;

    const sections = [];
    links.forEach((link) => {
      const href = link.getAttribute('href');
      if (href && href.length > 1) {
        const id = href.slice(1);
        const section = document.getElementById(id);
        if (section) sections.push({ id, link, section });
      }
    });
    if (sections.length === 0) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          sections.forEach((s) => {
            const isActive = s.id === entry.target.id;
            s.link.classList.toggle('active', isActive);
          });
        }
      });
    }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });

    sections.forEach((s) => observer.observe(s.section));

    // Cierre suave del menú móvil al pulsar un ancla
    links.forEach((link) => {
      link.addEventListener('click', () => {
        const collapse = document.getElementById('navbarContenido');
        if (collapse && collapse.classList.contains('show')) {
          if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            bootstrap.Collapse.getOrCreateInstance(collapse).hide();
          }
        }
      });
    });
  }

  // 5. NAVBAR CON SOMBRA Y ELEVACIÓN DINÁMICA AL HACER SCROLL
  function setupNavbarScroll() {
    const navbar = document.querySelector('.navbar-asoagro');
    if (!navbar) return;

    const update = () => {
      navbar.classList.toggle('navbar-scrolled', window.scrollY > 20);
    };

    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  // 6. ONDA (RIPPLE EFFECT) EN BOTONES
  function setupRipple() {
    if (REDUCE_MOTION) return;

    document.querySelectorAll('.btn-touch, .btn-aso-primary, .btn-aso-accent, .btn-hero-primary, .btn-hero-secondary').forEach((btn) => {
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

  // 7. INICIALIZAR AL CARGAR EL DOM
  document.addEventListener('DOMContentLoaded', () => {
    setupCounters();
    setupReveals();
    setupScrollSpy();
    setupNavbarScroll();
    setupRipple();
  });

  // 8. HELPERS GLOBALES EXPUESTOS EN WINDOW (AsoagroUI)
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

    // Mostrar/Ocultar Contraseña
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
