<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/header.php
 * Propósito: Encabezado institucional corporativo:
 *            - Modo claro forzado de alto contraste (#F4F7F5 / #FFFFFF).
 *            - Cero emojis: etiquetas AM/PM e iconografía vectorial Lucide Icons (1.5px).
 *            - Topbar en #082F24 y Navbar fija responsive (`navbar-expand-xl`) en #FFFFFF.
 * =========================================================================================
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$usuarioSesion = getCurrentUser();
$paginaActual = basename($_SERVER['SCRIPT_NAME'] ?? '');
$tituloPagina = $tituloPagina ?? (EMPRESA_SIGLA . ' | Control Lechero y Desarrollo Asociativo');
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light">
  <title><?php echo e($tituloPagina); ?></title>

  <meta name="description" content="Plataforma técnica de Control Lechero de ASOAGROALIZAL en Carmen de Carupa, Cundinamarca. NIT <?php echo e(EMPRESA_NIT); ?>.">
  <meta name="author" content="<?php echo e(EMPRESA_NOMBRE); ?>">
  <meta name="theme-color" content="#0F4C3A">

  <!-- Canonical y Favicon -->
  <link rel="canonical" href="http://localhost/asociacion/index.php">
  <link rel="icon" type="image/webp" href="assets/img/logo.webp">

  <!-- Preconnect a Fuentes y CDNs -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">

  <!-- Tipografía Técnica: Plus Jakarta Sans, Inter, JetBrains Mono -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Tailwind CSS via CDN (con preflight desactivado para preservar Bootstrap) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      corePlugins: { preflight: false },
      theme: {
        extend: {
          colors: {
            brand: {
              primary: '#0F4C3A',
              dark: '#082F24',
              secondary: '#15803D',
              accent: '#D97706',
              canvas: '#F4F7F5',
              surface: '#FFFFFF'
            }
          }
        }
      }
    };
  </script>

  <!-- Lucide Icons CDN (Monocromáticos con stroke-width: 1.5px) -->
  <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>

  <!-- ApexCharts CDN -->
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  <!-- DataTables CSS (si aplica) -->
  <?php if (!empty($usarDataTables)): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <?php endif; ?>

  <!-- Estilos del Sistema de Diseño Corporativo -->
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- 1. TOPBAR INSTITUCIONAL (#082F24) -->
  <aside class="corp-topbar" aria-label="Información institucional superior">
    <div class="container-fluid px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="d-inline-flex align-items-center gap-1.5">
          <i data-lucide="map-pin" style="color: #FCD34D;"></i>
          <span>Vereda Alizal, Carmen de Carupa &bull; NIT <?php echo e(EMPRESA_NIT); ?></span>
        </span>
        <span class="d-none d-lg-inline opacity-50">|</span>
        <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="d-inline-flex align-items-center gap-1.5" title="Teléfono institucional">
          <i data-lucide="phone" style="color: #FCD34D;"></i>
          <span><?php echo e(EMPRESA_TELEFONO); ?></span>
        </a>
        <span class="d-none d-lg-inline opacity-50">|</span>
        <span class="d-none d-md-inline-flex align-items-center gap-1.5">
          <i data-lucide="droplet" style="color: #6EE7B7;"></i>
          <span>Control Diario: Turnos AM y PM &bull; Cadena de Frío</span>
        </span>
      </div>

      <div class="d-flex align-items-center gap-3 ms-auto">
        <span class="d-none d-md-inline-flex align-items-center gap-1.5 text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
          <i data-lucide="shield-check" style="color: #6EE7B7;"></i>
          <span>Buenas Prácticas BPO</span>
        </span>
        <a href="index.php#contacto" class="corp-topbar-badge">
          Radicar F-PQRS
        </a>
      </div>
    </div>
  </aside>

  <!-- 2. NAVBAR FIJA BLANCA RESPONSIVE -->
  <nav class="navbar navbar-expand-xl corp-navbar sticky-top" aria-label="Navegación principal">
    <div class="container-fluid px-3 px-md-4">
      <a class="navbar-brand corp-brand" href="index.php">
        <img src="assets/img/logo.webp" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" class="corp-brand-logo" width="44" height="30" loading="eager">
        <div class="d-flex flex-column">
          <span class="corp-brand-text"><?php echo e(EMPRESA_SIGLA); ?></span>
          <span class="corp-brand-sub">Control Lechero y Trazabilidad</span>
        </div>
      </a>

      <!-- Botón Menú Móvil -->
      <button class="navbar-toggler border border-1 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido" aria-controls="navbarContenido" aria-expanded="false" aria-label="Abrir navegación">
        <i data-lucide="menu" style="width: 22px; height: 22px; color: var(--brand-primary);"></i>
      </button>

      <div class="collapse navbar-collapse" id="navbarContenido">
        <?php if ($paginaActual === 'index.php'): ?>
          <ul class="navbar-nav ms-auto align-items-xl-center gap-1 my-3 my-xl-0">
            <li class="nav-item"><a class="nav-link corp-nav-link active" href="#inicio">Inicio</a></li>
            <li class="nav-item"><a class="nav-link corp-nav-link" href="#areas">Operación</a></li>
            <li class="nav-item"><a class="nav-link corp-nav-link" href="#quienes-somos">Asociación</a></li>
            <li class="nav-item"><a class="nav-link corp-nav-link" href="#mision-vision">Misión y Visión</a></li>
            <li class="nav-item"><a class="nav-link corp-nav-link" href="#galeria">Galería</a></li>
            <li class="nav-item"><a class="nav-link corp-nav-link" href="#contacto">Contacto</a></li>

            <li class="nav-item d-flex align-items-center gap-2 ms-xl-3 mt-3 mt-xl-0">
              <?php if ($usuarioSesion): ?>
                <?php $urlDestino = ($usuarioSesion['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
                <a href="<?php echo $urlDestino; ?>" class="btn-corp-primary">
                  <i data-lucide="layout-dashboard"></i>
                  <span>Mi Panel</span>
                </a>
              <?php else: ?>
                <a href="registro.php" class="btn-corp-outline">
                  <span>Vincularse</span>
                </a>
                <a href="login.php" class="btn-corp-primary">
                  <i data-lucide="lock"></i>
                  <span>Portal Asociados</span>
                </a>
              <?php endif; ?>
            </li>
          </ul>
        <?php else: ?>
          <!-- Navegación para Módulos Transaccionales Internos -->
          <ul class="navbar-nav ms-auto align-items-xl-center gap-2 my-2 my-xl-0">
            <?php if ($usuarioSesion): ?>
              <li class="nav-item d-none d-md-block text-end me-2">
                <span class="badge border font-mono px-2 py-1 rounded" style="background-color: var(--bg-surface-subtle); color: var(--brand-primary); font-size: 0.7rem;">
                  ROL: <?php echo e($usuarioSesion['rol']); ?>
                </span>
                <span class="fw-bold d-block text-dark small mt-0.5"><?php echo e($usuarioSesion['nombre']); ?></span>
              </li>

              <li class="nav-item">
                <a href="index.php" class="btn-corp-outline">
                  <i data-lucide="home"></i>
                  <span>Portal</span>
                </a>
              </li>

              <?php if ($usuarioSesion['rol'] === 'ADMIN'): ?>
                <li class="nav-item">
                  <a href="admin.php" class="btn-corp-primary">
                    <i data-lucide="table"></i>
                    <span>Planilla Matriz</span>
                  </a>
                </li>
              <?php else: ?>
                <li class="nav-item">
                  <a href="panel.php" class="btn-corp-primary">
                    <i data-lucide="bar-chart-2"></i>
                    <span>Mis Pesajes</span>
                  </a>
                </li>
              <?php endif; ?>

              <li class="nav-item">
                <a href="logout.php" class="btn-corp-outline" style="border-color: #FCA5A5; color: #B91C1C !important;" title="Cerrar sesión de forma segura">
                  <i data-lucide="log-out"></i>
                  <span>Salir</span>
                </a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a href="index.php" class="btn-corp-outline">
                  <i data-lucide="arrow-left"></i>
                  <span>Volver al Portal</span>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Enlace accesible -->
  <a href="#contenido-principal" class="visually-hidden-focusable p-3 bg-dark text-white fw-bold">Saltar al contenido principal</a>

  <!-- Mensajes Flash SweetAlert2 -->
  <?php if (function_exists('renderSweetAlert')): ?>
    <?php echo renderSweetAlert(); ?>
  <?php endif; ?>
