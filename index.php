<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: index.php
 * Propósito: Landing page corporativa, inmersiva, accesible y 100% fiel a la ficha oficial.
 * Fuente Oficial: docs/FICHA_OFICIAL_ASOAGROALIZAL.md
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$usuarioActual = getCurrentUser();
$tituloPagina = EMPRESA_SIGLA . ' | Control Lechero y Desarrollo Asociativo';

// Manejo de envío síncrono si el navegador no tiene JavaScript activo
$errorContacto = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_contacto'])) {
    if (!empty($_POST['website_hp'])) {
        // Honeypot disparado silenciosamente
        setFlashAlert('success', 'Consulta Recibida', 'Tu mensaje ha sido registrado exitosamente.');
        header('Location: index.php#contacto');
        exit;
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($csrfToken)) {
        setFlashAlert('error', 'Sesión Expirada', 'El token de seguridad ha caducado. Inténtalo de nuevo.');
        header('Location: index.php#contacto');
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (empty($nombre) || empty($contacto) || empty($mensaje)) {
        setFlashAlert('warning', 'Campos Requeridos', 'Por favor diligencia todos los campos obligatorios.');
        header('Location: index.php#contacto');
        exit;
    }

    try {
        $pdo = getDB();
        $conId = generarUUID('con');
        $stmt = $pdo->prepare("INSERT INTO consulta (id, nombre, contacto, mensaje, fecha, atendida) VALUES (?, ?, ?, ?, NOW(), 0)");
        $stmt->execute([$conId, $nombre, $contacto, $mensaje]);
        setFlashAlert('success', '¡Consulta Enviada!', 'Gracias por comunicarte con ASOAGROALIZAL. Nuestro equipo atenderá tu solicitud.');
        header('Location: index.php#contacto');
        exit;
    } catch (Exception $e) {
        error_log("Error guardando consulta en index.php: " . $e->getMessage());
        setFlashAlert('error', 'Error al Enviar', 'Ocurrió una eventualidad procesando tu solicitud. Por favor intenta más tarde.');
        header('Location: index.php#contacto');
        exit;
    }
}

// Carga del encabezado unificado
require_once __DIR__ . '/includes/header.php';
?>

