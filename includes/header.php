<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/header.php
 * Propósito: Encabezado institucional corporativo inspirado en Central Ganadera:
 *            - Topbar institucional oscura (#072717) con datos oficiales y enlace F-PQRS.
 *            - Navbar sticky con logo lechero y botones a Portal Asociados y Registro.
 *            - Forzado exclusivo de modo claro (<meta name="color-scheme" content="light">).
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

  <meta name="description" content="Plataforma de Control Lechero y desarrollo asociativo de ASOAGROALIZAL en Carmen de Carupa, Cundinamarca. NIT <?php echo e(EMPRESA_NIT); ?>.">
  <meta name="author" content="<?php echo e(EMPRESA_NOMBRE); ?>">
  <meta name="theme-color" content="#0B3B24">

  <!-- Canonical y Open Graph -->
  <link rel="canonical" href="http://localhost/asociacion/index.php">
  <meta property="og:title" content="<?php echo e($tituloPagina); ?>">
  <meta property="og:description" content="Plataforma de Control Lechero y desarrollo asociativo de ASOAGROALIZAL en Carmen de Carupa, Cundinamarca.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="http://localhost/asociacion/index.php">
  <meta property="og:image" content="assets/img/hero-ganado.webp">

  <!-- Preconnect a CDNs externos -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">

  <!-- Favicon Oficial -->
  <link rel="icon" type="image/webp" href="assets/img/logo.webp">

  <!-- Preload de imagen del héroe -->
  <link rel="preload" as="image" fetchpriority="high" href="assets/img/hero-ganado-mobile.webp">
  <link rel="preload" as="image" href="assets/img/logo.webp">

  <!-- Google Fonts: Plus Jakarta Sans, Inter, JetBrains Mono -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Iconos: Bootstrap Icons + FontAwesome 6 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- DataTables CSS (si aplica) -->
  <?php if (!empty($usarDataTables)): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
  <?php endif; ?>

  <!-- Estilos Corporativos Globales -->
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- 1. TOPBAR INSTITUCIONAL OSCURA (Estilo Central Ganadera) -->
  <aside class="topbar-agro" aria-label="Información corporativa superior">
    <div class="container-fluid px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <!-- Datos de contacto oficiales -->
      <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="d-inline-flex align-items-center gap-1.5">
          <i class="fa-solid fa-location-dot" style="color: var(--accent-gold);"></i>
          <span>Vereda Alizal, Carmen de Carupa &bull; NIT <?php echo e(EMPRESA_NIT); ?></span>
        </span>
        <span class="d-none d-lg-inline text-secondary">|</span>
        <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="d-inline-flex align-items-center gap-1.5" title="Llamar a la Asociación">
          <i class="fa-solid fa-phone" style="color: var(--accent-gold);"></i>
          <span><?php echo e(EMPRESA_TELEFONO); ?></span>
        </a>
        <span class="d-none d-lg-inline text-secondary">|</span>
        <a href="https://wa.me/57<?php echo e(EMPRESA_TELEFONO); ?>" target="_blank" rel="noopener noreferrer" class="d-inline-flex align-items-center gap-1.5 text-success-emphasis" title="Escribir por WhatsApp">
          <i class="fa-brands fa-whatsapp fs-6 text-success"></i>
          <span>WhatsApp Directo</span>
        </a>
      </div>

      <!-- Certificaciones y Enlace F-PQRS -->
      <div class="d-flex align-items-center gap-3 ms-auto">
        <span class="d-none d-md-inline-flex align-items-center gap-1.5 text-light opacity-75">
          <i class="fa-solid fa-award" style="color: var(--accent-gold);"></i>
          <span>Estándar ICA / INVIMA</span>
        </span>
        <a href="index.php#contacto" class="topbar-badge-fpqrs text-decoration-none">
          <i class="fa-solid fa-file-pen me-1"></i> Radicar F-PQRS
        </a>
      </div>
    </div>
  </aside>

  <!-- 2. NAVBAR INSTITUCIONAL STICKY -->
  <nav class="navbar navbar-expand-xl navbar-agro sticky-top" aria-label="Navegación principal">
    <div class="container-fluid px-3 px-md-4">
      <a class="navbar-brand-agro" href="index.php">
        <img src="assets/img/logo.webp" alt="Logo Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="brand-logo" width="300" height="208" loading="eager">
        <div class="d-flex flex-column">
          <span class="brand-title"><?php echo e(EMPRESA_SIGLA); ?></span>
          <span class="brand-subtitle">Control Lechero &bull; Carmen de Carupa</span>
        </div>
      </a>

      <!-- Botón Menú Móvil -->
      <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido" aria-controls="navbarContenido" aria-expanded="false" aria-label="Abrir menú de navegación">
        <i class="fa-solid fa-bars fs-3" style="color: var(--primary-emerald);"></i>
      </button>

      <div class="collapse navbar-collapse" id="navbarContenido">
        <?php if ($paginaActual === 'index.php'): ?>
          <ul class="navbar-nav ms-auto align-items-xl-center gap-1 my-3 my-xl-0">
            <li class="nav-item"><a class="nav-link-agro active" href="#inicio">Inicio</a></li>
            <li class="nav-item"><a class="nav-link-agro" href="#quienes-somos">Conócenos</a></li>
            <li class="nav-item"><a class="nav-link-agro" href="#areas">Nuestras Áreas</a></li>
            <li class="nav-item"><a class="nav-link-agro" href="#precio">Precio en Quincena</a></li>
            <li class="nav-item"><a class="nav-link-agro" href="#infraestructura">Infraestructura</a></li>
            <li class="nav-item"><a class="nav-link-agro" href="#galeria">Galería</a></li>
            <li class="nav-item"><a class="nav-link-agro" href="#contacto">Contacto</a></li>

            <li class="nav-item d-flex align-items-center gap-2 ms-xl-3 mt-3 mt-xl-0">
              <?php if ($usuarioSesion): ?>
                <?php $urlDestino = ($usuarioSesion['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
                <a href="<?php echo $urlDestino; ?>" class="btn-nav-portal">
                  <i class="fa-solid fa-gauge-high"></i>
                  <span>Mi Panel</span>
                </a>
              <?php else: ?>
                <a href="registro.php" class="btn-nav-register">
                  <i class="fa-solid fa-user-plus me-1"></i> Registrarse
                </a>
                <a href="login.php" class="btn-nav-portal">
                  <i class="fa-solid fa-right-to-bracket"></i>
                  <span>Portal Asociados</span>
                </a>
              <?php endif; ?>
            </li>
          </ul>
        <?php else: ?>
          <!-- Navegación para Módulos de Sesión / Internos -->
          <ul class="navbar-nav ms-auto align-items-center gap-2 my-2 my-lg-0">
            <?php if ($usuarioSesion): ?>
              <li class="nav-item d-none d-md-block text-end me-2">
                <span class="badge px-3 py-1 rounded-pill fw-bold" style="background-color: var(--bg-surface-alt); color: var(--primary-emerald); border: 1px solid var(--border-subtle);">
                  Rol: <?php echo e($usuarioSesion['rol']); ?>
                </span>
                <span class="fw-bold d-block text-dark mt-0.5" style="font-size: 0.92rem;"><?php echo e($usuarioSesion['nombre']); ?></span>
              </li>

              <li class="nav-item">
                <a href="index.php" class="btn btn-sm btn-outline-secondary px-3 py-2 rounded-3 fw-bold">
                  <i class="fa-solid fa-house me-1"></i> Inicio
                </a>
              </li>

              <?php if ($usuarioSesion['rol'] === 'ADMIN'): ?>
                <li class="nav-item">
                  <a href="admin.php" class="btn btn-sm text-white px-3 py-2 rounded-3 fw-bold" style="background-color: var(--primary-emerald);">
                    <i class="fa-solid fa-table me-1"></i> Planilla Admin
                  </a>
                </li>
              <?php else: ?>
                <li class="nav-item">
                  <a href="panel.php" class="btn btn-sm text-white px-3 py-2 rounded-3 fw-bold" style="background-color: var(--primary-emerald);">
                    <i class="fa-solid fa-chart-simple me-1"></i> Mis Pesajes
                  </a>
                </li>
              <?php endif; ?>

              <li class="nav-item">
                <a href="logout.php" class="btn btn-sm btn-outline-danger px-3 py-2 rounded-3 fw-bold" title="Cerrar Sesión Segura">
                  <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
                </a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a href="index.php" class="btn btn-sm btn-outline-secondary px-3 py-2 rounded-3 fw-bold">
                  <i class="fa-solid fa-house me-1"></i> Volver al Portal
                </a>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Enlace accesible para saltar al contenido -->
  <a href="#contenido-principal" class="visually-hidden-focusable p-3 bg-dark text-white fw-bold">Saltar al contenido principal</a>

  <!-- Mensajes Flash SweetAlert2 -->
  <?php if (function_exists('renderSweetAlert')): ?>
    <?php echo renderSweetAlert(); ?>
  <?php endif; ?>
