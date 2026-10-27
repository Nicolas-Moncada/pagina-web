<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOCIACIÓN DE GANADEROS (ASOAGROALIZAL)
 * Archivo: index.php (Landing Page Agri-Tech & Corporativa Premium)
 * Entorno: XAMPP (Apache + PHP 8 + MySQL)
 * 
 * PUENTES CLAVE CON EL BACKEND (PHP):
 * 1. require_once api/db.php: Inicialización de PDO, seguridad y control de sesiones.
 * 2. getCurrentUser(): Detección de sesión activa (ADMIN o GANADERO) para adaptar
 *    la barra de navegación dinámicamente.
 * 3. api/consultas.php: Endpoint receptor por método POST para consultas y mensajes.
 * 4. date('Y'): Copyright automatizado con el año en curso.
 * =========================================================================================
 */

// [BACKEND PUENTE 1] Conexión a la base de datos y utilidades de sesión segura
require_once __DIR__ . '/api/db.php';

// [BACKEND PUENTE 2] Consulta de usuario en sesión
$usuarioActual = getCurrentUser();

// Datos Corporativos Oficiales y Legales
$razonSocial = "ASOCIACION CAMPESINA NO NACIONAL DE TRABAJADORES DE LA VEREDA ALIZAL MUNICIPIO DE CARMEN DE CARUPA DEPARTAMENTO DE CUNDINAMARCA";
$sigla = "ASOAGROALIZAL";
$nit = "832006127-2";
$ubicacion = "Vereda Alizal Finca El Recuerdo, Carmen De Carupa, Cundinamarca";
$telefono = "3143820926";
$correo = "asoagroalizal@gmail.com";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $sigla; ?> | Plataforma de Control Lechero e Innovación Agropecuaria</title>
  
  <meta name="description" content="Plataforma tecnológica de control lechero, buenas prácticas de ordeño (BPO), tanques de enfriamiento y desarrollo sostenible de ASOAGROALIZAL en Carmen de Carupa, Cundinamarca.">
  <meta name="author" content="<?php echo $sigla; ?>">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Tipografía Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

  <style>
    /* ==========================================================================
       SISTEMA DE DISEÑO AGRI-TECH PREMIUM (VARIABLES Y ESTILOS BASE)
       ========================================================================== */
    :root {
      --agri-dark: #06150b;
      --agri-forest: #0b2914;
      --agri-pine: #144722;
      --agri-emerald: #10b981;
      --agri-neon: #22c55e;
      --agri-gold: #f59e0b;
      --agri-gold-light: #fef3c7;
      --agri-cream: #fbfbf9;
      --agri-card-bg: rgba(255, 255, 255, 0.94);
      --agri-border: rgba(16, 185, 129, 0.16);
      --agri-shadow: 0 20px 45px -15px rgba(6, 21, 11, 0.10);
      --agri-shadow-hover: 0 30px 60px -15px rgba(16, 185, 129, 0.22);
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--agri-cream);
      color: #1a2e22;
      overflow-x: hidden;
      scroll-behavior: smooth;
    }

    /* ==========================================================================
       NAVBAR FLOTANTE CON GLASSMORFISM
       ========================================================================== */
    .navbar-glass-wrapper {
      position: fixed;
      top: 16px;
      left: 0;
      right: 0;
      z-index: 1040;
      pointer-events: none;
    }

    .navbar-glass {
      pointer-events: auto;
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255, 255, 255, 0.8);
      border-radius: 24px;
      box-shadow: 0 10px 30px rgba(6, 21, 11, 0.08);
      padding: 0.65rem 1.4rem;
      transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .navbar-glass.scrolled {
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 14px 40px rgba(6, 21, 11, 0.12);
      transform: translateY(-4px);
    }

    .brand-emblem {
      width: 44px;
      height: 44px;
      background: linear-gradient(135deg, #0b2914 0%, #10b981 100%);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-size: 1.35rem;
      box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
    }

    .nav-link-custom {
      font-weight: 600;
      font-size: 0.92rem;
      color: #2c4234 !important;
      padding: 0.5rem 0.9rem !important;
      border-radius: 12px;
      transition: all 0.25s ease;
    }

    .nav-link-custom:hover {
      color: #10b981 !important;
      background: rgba(16, 185, 129, 0.08);
    }

    .btn-agri-glow {
      background: linear-gradient(135deg, #0b2914 0%, #144722 60%, #10b981 100%);
      color: #ffffff !important;
      font-weight: 700;
      font-size: 0.9rem;
      padding: 0.65rem 1.5rem;
      border-radius: 16px;
      border: 1px solid rgba(16, 185, 129, 0.4);
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.32);
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
      overflow: hidden;
    }

    .btn-agri-glow:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 32px rgba(16, 185, 129, 0.45);
      border-color: #34d399;
    }

    /* ==========================================================================
       HERO SECTION AGRI-TECH (SUPERPOSICIÓN & LAYOUT DIVIDIDO)
       ========================================================================== */
    .hero-agri {
      position: relative;
      padding-top: 150px;
      padding-bottom: 90px;
      background: radial-gradient(circle at 85% 20%, rgba(16, 185, 129, 0.12) 0%, transparent 50%),
                  radial-gradient(circle at 10% 80%, rgba(245, 158, 11, 0.08) 0%, transparent 45%),
                  linear-gradient(180deg, #f4f9f5 0%, var(--agri-cream) 100%);
      overflow: hidden;
    }

    .pill-status {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      padding: 0.4rem 1.1rem;
      background: rgba(16, 185, 129, 0.10);
      border: 1px solid rgba(16, 185, 129, 0.25);
      border-radius: 50rem;
      font-size: 0.82rem;
      font-weight: 700;
      color: #0b2914;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .pulsing-dot {
      width: 9px;
      height: 9px;
      background-color: var(--agri-neon);
      border-radius: 50%;
      box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
      animation: pulseGreen 2s infinite;
    }

    @keyframes pulseGreen {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    .hero-headline {
      font-size: clamp(2.6rem, 5.2vw, 4.2rem);
      font-weight: 800;
      line-height: 1.12;
      color: var(--agri-dark);
      letter-spacing: -0.03em;
    }

    .text-gradient-emerald {
      background: linear-gradient(135deg, #0b2914 20%, #10b981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* Composición Visual Superpuesta (Overlap Graphics) */
    .hero-composition {
      position: relative;
      min-height: 520px;
    }

    .hero-main-img-card {
      position: relative;
      width: 92%;
      height: 480px;
      border-radius: 36px;
      overflow: hidden;
      box-shadow: 0 30px 60px rgba(6, 21, 11, 0.18);
      border: 6px solid #ffffff;
    }

    .hero-main-img-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transform: scale(1.02);
      transition: transform 0.8s ease;
    }

    .hero-main-img-card:hover img {
      transform: scale(1.06);
    }

    /* Chips flotantes superpuestos */
    .hero-float-chip {
      position: absolute;
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-radius: 20px;
      padding: 1rem 1.3rem;
      box-shadow: 0 18px 38px rgba(6, 21, 11, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.9);
      z-index: 10;
      transition: transform 0.3s ease;
    }

    .hero-float-chip:hover {
      transform: translateY(-4px);
    }

    .chip-left {
      bottom: 25px;
      left: -15px;
    }

    .chip-right {
      top: 30px;
      right: 0;
    }

    /* ==========================================================================
       SECCIÓN: NUESTRA IDENTIDAD (TARJETAS ASIMÉTRICAS & 32 ASOCIADOS)
       ========================================================================== */
    .section-identidad {
      padding: 100px 0;
      background: #ffffff;
      position: relative;
    }

    .card-identidad-asimetrica {
      border-radius: 30px;
      padding: 2.8rem 2.4rem;
      border: 1px solid rgba(16, 185, 129, 0.14);
      background: #ffffff;
      box-shadow: var(--agri-shadow);
      position: relative;
      height: 100%;
      transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .card-identidad-asimetrica:hover {
      transform: translateY(-8px);
      box-shadow: var(--agri-shadow-hover);
      border-color: rgba(16, 185, 129, 0.35);
    }

    .card-identidad-mision {
      background: linear-gradient(180deg, #f7fbf8 0%, #ffffff 100%);
      border-top: 5px solid #10b981;
    }

    .card-identidad-vision {
      background: linear-gradient(180deg, #fefcf7 0%, #ffffff 100%);
      border-top: 5px solid var(--agri-gold);
    }

    .icon-badge-box {
      width: 68px;
      height: 68px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin-bottom: 1.8rem;
    }

    .box-emerald {
      background: rgba(16, 185, 129, 0.12);
      color: #10b981;
    }

    .box-gold {
      background: rgba(245, 158, 11, 0.12);
      color: var(--agri-gold);
    }

    /* Tarjeta Monumental: 32 Asociados */
    .banner-asociados-monumental {
      background: radial-gradient(circle at 100% 0%, rgba(16, 185, 129, 0.22) 0%, transparent 60%),
                  linear-gradient(135deg, #07170c 0%, #0d3018 100%);
      color: #ffffff;
      border-radius: 36px;
      padding: 3.5rem 3rem;
      box-shadow: 0 25px 60px rgba(6, 21, 11, 0.25);
      position: relative;
      overflow: hidden;
    }

    /* ==========================================================================
       SECCIÓN: INFRAESTRUCTURA Y BPO (BENTO BOX GRID MODERNO)
       ========================================================================== */
    .section-bento {
      padding: 110px 0;
      background: var(--agri-dark);
      color: #ffffff;
      position: relative;
    }

    .bento-card {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 30px;
      padding: 2.5rem;
      height: 100%;
      position: relative;
      overflow: hidden;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .bento-card:hover {
      background: rgba(255, 255, 255, 0.07);
      border-color: rgba(16, 185, 129, 0.4);
      transform: translateY(-6px);
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
    }

    .bento-tag {
      font-size: 0.75rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      padding: 0.35rem 0.9rem;
      border-radius: 50rem;
      display: inline-block;
      margin-bottom: 1.2rem;
    }

    .bento-tag-green { background: rgba(34, 197, 94, 0.16); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
    .bento-tag-blue { background: rgba(56, 189, 248, 0.16); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
    .bento-tag-gold { background: rgba(251, 191, 36, 0.16); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }

    .bento-metric {
      font-size: 2.8rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1;
      letter-spacing: -0.02em;
    }

    /* ==========================================================================
       SECCIÓN: GALERÍA VISUAL AGRI-TECH
       ========================================================================== */
    .section-galeria {
      padding: 100px 0;
      background: #f8faf8;
    }

    .galeria-bento-item {
      position: relative;
      border-radius: 26px;
      overflow: hidden;
      height: 300px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.07);
    }

    .galeria-bento-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .galeria-bento-item:hover img {
      transform: scale(1.08);
    }

    .galeria-caption {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, transparent 40%, rgba(6, 21, 11, 0.90) 100%);
      color: #ffffff;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 1.5rem;
    }

    /* ==========================================================================
       SECCIÓN CONTACTO (SPLIT SCREEN CON FLOATING LABELS)
       ========================================================================== */
    .section-contacto {
      padding: 110px 0;
      background: #ffffff;
    }

    .split-contacto-box {
      border-radius: 36px;
      background: #ffffff;
      border: 1px solid rgba(16, 185, 129, 0.15);
      box-shadow: 0 25px 60px rgba(6, 21, 11, 0.06);
      overflow: hidden;
    }

    .form-floating > .form-control {
      border-radius: 16px;
      border: 1.5px solid #dbe6de;
      padding: 1.1rem 1rem;
      background-color: #fafdfa;
      font-weight: 500;
      transition: all 0.25s ease;
    }

    .form-floating > .form-control:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.16);
      background-color: #ffffff;
    }

    .contacto-dark-panel {
      background: linear-gradient(145deg, #07170c 0%, #0d2c16 100%);
      color: #ffffff;
      padding: 3.5rem 3rem;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .contacto-item-row {
      display: flex;
      gap: 1.2rem;
      align-items: flex-start;
      margin-bottom: 2rem;
    }

    .contacto-icon-pill {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
      flex-shrink: 0;
      border: 1px solid rgba(16, 185, 129, 0.25);
    }

    /* ==========================================================================
       FOOTER LEGAL ULTRA-PROFESIONAL
       ========================================================================== */
    .footer-ultra {
      background-color: #030a05;
      color: #94a398;
      font-size: 0.92rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      padding: 80px 0 40px 0;
    }

    .footer-ultra h6 {
      color: #ffffff;
      font-size: 0.82rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      margin-bottom: 1.4rem;
    }

    .footer-ultra a {
      color: #a7bcad;
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .footer-ultra a:hover {
      color: #34d399;
    }
  </style>
</head>
<body>

  <!-- ===================================================================================== -->
  <!-- 1. NAVBAR FLOTANTE CON EFECTO GLASSMORFISM                                            -->
  <!-- ===================================================================================== -->
  <div class="navbar-glass-wrapper">
    <div class="container">
      <nav class="navbar navbar-expand-lg navbar-glass" id="mainNavbar">
        <!-- Logotipo / Emblema Agri-Tech -->
        <a class="navbar-brand d-flex align-items-center gap-3 p-0" href="./index.php">
          <div class="brand-emblem">
            <i class="bi bi-droplet-half"></i>
          </div>
          <div>
            <span class="d-block fw-bold lh-1 text-dark" style="font-size: 1.25rem; letter-spacing: -0.02em;">
              <?php echo $sigla; ?>
            </span>
            <small class="text-muted fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.08em; text-transform: uppercase;">
              Control Lechero · Carmen de Carupa
            </small>
          </div>
        </a>

        <!-- Botón Menú Móvil -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir Menú">
          <i class="bi bi-list fs-2 text-dark"></i>
        </button>

        <!-- Enlaces Ancla -->
        <div class="collapse navbar-collapse" id="menuPrincipal">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-1 mb-3 mb-lg-0">
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#inicio">Inicio</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#identidad">Nuestra Identidad</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#infraestructura">Infraestructura & BPO</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#galeria">Galería</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom" href="#contacto">Contacto</a></li>
          </ul>

          <!-- [BACKEND PUENTE] Botón de Acción según sesión activa de PHP -->
          <div class="d-flex align-items-center gap-2 ms-lg-3">
            <?php if ($usuarioActual): ?>
              <!-- Usuario con sesión: Acceso a panel de control -->
              <a href="<?php echo ($usuarioActual['rol'] === 'ADMIN') ? './admin.php' : './panel.php'; ?>" class="btn btn-agri-glow d-flex align-items-center gap-2">
                <i class="bi bi-person-check-fill"></i>
                <span>Mi Panel (<?php echo htmlspecialchars($usuarioActual['nombre']); ?>)</span>
              </a>
              <a href="./logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-2 fw-bold" title="Cerrar Sesión">
                <i class="bi bi-box-arrow-right"></i>
              </a>
            <?php else: ?>
              <!-- Botón Requerido de Alto Contraste con Glow -->
              <a href="./login.php" class="btn btn-agri-glow d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Acceso al Sistema</span>
                <i class="bi bi-arrow-right-short fs-5"></i>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </nav>
    </div>
  </div>

  <main>
    <!-- ===================================================================================== -->
    <!-- 2. HERO SECTION IMPACTANTE (LAYOUT DIVIDIDO ASIMÉTRICO & OVERLAPPING CHIPS)           -->
    <!-- ===================================================================================== -->
    <section id="inicio" class="hero-agri">
      <div class="container">
        <div class="row align-items-center g-5">
          <!-- Columna Izquierda: Mensaje Persuasivo Bold -->
          <div class="col-lg-6">
            <div class="pill-status mb-4">
              <span class="pulsing-dot"></span>
              <span>Plataforma Oficial de Control y Liquidación</span>
            </div>

            <h1 class="hero-headline mb-4">
              Revolución Campesina: <span class="text-gradient-emerald">Transparencia, Frío y Calidad</span> en Cada Pesaje.
            </h1>

            <p class="lead text-muted mb-4 pe-lg-3" style="line-height: 1.7; font-size: 1.15rem;">
              En <strong><?php echo $sigla; ?></strong> transformamos el esfuerzo de las madrugadas en la vereda Alizal. Monitoreamos cada turno (Mañana y Tarde), garantizamos refrigeración rápida a 4°C y liquidamos quincenas con exactitud milimétrica para el bienestar de nuestras familias.
            </p>

            <!-- CTA Dual Requerido -->
            <div class="d-flex flex-column flex-sm-row gap-3 mb-5">
              <a href="./login.php" class="btn btn-agri-glow btn-lg px-4 py-3 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-fingerprint"></i>
                <span>Ingresar al Portal de Asociados</span>
              </a>
              <a href="#identidad" class="btn btn-outline-dark btn-lg px-4 py-3 rounded-4 fw-bold border-2 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-compass"></i>
                <span>Explorar la Asociación</span>
              </a>
            </div>

            <!-- Métricas Rápidas -->
            <div class="row g-3 pt-3 border-top border-2">
              <div class="col-4">
                <div class="fw-bold fs-2 text-dark lh-1">32</div>
                <small class="text-muted fw-semibold">Asociados Activos</small>
              </div>
              <div class="col-4 border-start border-end">
                <div class="fw-bold fs-2 text-success lh-1">4°C</div>
                <small class="text-muted fw-semibold">Cadena de Frío</small>
              </div>
              <div class="col-4">
                <div class="fw-bold fs-2 text-dark lh-1">2 Turnos</div>
                <small class="text-muted fw-semibold">Mañana & Tarde</small>
              </div>
            </div>
          </div>

          <!-- Columna Derecha: Superposición Asimétrica de Tarjetas e Imágenes -->
          <div class="col-lg-6">
            <div class="hero-composition">
              <!-- Tarjeta de Imagen Principal -->
              <div class="hero-main-img-card ms-auto">
                <img src="https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=1000&q=80" 
                     alt="Ganado lechero y praderas en Carmen de Carupa, Cundinamarca">
              </div>

              <!-- Chip Flotante Superior: BPO y Calidad -->
              <div class="hero-float-chip chip-right">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-3 bg-success text-white p-2 d-flex align-items-center justify-content-center fs-4" style="width: 48px; height: 48px;">
                    <i class="bi bi-shield-fill-check"></i>
                  </div>
                  <div>
                    <h6 class="mb-0 fw-bold text-dark">Buenas Prácticas (BPO)</h6>
                    <small class="text-muted">Ordeño higiénico certificado</small>
                  </div>
                </div>
              </div>

              <!-- Chip Flotante Inferior: 32 Familias Campesinas -->
              <div class="hero-float-chip chip-left">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-3 bg-warning text-dark p-2 d-flex align-items-center justify-content-center fs-4" style="width: 48px; height: 48px;">
                    <i class="bi bi-people-fill"></i>
                  </div>
                  <div>
                    <h6 class="mb-0 fw-bold text-dark">32 Productores Comprometidos</h6>
                    <small class="text-muted">Vereda Alizal · Finca El Recuerdo</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================================================================================== -->
    <!-- 3. SECCIÓN: NUESTRA IDENTIDAD (MISIÓN, VISIÓN 2030 Y 32 ASOCIADOS)                    -->
    <!-- ===================================================================================== -->
    <section id="identidad" class="section-identidad">
      <div class="container">
        <div class="row justify-content-center text-center mb-5">
          <div class="col-lg-8">
            <span class="pill-status mb-3">ADN Asociativo</span>
            <h2 class="display-5 fw-bold text-dark mb-3">Nuestra Identidad y Visión de Futuro</h2>
            <p class="lead text-muted">
              Unidos bajo el principio de solidaridad campesina para generar progreso duradero en las tierras fértiles de Carmen de Carupa.
            </p>
          </div>
        </div>

        <!-- Tarjetas Asimétricas Misión & Visión -->
        <div class="row g-4 align-items-stretch mb-5">
          <!-- Tarjeta Misión -->
          <div class="col-md-6">
            <div class="card-identidad-asimetrica card-identidad-mision">
              <div class="icon-badge-box box-emerald">
                <i class="bi bi-bullseye"></i>
              </div>
              <h3 class="h3 fw-bold text-dark mb-3">Nuestra Misión</h3>
              <p class="text-muted fs-5 mb-0" style="line-height: 1.7;">
                Unir esfuerzos para la <strong>producción sostenible de alimentos</strong> que permita el <strong>mejoramiento de la calidad de vida de las familias de la vereda de Alizal</strong>, protegiendo nuestro ecosistema y afianzando la dignidad del productor lácteo y agrícola.
              </p>
            </div>
          </div>

          <!-- Tarjeta Visión 2030 -->
          <div class="col-md-6">
            <div class="card-identidad-asimetrica card-identidad-vision">
              <div class="icon-badge-box box-gold">
                <i class="bi bi-rocket-takeoff-fill"></i>
              </div>
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="h3 fw-bold text-dark mb-0">Visión a 2030</h3>
                <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill">Horizonte Estratégico</span>
              </div>
              <p class="text-muted fs-5 mb-0" style="line-height: 1.7;">
                <strong>Transformar el 50% de nuestra producción láctea</strong>, dar <strong>valor agregado a la papa y tubérculos andinos</strong> propios de nuestra montaña, y <strong>expandirnos a nuevos mercados</strong> con productos diferenciados por su origen y pureza.
              </p>
            </div>
          </div>
        </div>

        <!-- Banner Monumental: El Factor Humano (32 Asociados Activos) -->
        <div class="banner-asociados-monumental">
          <div class="row align-items-center g-4">
            <div class="col-lg-8">
              <div class="d-flex align-items-center gap-3 mb-3">
                <span class="badge bg-success text-white fw-bold px-3 py-2 rounded-pill">Comunidad & Dignidad</span>
                <span class="text-white-50"><i class="bi bi-geo-alt-fill text-warning me-1"></i> Finca El Recuerdo</span>
              </div>
              <h3 class="display-6 fw-bold text-white mb-3">
                Una Familia de 32 Asociados Comprometidos con el Campo
              </h3>
              <p class="text-light opacity-75 fs-5 mb-0" style="line-height: 1.65;">
                No somos una corporación lejana: somos 32 familias campesinas de la vereda Alizal que decidieron asociarse para proteger el fruto de su trabajo, asegurar pesajes justos y garantizar el relevo generacional en la ganadería lechera colombiana.
              </p>
            </div>
            <div class="col-lg-4 text-lg-end">
              <div class="p-4 rounded-4 text-center" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15);">
                <div class="display-4 fw-bold text-warning mb-0">100%</div>
                <div class="text-white fw-bold">Compromiso Campesino</div>
                <small class="text-white-50 d-block mt-1">Transparencia y Cooperación</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================================================================================== -->
    <!-- 4. INFRAESTRUCTURA Y BPO (BENTO BOX GRID CREATIVO EN FONDO OSCURO)                     -->
    <!-- ===================================================================================== -->
    <section id="infraestructura" class="section-bento">
      <div class="container">
        <div class="row justify-content-center text-center mb-5">
          <div class="col-lg-8">
            <span class="bento-tag bento-tag-green">Tecnología de Vanguardia</span>
            <h2 class="display-5 fw-bold text-white mb-3">Infraestructura Propia y Buenas Prácticas</h2>
            <p class="text-white-50 lead">
              Integración de biotecnología, termografía y rigor higiénico para elevar los estándares del campo colombiano.
            </p>
          </div>
        </div>

        <!-- Layout Bento Box Grid -->
        <div class="row g-4">
          <!-- Bento 1: Tanques de Enfriamiento (Caja Grande 7 Columnas) -->
          <div class="col-lg-7">
            <div class="bento-card">
              <span class="bento-tag bento-tag-blue">Cadena de Frío Ininterrumpida</span>
              <div class="d-flex align-items-baseline gap-3 mb-2">
                <span class="bento-metric">4°C</span>
                <span class="text-white-50 fw-semibold">Refrigeración Inmediata</span>
              </div>
              <h4 class="fw-bold text-white mb-3">Tanques de Enfriamiento Tecnificados</h4>
              <p class="text-white-50 mb-4" style="line-height: 1.65;">
                Disponemos de tanques de acero inoxidable de enfriamiento rápido que reciben la leche inmediatamente culminado el ordeño. Esto inhibe la acidificación bacteriana y preserva intactas las propiedades composicionales de grasa y proteína demandadas por la industria pasteurizadora.
              </p>
              <div class="row g-3 pt-3 border-top border-white border-opacity-10 text-white-50 small">
                <div class="col-sm-6"><i class="bi bi-thermometer-snow text-info me-2"></i>Monitoreo digital térmico</div>
                <div class="col-sm-6"><i class="bi bi-shield-check text-info me-2"></i>Aislamiento de alto rendimiento</div>
              </div>
            </div>
          </div>

          <!-- Bento 2: Buenas Prácticas de Ordeño BPO (Caja 5 Columnas) -->
          <div class="col-lg-5">
            <div class="bento-card">
              <span class="bento-tag bento-tag-green">Rigor Higiénico</span>
              <div class="d-flex align-items-baseline gap-3 mb-2">
                <span class="bento-metric">BPO</span>
                <span class="text-white-50 fw-semibold">Doble Turno Diario</span>
              </div>
              <h4 class="fw-bold text-white mb-3">Buenas Prácticas de Ordeño</h4>
              <p class="text-white-50 mb-3" style="line-height: 1.65;">
                Capacitación continua en desinfección de pezones, pre y post-sellado, filtración mecánica y bienestar animal para garantizar leche Grado A en los turnos de Mañana (☀️) y Tarde (🌅).
              </p>
              <div class="p-3 rounded-3 mt-auto" style="background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.2);">
                <i class="bi bi-check-all text-success me-1"></i> Control de mastitis y acidez en cada lote
              </div>
            </div>
          </div>

          <!-- Bento 3: Laboratorio de Tejidos Vegetales (Caja 6 Columnas) -->
          <div class="col-lg-6">
            <div class="bento-card">
              <span class="bento-tag bento-tag-gold">Biotecnología Andina</span>
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-3 p-2 bg-warning bg-opacity-25 text-warning fs-3">
                  <i class="bi bi-flower3"></i>
                </div>
                <div>
                  <h4 class="fw-bold text-white mb-0">Laboratorio de Tejidos</h4>
                  <small class="text-warning">Propagación in-vitro y sanidad vegetal</small>
                </div>
              </div>
              <p class="text-white-50 mb-0" style="line-height: 1.65;">
                Producimos <strong>semillas limpias de papa y tubérculos andinos</strong> libres de virus y plagas. Nuestro laboratorio permite multiplicar variedades de alto rendimiento para asegurar cosechas abundantes en Carmen de Carupa.
              </p>
            </div>
          </div>

          <!-- Bento 4: Invernaderos Tecnificados (Caja 6 Columnas) -->
          <div class="col-lg-6">
            <div class="bento-card">
              <span class="bento-tag bento-tag-green">Protección y Clima</span>
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-3 p-2 bg-success bg-opacity-25 text-success fs-3">
                  <i class="bi bi-building-gear"></i>
                </div>
                <div>
                  <h4 class="fw-bold text-white mb-0">Invernaderos Tecnificados</h4>
                  <small class="text-success">Agricultura de precisión controlada</small>
                </div>
              </div>
              <p class="text-white-50 mb-0" style="line-height: 1.65;">
                Infraestructura moderna para la aclimatación de plántulas y producción protegida frente a heladas de páramo, fortaleciendo la seguridad alimentaria de nuestros 32 asociados y sus familias.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================================================================================== -->
    <!-- 5. GALERÍA VISUAL AGRI-TECH                                                           -->
    <!-- ===================================================================================== -->
    <section id="galeria" class="section-galeria">
      <div class="container">
        <div class="row justify-content-center text-center mb-5">
          <div class="col-lg-8">
            <span class="pill-status mb-3">Faenas del Día a Día</span>
            <h2 class="display-5 fw-bold text-dark mb-3">Nuestra Tierra, Ganado y Faenas</h2>
            <p class="lead text-muted">
              Postales auténticas de nuestras fincas en la vereda Alizal, donde la pasión por el campo se renueva cada mañana.
            </p>
          </div>
        </div>

        <div class="row g-4">
          <!-- Foto 1 -->
          <div class="col-sm-6 col-lg-3">
            <div class="galeria-bento-item">
              <img src="https://images.unsplash.com/photo-1546445317-29f4545e9d53?auto=format&fit=crop&w=700&q=80" 
                   alt="Ganado pastando libremente en las montañas de Carmen de Carupa">
              <div class="galeria-caption">
                <span class="badge bg-success mb-1 w-auto align-self-start">Pastoreo Libre</span>
                <h6 class="fw-bold mb-0">Pastos Nutritivos</h6>
                <small class="text-white-50">Bienestar y nutrición animal</small>
              </div>
            </div>
          </div>

          <!-- Foto 2 -->
          <div class="col-sm-6 col-lg-3">
            <div class="galeria-bento-item">
              <img src="https://images.unsplash.com/photo-1527153857715-3908f2ae5e81?auto=format&fit=crop&w=700&q=80" 
                   alt="Faena de ordeño limpio y riguroso en establo campesino">
              <div class="galeria-caption">
                <span class="badge bg-warning text-dark mb-1 w-auto align-self-start">Buenas Prácticas</span>
                <h6 class="fw-bold mb-0">Ordeño Higiénico</h6>
                <small class="text-white-50">Doble turno: Mañana y Tarde</small>
              </div>
            </div>
          </div>

          <!-- Foto 3 -->
          <div class="col-sm-6 col-lg-3">
            <div class="galeria-bento-item">
              <img src="https://images.unsplash.com/photo-1596733430284-f7437764b1a9?auto=format&fit=crop&w=700&q=80" 
                   alt="Tanques y canecas térmicas para el transporte seguro de leche">
              <div class="galeria-caption">
                <span class="badge bg-info text-dark mb-1 w-auto align-self-start">Cadena de Frío</span>
                <h6 class="fw-bold mb-0">Acopio en Tanques</h6>
                <small class="text-white-50">Temperatura constante a 4°C</small>
              </div>
            </div>
          </div>

          <!-- Foto 4 -->
          <div class="col-sm-6 col-lg-3">
            <div class="galeria-bento-item">
              <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=700&q=80" 
                   alt="Paisaje campesino de la Finca El Recuerdo, Carmen de Carupa">
              <div class="galeria-caption">
                <span class="badge bg-secondary mb-1 w-auto align-self-start">Finca El Recuerdo</span>
                <h6 class="fw-bold mb-0">Vereda Alizal</h6>
                <small class="text-white-50">Cuna de nuestra asociación</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================================================================================== -->
    <!-- 6. SECCIÓN CONTACTO (SPLIT SCREEN CON FLOATING LABELS & TARJETA CONTRASTE)           -->
    <!-- ===================================================================================== -->
    <section id="contacto" class="section-contacto">
      <div class="container">
        <div class="row justify-content-center text-center mb-5">
          <div class="col-lg-8">
            <span class="pill-status mb-3">Atención y Enlace</span>
            <h2 class="display-5 fw-bold text-dark mb-3">Comunícate con la Asociación</h2>
            <p class="lead text-muted">
              Estamos a tu disposición para solicitudes de afiliación, comercialización de leche o consultas a la Junta Directiva.
            </p>
          </div>
        </div>

        <div class="split-contacto-box">
          <div class="row g-0">
            <!-- Formulario a la Izquierda (Floating Labels de Bootstrap 5) -->
            <div class="col-lg-7 p-4 p-md-5">
              <h3 class="fw-bold text-dark mb-2">Envíanos un Mensaje</h3>
              <p class="text-muted mb-4 small">
                Tus datos están protegidos conforme a la Ley 1581 de 2012 de Protección de Datos Personales.
              </p>

              <!-- Alerta Dinámica AJAX -->
              <div id="contacto-alert" class="alert d-none shadow-sm rounded-4" role="alert"></div>

              <!-- [BACKEND PUENTE 3] Formulario con action a api/consultas.php -->
              <form id="form-contacto-publico" action="./api/consultas.php" method="POST">
                <!-- Nombre Completo (Floating Label) -->
                <div class="form-floating mb-3">
                  <input type="text" class="form-control" id="floatingNombre" name="nombre" placeholder="Nombre Completo" required>
                  <label for="floatingNombre"><i class="bi bi-person me-2"></i>Nombre Completo *</label>
                </div>

                <!-- Contacto (Teléfono o Correo) -->
                <div class="form-floating mb-3">
                  <input type="text" class="form-control" id="floatingContacto" name="contacto" placeholder="Teléfono o Correo" required>
                  <label for="floatingContacto"><i class="bi bi-envelope-at me-2"></i>Teléfono o Correo Electrónico *</label>
                </div>

                <!-- Mensaje -->
                <div class="form-floating mb-4">
                  <textarea class="form-control" placeholder="Mensaje" id="floatingMensaje" name="mensaje" style="height: 140px" required></textarea>
                  <label for="floatingMensaje"><i class="bi bi-chat-text me-2"></i>Cuéntanos tu inquietud o solicitud *</label>
                </div>

                <!-- Botón de Envío -->
                <button type="submit" id="btn-enviar-contacto" class="btn btn-agri-glow w-100 py-3 fs-6 d-flex align-items-center justify-content-center gap-2">
                  <i class="bi bi-send-fill"></i>
                  <span>Enviar Mensaje Directo a la Administración</span>
                </button>
              </form>
            </div>

            <!-- Información a la Derecha (Tarjeta de Alto Contraste) -->
            <div class="col-lg-5">
              <div class="contacto-dark-panel">
                <div>
                  <span class="bento-tag bento-tag-gold mb-3">Sede Oficial Campesina</span>
                  <h4 class="fw-bold text-white mb-4"><?php echo $sigla; ?></h4>

                  <!-- Fila 1: Dirección -->
                  <div class="contacto-item-row">
                    <div class="contacto-icon-pill">
                      <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold text-white mb-1">Ubicación</h6>
                      <p class="text-white-50 small mb-0"><?php echo $ubicacion; ?></p>
                    </div>
                  </div>

                  <!-- Fila 2: Teléfono -->
                  <div class="contacto-item-row">
                    <div class="contacto-icon-pill">
                      <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold text-white mb-1">Línea Móvil / WhatsApp</h6>
                      <p class="text-white-50 small mb-0">
                        <a href="tel:<?php echo $telefono; ?>" class="text-white text-decoration-none fw-bold">
                          +57 <?php echo $telefono; ?>
                        </a>
                      </p>
                    </div>
                  </div>

                  <!-- Fila 3: Correo -->
                  <div class="contacto-item-row">
                    <div class="contacto-icon-pill">
                      <i class="bi bi-envelope-fill"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold text-white mb-1">Correo Institucional</h6>
                      <p class="text-white-50 small mb-0">
                        <a href="mailto:<?php echo $correo; ?>" class="text-white text-decoration-none">
                          <?php echo $correo; ?>
                        </a>
                      </p>
                    </div>
                  </div>

                  <!-- Fila 4: Horarios de Acopio -->
                  <div class="contacto-item-row mb-0">
                    <div class="contacto-icon-pill">
                      <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold text-white mb-1">Horario de Acopio y Pesaje</h6>
                      <p class="text-white-50 small mb-0">
                        ☀️ Turno Mañana: 05:00 a.m. – 07:30 a.m.<br>
                        🌅 Turno Tarde: 03:30 p.m. – 05:30 p.m.
                      </p>
                    </div>
                  </div>
                </div>

                <!-- NIT Legal -->
                <div class="pt-4 mt-4 border-top border-white border-opacity-10 small text-white-50">
                  <i class="bi bi-shield-check text-success me-1"></i> Organización Campesina Registrada · NIT: <?php echo $nit; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ===================================================================================== -->
  <!-- 7. FOOTER LEGAL ULTRA-PROFESIONAL                                                     -->
  <!-- ===================================================================================== -->
  <footer class="footer-ultra">
    <div class="container">
      <div class="row g-4 justify-content-between mb-5">
        <!-- Razón Social y Misión -->
        <div class="col-lg-5">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="brand-emblem" style="width: 36px; height: 36px; font-size: 1.1rem;">
              <i class="bi bi-droplet-half"></i>
            </div>
            <h5 class="fw-bold text-white mb-0"><?php echo $sigla; ?></h5>
          </div>
          <p class="text-white-50 small mb-2" style="line-height: 1.6;">
            <strong>Razón Social Oficial:</strong><br>
            <?php echo $razonSocial; ?>
          </p>
          <p class="text-white-50 small mb-0">
            <strong>NIT:</strong> <?php echo $nit; ?>
          </p>
        </div>

        <!-- Navegación Rápida -->
        <div class="col-sm-6 col-lg-3">
          <h6>Navegación</h6>
          <ul class="list-unstyled d-flex flex-column gap-2 small">
            <li><a href="#inicio"><i class="bi bi-chevron-right me-1 text-success"></i>Inicio</a></li>
            <li><a href="#identidad"><i class="bi bi-chevron-right me-1 text-success"></i>Nuestra Identidad</a></li>
            <li><a href="#infraestructura"><i class="bi bi-chevron-right me-1 text-success"></i>Infraestructura & BPO</a></li>
            <li><a href="#galeria"><i class="bi bi-chevron-right me-1 text-success"></i>Galería</a></li>
            <li><a href="#contacto"><i class="bi bi-chevron-right me-1 text-success"></i>Contacto</a></li>
          </ul>
        </div>

        <!-- Acceso y Enlaces Legales Colombia -->
        <div class="col-sm-6 col-lg-4">
          <h6>Marco Legal y Accesos</h6>
          <ul class="list-unstyled d-flex flex-column gap-2 small mb-3">
            <li>
              <a href="./login.php" class="text-warning fw-bold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Portal de Asociados (Ingreso)
              </a>
            </li>
            <li>
              <a href="./registro.php">
                <i class="bi bi-person-plus me-1"></i> Afiliación de Nuevos Ganaderos
              </a>
            </li>
            <!-- Modales Legales Requeridos (Ley 1581 de 2012 y Términos) -->
            <li>
              <a href="#" data-bs-toggle="modal" data-bs-target="#modalHabeasData">
                <i class="bi bi-file-earmark-lock2 me-1 text-success"></i> Política de Tratamiento de Datos Personales (Ley 1581 de 2012)
              </a>
            </li>
            <li>
              <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos">
                <i class="bi bi-journal-check me-1 text-success"></i> Términos y Condiciones de Uso
              </a>
            </li>
          </ul>
          <small class="text-white-50 d-block">
            <i class="bi bi-geo-alt me-1 text-warning"></i> <?php echo $ubicacion; ?>
          </small>
        </div>
      </div>

      <!-- [BACKEND PUENTE 4] Barra de Copyright Dinámica en PHP -->
      <div class="pt-4 border-top border-secondary border-opacity-25 text-center text-white-50 small">
        <p class="mb-0">
          &copy; <?php echo date('Y'); ?> <strong><?php echo $sigla; ?></strong>. Todos los derechos reservados. 
          Diseñado con estándares Agri-Tech para los 32 asociados de la vereda Alizal, Carmen de Carupa, Cundinamarca.
        </p>
      </div>
    </div>
  </footer>

  <!-- ===================================================================================== -->
  <!-- MODAL: POLÍTICA DE TRATAMIENTO DE DATOS PERSONALES (LEY 1581 DE 2012)                 -->
  <!-- ===================================================================================== -->
  <div class="modal fade" id="modalHabeasData" tabindex="-1" aria-labelledby="modalHabeasDataLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content rounded-4 border-0">
        <div class="modal-header bg-success text-white py-3">
          <h5 class="modal-title fw-bold" id="modalHabeasDataLabel">
            <i class="bi bi-shield-lock-fill me-2"></i>Política de Tratamiento de Datos Personales (Ley 1581 de 2012)
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-dark small lh-lg p-4">
          <p class="fw-bold mb-2">Conforme a la Ley Estatutaria 1581 de 2012 y el Decreto Reglamentario 1377 de 2013 de la República de Colombia:</p>
          <p>
            <strong>1. Responsable del Tratamiento:</strong> La <em><?php echo $razonSocial; ?></em> (en adelante <strong><?php echo $sigla; ?></strong>), con NIT <?php echo $nit; ?>, con domicilio en <?php echo $ubicacion; ?>, es la entidad responsable del tratamiento de los datos personales suministrados por sus asociados, proveedores y visitantes.
          </p>
          <p>
            <strong>2. Finalidad del Tratamiento:</strong> Los datos recolectados (nombres, documentos de identidad, teléfonos, correos electrónicos y registros de pesajes lecheros) son utilizados exclusivamente para:
          </p>
          <ul>
            <li>La liquidación quincenal y control administrativo del acopio lechero.</li>
            <li>La comunicación oficial entre la Junta Directiva y los 32 asociados.</li>
            <li>La atención y respuesta a las consultas enviadas mediante el formulario web.</li>
            <li>Cumplimiento de las obligaciones tributarias y legales ante las entidades del Estado.</li>
          </ul>
          <p>
            <strong>3. Derechos del Titular (Habeas Data):</strong> En calidad de titular de la información, usted tiene derecho a conocer, actualizar, rectificar y solicitar la supresión de sus datos personales, así como revocar la autorización otorgada para su tratamiento, a través de nuestro correo oficial <strong><?php echo $correo; ?></strong> o al teléfono <strong><?php echo $telefono; ?></strong>.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Entendido y Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================================================================================== -->
  <!-- MODAL: TÉRMINOS Y CONDICIONES DE USO                                                  -->
  <!-- ===================================================================================== -->
  <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content rounded-4 border-0">
        <div class="modal-header bg-dark text-white py-3">
          <h5 class="modal-title fw-bold" id="modalTerminosLabel">
            <i class="bi bi-file-text me-2"></i>Términos y Condiciones de Uso de la Plataforma
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-dark small lh-lg p-4">
          <h6 class="fw-bold">1. Aceptación de los Términos</h6>
          <p>El acceso y uso de la Plataforma de Control Lechero de <?php echo $sigla; ?> confiere la condición de usuario e implica la aceptación plena de las presentes disposiciones.</p>
          
          <h6 class="fw-bold">2. Acceso al Portal de Asociados</h6>
          <p>El portal privado está reservado para los 32 asociados activos y el personal administrativo autorizado. Las credenciales de acceso son personales e intransferibles. Cada asociado es responsable de la custodia de su contraseña.</p>

          <h6 class="fw-bold">3. Veracidad de la Información</h6>
          <p>Los registros de pesajes registrados en la plataforma constituyen soporte oficial para las liquidaciones quincenales. El usuario que detecte alguna inconsistencia cuenta con el buzón de quejas oficial en su panel para solicitar la correspondiente auditoría.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5.3 Bundle con Popper JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

  <!-- ===================================================================================== -->
  <!-- SCRIPT CLIENTE: NAVBAR SCROLL EFFECT & ENVÍO ASÍNCRONO DEL FORMULARIO                  -->
  <!-- ===================================================================================== -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // 1. Efecto sutil de sombra y elevación al hacer Scroll en el Navbar
      const navbar = document.getElementById("mainNavbar");
      window.addEventListener("scroll", function() {
        if (window.scrollY > 40) {
          navbar.classList.add("scrolled");
        } else {
          navbar.classList.remove("scrolled");
        }
      });

      // 2. Envío Asíncrono del Formulario con Floating Labels hacia api/consultas.php
      const formContacto = document.getElementById("form-contacto-publico");
      const btnEnviar = document.getElementById("btn-enviar-contacto");
      const alertBox = document.getElementById("contacto-alert");

      if (formContacto) {
        formContacto.addEventListener("submit", async function(event) {
          event.preventDefault();

          alertBox.className = "alert d-none shadow-sm rounded-4";
          alertBox.textContent = "";

          btnEnviar.disabled = true;
          const textoOriginal = btnEnviar.innerHTML;
          btnEnviar.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Transmitiendo consulta...`;

          const payload = {
            nombre: document.getElementById("floatingNombre").value.trim(),
            contacto: document.getElementById("floatingContacto").value.trim(),
            mensaje: document.getElementById("floatingMensaje").value.trim()
          };

          try {
            // [BACKEND PUENTE] Envío POST asíncrono al endpoint PHP
            const respuesta = await fetch("./api/consultas.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
              },
              body: JSON.stringify(payload)
            });

            const data = await respuesta.json();

            if (respuesta.ok && data.ok) {
              alertBox.className = "alert alert-success d-flex align-items-center gap-2 shadow-sm rounded-4";
              alertBox.innerHTML = `<i class="bi bi-check-circle-fill fs-5"></i> <div><strong>¡Excelente!</strong> ${data.mensaje || 'Tu mensaje fue recibido satisfactoriamente.'}</div>`;
              alertBox.classList.remove("d-none");
              formContacto.reset();
            } else {
              alertBox.className = "alert alert-danger d-flex align-items-center gap-2 shadow-sm rounded-4";
              alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill fs-5"></i> <div><strong>Atención:</strong> ${data.error || 'No fue posible registrar la consulta.'}</div>`;
              alertBox.classList.remove("d-none");
            }
          } catch (error) {
            alertBox.className = "alert alert-danger d-flex align-items-center gap-2 shadow-sm rounded-4";
            alertBox.innerHTML = `<i class="bi bi-wifi-off fs-5"></i> <div><strong>Error de conexión:</strong> No se pudo comunicar con el servidor local. Por favor verifica que Apache y MySQL estén activos en XAMPP.</div>`;
            alertBox.classList.remove("d-none");
          } finally {
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = textoOriginal;
          }
        });
      }
    });
  </script>
</body>
</html>