<style>
  /* Hero Visual y Estilos Específicos de Landing */
  .hero-section {
    padding-top: 5rem;
    padding-bottom: 5rem;
    background: radial-gradient(circle at 85% 15%, rgba(96, 196, 248, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 10% 85%, rgba(245, 158, 11, 0.12) 0%, transparent 40%),
                linear-gradient(180deg, rgba(232, 245, 233, 0.6) 0%, var(--bs-body-bg) 100%);
  }

  [data-bs-theme="dark"] .hero-section {
    background: radial-gradient(circle at 85% 15%, rgba(96, 196, 248, 0.08) 0%, transparent 45%),
                radial-gradient(circle at 10% 85%, rgba(245, 158, 11, 0.06) 0%, transparent 40%),
                linear-gradient(180deg, #131c16 0%, var(--bs-body-bg) 100%);
  }

  .hero-badge {
    background: var(--aso-secondary-light);
    border: 1px solid rgba(46, 125, 50, 0.25);
    color: var(--aso-primary);
    font-weight: 700;
    font-size: 0.85rem;
    padding: 0.4rem 1rem;
    border-radius: var(--radius-pill);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  [data-bs-theme="dark"] .hero-badge {
    background: #18291f;
    color: var(--aso-secondary);
    border-color: #24352b;
  }

  .hero-emblem-card {
    background: var(--card-bg);
    border: 2px solid var(--card-border);
    border-radius: 28px;
    padding: 2.5rem 2rem;
    box-shadow: var(--shadow-lg);
    text-align: center;
    position: relative;
  }

  .hero-logo-img {
    max-width: 250px;
    width: 100%;
    height: auto;
    filter: drop-shadow(0 12px 24px rgba(20, 71, 34, 0.20));
    transition: transform 0.4s ease;
  }

  .hero-logo-img:hover {
    transform: scale(1.03);
  }

  .bento-container {
    background: var(--bs-body-bg);
  }

  .bento-highlight {
    background: linear-gradient(145deg, rgba(20, 71, 34, 0.05) 0%, rgba(46, 125, 50, 0.12) 100%);
    border: 1px solid rgba(46, 125, 50, 0.3);
  }

  [data-bs-theme="dark"] .bento-highlight {
    background: linear-gradient(145deg, rgba(20, 71, 34, 0.25) 0%, rgba(46, 125, 50, 0.15) 100%);
    border-color: rgba(74, 222, 128, 0.25);
  }

  .bpo-step-badge {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--aso-primary);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 800;
    margin-bottom: 1rem;
    box-shadow: 0 4px 10px rgba(20, 71, 34, 0.25);
  }

  .ally-pill {
    background-color: var(--card-bg);
    border: 1px solid var(--card-border);
    padding: 1rem 1.75rem;
    border-radius: var(--radius-pill);
    font-weight: 700;
    color: var(--bs-body-color);
    box-shadow: var(--shadow-sm);
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
  }

  .honeypot-field {
    display: none !important;
    visibility: hidden !important;
    position: absolute !important;
    left: -9999px !important;
  }
</style>

<main id="contenido-principal">

  <!-- ========================================================================
       SECCIÓN 1: HERO ASIMÉTRICO CON DOBLE LLAMADO A LA ACCIÓN
       ======================================================================== -->
  <section class="hero-section position-relative overflow-hidden">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-7 text-center text-lg-start">
          <div class="hero-badge mb-3">
            <i class="bi bi-patch-check-fill text-success"></i>
            <span>ECONOMÍA CAMPESINA &bull; CARMEN DE CARUPA</span>
          </div>

          <h1 class="display-4 fw-extrabold text-success mb-3" style="line-height: 1.15;">
            Control Lechero y Progreso Asociativo en la Vereda <span class="text-warning">Alizal</span>
          </h1>

          <p class="lead text-muted mb-4 fs-5" style="max-width: 620px;">
            Plataforma oficial de <strong><?php echo e(EMPRESA_SIGLA); ?></strong> para el registro sistemático de pesajes diarios, liquidaciones quincenales transparentes y fomento de Buenas Prácticas de Ordeño.
          </p>

          <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start mb-5">
            <?php if ($usuarioActual): ?>
              <?php $urlDestino = ($usuarioActual['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
              <a href="<?php echo $urlDestino; ?>" class="btn btn-touch btn-aso-primary btn-lg px-4 shadow">
                <i class="bi bi-speedometer2"></i>
                <span>Ir a Mi Panel de Control</span>
              </a>
            <?php else: ?>
              <a href="login.php" class="btn btn-touch btn-aso-primary btn-lg px-4 shadow">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Ingresar a la Plataforma</span>
              </a>
            <?php endif; ?>
            
            <a href="#quienes-somos" class="btn btn-touch btn-outline-success btn-lg px-4 border-2">
              <i class="bi bi-info-circle"></i>
              <span>Conocer Más</span>
            </a>
          </div>

          <!-- Métricas Destacadas Oficiales -->
          <div class="row g-3 border-top border-success-subtle pt-4 text-start">
            <div class="col-6 col-sm-4">
              <div class="fw-bold fs-3 text-success counter-animated" data-target="<?php echo e(EMPRESA_ASOCIADOS_TOTAL); ?>">0</div>
              <small class="text-muted fw-semibold d-block">Asociados Vinculados</small>
            </div>
            <div class="col-6 col-sm-4">
              <div class="fw-bold fs-3 text-success">25+ Años</div>
              <small class="text-muted fw-semibold d-block">Trayectoria (Año 2000)</small>
            </div>
            <div class="col-12 col-sm-4">
              <div class="fw-bold fs-3 text-warning">2 Turnos</div>
              <small class="text-muted fw-semibold d-block">Mañana ☀️ y Tarde 🌅</small>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="hero-emblem-card reveal-on-scroll">
            <img src="assets/img/logo.png" alt="Emblema Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="hero-logo-img mb-3">
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
          <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill uppercase mb-2">Identidad y Personería</span>
          <h2 class="display-6 fw-bold text-success">Quiénes Somos</h2>
          <div class="mx-auto" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <div class="row align-items-stretch g-4">
        <div class="col-md-6 reveal-on-scroll">
          <div class="card card-rural p-4 p-lg-5 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-4 bg-success text-white p-3 d-flex align-items-center justify-content-center fs-3" style="width: 56px; height: 56px;">
                <i class="bi bi-bank2"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-0">Razón Social y Naturaleza</h5>
                <small class="text-muted">Constitución Legal y Formal</small>
              </div>
            </div>
            <p class="text-muted mb-3">
              Somos legalmente la <strong><?php echo e(EMPRESA_NOMBRE); ?></strong>, identificada con NIT <strong><?php echo e(EMPRESA_NIT); ?></strong>, con personería jurídica y reconocida bajo la sigla <strong><?php echo e(EMPRESA_SIGLA); ?></strong>.
            </p>
            <p class="text-muted mb-0">
              Constituida en el año <strong><?php echo e(EMPRESA_ANO_CONSTITUCION); ?></strong>, la asociación acumula más de 25 años de trayectoria ininterrumpida, consolidando la unión solidaria de las familias trabajadoras del campo en Carmen de Carupa, Cundinamarca.
            </p>
          </div>
        </div>

        <div class="col-md-6 reveal-on-scroll">
          <div class="card card-rural p-4 p-lg-5 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-4 bg-warning text-dark p-3 d-flex align-items-center justify-content-center fs-3" style="width: 56px; height: 56px;">
                <i class="bi bi-geo-alt-fill"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-0">Arraigo y Territorio</h5>
                <small class="text-muted">Carmen de Carupa, Cundinamarca</small>
              </div>
            </div>
            <p class="text-muted mb-3">
              Nuestra sede se encuentra ubicada en la <strong>Vereda Alizal, Finca El Recuerdo</strong>. Operamos en un ecosistema andino de alta montaña propicio para el pastoreo sostenible y el bienestar de los hatos lecheros.
            </p>
            <p class="text-muted mb-0">
              La asociación canaliza la producción lechera comunitaria mediante pesajes sistemáticos por turnos, facilitando a cada productor un registro ordenado y liquidaciones equitativas con estricto sentido cooperativo.
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
              Cada entrega de leche diaria refleja el esfuerzo, la constancia y la vocación lechera de nuestras familias asociadas en la vereda.
            </p>

            <div class="d-inline-flex flex-column align-items-center justify-content-center p-4 rounded-4 mb-4" style="background: var(--aso-primary); color: #ffffff; min-width: 250px; box-shadow: var(--shadow-md);">
              <span class="display-2 fw-extrabold text-warning counter-animated" data-target="<?php echo e(EMPRESA_ASOCIADOS_TOTAL); ?>">0</span>
              <span class="fs-5 fw-bold text-uppercase tracking-wider">Asociados Vinculados</span>
              <small class="opacity-75">Productores de la Vereda Alizal</small>
            </div>

            <p class="small text-muted mb-0">
              <i class="bi bi-shield-check text-success me-1"></i> Participación democrática, equidad en las liquidaciones quincenales y acompañamiento técnico constante.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 4: MISIÓN Y VISIÓN (TEXTOS EXACTOS DE LA FICHA OFICIAL)
       ======================================================================== -->
  <section id="mision-vision" class="py-5">
    <div class="container py-4">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill uppercase mb-2">Horizonte Institucional</span>
          <h2 class="display-6 fw-bold text-success">Misión y Visión</h2>
          <div class="mx-auto mt-2" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <div class="row g-4 align-items-stretch">
        <div class="col-md-6 reveal-on-scroll">
          <div class="card card-rural p-4 p-lg-5 h-100 border-top border-4 border-success">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-success text-white p-3 d-flex align-items-center justify-content-center fs-3" style="width: 56px; height: 56px;">
                <i class="bi bi-compass"></i>
              </div>
              <h3 class="fw-bold mb-0 text-success">Misión</h3>
            </div>
            <p class="text-muted fs-5 leading-relaxed mb-0">
              Asoagroalizal es una organización que busca unir esfuerzos para la producción sostenible de alimentos que permita el mejoramiento de la calidad de vida de las familias de la vereda de Alizal.
            </p>
          </div>
        </div>

        <div class="col-md-6 reveal-on-scroll">
          <div class="card card-rural p-4 p-lg-5 h-100 border-top border-4 border-warning">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-warning text-dark p-3 d-flex align-items-center justify-content-center fs-3" style="width: 56px; height: 56px;">
                <i class="bi bi-eye-fill"></i>
              </div>
              <h3 class="fw-bold mb-0 text-success">Visión</h3>
            </div>
            <p class="text-muted fs-5 leading-relaxed mb-0">
              Para el año 2030 Asoagroalizal busca llegar a transformar el 50% de su producción láctea y darle valor agregado a otros productos como la papa y los tubérculos andinos, así como buscar nuevos mercados que le generen ingresos a los asociados y sus familias.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 5: INFRAESTRUCTURA PRODUCTIVA (GRID TIPO BENTO)
       ======================================================================== -->
  <section id="infraestructura" class="py-5 bento-container">
    <div class="container py-4">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill uppercase mb-2">Capacidad y Recursos</span>
          <h2 class="display-6 fw-bold text-success">Infraestructura y Equipamiento</h2>
          <p class="text-muted">Equipos y activos comunitarios al servicio de la producción agropecuaria de la vereda.</p>
          <div class="mx-auto" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <!-- Bento Grid Responsive -->
      <div class="row g-4">
        <!-- Bento 1 (Grande): Tanques de Enfriamiento -->
        <div class="col-lg-8 reveal-on-scroll">
          <div class="bento-card bento-highlight">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
              <div class="bento-icon-wrapper bg-success text-white">
                <i class="bi bi-snow2"></i>
              </div>
              <span class="badge bg-success px-3 py-2 rounded-pill">Cadena de Frío Colectiva</span>
            </div>
            <div>
              <h4 class="fw-bold text-success mb-2">Tanques de Enfriamiento</h4>
              <p class="text-muted mb-0">
                Instalaciones dedicadas a la recepción y conservación inmediata de la leche proveniente de los ordeños matutino y vespertino. Mantienen la inocuidad y detienen el desarrollo de acidez en el producto antes de su recolección industrial.
              </p>
            </div>
          </div>
        </div>

        <!-- Bento 2: Laboratorio de Cultivo de Tejidos -->
        <div class="col-lg-4 col-md-6 reveal-on-scroll">
          <div class="bento-card">
            <div class="bento-icon-wrapper bg-warning text-dark">
              <i class="bi bi-flower1"></i>
            </div>
            <div>
              <h5 class="fw-bold text-success mb-2">Laboratorio de Tejidos</h5>
              <p class="text-muted small mb-0">
                Espacio técnico orientado a la propagación vegetal, sanidad de semillas y mejoramiento de cultivos andinos para la sostenibilidad agrícola de la comunidad.
              </p>
            </div>
          </div>
        </div>

        <!-- Bento 3: Invernaderos -->
        <div class="col-lg-4 col-md-6 reveal-on-scroll">
          <div class="bento-card">
            <div class="bento-icon-wrapper bg-info text-dark">
              <i class="bi bi-houses-fill"></i>
            </div>
            <div>
              <h5 class="fw-bold text-success mb-2">Invernaderos</h5>
              <p class="text-muted small mb-0">
                Ambientes protegidos para el desarrollo de cultivos controlados, producción tecnificada y adaptación agroclimática de plántulas frente a las heladas de la zona.
              </p>
            </div>
          </div>
        </div>

        <!-- Bento 4: Cantinas y Equipos de Recolección -->
        <div class="col-lg-4 col-md-6 reveal-on-scroll">
          <div class="bento-card">
            <div class="bento-icon-wrapper bg-secondary text-white">
              <i class="bi bi-bucket-fill"></i>
            </div>
            <div>
              <h5 class="fw-bold text-success mb-2">Cantinas de Transporte</h5>
              <p class="text-muted small mb-0">
                Recipientes sanitarios para el traslado higiénico de la leche recién ordeñada desde cada finca hasta los tanques de recepción veredal.
              </p>
            </div>
          </div>
        </div>

        <!-- Bento 5: Nevera de Muestras -->
        <div class="col-lg-4 col-md-6 reveal-on-scroll">
          <div class="bento-card">
            <div class="bento-icon-wrapper bg-primary text-white">
              <i class="bi bi-thermometer-snow"></i>
            </div>
            <div>
              <h5 class="fw-bold text-success mb-2">Nevera de Conservación</h5>
              <p class="text-muted small mb-0">
                Refrigeración para la preservación de reactivos, muestras biológicas y suministros sanitarios de control veterinario.
              </p>
            </div>
          </div>
        </div>

        <!-- Bento 6: Carguero (Logística Veredal) -->
        <div class="col-lg-6 col-md-6 reveal-on-scroll">
          <div class="bento-card">
            <div class="bento-icon-wrapper bg-warning text-dark">
              <i class="bi bi-truck"></i>
            </div>
            <div>
              <h5 class="fw-bold text-success mb-2">Carguero Veredal</h5>
              <p class="text-muted small mb-0">
                Vehículo de transporte y apoyo logístico para la movilización de insumos, cantinas y forrajes entre las fincas y el punto central de acopio en la Vereda Alizal.
              </p>
            </div>
          </div>
        </div>

        <!-- Bento 7: Terreno Propio -->
        <div class="col-lg-6 col-md-6 reveal-on-scroll">
          <div class="bento-card">
            <div class="bento-icon-wrapper bg-success text-white">
              <i class="bi bi-signpost-2-fill"></i>
            </div>
            <div>
              <h5 class="fw-bold text-success mb-2">Terreno Propio</h5>
              <p class="text-muted small mb-0">
                Predio perteneciente a la asociación en la Finca El Recuerdo, base territorial para la consolidación de la sede comunitaria y futuros proyectos de transformación láctea.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 6: ENFOQUE BPO (LÍNEAS DE TRABAJO EN CALIDAD DE LECHE)
       ======================================================================== -->
  <section id="enfoque-bpo" class="py-5" style="background: var(--aso-secondary-light);">
    <div class="container py-4">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <span class="badge bg-success text-white fw-bold px-3 py-2 rounded-pill uppercase mb-2">Enfoque Operativo</span>
          <h2 class="display-6 fw-bold text-success">Líneas de Trabajo en Calidad de Leche (BPO)</h2>
          <p class="text-muted">Directrices y buenas prácticas promovidas en los predios de nuestros asociados.</p>
          <div class="mx-auto" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Paso 1 -->
        <div class="col-md-6 col-lg-3 reveal-on-scroll">
          <div class="bpo-step-card">
            <div class="bpo-step-badge">1</div>
            <h5 class="fw-bold text-success mb-2">Higiene en el Ordeño</h5>
            <p class="text-muted small mb-0">
              Protocolos de aseo personal del ordeñador, limpieza cuidadosa de pezones y desinfección preventiva del instrumental de ordeño.
            </p>
          </div>
        </div>

        <!-- Paso 2 -->
        <div class="col-md-6 col-lg-3 reveal-on-scroll">
          <div class="bpo-step-card">
            <div class="bpo-step-badge">2</div>
            <h5 class="fw-bold text-success mb-2">Filtrado y Aseo</h5>
            <p class="text-muted small mb-0">
              Filtrado higiénico inmediato del líquido en lienzos o mallas sanitarias para eliminar impurezas físicas tras la recolección.
            </p>
          </div>
        </div>

        <!-- Paso 3 -->
        <div class="col-md-6 col-lg-3 reveal-on-scroll">
          <div class="bpo-step-card">
            <div class="bpo-step-badge">3</div>
            <h5 class="fw-bold text-success mb-2">Conservación en Frío</h5>
            <p class="text-muted small mb-0">
              Traslado expedito hacia los tanques comunitarios para ingresar el producto al sistema de refrigeración y proteger sus cualidades organolépticas.
            </p>
          </div>
        </div>

        <!-- Paso 4 -->
        <div class="col-md-6 col-lg-3 reveal-on-scroll">
          <div class="bpo-step-card">
            <div class="bpo-step-badge">4</div>
            <h5 class="fw-bold text-success mb-2">Registro y Trazabilidad</h5>
            <p class="text-muted small mb-0">
              Anotación sistemática del volumen entregado en cada turno (mañana y tarde) en la plataforma para control y transparencia comunitaria.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 7: ALIADOS INSTITUCIONALES (SOLO TEXTO OFICIAL)
       ======================================================================== -->
  <section id="aliados" class="py-5">
    <div class="container py-3">
      <div class="row justify-content-center text-center mb-4">
        <div class="col-lg-8">
          <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill uppercase mb-2">Cooperación y Alianzas</span>
          <h2 class="display-6 fw-bold text-success">Aliados Estratégicos</h2>
          <p class="text-muted">Organizaciones que han acompañado procesos de fortalecimiento y desarrollo asociativo.</p>
        </div>
      </div>

      <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 reveal-on-scroll">
        <div class="ally-pill">
          <i class="bi bi-people-fill text-success fs-4"></i>
          <span>Corporación PBA</span>
        </div>
        <div class="ally-pill">
          <i class="bi bi-award-fill text-warning fs-4"></i>
          <span>Fundación Bolívar Davivienda</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       SECCIÓN 8: CONTACTO Y UBICACIÓN
       ======================================================================== -->
  <section id="contacto" class="py-5" style="background: var(--bs-body-bg);">
    <div class="container py-4">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill uppercase mb-2">Canales Oficiales</span>
          <h2 class="display-6 fw-bold text-success">Contacto y Atención</h2>
          <p class="text-muted">Comunícate con la administración de ASOAGROALIZAL o visítanos en la Vereda Alizal.</p>
          <div class="mx-auto" style="width: 70px; height: 4px; background: var(--aso-accent); border-radius: 2px;"></div>
        </div>
      </div>

      <div class="row g-4 align-items-stretch">
        <!-- Columna 1: Formulario con Floating Labels -->
        <div class="col-lg-6 reveal-on-scroll">
          <div class="card card-rural p-4 p-lg-5 h-100">
            <h4 class="fw-bold text-success mb-4">Radicar una Consulta o Mensaje</h4>

            <form id="formContacto" action="index.php#contacto" method="POST">
              <?php echo campoCsrf(); ?>
              <input type="hidden" name="accion_contacto" value="1">
              
              <!-- Honeypot anti-spam invisible para humanos -->
              <input type="text" name="website_hp" class="honeypot-field" tabindex="-1" autocomplete="off">

              <!-- Floating Label 1: Nombre -->
              <div class="form-floating mb-3">
                <input type="text" class="form-control form-control-touch" id="cNombre" name="nombre" placeholder="Nombre completo" required minlength="3">
                <label for="cNombre" class="text-muted">Nombre completo *</label>
              </div>

              <!-- Floating Label 2: Contacto -->
              <div class="form-floating mb-3">
                <input type="text" class="form-control form-control-touch" id="cContacto" name="contacto" placeholder="Teléfono o correo" required minlength="6">
                <label for="cContacto" class="text-muted">Teléfono celular o correo de contacto *</label>
              </div>

              <!-- Floating Label 3: Mensaje -->
              <div class="form-floating mb-4">
                <textarea class="form-control form-control-touch" id="cMensaje" name="mensaje" placeholder="Mensaje" style="height: 130px;" required minlength="10"></textarea>
                <label for="cMensaje" class="text-muted">Mensaje o inquietud *</label>
              </div>

              <button type="submit" id="btnEnviarContacto" class="btn btn-touch btn-aso-primary w-100 shadow">
                <i class="bi bi-send-fill"></i>
                <span>Enviar Mensaje</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Columna 2: Datos Oficiales y Mapa Embebido -->
        <div class="col-lg-6 reveal-on-scroll">
          <div class="card card-rural p-4 p-lg-5 h-100 d-flex flex-column justify-content-between">
            <div>
              <h4 class="fw-bold text-success mb-4">Datos Institucionales</h4>

              <div class="d-flex flex-column gap-3 mb-4">
                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-geo-alt-fill fs-5"></i>
                  </div>
                  <div>
                    <span class="d-block fw-bold small text-muted">Sede Operativa</span>
                    <span class="fw-semibold"><?php echo e(EMPRESA_UBICACION); ?></span>
                  </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-telephone-fill fs-5"></i>
                  </div>
                  <div>
                    <span class="d-block fw-bold small text-muted">Teléfono Único Oficial</span>
                    <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="fw-semibold text-decoration-none text-success">
                      +57 <?php echo e(EMPRESA_TELEFONO); ?>
                    </a>
                  </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-envelope-fill fs-5"></i>
                  </div>
                  <div>
                    <span class="d-block fw-bold small text-muted">Correo Electrónico</span>
                    <a href="mailto:<?php echo e(EMPRESA_CORREO); ?>" class="fw-semibold text-decoration-none text-success">
                      <?php echo e(EMPRESA_CORREO); ?>
                    </a>
                  </div>
                </div>
              </div>

              <!-- Integración Condicional de WhatsApp según WHATSAPP_ACTIVO -->
              <?php if (WHATSAPP_ACTIVO): ?>
                <div class="mb-4">
                  <a href="https://wa.me/57<?php echo e(EMPRESA_TELEFONO); ?>?text=Hola%20AsoagroAlisal,%20deseo%20comunicarme%20con%20ustedes." target="_blank" rel="noopener noreferrer" class="btn btn-touch w-100 rounded-pill shadow text-white" style="background-color: #25D366;">
                    <i class="bi bi-whatsapp fs-5"></i>
                    <span>Contactar por WhatsApp</span>
                  </a>
                </div>
              <?php endif; ?>
            </div>

            <!-- Mapa Embebido de la Vereda Alizal / Carmen de Carupa -->
            <div class="rounded-4 overflow-hidden border border-success-subtle" style="height: 240px;">
              <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31742.618685157143!2d-73.93189914447477!3d5.348624136450629!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e404e902b485521%3A0xcdbc901e855734c5!2sCarmen%20de%20Carupa%2C%20Cundinamarca!5e0!3m2!1ses!2sco!4v1714500000000!5m2!1ses!2sco" 
                width="100%" 
                height="100%" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                title="Mapa de ubicación Vereda Alizal, Carmen de Carupa">
              </iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<script>
  // Manejo de envío asíncrono para el formulario de contacto con SweetAlert2
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formContacto');
    const btn = document.getElementById('btnEnviarContacto');

    if (form && btn) {
      form.addEventListener('submit', async function (e) {
        // Si Fetch API está disponible, enviar asíncronamente a api/consultas.php
        if (window.fetch && window.AsoagroUI) {
          e.preventDefault();

          const nombre = document.getElementById('cNombre').value.trim();
          const contacto = document.getElementById('cContacto').value.trim();
          const mensaje = document.getElementById('cMensaje').value.trim();
          const honeypot = form.querySelector('input[name="website_hp"]').value;
          const csrf = form.querySelector('input[name="csrf_token"]').value;

          btn.disabled = true;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Enviando...';

          try {
            const resp = await fetch('api/consultas.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                nombre: nombre,
                contacto: contacto,
                mensaje: mensaje,
                website_hp: honeypot,
                csrf_token: csrf
              })
            });

            const data = await resp.json();

            if (data && data.success) {
              window.AsoagroUI.showSuccess('¡Consulta Enviada!', data.message || 'Tu inquietud ha sido registrada en el sistema.');
              form.reset();
            } else {
              window.AsoagroUI.showError('No se pudo enviar', data.message || 'Por favor revisa los datos e intenta de nuevo.');
            }
          } catch (err) {
            // Fallback: Si la API arroja error de conexión o parseo, enviar el form normalmente
            form.submit();
          } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> <span>Enviar Mensaje</span>';
          }
        }
      });
    }
  });
</script>

<?php
// Pie de página unificado
require_once __DIR__ . '/includes/footer.php';
?>
