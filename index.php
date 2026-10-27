<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOCIACIÓN DE GANADEROS (ASOAGROALIZAL)
 * Archivo: index.php (Landing Page Corporativa)
 * Entorno: XAMPP (Apache + PHP 8 + MySQL)
 * 
 * PUENTE CON EL BACKEND (PHP):
 * 1. Inclusión de api/db.php para inicializar la conexión PDO y helpers de autenticación.
 * 2. Consulta de sesión activa para alternar dinámicamente el botón del Navbar
 *    (muestra "Acceso al Sistema" o "Ir a mi Panel" según corresponda).
 * 3. Formulario integrado con api/consultas.php para recepción y guardado de mensajes.
 * 4. Copyright con año dinámico date('Y').
 * =========================================================================================
 */

// [BACKEND] 1. Cargar capa de base de datos, sesiones seguras y funciones auxiliares
require_once __DIR__ . '/api/db.php';

// [BACKEND] 2. Verificar si existe una sesión de usuario activa (ADMIN o GANADERO)
$usuarioActual = getCurrentUser();

// Definición de metadatos corporativos constantes
$razonSocial = "ASOCIACION CAMPESINA NO NACIONAL DE TRABAJADORES DE LA VEREDA ALIZAL MUNICIPIO DE CARMEN DE CARUPA DEPARTAMENTO DE CUNDINAMARCA";
$sigla = "ASOAGROALIZAL";
$nit = "832006127-2";
$direccion = "Vereda Alizal Finca El Recuerdo, Carmen De Carupa, Cundinamarca";
$telefono = "3185235690";
$correo = "asoagroalizal@gmail.com";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $sigla; ?> | Plataforma de Control Lechero y Desarrollo Campesino</title>
  
  <!-- SEO y Metadatos Corporativos -->
  <meta name="description" content="Plataforma de Control Lechero de ASOAGROALIZAL. Unión campesina, buenas prácticas de ordeño, tanques de enfriamiento y desarrollo sostenible en Carmen de Carupa, Cundinamarca.">
  <meta name="author" content="<?php echo $sigla; ?>">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Google Fonts: Plus Jakarta Sans & Playfair Display -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">

  <style>
    :root {
      --color-primario: #1c5229;
      --color-primario-hover: #143d1e;
      --color-secundario: #c59b27;
      --color-tierra: #5d4037;
      --color-crema: #fdfbf7;
      --color-campo-soft: #f1f8f3;
      --color-texto-oscuro: #1d251e;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--color-texto-oscuro);
      background-color: var(--color-crema);
      overflow-x: hidden;
      scroll-behavior: smooth;
    }

    h1, h2, h3, .font-serif {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 700;
    }

    /* Navbar Estilizado */
    .navbar-corporativo {
      background-color: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(12px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }

    .navbar-brand-logo {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, var(--color-primario), #2e7d32);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 14px;
      font-size: 1.5rem;
      box-shadow: 0 4px 12px rgba(28, 82, 41, 0.25);
    }

    .nav-link {
      font-weight: 600;
      color: #374151;
      padding: 0.5rem 1rem !important;
      transition: color 0.2s ease;
    }

    .nav-link:hover {
      color: var(--color-primario);
    }

    /* Botones personalizados */
    .btn-campo {
      background: linear-gradient(135deg, var(--color-primario), #2e7d32);
      color: #ffffff;
      font-weight: 700;
      border: none;
      padding: 0.75rem 1.6rem;
      border-radius: 12px;
      box-shadow: 0 6px 18px rgba(28, 82, 41, 0.22);
      transition: all 0.25s ease;
    }

    .btn-campo:hover {
      background: linear-gradient(135deg, var(--color-primario-hover), #236527);
      color: #ffffff;
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(28, 82, 41, 0.32);
    }

    .btn-outline-campo {
      color: var(--color-primario);
      border: 2px solid var(--color-primario);
      font-weight: 700;
      padding: 0.75rem 1.6rem;
      border-radius: 12px;
      background: transparent;
      transition: all 0.25s ease;
    }

    .btn-outline-campo:hover {
      background-color: var(--color-campo-soft);
      color: var(--color-primario-hover);
      border-color: var(--color-primario-hover);
      transform: translateY(-2px);
    }

    /* Hero Section */
    .hero-section {
      position: relative;
      background: linear-gradient(180deg, #eaf4ed 0%, #fbfdfc 60%, var(--color-crema) 100%);
      padding: 5rem 0 6rem 0;
      border-bottom: 1px solid #e1eee4;
    }

    .badge-campo {
      background-color: #e2f2e5;
      color: var(--color-primario);
      font-weight: 700;
      font-size: 0.85rem;
      padding: 0.45rem 1rem;
      border-radius: 50rem;
      border: 1px solid #c7e5ce;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }

    .hero-title {
      font-size: clamp(2.3rem, 4.5vw, 3.6rem);
      line-height: 1.18;
      color: #112817;
      letter-spacing: -0.02em;
    }

    .hero-title .destacado {
      color: var(--color-primario);
      position: relative;
      display: inline-block;
    }

    .hero-card-img {
      border-radius: 28px;
      overflow: hidden;
      box-shadow: 0 20px 45px rgba(20, 60, 30, 0.15);
      border: 6px solid #ffffff;
      position: relative;
    }

    .hero-card-img img {
      width: 100%;
      height: 440px;
      object-fit: cover;
      display: block;
      transition: transform 0.6s ease;
    }

    .hero-card-img:hover img {
      transform: scale(1.03);
    }

    .floating-stats-card {
      position: absolute;
      bottom: 24px;
      left: 24px;
      right: 24px;
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(10px);
      padding: 1.25rem 1.5rem;
      border-radius: 18px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.8);
    }

    /* Cards de Información y Servicios */
    .card-corporativa {
      background: #ffffff;
      border: 1px solid #e5ede7;
      border-radius: 20px;
      padding: 2.2rem 1.8rem;
      transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
      height: 100%;
      position: relative;
      overflow: hidden;
    }

    .card-corporativa:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 36px rgba(24, 70, 35, 0.10);
      border-color: #b8dac1;
    }

    .card-icon-wrapper {
      width: 60px;
      height: 60px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      margin-bottom: 1.4rem;
    }

    .icon-verde {
      background-color: #e7f5eb;
      color: var(--color-primario);
    }

    .icon-dorado {
      background-color: #fdf5e2;
      color: #b5830e;
    }

    .icon-azul {
      background-color: #e5f3fc;
      color: #0b72b9;
    }

    /* Galería */
    .galeria-item {
      position: relative;
      border-radius: 18px;
      overflow: hidden;
      height: 270px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    }

    .galeria-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .galeria-item:hover img {
      transform: scale(1.08);
    }

    .galeria-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, transparent 40%, rgba(10, 33, 16, 0.88) 100%);
      color: #fff;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 1.25rem;
    }

    /* Sección Contacto */
    .seccion-contacto {
      background-color: #ffffff;
      border-radius: 28px;
      border: 1px solid #e1ebe3;
      box-shadow: 0 12px 36px rgba(0, 0, 0, 0.04);
      overflow: hidden;
    }

    .info-contacto-card {
      background: linear-gradient(145deg, var(--color-primario), #133a1c);
      color: #ffffff;
      padding: 3rem 2.5rem;
      height: 100%;
      border-radius: 24px;
    }

    .form-control, .form-select {
      padding: 0.8rem 1.1rem;
      border-radius: 12px;
      border: 1.5px solid #d2ded5;
      font-size: 0.95rem;
    }

    .form-control:focus {
      border-color: var(--color-primario);
      box-shadow: 0 0 0 0.25rem rgba(28, 82, 41, 0.15);
    }

    /* Footer Legal Institucional */
    .footer-institucional {
      background-color: #0b1c10;
      color: #d1ded5;
      font-size: 0.9rem;
      border-top: 4px solid var(--color-secundario);
    }

    .footer-institucional a {
      color: #a8cfb3;
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .footer-institucional a:hover {
      color: #ffffff;
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- ===================================================================================== -->
  <!-- BARRA DE NAVEGACIÓN (NAVBAR)                                                          -->
  <!-- ===================================================================================== -->
  <header class="sticky-top">
    <nav class="navbar navbar-expand-lg navbar-corporativo py-3">
      <div class="container">
        <!-- Logotipo / Identidad Corporativa -->
        <a class="navbar-brand d-flex align-items-center gap-3" href="./index.php">
          <div class="navbar-brand-logo">
            <i class="bi bi-droplet-fill"></i>
          </div>
          <div>
            <span class="d-block fw-bold text-dark lh-sm" style="font-size: 1.25rem; letter-spacing: -0.01em;">
              <?php echo $sigla; ?>
            </span>
            <small class="d-block text-muted text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.08em;">
              Carmen de Carupa · Cundinamarca
            </small>
          </div>
        </a>

        <!-- Botón Toggle Responsive para Móviles -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido" aria-controls="navbarContenido" aria-expanded="false" aria-label="Abrir navegación">
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Enlaces Ancla y Acciones -->
        <div class="collapse navbar-collapse" id="navbarContenido">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
            <li class="nav-item">
              <a class="nav-link" href="#inicio">Inicio</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#quienes-somos">¿Quiénes Somos?</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#labor-infraestructura">Labor e Infraestructura</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#galeria">Galería</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#contacto">Contacto</a>
            </li>
          </ul>

          <!-- [BACKEND] Renderizado condicional según estado de autenticación de PHP -->
          <div class="d-flex align-items-center gap-2 ms-lg-3 mt-3 mt-lg-0">
            <?php if ($usuarioActual): ?>
              <!-- Usuario con sesión activa: Muestra acceso directo a su panel -->
              <a href="<?php echo ($usuarioActual['rol'] === 'ADMIN') ? './admin.php' : './panel.php'; ?>" class="btn btn-campo d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2"></i>
                <span>Mi Panel (<?php echo htmlspecialchars($usuarioActual['nombre']); ?>)</span>
              </a>
              <a href="./logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Cerrar Sesión">
                <i class="bi bi-box-arrow-right"></i> Salir
              </a>
            <?php else: ?>
              <!-- Visitante / Asociado no autenticado: Botón estricto requerido -->
              <a href="./login.php" class="btn btn-campo d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Acceso al Sistema</span>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <main>
    <!-- ===================================================================================== -->
    <!-- HERO SECTION                                                                          -->
    <!-- ===================================================================================== -->
    <section id="inicio" class="hero-section">
      <div class="container">
        <div class="row align-items-center g-5">
          <!-- Columna Copywriting Hero -->
          <div class="col-lg-7 text-center text-lg-start">
            <div class="badge-campo mb-3 shadow-sm">
              <i class="bi bi-patch-check-fill text-success"></i>
              <span>32 Familias Campesinas Unidas por el Desarrollo Rural</span>
            </div>

            <h1 class="hero-title mb-3">
              Fuerza Campesina, <span class="destacado">Pureza y Transparencia</span> en Cada Litro
            </h1>

            <p class="lead text-muted mb-4 pe-lg-4" style="line-height: 1.65; font-size: 1.15rem;">
              En <strong><?php echo $sigla; ?></strong> dignificamos la labor de la vereda Alizal. Garantizamos pesajes exactos en los turnos de mañana y tarde, conservación en frío con tecnología propia y liquidaciones quincenales transparentes para todos nuestros asociados.
            </p>

            <!-- Call To Action Dual (Requerido) -->
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start mb-4">
              <a href="./login.php" class="btn btn-campo btn-lg d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-door-open-fill"></i>
                <span>Acceso al Sistema de Control</span>
                <i class="bi bi-arrow-right"></i>
              </a>
              <a href="#quienes-somos" class="btn btn-outline-campo btn-lg d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-compass"></i>
                <span>Conocer Más de Nuestra Labor</span>
              </a>
            </div>

            <!-- Métricas Clave de la Organización -->
            <div class="row g-3 pt-4 border-top border-2 mt-2 text-center text-sm-start">
              <div class="col-4">
                <h3 class="fw-bold text-success mb-0" style="font-size: 1.9rem;">32</h3>
                <small class="text-muted fw-semibold">Asociados Activos</small>
              </div>
              <div class="col-4 border-start border-end">
                <h3 class="fw-bold text-success mb-0" style="font-size: 1.9rem;">2 Turnos</h3>
                <small class="text-muted fw-semibold">Mañana ☀️ / Tarde 🌅</small>
              </div>
              <div class="col-4">
                <h3 class="fw-bold text-success mb-0" style="font-size: 1.9rem;">100%</h3>
                <small class="text-muted fw-semibold">Pago Justo y Oportuno</small>
              </div>
            </div>
          </div>

          <!-- Columna Imagen / Visual Showcase -->
          <div class="col-lg-5">
            <div class="hero-card-img">
              <img src="https://images.unsplash.com/photo-1527153857715-3908f2ae5e81?auto=format&fit=crop&w=900&q=80" 
                   alt="Paisaje de pasturas verdes y ganado lechero en Carmen de Carupa, Cundinamarca" 
                   class="img-fluid">
              
              <!-- Tarjeta Flotante Informativa -->
              <div class="floating-stats-card">
                <div class="d-flex align-items-center gap-3">
                  <div class="p-2 bg-success text-white rounded-3 fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-award-fill"></i>
                  </div>
                  <div>
                    <h6 class="mb-0 fw-bold text-dark">Calidad Certificada en Finca</h6>
                    <small class="text-muted">Buenas Prácticas de Ordeño (BPO) en Carmen de Carupa</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================================================================================== -->
    <!-- SECCIÓN: ¿QUIÉNES SOMOS?                                                              -->
    <!-- ===================================================================================== -->
    <section id="quienes-somos" class="py-5" style="background-color: #ffffff;">
      <div class="container py-lg-4">
        <div class="text-center max-w-700 mx-auto mb-5">
          <span class="badge-campo mb-2">Nuestra Esencia Campesina</span>
          <h2 class="display-6 fw-bold mb-3 text-dark">¿Quiénes Somos?</h2>
          <p class="text-muted lead fs-6">
            Nacidos en el corazón de Carmen de Carupa, somos una organización comunitaria y gremial que agrupa a <strong>32 asociados activos y comprometidos con el campo</strong>. Transformamos el esfuerzo de cada madrugada en bienestar digno para nuestras familias rurales.
          </p>
        </div>

        <!-- Misión y Visión a 2030 -->
        <div class="row g-4 align-items-stretch">
          <!-- Tarjeta Misión -->
          <div class="col-md-6">
            <div class="card-corporativa h-100 border-start border-4 border-success">
              <div class="card-icon-wrapper icon-verde">
                <i class="bi bi-bullseye"></i>
              </div>
              <h3 class="h4 fw-bold text-dark mb-3">Nuestra Misión</h3>
              <p class="text-muted leading-relaxed mb-0">
                Unir esfuerzos para la <strong>producción sostenible de alimentos</strong> que permita el mejoramiento integral y continuo de la <strong>calidad de vida de las familias de la vereda de Alizal</strong>, fomentando el trabajo cooperativo, la honestidad gremial y la protección de nuestro entorno andino.
              </p>
            </div>
          </div>

          <!-- Tarjeta Visión 2030 -->
          <div class="col-md-6">
            <div class="card-corporativa h-100 border-start border-4 border-warning">
              <div class="card-icon-wrapper icon-dorado">
                <i class="bi bi-binoculars-fill"></i>
              </div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="h4 fw-bold text-dark mb-0">Visión a 2030</h3>
                <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill">Meta Estratégica</span>
              </div>
              <p class="text-muted leading-relaxed mb-0 mt-3">
                Para el año 2030, seremos una organización consolidada capaz de <strong>transformar el 50% de nuestra producción láctea</strong>, dar un alto <strong>valor agregado a la papa y los tubérculos andinos</strong> propios de nuestra región, y <strong>expandirnos competitivamente a nuevos mercados departamentales y nacionales</strong>.
              </p>
            </div>
          </div>
        </div>

        <!-- Franja de Valores de la Comunidad (32 Asociados) -->
        <div class="mt-5 p-4 p-lg-5 rounded-4" style="background: linear-gradient(135deg, #f0f7f2, #ffffff); border: 1.5px solid #d4e7d9;">
          <div class="row align-items-center g-4">
            <div class="col-lg-8">
              <h4 class="fw-bold text-success mb-2">
                <i class="bi bi-people-fill me-2"></i> Una Familia de 32 Familias Campesinas
              </h4>
              <p class="text-muted mb-0">
                Cada gota de leche entregada en nuestros centros de acopio representa la honestidad, el sudor y la esperanza de 32 hogares de la vereda Alizal. Creemos firmemente que el cooperativismo campesino es el motor que transforma el territorio.
              </p>
            </div>
            <div class="col-lg-4 text-lg-end">
              <a href="#contacto" class="btn btn-outline-campo">
                <i class="bi bi-chat-heart me-1"></i> Contactar a la Junta
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================================================================================== -->
    <!-- SECCIÓN: NUESTRA LABOR E INFRAESTRUCTURA                                              -->
    <!-- ===================================================================================== -->
    <section id="labor-infraestructura" class="py-5" style="background-color: var(--color-campo-soft);">
      <div class="container py-lg-4">
        <div class="text-center max-w-700 mx-auto mb-5">
          <span class="badge-campo mb-2">Capacidad Técnica y Productiva</span>
          <h2 class="display-6 fw-bold mb-3 text-dark">Nuestra Labor e Infraestructura</h2>
          <p class="text-muted lead fs-6">
            Combinamos la sabiduría tradicional de nuestras tierras con tecnología propia para garantizar alimentos sanos, inocuos y con trazabilidad desde el campo hasta la mesa.
          </p>
        </div>

        <div class="row g-4">
          <!-- Card 1: Acopio Lechero y BPO -->
          <div class="col-lg-4 col-md-6">
            <div class="card-corporativa">
              <div class="card-icon-wrapper icon-verde">
                <i class="bi bi-shield-check"></i>
              </div>
              <h4 class="fw-bold text-dark mb-3">Acopio Lechero y BPO</h4>
              <p class="text-muted mb-3">
                Implementamos y vigilamos activamente las <strong>Buenas Prácticas de Ordeño (BPO)</strong> en cada una de las fincas asociadas. Mantenemos una rigurosa rutina de higiene de pezones, filtrado inmediato y control microbiológico continuo.
              </p>
              <ul class="list-unstyled text-muted small mb-0 d-flex flex-column gap-2">
                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Ordeño higiénico en doble turno</li>
                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Monitoreo de mastitis y acidez</li>
                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Pesajes digitales sistematizados</li>
              </ul>
            </div>
          </div>

          <!-- Card 2: Tanques de Enfriamiento (Cadena de Frío) -->
          <div class="col-lg-4 col-md-6">
            <div class="card-corporativa">
              <div class="card-icon-wrapper icon-azul">
                <i class="bi bi-snow2"></i>
              </div>
              <h4 class="fw-bold text-dark mb-3">Cadena de Frío Propia</h4>
              <p class="text-muted mb-3">
                Contamos con <strong>tanques de enfriamiento de acero inoxidable</strong> con tecnología propia de refrigeración rápida a 4°C, lo cual detiene la proliferación bacteriana y conserva intactas las propiedades nutricionales y grasas de la leche.
              </p>
              <ul class="list-unstyled text-muted small mb-0 d-flex flex-column gap-2">
                <li><i class="bi bi-check-circle-fill text-primary me-2"></i>Enfriamiento rápido post-ordeño</li>
                <li><i class="bi bi-check-circle-fill text-primary me-2"></i>Monitoreo constante de temperatura</li>
                <li><i class="bi bi-check-circle-fill text-primary me-2"></i>Conservación óptima para la industria</li>
              </ul>
            </div>
          </div>

          <!-- Card 3: Laboratorio de Tejidos e Invernaderos -->
          <div class="col-lg-4 col-md-12">
            <div class="card-corporativa">
              <div class="card-icon-wrapper icon-dorado">
                <i class="bi bi-flower1"></i>
              </div>
              <h4 class="fw-bold text-dark mb-3">Biotecnología e Invernaderos</h4>
              <p class="text-muted mb-3">
                Miramos al futuro agropecuario: disponemos de infraestructura con <strong>laboratorio de tejidos vegetales e invernaderos tecnificados</strong> para la reproducción de semillas limpias de papa y tubérculos andinos con alta resistencia.
              </p>
              <ul class="list-unstyled text-muted small mb-0 d-flex flex-column gap-2">
                <li><i class="bi bi-check-circle-fill text-warning me-2"></i>Semilla limpia certificada de papa</li>
                <li><i class="bi bi-check-circle-fill text-warning me-2"></i>Diversificación de cultivos andinos</li>
                <li><i class="bi bi-check-circle-fill text-warning me-2"></i>Micropropagación in vitro sostenible</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================================================================================== -->
    <!-- GALERÍA VISUAL DE NUESTRA LABOR                                                       -->
    <!-- ===================================================================================== -->
    <section id="galeria" class="py-5" style="background-color: #ffffff;">
      <div class="container py-lg-4">
        <div class="text-center max-w-700 mx-auto mb-5">
          <span class="badge-campo mb-2">Testimonio Gráfico</span>
          <h2 class="display-6 fw-bold mb-3 text-dark">Nuestra Tierra, Fincas y Ordeño</h2>
          <p class="text-muted lead fs-6">
            Un recorrido por las faenas diarias en Carmen de Carupa, donde el compromiso campesino se vive desde las cuatro de la mañana en cada potrero y establo.
          </p>
        </div>

        <div class="row g-4">
          <!-- Foto 1: Ganado en Pastoreo -->
          <div class="col-sm-6 col-lg-3">
            <div class="galeria-item">
              <img src="https://images.unsplash.com/photo-1546445317-29f4545e9d53?auto=format&fit=crop&w=700&q=80" 
                   alt="Bovinos lecheros alimentándose en pasturas naturales de la Vereda Alizal" 
                   class="img-fluid">
              <div class="galeria-overlay">
                <span class="badge bg-success mb-1 w-auto align-self-start">Finca Campesina</span>
                <h6 class="fw-bold mb-0">Pastoreo Libre y Sano</h6>
                <small class="text-light opacity-75">Bienestar animal en alta montaña</small>
              </div>
            </div>
          </div>

          <!-- Foto 2: Faena de Ordeño Limpio -->
          <div class="col-sm-6 col-lg-3">
            <div class="galeria-item">
              <img src="https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=700&q=80" 
                   alt="Productor campesino realizando faena de ordeño higiénico con canastilla esterilizada" 
                   class="img-fluid">
              <div class="galeria-overlay">
                <span class="badge bg-warning text-dark mb-1 w-auto align-self-start">Buenas Prácticas</span>
                <h6 class="fw-bold mb-0">Faena de Ordeño Limpio</h6>
                <small class="text-light opacity-75">Turnos de Mañana y Tarde</small>
              </div>
            </div>
          </div>

          <!-- Foto 3: Recolección y Control de Frío -->
          <div class="col-sm-6 col-lg-3">
            <div class="galeria-item">
              <img src="https://images.unsplash.com/photo-1596733430284-f7437764b1a9?auto=format&fit=crop&w=700&q=80" 
                   alt="Cantinas y tanques de almacenamiento de leche fresca de alta calidad" 
                   class="img-fluid">
              <div class="galeria-overlay">
                <span class="badge bg-primary mb-1 w-auto align-self-start">Tecnología</span>
                <h6 class="fw-bold mb-0">Tanques y Cadena de Frío</h6>
                <small class="text-light opacity-75">Conservación a 4°C garantizada</small>
              </div>
            </div>
          </div>

          <!-- Foto 4: Paisaje y Finca El Recuerdo -->
          <div class="col-sm-6 col-lg-3">
            <div class="galeria-item">
              <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=700&q=80" 
                   alt="Amanecer en las montañas de Carmen de Carupa, Vereda Alizal" 
                   class="img-fluid">
              <div class="galeria-overlay">
                <span class="badge bg-secondary mb-1 w-auto align-self-start">Territorio</span>
                <h6 class="fw-bold mb-0">Finca El Recuerdo</h6>
                <small class="text-light opacity-75">Cuna de ASOAGROALIZAL</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================================================================================== -->
    <!-- SECCIÓN: CONTACTO Y CANAL DE ATENCIÓN (2 COLUMNAS)                                    -->
    <!-- ===================================================================================== -->
    <section id="contacto" class="py-5" style="background-color: var(--color-campo-soft);">
      <div class="container py-lg-4">
        <div class="text-center max-w-700 mx-auto mb-5">
          <span class="badge-campo mb-2">Canal Oficial de Comunicación</span>
          <h2 class="display-6 fw-bold mb-3 text-dark">Ponte en Contacto con Nosotros</h2>
          <p class="text-muted lead fs-6">
            ¿Deseas conocer más sobre el proceso de afiliación, comprar nuestra producción láctea o consultar a la administración? Déjanos tu mensaje.
          </p>
        </div>

        <div class="seccion-contacto">
          <div class="row g-0">
            <!-- Columna Izquierda: Formulario (POST -> api/consultas.php) -->
            <div class="col-lg-7 p-4 p-md-5">
              <h4 class="fw-bold text-dark mb-2">Envíanos un Mensaje Directo</h4>
              <p class="text-muted small mb-4">
                Tus datos serán tratados bajo estricta reserva de conformidad con la Ley 1581 de 2012.
              </p>

              <!-- Alerta Dinámica AJAX -->
              <div id="contacto-alert" class="alert d-none shadow-sm" role="alert"></div>

              <!-- [BACKEND] Formulario conectado a api/consultas.php mediante POST y AJAX -->
              <form id="form-contacto-publico" action="./api/consultas.php" method="POST" class="needs-validation">
                <div class="mb-3">
                  <label for="nombre" class="form-label fw-bold text-dark small">Nombre Completo <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control border-start-0" id="nombre" name="nombre" placeholder="Ej: Luis Hernando Moncada" required>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="contacto" class="form-label fw-bold text-dark small">Teléfono Móvil o Correo Electrónico <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope-at"></i></span>
                    <input type="text" class="form-control border-start-0" id="contacto" name="contacto" placeholder="Ej: 3185235690 o correo@ejemplo.com" required>
                  </div>
                  <div class="form-text text-muted small">Te responderemos a la mayor brevedad a este medio.</div>
                </div>

                <div class="mb-4">
                  <label for="mensaje" class="form-label fw-bold text-dark small">Mensaje o Inquietud <span class="text-danger">*</span></label>
                  <textarea class="form-control" id="mensaje" name="mensaje" rows="4" placeholder="Cuéntanos sobre tu inquietud, consulta comercial o solicitud de afiliación a la asociación..." required></textarea>
                </div>

                <button type="submit" id="btn-enviar-contacto" class="btn btn-campo w-100 py-3 d-flex align-items-center justify-content-center gap-2">
                  <i class="bi bi-send-fill"></i>
                  <span>Enviar Mensaje a la Administración</span>
                </button>
              </form>
            </div>

            <!-- Columna Derecha: Información Oficial de la Asociación -->
            <div class="col-lg-5 p-4 p-md-5 d-flex flex-column justify-content-between text-white" style="background: linear-gradient(145deg, #164621, #0c2b13);">
              <div>
                <span class="badge bg-warning text-dark fw-bold mb-3 px-3 py-1 rounded-pill">Sede Campesina</span>
                <h4 class="fw-bold mb-4 text-white"><?php echo $sigla; ?></h4>

                <div class="d-flex flex-column gap-4">
                  <!-- Dirección -->
                  <div class="d-flex align-items-start gap-3">
                    <div class="fs-4 text-warning">
                      <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold mb-1 text-white">Ubicación y Sede Principal</h6>
                      <p class="mb-0 text-light opacity-75 small">
                        <?php echo $direccion; ?>
                      </p>
                    </div>
                  </div>

                  <!-- Teléfono -->
                  <div class="d-flex align-items-start gap-3">
                    <div class="fs-4 text-warning">
                      <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold mb-1 text-white">Línea Telefónica y WhatsApp</h6>
                      <p class="mb-0 text-light opacity-75 small">
                        <a href="tel:<?php echo $telefono; ?>" class="text-white text-decoration-none fw-semibold">
                          +57 <?php echo $telefono; ?>
                        </a>
                      </p>
                    </div>
                  </div>

                  <!-- Correo -->
                  <div class="d-flex align-items-start gap-3">
                    <div class="fs-4 text-warning">
                      <i class="bi bi-envelope-fill"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold mb-1 text-white">Correo Electrónico Institucional</h6>
                      <p class="mb-0 text-light opacity-75 small">
                        <a href="mailto:<?php echo $correo; ?>" class="text-white text-decoration-none">
                          <?php echo $correo; ?>
                        </a>
                      </p>
                    </div>
                  </div>

                  <!-- Horarios de Acopio -->
                  <div class="d-flex align-items-start gap-3">
                    <div class="fs-4 text-warning">
                      <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold mb-1 text-white">Horarios de Acopio Lechero</h6>
                      <p class="mb-0 text-light opacity-75 small">
                        ☀️ Mañana: 05:00 a.m. – 07:30 a.m.<br>
                        🌅 Tarde: 03:30 p.m. – 05:30 p.m.
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- NIT y Transparencia -->
              <div class="mt-4 pt-3 border-top border-light border-opacity-25 small text-light opacity-75">
                <i class="bi bi-shield-check text-warning me-1"></i> Organización Campesina Registrada · NIT: <?php echo $nit; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ===================================================================================== -->
  <!-- FOOTER LEGAL INSTITUCIONAL (OBLIGATORIO)                                               -->
  <!-- ===================================================================================== -->
  <footer class="footer-institucional py-5">
    <div class="container">
      <div class="row g-4 justify-content-between mb-4">
        <!-- Razón Social y Misión Resumida -->
        <div class="col-lg-5">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="fs-3 text-warning"><i class="bi bi-droplet-half"></i></span>
            <h5 class="fw-bold text-white mb-0"><?php echo $sigla; ?></h5>
          </div>
          <p class="text-white-50 small mb-2" style="line-height: 1.6;">
            <strong>Razón Social:</strong><br>
            <?php echo $razonSocial; ?>
          </p>
          <p class="text-white-50 small mb-0">
            <strong>NIT:</strong> <?php echo $nit; ?>
          </p>
        </div>

        <!-- Enlaces Rápidos y Navegación -->
        <div class="col-sm-6 col-lg-3">
          <h6 class="text-white fw-bold text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.08em;">
            Navegación Institucional
          </h6>
          <ul class="list-unstyled d-flex flex-column gap-2 small">
            <li><a href="#inicio"><i class="bi bi-chevron-right me-1"></i>Inicio</a></li>
            <li><a href="#quienes-somos"><i class="bi bi-chevron-right me-1"></i>¿Quiénes Somos? (Misión / Visión)</a></li>
            <li><a href="#labor-infraestructura"><i class="bi bi-chevron-right me-1"></i>Labor e Infraestructura BPO</a></li>
            <li><a href="#galeria"><i class="bi bi-chevron-right me-1"></i>Galería de Faenas</a></li>
            <li><a href="#contacto"><i class="bi bi-chevron-right me-1"></i>Atención al Ganadero</a></li>
          </ul>
        </div>

        <!-- Acceso al Sistema y Enlaces Legales Colombia -->
        <div class="col-sm-6 col-lg-4">
          <h6 class="text-white fw-bold text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.08em;">
            Sistema y Marco Legal Colombiano
          </h6>
          <ul class="list-unstyled d-flex flex-column gap-2 small mb-3">
            <li>
              <a href="./login.php" class="text-warning fw-bold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Portal de Asociados (Ingreso)
              </a>
            </li>
            <li>
              <a href="./registro.php">
                <i class="bi bi-person-plus me-1"></i> Solicitud de Registro de Asociado
              </a>
            </li>
            <!-- Enlaces Legales Exigidos con Modales Informativos -->
            <li>
              <a href="#" data-bs-toggle="modal" data-bs-target="#modalHabeasData">
                <i class="bi bi-file-earmark-lock2 me-1"></i> Política de Tratamiento de Datos Personales (Ley 1581 de 2012)
              </a>
            </li>
            <li>
              <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos">
                <i class="bi bi-journal-check me-1"></i> Términos y Condiciones de Uso de la Plataforma
              </a>
            </li>
          </ul>
          <p class="small text-white-50 mb-0">
            <i class="bi bi-geo-alt me-1 text-warning"></i> <?php echo $direccion; ?>
          </p>
        </div>
      </div>

      <!-- Barra de Copyright Dinámico PHP -->
      <div class="pt-4 mt-4 border-top border-secondary border-opacity-25 text-center text-white-50 small">
        <p class="mb-0">
          &copy; <?php echo date('Y'); ?> <strong><?php echo $sigla; ?></strong>. Todos los derechos reservados. 
          Desarrollado para el fortalecimiento gremial de la vereda Alizal, Carmen de Carupa, Cundinamarca.
        </p>
      </div>
    </div>
  </footer>

  <!-- ===================================================================================== -->
  <!-- MODAL 1: POLÍTICA DE TRATAMIENTO DE DATOS PERSONALES (LEY 1581 DE 2012)              -->
  <!-- ===================================================================================== -->
  <div class="modal fade" id="modalHabeasData" tabindex="-1" aria-labelledby="modalHabeasDataLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title fw-bold" id="modalHabeasDataLabel">
            <i class="bi bi-shield-lock-fill me-2"></i>Política de Tratamiento de Datos Personales
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-toggle="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-dark small lh-lg">
          <p class="fw-bold mb-2">Conforme a la Ley Estatutaria 1581 de 2012 y el Decreto Reglamentario 1377 de 2013 de la República de Colombia:</p>
          <p>
            <strong>1. Responsable del Tratamiento:</strong> La <em><?php echo $razonSocial; ?></em> (en adelante <strong><?php echo $sigla; ?></strong>), con NIT <?php echo $nit; ?>, con domicilio en <?php echo $direccion; ?>, es la entidad responsable del tratamiento de los datos personales suministrados por sus asociados, proveedores y visitantes.
          </p>
          <p>
            <strong>2. Finalidad del Tratamiento:</strong> Los datos recolectados (nombres, documentos de identidad, teléfonos, correos electrónicos y registros de pesajes lecheros) son utilizados exclusivamente para:
          </p>
          <ul>
            <li>La liquidación quincenal y control administrativo del acopio lechero.</li>
            <li>La comunicación oficial entre la Junta Directiva y los asociados.</li>
            <li>La atención y respuesta a las consultas enviadas mediante el formulario web.</li>
            <li>Cumplimiento de las obligaciones tributarias y legales ante las entidades del Estado.</li>
          </ul>
          <p>
            <strong>3. Derechos del Titular (Habeas Data):</strong> En calidad de titular de la información, usted tiene derecho a conocer, actualizar, rectificar y solicitar la supresión de sus datos personales, así como revocar la autorización otorgada para su tratamiento, a través de nuestro correo oficial <strong><?php echo $correo; ?></strong>.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Entendido y Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================================================================================== -->
  <!-- MODAL 2: TÉRMINOS Y CONDICIONES DE USO                                                -->
  <!-- ===================================================================================== -->
  <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title fw-bold" id="modalTerminosLabel">
            <i class="bi bi-file-text me-2"></i>Términos y Condiciones de Uso
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-toggle="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-dark small lh-lg">
          <h6 class="fw-bold">1. Aceptación de los Términos</h6>
          <p>El acceso y uso de la Plataforma de Control Lechero de <?php echo $sigla; ?> confiere la condición de usuario e implica la aceptación plena de las presentes disposiciones.</p>
          
          <h6 class="fw-bold">2. Acceso al Portal de Asociados</h6>
          <p>El portal privado está reservado para los 32 asociados activos y el personal administrativo autorizado. Las credenciales de acceso son personales e intransferibles. Cada asociado es responsable de la custodia de su contraseña.</p>

          <h6 class="fw-bold">3. Veracidad de la Información</h6>
          <p>Los registros de pesajes registrados en la plataforma constituyen soporte oficial para las liquidaciones quincenales. El usuario que detecte alguna inconsistencia cuenta con el buzón de quejas oficial en su panel para solicitar la correspondiente auditoría.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5.3 Bundle con Popper JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

  <!-- ===================================================================================== -->
  <!-- SCRIPT CLIENTE: ENVÍO ASÍNCRONO DEL FORMULARIO DE CONTACTO A api/consultas.php       -->
  <!-- ===================================================================================== -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const formContacto = document.getElementById("form-contacto-publico");
      const btnEnviar = document.getElementById("btn-enviar-contacto");
      const alertBox = document.getElementById("contacto-alert");

      if (formContacto) {
        formContacto.addEventListener("submit", async function(event) {
          // Evitamos el refresco tradicional de página para ofrecer experiencia moderna SPA
          event.preventDefault();

          // Ocultar alertas previas
          alertBox.className = "alert d-none shadow-sm";
          alertBox.textContent = "";

          // Estado visual de carga en el botón
          btnEnviar.disabled = true;
          const textoOriginal = btnEnviar.innerHTML;
          btnEnviar.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Enviando consulta...`;

          // Recolección y saneamiento de campos del formulario
          const payload = {
            nombre: document.getElementById("nombre").value.trim(),
            contacto: document.getElementById("contacto").value.trim(),
            mensaje: document.getElementById("mensaje").value.trim()
          };

          try {
            // [BACKEND PUENTE] Petición POST al endpoint nativo api/consultas.php
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
              // Respuesta exitosa del backend
              alertBox.className = "alert alert-success d-flex align-items-center gap-2 shadow-sm";
              alertBox.innerHTML = `<i class="bi bi-check-circle-fill fs-5"></i> <div><strong>¡Excelente!</strong> ${data.mensaje || 'Tu mensaje fue recibido satisfactoriamente.'}</div>`;
              alertBox.classList.remove("d-none");
              formContacto.reset();
            } else {
              // Error validado por el backend
              alertBox.className = "alert alert-danger d-flex align-items-center gap-2 shadow-sm";
              alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill fs-5"></i> <div><strong>Atención:</strong> ${data.error || 'No fue posible registrar la consulta.'}</div>`;
              alertBox.classList.remove("d-none");
            }
          } catch (error) {
            // Error de conexión o falla de red
            alertBox.className = "alert alert-danger d-flex align-items-center gap-2 shadow-sm";
            alertBox.innerHTML = `<i class="bi bi-wifi-off fs-5"></i> <div><strong>Error de conexión:</strong> No se pudo comunicar con el servidor local. Por favor verifica que Apache y MySQL estén activos.</div>`;
            alertBox.classList.remove("d-none");
          } finally {
            // Restaurar estado del botón
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = textoOriginal;
          }
        });
      }
    });
  </script>
</body>
</html>
