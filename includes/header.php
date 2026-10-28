<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/header.php
 * Propósito: Encabezado institucional accesible para adultos mayores rurales:
 *            - Forzado exclusivo de modo claro (<meta name="color-scheme" content="light">).
 *            - Control de tamaño de letra fijo (A, A+, A++).
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

  <!-- Estilos del Sistema de Diseño Premium Accesible (Azul Marino / Crema / Atkinson) -->
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body class="d-flex flex-column min-vh-100 auth-bg">

  <!-- 1. BARRA SUPERIOR FIJA DE ACCESIBILIDAD (CONTROL DE TAMAÑO DE LETRA: A  A+  A++) -->
  <aside class="top-accessibility-bar" aria-label="Controles de accesibilidad">
    <div class="container<?php echo (!empty($navbarFluido)) ? '-fluid px-3 px-md-4' : ''; ?> d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="small fw-bold text-dark d-flex align-items-center gap-2">
        <i class="bi bi-shield-check text-primary"></i>
        <span>Sede Oficial: Vereda Alizal &bull; Carmen de Carupa &bull; Tel: <?php echo e(EMPRESA_TELEFONO); ?></span>
      </div>

      <div class="d-flex align-items-center gap-2 ms-auto">
        <span class="small fw-bold text-dark me-1">Tamaño de letra:</span>
        <div class="font-size-controls" role="group" aria-label="Ajuste de tamaño de fuente">
          <button type="button" class="btn-font-size active" data-size="normal" title="Tamaño normal (20px)" aria-pressed="true">A</button>
          <button type="button" class="btn-font-size" data-size="large" title="Tamaño grande (23px)" aria-pressed="false">A+</button>
          <button type="button" class="btn-font-size" data-size="xlarge" title="Tamaño muy grande (26px)" aria-pressed="false">A++</button>
        </div>
      </div>
    </div>
  </aside>

  <!-- 2. NAVBAR INSTITUCIONAL SÓLIDO (FONDO BLANCO SÓLIDO, ALTO CONTRASTE) -->
  <nav class="navbar navbar-expand-lg navbar-asoagro sticky-top" aria-label="Navegación principal">
    <div class="container<?php echo (!empty($navbarFluido)) ? '-fluid px-3 px-md-4' : ''; ?>">
      <a class="navbar-brand d-flex align-items-center gap-3 text-decoration-none" href="index.php">
        <img src="assets/img/logo.png" alt="Logo Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="brand-logo-img">
        <div class="d-flex flex-column">
          <span class="brand-text-title lh-1"><?php echo e(EMPRESA_SIGLA); ?></span>
          <small class="brand-text-sub">Plataforma de Control Lechero</small>
        </div>
      </a>

      <!-- Botón Menú Móvil Grande -->
      <button class="navbar-toggler border-2 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido" aria-controls="navbarContenido" aria-expanded="false" aria-label="Abrir menú de navegación" style="min-height: 48px; border-color: var(--primary);">
        <i class="bi bi-list fs-2" style="color: var(--primary);"></i>
      </button>

      <div class="collapse navbar-collapse" id="navbarContenido">
        <?php if ($paginaActual === 'index.php'): ?>
          <ul class="navbar-nav ms-auto align-items-lg-center gap-1 my-3 my-lg-0">
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#quienes-somos">Quiénes Somos</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#mision-vision">Misión & Visión</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#territorio-comunidad">Nuestra Tierra</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#infraestructura">Infraestructura</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#enfoque-bpo">Calidad BPO</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#contacto">Contacto</a></li>

            <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
              <?php if ($usuarioSesion): ?>
                <?php $urlDestino = ($usuarioSesion['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
                <a href="<?php echo $urlDestino; ?>" class="btn btn-touch btn-aso-accent">
                  <i class="bi bi-speedometer2"></i>
                  <span>Mi Panel</span>
                </a>
              <?php else: ?>
                <a href="login.php" class="btn btn-touch btn-aso-primary">
                  <i class="bi bi-box-arrow-in-right"></i>
                  <span>Acceso al Sistema</span>
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
            <?php endif; ?>

            <?php if ($usuarioSesion): ?>
              <li class="nav-item">
                <a href="cambiar_password.php" class="btn btn-touch btn-outline-primary" title="Cambiar clave">
                  <i class="bi bi-key-fill"></i>
                  <span>Cambiar Clave</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php" class="btn btn-touch btn-outline-primary" title="Sitio público">
                  <i class="bi bi-globe"></i>
                  <span>Inicio</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="logout.php" class="btn btn-touch btn-danger" style="background-color: var(--danger); border-color: var(--danger); color: #ffffff;">
                  <i class="bi bi-box-arrow-right"></i>
                  <span>Cerrar Sesión</span>
                </a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a href="index.php" class="btn btn-touch btn-outline-primary">
                  <i class="bi bi-arrow-left"></i>
                  <span>Volver al Inicio</span>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </nav>
