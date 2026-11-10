<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/header.php
 * Propósito: Encabezado institucional minimalista inspirado en Central Ganadera:
 *            - Modo claro forzado de alto contraste.
 *            - Cero emojis: tipografía sobria e iconografía vectorial Lucide Icons (stroke: 1.5px).
 *            - Topbar sobria en #071F13 y Navbar fija en #FFFFFF con borde fino #E2E8F0.
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
  <meta name="theme-color" content="#0A2E1C">

  <!-- Canonical y Favicon -->
  <link rel="canonical" href="http://localhost/asociacion/index.php">
  <link rel="icon" type="image/webp" href="assets/img/logo.webp">

  <!-- Preconnect a Fuentes y CDNs -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://unpkg.com">

  <!-- Tipografía Técnica: Plus Jakarta Sans, Inter, JetBrains Mono -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Tailwind CSS via CDN (con preflight desactivado para preservar arquitectura) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      corePlugins: { preflight: false },
      theme: {
        extend: {
          colors: {
            brand: {
              primary: '#0A2E1C',
              secondary: '#14532D',
              canvas: '#F8FAFC',
              surface: '#FFFFFF'
            }
          }
        }
      }
    };
  </script>

  <!-- Lucide Icons CDN (Monocromáticos con stroke-width: 1.5px) -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- ApexCharts CDN (Visualización Financiera Sobria) -->
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  <!-- DataTables CSS (si aplica) -->
  <?php if (!empty($usarDataTables)): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <?php endif; ?>

  <!-- Estilos del Sistema de Diseño Minimalista -->
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-slate-50 text-slate-900">

  <!-- 1. TOPBAR INSTITUCIONAL SOBRIA (#071F13) -->
  <aside class="corp-topbar" aria-label="Información institucional superior">
    <div class="container-fluid px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <!-- Datos oficiales -->
      <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="d-inline-flex align-items-center gap-1.5">
          <i data-lucide="map-pin" class="text-slate-400"></i>
          <span>Vereda Alizal, Carmen de Carupa &bull; NIT <?php echo e(EMPRESA_NIT); ?></span>
        </span>
        <span class="d-none d-lg-inline text-slate-600">|</span>
        <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="d-inline-flex align-items-center gap-1.5" title="Teléfono institucional">
          <i data-lucide="phone" class="text-slate-400"></i>
          <span><?php echo e(EMPRESA_TELEFONO); ?></span>
        </a>
        <span class="d-none d-lg-inline text-slate-600">|</span>
        <span class="d-none d-md-inline-flex align-items-center gap-1.5 text-slate-300">
          <i data-lucide="clock" class="text-slate-400"></i>
          <span>Recepción: 05:30 - 07:30 / 16:00 - 17:30</span>
        </span>
      </div>

      <!-- Enlace directo F-PQRS -->
      <div class="d-flex align-items-center gap-3 ms-auto">
        <span class="d-none d-md-inline-flex align-items-center gap-1.5 text-slate-300 text-xs font-semibold uppercase tracking-wider">
          <i data-lucide="shield-check" class="text-slate-400"></i>
          <span>Norma ICA / INVIMA</span>
        </span>
        <a href="index.php#contacto" class="corp-topbar-badge">
          Radicar F-PQRS
        </a>
      </div>
    </div>
  </aside>

  <!-- 2. NAVBAR FIJA BLANCA CON BORDE SUTIL #E2E8F0 -->
  <nav class="corp-navbar" aria-label="Navegación principal">
    <div class="container-fluid px-3 px-md-4">
      <a class="corp-brand" href="index.php">
        <img src="assets/img/logo.webp" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" class="corp-brand-logo" width="44" height="30" loading="eager">
        <div class="d-flex flex-column">
          <span class="corp-brand-text"><?php echo e(EMPRESA_SIGLA); ?></span>
          <span class="corp-brand-sub">Control Lechero y Trazabilidad</span>
        </div>
      </a>

      <!-- Botón Menú Móvil -->
      <button class="navbar-toggler border-0 p-2 d-xl-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido" aria-controls="navbarContenido" aria-expanded="false" aria-label="Abrir navegación">
        <i data-lucide="menu" class="w-6 h-6 text-slate-800"></i>
      </button>

      <div class="collapse navbar-collapse" id="navbarContenido">
        <?php if ($paginaActual === 'index.php'): ?>
          <ul class="navbar-nav ms-auto align-items-xl-center gap-1 my-3 my-xl-0">
            <li class="nav-item"><a class="corp-nav-link active" href="#inicio">Inicio</a></li>
            <li class="nav-item"><a class="corp-nav-link" href="#quienes-somos">Nuestra Asociación</a></li>
            <li class="nav-item"><a class="corp-nav-link" href="#areas">Estándares de Calidad</a></li>
            <li class="nav-item"><a class="corp-nav-link" href="#precio">Precio Quincenal</a></li>
            <li class="nav-item"><a class="corp-nav-link" href="#infraestructura">Planta y Fincas</a></li>
            <li class="nav-item"><a class="corp-nav-link" href="#contacto">Contacto</a></li>

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
                  <span>Portal de Asociados</span>
                </a>
              <?php endif; ?>
            </li>
          </ul>
        <?php else: ?>
          <!-- Navegación para Módulos Transaccionales Internos -->
          <ul class="navbar-nav ms-auto align-items-center gap-2 my-2 my-lg-0">
            <?php if ($usuarioSesion): ?>
              <li class="nav-item d-none d-md-block text-end me-2">
                <span class="badge border bg-slate-100 text-slate-800 font-mono text-xs font-semibold px-2.5 py-1 rounded">
                  ROL: <?php echo e($usuarioSesion['rol']); ?>
                </span>
                <span class="fw-bold d-block text-slate-900 text-sm mt-0.5"><?php echo e($usuarioSesion['nombre']); ?></span>
              </li>

              <li class="nav-item">
                <a href="index.php" class="btn-corp-outline py-1.5 px-3 text-xs">
                  <i data-lucide="home"></i>
                  <span>Portal</span>
                </a>
              </li>

              <?php if ($usuarioSesion['rol'] === 'ADMIN'): ?>
                <li class="nav-item">
                  <a href="admin.php" class="btn-corp-primary py-1.5 px-3 text-xs">
                    <i data-lucide="table"></i>
                    <span>Planilla Matriz</span>
                  </a>
                </li>
              <?php else: ?>
                <li class="nav-item">
                  <a href="panel.php" class="btn-corp-primary py-1.5 px-3 text-xs">
                    <i data-lucide="chart-column"></i>
                    <span>Mis Pesajes</span>
                  </a>
                </li>
              <?php endif; ?>

              <li class="nav-item">
                <a href="logout.php" class="btn-corp-outline border-rose-300 text-rose-700 py-1.5 px-3 text-xs" title="Cerrar sesión de forma segura">
                  <i data-lucide="log-out"></i>
                  <span>Salir</span>
                </a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a href="index.php" class="btn-corp-outline py-1.5 px-3 text-xs">
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
