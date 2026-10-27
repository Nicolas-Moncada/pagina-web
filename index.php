<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: index.php
 * Propósito: Landing page corporativa, inmersiva, accesible y 100% Bootstrap 5.3.
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$usuarioActual = getCurrentUser();
$pdo = getDB();

// Procesar formulario de contacto público vía POST
$mensajeContactoEnviado = false;
$errorContacto = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['accion_contacto'])) {
    $tokenRecibido = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($tokenRecibido)) {
        $errorContacto = 'La sesión de seguridad ha expirado. Por favor recarga la página.';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $contacto = trim($_POST['contacto'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');

        if (empty($nombre) || empty($contacto) || empty($mensaje)) {
            $errorContacto = 'Por favor completa todos los campos del formulario.';
        } elseif (strlen($nombre) < 3 || strlen($mensaje) < 10) {
            $errorContacto = 'El mensaje debe ser claro y contener al menos 10 caracteres.';
        } else {
            try {
                $conId = generarUUID('con');
                $stmt = $pdo->prepare("INSERT INTO consulta (id, nombre, contacto, mensaje, fecha, atendida) VALUES (?, ?, ?, ?, NOW(), 0)");
                $stmt->execute([$conId, $nombre, $contacto, $mensaje]);
                $mensajeContactoEnviado = true;
                setFlashAlert('success', '¡Mensaje Enviado!', 'Tu consulta ha sido radicada correctamente. La administración se comunicará contigo.');
            } catch (Exception $e) {
                error_log("Error guardando consulta: " . $e->getMessage());
                $errorContacto = 'Ocurrió un problema al enviar tu mensaje. Intenta nuevamente o comunícate vía WhatsApp.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e(EMPRESA_SIGLA); ?> | Plataforma de Control Lechero e Innovación Agropecuaria</title>
  
  <meta name="description" content="Plataforma de Control Lechero, Buenas Prácticas de Ordeño (BPO) y desarrollo asociativo de ASOAGROALIZAL en Carmen de Carupa, Cundinamarca. NIT 832006127-2.">
  <meta name="author" content="<?php echo e(EMPRESA_NOMBRE); ?>">

  <!-- Script Anti-Parpadeo (Anti-FOUC) para Modo Oscuro Inmediato -->
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
  
  <!-- Estilos Globales de Marca AsoagroAlisal -->
  <link rel="stylesheet" href="assets/css/estilos.css">

  <style>
    /* Hero Visual y Componentes Exclusivos de Landing */
    .hero-wrapper {
      padding-top: 140px;
      padding-bottom: 90px;
      background: radial-gradient(circle at 85% 15%, rgba(96, 196, 248, 0.15) 0%, transparent 45%),
                  radial-gradient(circle at 10% 85%, rgba(245, 158, 11, 0.12) 0%, transparent 40%),
                  linear-gradient(180deg, rgba(232, 245, 233, 0.6) 0%, var(--bs-body-bg) 100%);
    }

    [data-bs-theme="dark"] .hero-wrapper {
      background: radial-gradient(circle at 85% 15%, rgba(96, 196, 248, 0.08) 0%, transparent 45%),
                  radial-gradient(circle at 10% 85%, rgba(245, 158, 11, 0.06) 0%, transparent 40%),
                  linear-gradient(180deg, #131c16 0%, var(--bs-body-bg) 100%);
    }

    .hero-emblem-card {
      background: var(--card-bg);
      border: 3px solid var(--card-border);
      border-radius: 36px;
      padding: 2.5rem;
      box-shadow: var(--shadow-lg);
      text-align: center;
      position: relative;
    }

    .hero-emblem-img {
      max-width: 280px;
      height: auto;
      filter: drop-shadow(0 15px 25px rgba(20, 71, 34, 0.20));
      transition: transform 0.4s ease;
    }

    .hero-emblem-img:hover {
      transform: scale(1.04);
    }

    .step-card {
      position: relative;
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-lg);
      padding: 2rem 1.5rem;
      height: 100%;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .step-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-md);
      border-color: var(--aso-secondary);
    }

    .step-number-badge {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: var(--aso-primary);
      color: #ffffff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      font-weight: 800;
      margin-bottom: 1rem;
      box-shadow: 0 4px 12px rgba(20, 71, 34, 0.3);
    }
  </style>
</head>
<body>

  <!-- ========================================================================
       BARRA DE NAVEGACIÓN SUPERIOR FIJA
       ======================================================================== -->
  <nav class="navbar navbar-expand-lg navbar-asoagro fixed-top py-2">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
        <img src="assets/img/logo.png" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" class="brand-logo-img">
        <div class="d-flex flex-column">
          <span class="fw-bolder fs-5 text-success lh-1"><?php echo e(EMPRESA_SIGLA); ?></span>
          <small class="text-muted fw-bold" style="font-size: 0.72rem;">Control Lechero &bull; Carmen de Carupa</small>
        </div>
      </a>

      <!-- Botón hamburguesa móvil accesible -->
      <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido" aria-controls="navbarContenido" aria-expanded="false" aria-label="Abrir menú de navegación">
        <i class="bi bi-list fs-2 text-success"></i>
      </button>

      <div class="collapse navbar-collapse" id="navbarContenido">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-1 my-3 my-lg-0">
          <li class="nav-item"><a class="nav-link nav-link-custom" href="#quienes-somos">Quiénes Somos</a></li>
          <li class="nav-item"><a class="nav-link nav-link-custom" href="#familia">Nuestra Familia</a></li>
          <li class="nav-item"><a class="nav-link nav-link-custom" href="#mision-vision">Misión & Visión</a></li>
          <li class="nav-item"><a class="nav-link nav-link-custom" href="#infraestructura">Infraestructura</a></li>
          <li class="nav-item"><a class="nav-link nav-link-custom" href="#enfoque-bpo">Buenas Prácticas (BPO)</a></li>
          <li class="nav-item"><a class="nav-link nav-link-custom" href="#contacto">Contacto</a></li>
          
          <!-- Botón de Alternar Modo Oscuro -->
          <li class="nav-item ms-lg-2">
            <button type="button" class="btn btn-touch btn-outline-success border-2 rounded-circle p-0 theme-toggle-btn" style="width: 44px; height: 44px;" aria-label="Alternar modo oscuro">
              <i class="bi bi-moon-stars-fill fs-5"></i>
            </button>
          </li>

          <!-- Botón de Ingreso a la Plataforma -->
          <li class="nav-item ms-lg-2">
            <?php if ($usuarioActual): ?>
              <?php $urlDestino = ($usuarioActual['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
              <a href="<?php echo $urlDestino; ?>" class="btn btn-touch btn-aso-accent">
                <i class="bi bi-speedometer2"></i>
                <span>Mi Panel</span>
              </a>
            <?php else: ?>
              <a href="login.php" class="btn btn-touch btn-aso-primary">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Ingresar al Sistema</span>
              </a>
            <?php endif; ?>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- ========================================================================
       SECCIÓN 1: HERO INMERSIVO
       ======================================================================== -->
  <section class="hero-wrapper position-relative overflow-hidden">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-7 text-center text-lg-start">
          <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded-pill" style="background: var(--aso-secondary-light); border: 1px solid rgba(46, 125, 50, 0.25);">
            <span class="badge rounded-pill bg-success p-1" style="width: 10px; height: 10px;"></span>
            <span class="fw-bold small text-success">ASOCIATIVIDAD CAMPESINA &bull; ALTA CALIDAD HIGIÉNICA</span>
          </div>

          <h1 class="display-4 fw-extrabold text-success mb-3" style="line-height: 1.15;">
            Control Lechero y Progreso Ganadero en el <span class="text-warning">Alizal</span>
          </h1>

          <p class="lead text-muted mb-4 fs-5" style="max-width: 600px;">
            Plataforma tecnológica oficial de <strong><?php echo e(EMPRESA_SIGLA); ?></strong> para el registro diario de pesajes, trazabilidad quincenal, cadena de frío y bienestar de nuestras familias campesinas en Carmen de Carupa.
          </p>

          <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
            <?php if ($usuarioActual): ?>
              <a href="<?php echo ($usuarioActual['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>" class="btn btn-touch btn-aso-primary btn-lg px-4 shadow">
                <i class="bi bi-grid-fill"></i>
                <span>Abrir Panel de Control</span>
              </a>
            <?php else: ?>
              <a href="login.php" class="btn btn-touch btn-aso-primary btn-lg px-4 shadow">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Ingresar como Asociado</span>
              </a>
            <?php endif; ?>
            <a href="#quienes-somos" class="btn btn-touch btn-outline-success btn-lg px-4 border-2">
              <i class="bi bi-info-circle"></i>
              <span>Conocer la Asociación</span>
            </a>
          </div>

          <!-- Puntos destacados rápidos -->
          <div class="row mt-5 pt-3 g-3 border-top border-success-subtle text-start">
            <div class="col-4">
              <div class="fw-bold fs-4 text-success counter-animated" data-target="32">0</div>
              <small class="text-muted fw-semibold">Asociados Activos</small>
            </div>
            <div class="col-4">
              <div class="fw-bold fs-4 text-success">4.0 °C</div>
              <small class="text-muted fw-semibold">Tanques en Frío</small>
            </div>
            <div class="col-4">
              <div class="fw-bold fs-4 text-success">100%</div>
              <small class="text-muted fw-semibold">Buenas Prácticas BPO</small>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="hero-emblem-card reveal-on-scroll">
            <img src="assets/img/logo.png" alt="Emblema Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="hero-emblem-img mb-3">
            <h4 class="fw-bold text-success mb-1"><?php echo e(EMPRESA_SIGLA); ?></h4>
            <p class="text-muted small mb-3">Vereda Alizal &bull; Finca El Recuerdo</p>
            <div class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
              NIT <?php echo e(EMPRESA_NIT); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 2: QUIÉNES SOMOS
       ======================================================================== -->
  <section id="quienes-somos" class="py-5">
    <div class="container py-4">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-10">
          <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill uppercase mb-2">Identidad Institucional</span>
          <h2 class="display-6 fw-bold text-success">Quiénes Somos</h2>
          <div class="mx-auto" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <div class="row align-items-center g-4">
        <div class="col-md-6 reveal-on-scroll">
          <div class="card card-rural p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-4 bg-success text-white p-3 d-flex align-items-center justify-center fs-3" style="width: 56px; height: 56px;">
                <i class="bi bi-bank"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-0">Razón Social Legal</h5>
                <small class="text-muted">Personería Jurídica Vigente</small>
              </div>
            </div>
            <p class="text-muted">
              Somos legalmente la <strong><?php echo e(EMPRESA_NOMBRE); ?></strong>, identificada con NIT <strong><?php echo e(EMPRESA_NIT); ?></strong>. Nacimos del esfuerzo solidario de las productoras y productores campesinos de la Vereda Alizal en el municipio de Carmen de Carupa, departamento de Cundinamarca.
            </p>
            <p class="text-muted mb-0">
              Nuestro propósito es consolidar un modelo productivo sostenible, justo y solidario que dignifique el trabajo lechero de la región a través de la cooperación mutua y la implementación de tecnologías limpias.
            </p>
          </div>
        </div>

        <div class="col-md-6 reveal-on-scroll">
          <div class="card card-rural p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-4 bg-warning text-dark p-3 d-flex align-items-center justify-center fs-3" style="width: 56px; height: 56px;">
                <i class="bi bi-geo-alt-fill"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-0">Arraigo y Territorio</h5>
                <small class="text-muted">Carmen de Carupa, Cundinamarca</small>
              </div>
            </div>
            <p class="text-muted">
              Ubicados en la <strong>Finca El Recuerdo, Vereda Alizal</strong>, aprovechamos las condiciones privilegiadas de la alta montaña andina para la producción de leche de óptimo valor proteico y graso.
            </p>
            <p class="text-muted mb-0">
              Promovemos la conservación de los recursos hídricos, el cuidado de los pastos nativos y la tecnificación no invasiva del ordeño para garantizar un producto inocuo y de primer nivel para la industria nacional.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 3: NUESTRA FAMILIA (CONTADOR ANIMADO 32 ASOCIADOS)
       ======================================================================== -->
  <section id="familia" class="py-5" style="background: var(--aso-secondary-light);">
    <div class="container py-4">
      <div class="card card-rural border-0 shadow-lg p-4 p-md-5 text-center reveal-on-scroll">
        <div class="row align-items-center justify-content-center">
          <div class="col-lg-8">
            <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill uppercase mb-2">Comunidad y Cooperación</span>
            <h2 class="display-5 fw-extrabold text-success mb-3">Nuestra Familia Campesina</h2>
            <p class="lead text-muted mb-4">
              Cada gota de leche que llega a nuestros centros de enfriamiento representa el sacrificio, madrugadas y amor por la tierra de las familias que conforman la asociación.
            </p>

            <div class="d-inline-flex flex-column align-items-center justify-content-center p-4 rounded-4 mb-4" style="background: var(--aso-primary); color: #ffffff; min-width: 240px; box-shadow: var(--shadow-md);">
              <span class="display-2 fw-extrabold text-warning counter-animated" data-target="<?php echo e(EMPRESA_ASOCIADOS_TOTAL); ?>">0</span>
              <span class="fs-5 fw-bold text-uppercase tracking-wider">Asociados Activos</span>
              <small class="opacity-75">Productores de la Vereda Alizal</small>
            </div>

            <p class="small text-muted mb-0">
              <i class="bi bi-shield-check text-success me-1"></i> Participación democrática, equidad en la liquidación quincenal y apoyo técnico solidario continuo.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 4: MISIÓN Y VISIÓN (BORRADOR EDITABLE CORPORATIVO)
       ======================================================================== -->
  <section id="mision-vision" class="py-5">
    <div class="container py-4">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill uppercase mb-2">Horizonte Estratégico</span>
          <h2 class="display-6 fw-bold text-success">Misión y Visión</h2>
          <small class="text-muted d-block mt-1"><em>[Borrador Institucional Editable]</em></small>
          <div class="mx-auto mt-2" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-md-6 reveal-on-scroll">
          <div class="card card-rural p-4 p-md-5 h-100 border-top border-4 border-success">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-success text-white p-3 d-flex align-items-center justify-center fs-3" style="width: 56px; height: 56px;">
                <i class="bi bi-bullseye"></i>
              </div>
              <h3 class="fw-bold mb-0 text-success">Misión</h3>
            </div>
            <p class="text-muted fs-5 leading-relaxed">
              Agrupar, capacitar y respaldar a los 32 ganaderos campesinos de la Vereda Alizal en Carmen de Carupa, garantizando la recolección, conservación higiénica en frío y comercialización justa de leche cruda con altos estándares de calidad, protegiendo la soberanía alimentaria familiar y fomentando el desarrollo socioeconómico sostenible de nuestro territorio rural.
            </p>
          </div>
        </div>

        <div class="col-md-6 reveal-on-scroll">
          <div class="card card-rural p-4 p-md-5 h-100 border-top border-4 border-warning">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-warning text-dark p-3 d-flex align-items-center justify-center fs-3" style="width: 56px; height: 56px;">
                <i class="bi bi-binoculars-fill"></i>
              </div>
              <h3 class="fw-bold mb-0 text-success">Visión</h3>
            </div>
            <p class="text-muted fs-5 leading-relaxed">
              Para el año 2030, ser reconocidos como una asociación modelo de economía campesina en Cundinamarca, consolidando infraestructura tecnificada de frío, trazabilidad digital en tiempo real, certificación plena en Buenas Prácticas Ganaderas (BPG) y transformación con valor agregado que asegure precios justos y bienestar integral para todas nuestras generaciones de productores.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 5: INFRAESTRUCTURA PRODUCTIVA (IMÁGENES LOCALES)
       ======================================================================== -->
  <section id="infraestructura" class="py-5" style="background: var(--bs-body-bg);">
    <div class="container py-4">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill uppercase mb-2">Capacidad Instalada</span>
          <h2 class="display-6 fw-bold text-success">Infraestructura y Equipamiento</h2>
          <p class="text-muted">Inversión colectiva para garantizar la inocuidad y la conservación de la leche en origen.</p>
          <div class="mx-auto" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Tarjeta 1: Tanques de Enfriamiento -->
        <div class="col-lg-4 col-md-6 reveal-on-scroll">
          <div class="card card-rural h-100">
            <div class="position-relative overflow-hidden" style="height: 220px; background: #e2e8f0;">
              <img src="assets/img/infra-tanques.svg" alt="Tanques de enfriamiento de leche" class="w-100 h-100 object-fit-cover">
              <span class="position-absolute top-0 end-0 m-3 badge bg-success">Cadena de Frío</span>
            </div>
            <div class="card-body p-4">
              <h4 class="fw-bold text-success mb-2">Tanques de Enfriamiento</h4>
              <p class="text-muted small mb-3">
                Equipos en acero inoxidable con agitadores automáticos y refrigeración a <strong>4.0 °C</strong> que detienen la proliferación microbiana inmediatamente tras el ordeño matutino y vespertino.
              </p>
              <div class="d-flex justify-content-between text-muted small border-top pt-2">
                <span>Capacidad: [COMPLETAR] Litros</span>
                <span class="fw-bold text-success">Monitoreo Diario</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tarjeta 2: Laboratorio y Control de Calidad -->
        <div class="col-lg-4 col-md-6 reveal-on-scroll">
          <div class="card card-rural h-100">
            <div class="position-relative overflow-hidden" style="height: 220px; background: #e2e8f0;">
              <img src="assets/img/infra-laboratorio.svg" alt="Laboratorio de análisis de leche" class="w-100 h-100 object-fit-cover">
              <span class="position-absolute top-0 end-0 m-3 badge bg-warning text-dark">Sanidad e Inocuidad</span>
            </div>
            <div class="card-body p-4">
              <h4 class="fw-bold text-success mb-2">Laboratorio y Control</h4>
              <p class="text-muted small mb-3">
                Pruebas físico-químicas rutinarias al pie del tanque: mastitis (CMT), densidad con lactodensímetro, alcoholometría al 78% y verificación organoléptica previa al acopio.
              </p>
              <div class="d-flex justify-content-between text-muted small border-top pt-2">
                <span>Parámetro: Grasa y Proteína</span>
                <span class="fw-bold text-success">Cero Antibióticos</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tarjeta 3: Invernaderos y Forrajes -->
        <div class="col-lg-4 col-md-6 reveal-on-scroll">
          <div class="card card-rural h-100">
            <div class="position-relative overflow-hidden" style="height: 220px; background: #e2e8f0;">
              <img src="assets/img/infra-invernadero.svg" alt="Invernaderos y forrajes verdes" class="w-100 h-100 object-fit-cover">
              <span class="position-absolute top-0 end-0 m-3 badge bg-info text-dark">Sostenibilidad</span>
            </div>
            <div class="card-body p-4">
              <h4 class="fw-bold text-success mb-2">Invernaderos y Forrajes</h4>
              <p class="text-muted small mb-3">
                Módulos de forraje verde hidropónico y bancos de pastos de corte resistentes a heladas para mitigar temporadas secas y asegurar la nutrición balanceada del hato lechero.
              </p>
              <div class="d-flex justify-content-between text-muted small border-top pt-2">
                <span>Área: [COMPLETAR] m²</span>
                <span class="fw-bold text-success">Forrajes Limpios</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 6: ENFOQUE BPO (BUENAS PRÁCTICAS DE ORDEÑO)
       ======================================================================== -->
  <section id="enfoque-bpo" class="py-5" style="background: var(--aso-secondary-light);">
    <div class="container py-4">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <span class="badge bg-success text-white fw-bold px-3 py-2 rounded-pill uppercase mb-2">Protocolo Técnico</span>
          <h2 class="display-6 fw-bold text-success">Línea de Proceso BPO</h2>
          <p class="text-muted">Pasos obligatorios de higiene y sanidad implementados en cada una de las fincas afiliadas.</p>
          <div class="mx-auto" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Paso 1 -->
        <div class="col-md-6 col-lg-3 reveal-on-scroll">
          <div class="step-card">
            <div class="step-number-badge">1</div>
            <h5 class="fw-bold text-success mb-2">Higiene y Desinfección</h5>
            <p class="text-muted small mb-0">
              Lavado riguroso de manos del ordeñador y desinfección de pezoneras o cantinas con soluciones autorizadas biodegradables.
            </p>
          </div>
        </div>

        <!-- Paso 2 -->
        <div class="col-md-6 col-lg-3 reveal-on-scroll">
          <div class="step-card">
            <div class="step-number-badge">2</div>
            <h5 class="fw-bold text-success mb-2">Despunte y Pre-sellado</h5>
            <p class="text-muted small mb-0">
              Extracción de los tres primeros chorros en taza de fondo oscuro para detección temprana de mastitis y desinfección preventiva.
            </p>
          </div>
        </div>

        <!-- Paso 3 -->
        <div class="col-md-6 col-lg-3 reveal-on-scroll">
          <div class="step-card">
            <div class="step-number-badge">3</div>
            <h5 class="fw-bold text-success mb-2">Ordeño y Post-sellado</h5>
            <p class="text-muted small mb-0">
              Ordeño a fondo sin maltratar la ubre en turnos matutinos (5:00 AM) y vespertinos (3:00 PM), seguido de sellador barrera.
            </p>
          </div>
        </div>

        <!-- Paso 4 -->
        <div class="col-md-6 col-lg-3 reveal-on-scroll">
          <div class="step-card">
            <div class="step-number-badge">4</div>
            <h5 class="fw-bold text-success mb-2">Filtrado y Cadena de Frío</h5>
            <p class="text-muted small mb-0">
              Colado en filtros de tela lavable o acero inoxidable e ingreso inmediato al tanque de enfriamiento a 4.0 °C en menos de dos horas.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 7: CONTACTO Y UBICACIÓN
       ======================================================================== -->
  <section id="contacto" class="py-5">
    <div class="container py-4">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill uppercase mb-2">Comunícate con Nosotros</span>
          <h2 class="display-6 fw-bold text-success">Ubicación y Canales Oficiales</h2>
          <p class="text-muted">Estamos a tu disposición en la Vereda Alizal de Carmen de Carupa.</p>
          <div class="mx-auto" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Tarjeta de Información y Formulario -->
        <div class="col-lg-6">
          <div class="card card-rural p-4 p-md-5 h-100">
            <h4 class="fw-bold text-success mb-4">Envíanos una Consulta</h4>

            <?php if (!empty($errorContacto)): ?>
              <div class="alert alert-danger rounded-4 py-2 small fw-bold">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo e($errorContacto); ?>
              </div>
            <?php endif; ?>

            <?php if ($mensajeContactoEnviado): ?>
              <div class="alert alert-success rounded-4 py-3 small fw-bold">
                <i class="bi bi-check-circle-fill me-1"></i> ¡Gracias por escribirnos! Tu inquietud ha sido registrada en el sistema.
              </div>
            <?php endif; ?>

            <form action="index.php#contacto" method="POST" class="space-y-3">
              <?php echo campoCsrf(); ?>
              <input type="hidden" name="accion_contacto" value="1">

              <div class="mb-3">
                <label for="nombre" class="form-label fw-bold small text-muted">Nombre Completo *</label>
                <input type="text" class="form-control form-control-touch" id="nombre" name="nombre" required placeholder="Ej: Juan Pérez">
              </div>

              <div class="mb-3">
                <label for="contacto" class="form-label fw-bold small text-muted">Teléfono o Correo Electrónico *</label>
                <input type="text" class="form-control form-control-touch" id="contacto" name="contacto" required placeholder="Ej: 3101234567 o correo@ejemplo.com">
              </div>

              <div class="mb-3">
                <label for="mensaje" class="form-label fw-bold small text-muted">Mensaje o Inquietud *</label>
                <textarea class="form-control form-control-touch" id="mensaje" name="mensaje" rows="4" required placeholder="Escribe tu mensaje..."></textarea>
              </div>

              <button type="submit" class="btn btn-touch btn-aso-primary w-100 shadow">
                <i class="bi bi-send-fill"></i>
                <span>Enviar Consulta</span>
              </button>
            </form>

            <hr class="my-4">

            <!-- Enlaces Directos -->
            <div class="d-flex flex-column gap-3 small text-muted">
              <div>
                <i class="bi bi-geo-alt-fill text-danger fs-5 me-2 align-middle"></i>
                <strong>Sede Principal:</strong> <?php echo e(EMPRESA_UBICACION); ?>
              </div>
              <div>
                <i class="bi bi-envelope-fill text-primary fs-5 me-2 align-middle"></i>
                <strong>Correo:</strong> <a href="mailto:<?php echo e(EMPRESA_CORREO); ?>" class="text-decoration-none text-muted"><?php echo e(EMPRESA_CORREO); ?></a>
              </div>
              <div>
                <i class="bi bi-telephone-fill text-success fs-5 me-2 align-middle"></i>
                <strong>Teléfono:</strong> <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="text-decoration-none text-muted">+57 <?php echo e(EMPRESA_TELEFONO); ?></a>
              </div>
            </div>

            <!-- Botón WhatsApp -->
            <div class="mt-4">
              <a href="https://wa.me/57<?php echo e(EMPRESA_TELEFONO); ?>?text=Hola%20AsoagroAlisal,%20deseo%20m%C3%A1s%20informaci%C3%B3n." target="_blank" rel="noopener noreferrer" class="btn btn-touch w-100 rounded-4 shadow text-white" style="background-color: #25D366; font-size: 1.05rem;">
                <i class="bi bi-whatsapp fs-5"></i>
                <span>Hablar por WhatsApp (+57 <?php echo e(EMPRESA_TELEFONO); ?>)</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Mapa Embebido de Carmen de Carupa / Vereda Alizal -->
        <div class="col-lg-6">
          <div class="card card-rural h-100 overflow-hidden p-2">
            <iframe 
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31742.618685157143!2d-73.93189914447477!3d5.348624136450629!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e404e902b485521%3A0xcdbc901e855734c5!2sCarmen%20de%20Carupa%2C%20Cundinamarca!5e0!3m2!1ses!2sco!4v1714500000000!5m2!1ses!2sco" 
              width="100%" 
              height="100%" 
              style="border:0; min-height: 480px; border-radius: 16px;" 
              allowfullscreen="" 
              loading="lazy" 
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       FOOTER CORPORATIVO
       ======================================================================== -->
  <footer class="py-5" style="background: #0b2914; color: #e2ede6;">
    <div class="container py-3">
      <div class="row g-4 align-items-center">
        <div class="col-lg-6 text-center text-lg-start">
          <div class="d-flex align-items-center gap-3 justify-content-center justify-content-lg-start mb-2">
            <img src="assets/img/logo.png" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" style="max-height: 44px;">
            <h5 class="fw-bold mb-0 text-white"><?php echo e(EMPRESA_SIGLA); ?></h5>
          </div>
          <p class="small opacity-75 mb-1">
            <?php echo e(EMPRESA_NOMBRE); ?>
          </p>
          <p class="small text-warning fw-bold mb-0">NIT: <?php echo e(EMPRESA_NIT); ?> &bull; Carmen de Carupa, Cundinamarca</p>
        </div>

        <div class="col-lg-6 text-center text-lg-end">
          <div class="d-flex gap-3 justify-content-center justify-content-lg-end mb-2">
            <a href="login.php" class="text-white text-decoration-none small hover-underline">Acceso a la Plataforma</a>
            <span class="opacity-50">|</span>
            <a href="#contacto" class="text-white text-decoration-none small hover-underline">Contacto y PQRS</a>
            <span class="opacity-50">|</span>
            <a href="javascript:window.scrollTo({top:0, behavior:'smooth'})" class="text-warning text-decoration-none small hover-underline">Volver arriba ↑</a>
          </div>
          <p class="small opacity-50 mb-0">
            Plataforma de Control Lechero &copy; <?php echo date('Y'); ?>. Todos los derechos reservados.
          </p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Scripts: Bootstrap 5.3 Bundle + SweetAlert2 + App.js -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/app.js"></script>

  <!-- SweetAlert2 Flash Messages -->
  <?php echo renderSweetAlert(); ?>

</body>
</html>
