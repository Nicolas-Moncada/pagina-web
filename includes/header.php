<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/header.php
 * Propósito: Encabezado institucional accesible para adultos mayores rurales:
 *            - Forzado exclusivo de modo claro (<meta name="color-scheme" content="light">).
 *            - Navegación responsive sólida, de alto contraste y compatible cross-browser.
 * =========================================================================================
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$usuarioSesion = getCurrentUser();
$paginaActual = basename($_SERVER['SCRIPT_NAME'] ?? '');
$tituloPagina = $tituloPagina ?? (EMPRESA_SIGLA . ' | Control Lechero');
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

  <!-- Favicon Oficial -->
  <link rel="icon" type="image/png" href="assets/img/logo.png">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Bootstrap Icons 1.11.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- DataTables CSS (si está habilitado en la vista) -->
  <?php if (!empty($usarDataTables)): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
  <?php endif; ?>

  <!-- Estilos del Sistema de Diseño Premium Accesible (Azul Marino / Crema / Oro) -->
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body class="d-flex flex-column min-vh-100 auth-bg">

  <!-- 1. BARRA SUPERIOR INSTITUCIONAL (DATOS OFICIALES, SIN SELECTOR DE TAMAÑO) -->
  <aside class="top-accessibility-bar py-2" aria-label="Información institucional superior">
    <div class="container-fluid px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="small fw-bold text-dark d-flex align-items-center gap-2">
        <i class="bi bi-geo-alt-fill" style="color: var(--accent);"></i>
        <span>Sede Oficial: Vereda Alizal &bull; Carmen de Carupa &bull; Tel: <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="text-dark text-decoration-none fw-bold"><?php echo e(EMPRESA_TELEFONO); ?></a></span>
      </div>

      <div class="d-none d-md-flex align-items-center gap-2 ms-auto small text-muted fw-bold">
        <i class="bi bi-shield-check text-primary"></i>
        <span>Control Lechero Quincenal &bull; 32 Familias Asociadas</span>
      </div>
    </div>
  </aside>

  <!-- 2. NAVBAR INSTITUCIONAL SÓLIDO (FONDO BLANCO SÓLIDO, ALTO CONTRASTE) -->
  <nav class="navbar navbar-expand-xl navbar-asoagro sticky-top" aria-label="Navegación principal">
    <div class="container-fluid px-3 px-md-4">
      <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="index.php">
        <img src="assets/img/logo.png" alt="Logo Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="brand-logo-img">
        <div class="d-flex flex-column">
          <span class="brand-text-title lh-1"><?php echo e(EMPRESA_SIGLA); ?></span>
          <small class="brand-text-sub text-uppercase" style="letter-spacing: 0.05em; font-size: 0.68rem;">Control Lechero</small>
        </div>
      </a>

      <!-- Botón Menú Móvil Grande -->
      <button class="navbar-toggler border-2 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido" aria-controls="navbarContenido" aria-expanded="false" aria-label="Abrir menú de navegación" style="min-height: 48px; border-color: var(--primary);">
        <i class="bi bi-list fs-2" style="color: var(--primary);"></i>
      </button>

      <div class="collapse navbar-collapse" id="navbarContenido">
        <?php if ($paginaActual === 'index.php'): ?>
          <ul class="navbar-nav ms-auto align-items-xl-center gap-1 my-3 my-xl-0">
            <li class="nav-item"><a class="nav-link nav-link-custom active" href="#inicio">Inicio</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#quienes-somos">Quiénes Somos</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#como-funciona">Cómo Funciona</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#infraestructura">Infraestructura</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#mision-vision">Misión &amp; Visión</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#para-el-ganadero">Para el Ganadero</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#preguntas">Preguntas</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#contacto">Contacto</a></li>

            <li class="nav-item ms-xl-2 mt-2 mt-xl-0">
              <?php if ($usuarioSesion): ?>
                <?php $urlDestino = ($usuarioSesion['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
                <a href="<?php echo $urlDestino; ?>" class="btn btn-touch btn-aso-accent btn-header-login px-3 py-2">
                  <i class="bi bi-speedometer2"></i>
                  <span>Mi Panel</span>
                </a>
              <?php else: ?>
                <a href="login.php" class="btn btn-touch btn-aso-primary btn-header-login px-3 py-2">
                  <i class="bi bi-box-arrow-in-right"></i>
                  <span>Ingresar</span>
                </a>
              <?php endif; ?>
            </li>
          </ul>
        <?php else: ?>
          <!-- Navegación para Módulos de Sesión / Internos -->
          <ul class="navbar-nav ms-auto align-items-center gap-2 my-2 my-lg-0">
            <?php if ($usuarioSesion): ?>
              <li class="nav-item d-none d-md-block text-end me-2">
                <span class="badge px-3 py-1.5 rounded-pill fw-bold" style="background-color: var(--surface-2); color: var(--primary); border: 1.5px solid var(--border);">
                  Rol: <?php echo e($usuarioSesion['rol']); ?>
                </span>
                <span class="fw-bold d-block text-dark mt-1" style="font-size: 0.95rem;"><?php echo e($usuarioSesion['nombre']); ?></span>
              </li>

              <li class="nav-item">
                <a href="index.php" class="btn btn-touch btn-outline-primary py-2 px-3">
                  <i class="bi bi-house-door-fill"></i>
                  <span class="d-none d-sm-inline">Inicio</span>
                </a>
              </li>

              <?php if ($usuarioSesion['rol'] === 'ADMIN'): ?>
                <li class="nav-item">
                  <a href="admin.php" class="btn btn-touch btn-aso-primary py-2 px-3">
                    <i class="bi bi-table"></i>
                    <span>Planilla Admin</span>
                  </a>
                </li>
              <?php else: ?>
                <li class="nav-item">
                  <a href="panel.php" class="btn btn-touch btn-aso-primary py-2 px-3">
                    <i class="bi bi-speedometer2"></i>
                    <span>Mis Pesajes</span>
                  </a>
                </li>
              <?php endif; ?>

              <li class="nav-item">
                <a href="logout.php" class="btn btn-touch btn-outline-danger py-2 px-3" title="Cerrar Sesión Segura">
                  <i class="bi bi-box-arrow-right"></i>
                  <span class="d-none d-sm-inline">Salir</span>
                </a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a href="index.php" class="btn btn-touch btn-outline-primary py-2 px-3">
                  <i class="bi bi-house-door-fill"></i>
                  <span>Volver al Inicio</span>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Enlace de accesibilidad para saltar al contenido principal -->
  <a href="#contenido-principal" class="visually-hidden-focusable p-3 bg-dark text-white fw-bold">Saltar al contenido principal</a>

  <!-- Contenedor del Mensaje Flash Global si existe -->
  <?php if (function_exists('renderSweetAlert')): ?>
    <?php echo renderSweetAlert(); ?>
  <?php endif; ?>
