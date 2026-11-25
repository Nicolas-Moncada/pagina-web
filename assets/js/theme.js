(function () {
  'use strict';
  const REDUCE_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.documentElement.setAttribute('data-bs-theme', 'light');
  document.documentElement.style.colorScheme = 'light';

  function animateCounter(el) {
    const raw = el.getAttribute('data-target') || el.getAttribute('data-counter-target') || el.textContent;
    const target = parseFloat(String(raw).replace(/[^\d.,-]/g, '').replace(',', '.'));
    if (isNaN(target)) return;
    const decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
    const prefix = el.getAttribute('data-prefix') || el.getAttribute('data-counter-prefix') || '';
    const suffix = el.getAttribute('data-suffix') || el.getAttribute('data-counter-suffix') || '';
    const separator = el.getAttribute('data-separator') !== 'off';
    const duration = REDUCE_MOTION ? 0 : (parseInt(el.getAttribute('data-duration') || '1200', 10) || 1200);
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
      const eased = 1 - Math.pow(1 - progress, 3);
      render(target * eased);
      if (progress < 1) requestAnimationFrame(step);
      else render(target);
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
    }, { threshold: 0.3 });
    counters.forEach((c) => observer.observe(c));
  }

  function setupReveals() {
    const items = document.querySelectorAll('.reveal, [data-reveal]');
    if (items.length === 0) return;
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
    }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });
    items.forEach((el) => observer.observe(el));
  }

  function setupScrollSpy() {
    const links = document.querySelectorAll('.corp-nav-link[href^="#"]');
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
          sections.forEach((s) => s.link.classList.toggle('active', s.id === entry.target.id));
        }
      });
    }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });
    sections.forEach((s) => observer.observe(s.section));

    links.forEach((link) => {
      link.addEventListener('click', () => {
        const collapse = document.getElementById('navbarContenido');
        if (collapse && collapse.classList.contains('show') && typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
          bootstrap.Collapse.getOrCreateInstance(collapse).hide();
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    setupCounters();
    setupReveals();
    setupScrollSpy();
  });

  window.AsoagroUI = {
    formatCOP: function (val) {
      if (val === null || val === undefined || isNaN(val)) return '$ 0';
      return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0
      }).format(val);
    },
    formatLitros: function (val) {
      if (val === null || val === undefined || isNaN(val)) return '0,0 L';
      return new Intl.NumberFormat('es-CO', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
      }).format(val) + ' L';
    },
    showSuccess: function (title, text = '') {
      if (typeof Swal !== 'undefined') {
        return Swal.fire({
          icon: 'success',
          title: title,
          text: text,
          confirmButtonColor: '#0F4C3A',
          confirmButtonText: 'Aceptar',
          customClass: { popup: 'rounded-4 shadow-lg border' }
        });
      }
      alert(title + (text ? '\n' + text : ''));
    },
    showError: function (title, text = '') {
      if (typeof Swal !== 'undefined') {
        return Swal.fire({
          icon: 'error',
          title: title,
          text: text,
          confirmButtonColor: '#0F4C3A',
          confirmButtonText: 'Entendido',
          customClass: { popup: 'rounded-4 shadow-lg border' }
        });
      }
      alert(title + (text ? '\n' + text : ''));
    },
    showWarning: function (title, text = '') {
      if (typeof Swal !== 'undefined') {
        return Swal.fire({
          icon: 'warning',
          title: title,
          text: text,
          confirmButtonColor: '#D97706',
          confirmButtonText: 'Aceptar',
          customClass: { popup: 'rounded-4 shadow-lg border' }
        });
      }
      alert(title + (text ? '\n' + text : ''));
    },
    confirmAction: async function (title, text, confirmBtnText = 'Sí, continuar') {
      if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
          title: title,
          text: text,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#0F4C3A',
          cancelButtonColor: '#64748B',
          confirmButtonText: confirmBtnText,
          cancelButtonText: 'Cancelar',
          customClass: { popup: 'rounded-4 shadow-lg border' }
        });
        return result.isConfirmed;
      }
      return confirm(title + (text ? '\n' + text : ''));
    },
    refreshCaptcha: function (imgSelector = '#captcha-img', inputSelector = '#captcha-input') {
      const img = document.querySelector(imgSelector);
      const input = inputSelector instanceof Element ? inputSelector : document.querySelector(inputSelector || '#captcha-input');
      if (img) img.src = 'api/captcha_img.php?t=' + Date.now();
      if (input) {
        input.value = '';
        input.focus();
      }
    },
    togglePassword: function (inputSelector, buttonSelector) {
      const input = inputSelector instanceof Element ? inputSelector : document.querySelector(inputSelector);
      const btn = buttonSelector instanceof Element ? buttonSelector : document.querySelector(buttonSelector);
      if (input && btn) {
        const icon = btn.querySelector('[data-lucide]');
        if (input.type === 'password') {
          input.type = 'text';
          if (icon) icon.setAttribute('data-lucide', 'eye-off');
          btn.setAttribute('title', 'Ocultar contraseña');
        } else {
          input.type = 'password';
          if (icon) icon.setAttribute('data-lucide', 'eye');
          btn.setAttribute('title', 'Mostrar contraseña');
        }
        if (typeof lucide !== 'undefined') {
          lucide.createIcons({ attrs: { 'stroke-width': 1.5 } });
        }
      }
    },
    dataTablesSpanish: {
      processing: 'Procesando registros...',
      search: 'Buscar en tabla:',
      lengthMenu: 'Mostrar _MENU_ registros',
      info: 'Mostrando de _START_ a _END_ de _TOTAL_ registros',
      infoEmpty: 'Mostrando 0 registros',
      infoFiltered: '(filtrado de _MAX_ totales)',
      loadingRecords: 'Cargando datos...',
      zeroRecords: 'No se encontraron registros',
      emptyTable: 'No hay registros disponibles',
      paginate: { first: '«', previous: '‹ Anterior', next: 'Siguiente ›', last: '»' }
    }
  };
})();
