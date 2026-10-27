<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/header.php
 * Propósito: Encabezado compartido, meta tags, anti-FOUC y navegación responsive.
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
  <title><?php echo e($tituloPagina); ?></title>
  
  <meta name="description" content="Plataforma de Control Lechero y desarrollo asociativo de ASOAGROALIZAL en Carmen de Carupa, Cundinamarca. NIT <?php echo e(EMPRESA_NIT); ?>.">
  <meta name="author" content="<?php echo e(EMPRESA_NOMBRE); ?>">

  <!-- Script Anti-Parpadeo Inmediato (Anti-FOUC) -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('asoagro_theme');
      const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const theme = savedTheme ? savedTheme : (systemDark ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', theme);
    })();
  </script>

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

  <!-- Estilos del Sistema de Diseño Agri-Tech (Variables :root y Tema) -->
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- Navbar Flotante Glassmorphism -->
  <nav class="navbar navbar-expand-lg navbar-asoagro sticky-top py-2" aria-label="Navegación principal">
    <div class="container<?php echo (!empty($navbarFluido)) ? '-fluid px-3 px-md-4' : ''; ?>">
      <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
        <img src="assets/img/logo.png" alt="Logo Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="brand-logo-img">
        <div class="d-flex flex-column">
          <span class="fw-bolder fs-5 text-success brand-text-title lh-1"><?php echo e(EMPRESA_SIGLA); ?></span>
          <small class="text-muted fw-bold brand-text-sub" style="font-size: 0.70rem;">Control Lechero &bull; Carmen de Carupa</small>
        </div>
      </a>

      <!-- Botón Hamburguesa Accesible -->
      <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido" aria-controls="navbarContenido" aria-expanded="false" aria-label="Abrir menú de navegación">
        <i class="bi bi-list fs-2 text-success"></i>
      </button>

      <div class="collapse navbar-collapse" id="navbarContenido">
        <?php if ($paginaActual === 'index.php'): ?>
          <ul class="navbar-nav ms-auto align-items-lg-center gap-1 my-3 my-lg-0">
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#quienes-somos">Quiénes Somos</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#familia">Nuestra Familia</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#mision-vision">Misión & Visión</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#infraestructura">Infraestructura</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#enfoque-bpo">Calidad BPO</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#aliados">Aliados</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#contacto">Contacto</a></li>
            
            <li class="nav-item ms-lg-2">
              <button type="button" class="btn btn-touch btn-outline-success border-2 rounded-circle p-0 theme-toggle-btn" style="width: 44px; height: 44px;" aria-label="Alternar modo oscuro">
                <i class="bi bi-moon-stars-fill fs-5"></i>
              </button>
            </li>

            <li class="nav-item ms-lg-2">
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
                <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill fw-bold">
                  <?php echo e($usuarioSesion['rol']); ?>
                </span>
                <span class="fw-bold d-block text-success small"><?php echo e($usuarioSesion['nombre']); ?></span>
              </li>
            <?php endif; ?>

            <li class="nav-item">
              <button type="button" class="btn btn-touch btn-outline-success border-2 rounded-circle p-0 theme-toggle-btn" style="width: 42px; height: 42px;" aria-label="Alternar modo oscuro">
                <i class="bi bi-moon-stars-fill fs-5"></i>
              </button>
            </li>

            <?php if ($usuarioSesion): ?>
              <li class="nav-item">
                <a href="cambiar_password.php" class="btn btn-touch btn-outline-secondary border-2 rounded-pill px-3 py-1 small" title="Cambiar clave">
                  <i class="bi bi-key-fill text-warning"></i>
                  <span class="d-none d-lg-inline">Clave</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php" class="btn btn-touch btn-outline-success border-2 rounded-pill px-3 py-1 small" title="Sitio público">
                  <i class="bi bi-globe"></i>
                  <span class="d-none d-lg-inline">Sitio</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="logout.php" class="btn btn-touch btn-danger rounded-pill px-3 py-1 small fw-bold">
                  <i class="bi bi-box-arrow-right"></i>
                  <span>Salir</span>
                </a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a href="index.php" class="btn btn-touch btn-outline-success border-2 rounded-pill px-3 py-1 small">
                  <i class="bi bi-arrow-left"></i>
                  <span>Inicio</span>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </nav>

