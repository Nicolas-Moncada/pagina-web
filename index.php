<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: index.php
 * Propósito: Landing page corporativa institucional accesible para adultos mayores rurales:
 *            - Tipografía Atkinson Hyperlegible, texto base 20px, botones grandes de 56px.
 *            - Paleta Premium: Azul marino profundo, crema cálido y blanco sólido.
 *            - Cero glassmorphism: Tarjetas blancas sólidas con sombras suaves difuminadas.
 *            - Etiquetas de formulario siempre visibles sobre el input.
 *            - Totalmente responsive: Se apila a 1 columna en teléfonos celulares.
 * Fuente de Datos Oficial: docs/FICHA_OFICIAL_ASOAGROALIZAL.md
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$usuarioActual = getCurrentUser();
$tituloPagina = EMPRESA_SIGLA . ' · Control Lechero y Desarrollo Asociativo | Carmen de Carupa';

// Manejo de envío de formulario de contacto (Fallback síncrono si JS está inactivo)
$errorContacto = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_contacto'])) {
    if (!empty($_POST['website_hp'])) {
        // Honeypot anti-spam
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
    $aceptaDatos = !empty($_POST['acepta_datos']);

    if (empty($nombre) || empty($contacto) || empty($mensaje) || !$aceptaDatos) {
        setFlashAlert('warning', 'Campos Requeridos', 'Por favor diligencia todos los campos obligatorios y autoriza el tratamiento de datos.');
        header('Location: index.php#contacto');
        exit;
    }

    try {
        $pdo = getDB();
        $conId = generarUUID('con');
        $stmt = $pdo->prepare("INSERT INTO consulta (id, nombre, contacto, mensaje, fecha, atendida) VALUES (?, ?, ?, ?, NOW(), 0)");
        $stmt->execute([$conId, $nombre, $contacto, $mensaje]);
        setFlashAlert('success', '¡Consulta Enviada!', 'Gracias por comunicarte con ASOAGROALIZAL. Tu mensaje ha sido registrado.');
        header('Location: index.php#contacto');
        exit;
    } catch (Exception $e) {
        error_log("Error guardando consulta en index.php: " . $e->getMessage());
        setFlashAlert('error', 'Error al Enviar', 'Ocurrió una eventualidad procesando tu solicitud. Por favor intenta más tarde.');
        header('Location: index.php#contacto');
        exit;
    }
}

// Inclusión del encabezado global unificado
require_once __DIR__ . '/includes/header.php';
?>

<main id="contenido-principal">

  <!-- ========================================================================
       1. HÉROE PRINCIPAL SÓLIDO EN AZUL MARINO (ALTO CONTRASTE, CERO GLASSMORPHISM)
       ======================================================================== -->
  <section class="hero-grad text-white position-relative">
    <span class="hero-blob blob-1" aria-hidden="true"></span>
    <span class="hero-blob blob-2" aria-hidden="true"></span>
    <span class="hero-blob blob-3" aria-hidden="true"></span>
    <div class="container py-4 position-relative">
      
      <div class="row align-items-center g-5 mb-5">
        <div class="col-lg-8 reveal">
          <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill mb-3 fw-bold" style="background-color: rgba(255, 255, 255, 0.15); border: 1.5px solid rgba(255, 255, 255, 0.3);">
            <i class="bi bi-geo-alt-fill text-warning"></i>
            <span class="text-white"><?php echo e(EMPRESA_SIGLA); ?> &bull; Carmen de Carupa, Cundinamarca</span>
          </div>

          <h1 class="display-4 fw-bold text-white mb-3" style="line-height: 1.18;">
            Uniendo Esfuerzos para la Producción Sostenible y el Progreso Campesino
          </h1>

          <p class="text-white mb-4" style="font-size: 1.25rem; line-height: 1.6; opacity: 0.95; max-width: 760px;">
            Plataforma oficial de control lechero, pesajes diarios sistemáticos por turnos (☀️ Mañana y 🌅 Tarde) y fomento de Buenas Prácticas de Ordeño en la Vereda Alizal.
          </p>

          <div class="d-flex flex-wrap gap-3 align-items-center">
            <?php if ($usuarioActual): ?>
              <?php $urlDestino = ($usuarioActual['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
              <a href="<?php echo $urlDestino; ?>" class="btn btn-touch btn-aso-accent fs-5 shadow">
                <i class="bi bi-speedometer2 fs-4"></i>
                <span>Mi Panel de Control</span>
              </a>
            <?php else: ?>
              <a href="login.php" class="btn btn-touch btn-aso-accent fs-5 shadow">
                <i class="bi bi-box-arrow-in-right fs-4"></i>
                <span>Acceso al Sistema</span>
              </a>
            <?php endif; ?>

            <a href="#quienes-somos" class="btn btn-touch btn-outline-light fs-5" style="border: 2px solid #ffffff;">
              <i class="bi bi-info-circle fs-4"></i>
              <span>Conocer la Asociación</span>
            </a>
          </div>
        </div>

        <div class="col-lg-4 text-center reveal" style="--reveal-delay: 140ms;">
          <div class="card p-4 rounded-4 shadow-lg border-2 text-dark" style="background-color: #ffffff; border-color: var(--border);">
            <img src="assets/img/logo.png" alt="Emblema Institucional <?php echo e(EMPRESA_SIGLA); ?>" class="img-fluid mx-auto mb-3" style="max-height: 200px;">
            <h4 class="fw-bold mb-1 text-primary"><?php echo e(EMPRESA_SIGLA); ?></h4>
            <span class="text-muted d-block mb-3" style="font-size: 0.95rem;">Vereda Alizal &bull; Finca El Recuerdo</span>
            <span class="badge px-3 py-2 rounded-pill fw-bold text-white fs-6" style="background-color: var(--primary);">
              NIT: <?php echo e(EMPRESA_NIT); ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Tarjetas de Datos Rápidos Destacados -->
      <div class="row g-3 pt-3">
        <div class="col-md-4">
          <div class="card p-3 rounded-4 shadow-sm border-0 text-dark h-100" style="background-color: #ffffff;">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 56px; height: 56px; background-color: var(--primary); font-size: 1.5rem;">
                <i class="bi bi-people-fill"></i>
              </div>
              <div>
                <span class="display-6 fw-bold text-primary d-block counter-animated" data-target="32">32</span>
                <span class="fw-bold text-dark">Asociados Activos</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card p-3 rounded-4 shadow-sm border-0 text-dark h-100" style="background-color: #ffffff;">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 56px; height: 56px; background-color: var(--accent); font-size: 1.5rem;">
                <i class="bi bi-award-fill"></i>
              </div>
              <div>
                <span class="display-6 fw-bold text-dark d-block counter-animated" data-target="25" data-suffix="+">25+</span>
                <span class="fw-bold text-dark">Constituida en el Año 2000</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card p-3 rounded-4 shadow-sm border-0 text-dark h-100" style="background-color: #ffffff;">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 56px; height: 56px; background-color: var(--primary); font-size: 1.5rem;">
                <i class="bi bi-clock-history"></i>
              </div>
              <div>
                <span class="display-6 fw-bold text-primary d-block">2 <small class="fw-bold fs-4">Turnos</small></span>
                <span class="fw-bold text-dark">☀️ Mañana y 🌅 Tarde</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- Ola divisoria entre héroe y sección de misión (decorativa) -->
  <svg class="wave-divider" viewBox="0 0 1440 64" preserveAspectRatio="none" aria-hidden="true">
    <path d="M0,34 C240,62 460,6 720,18 C980,30 1220,10 1440,30 L1440,64 L0,64 Z" fill="#eee9dd"></path>
  </svg>

  <!-- ========================================================================
       2. MISIÓN Y VISIÓN (TARJETAS BLANCAS SÓLIDAS DE ALTO CONTRASTE)
       ======================================================================== -->
  <section id="mision-vision" class="py-5" style="background-color: var(--surface-2);">
    <div class="container py-4">
      
      <div class="text-center mb-5">
        <span class="section-eyebrow">Direccionamiento Estratégico</span>
        <h2 class="section-title">Misión y Visión Oficial</h2>
        <p class="section-lead mx-auto">
          Principios y horizonte institucional aprobados por la junta directiva y asociados de ASOAGROALIZAL.
        </p>
      </div>

      <div class="row g-4">
        <div class="col-md-6">
          <div class="card card-rural border-2 h-100" style="border-top: 6px solid var(--primary) !important;">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: var(--primary); font-size: 1.4rem;">
                <i class="bi bi-compass"></i>
              </div>
              <div>
                <small class="text-muted fw-bold text-uppercase">Texto Oficial</small>
                <h3 class="fw-bold text-primary mb-0">Misión Institucional</h3>
              </div>
            </div>
            <p class="fs-5 text-dark mb-0" style="line-height: 1.7;">
              "Asoagroalizal es una organización que busca unir esfuerzos para la producción sostenible de alimentos que permita el mejoramiento de la calidad de vida de las familias de la vereda de Alizal."
            </p>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card card-rural border-2 h-100" style="border-top: 6px solid var(--accent) !important;">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: var(--accent); font-size: 1.4rem;">
                <i class="bi bi-eye"></i>
              </div>
              <div>
                <small class="text-muted fw-bold text-uppercase">Horizonte 2030</small>
                <h3 class="fw-bold mb-0" style="color: var(--accent);">Visión Oficial</h3>
              </div>
            </div>
            <p class="fs-5 text-dark mb-0" style="line-height: 1.7;">
              "Para el año 2030 Asoagroalizal busca llegar a transformar el 50% de su producción láctea y darle valor agregado a otros productos como la papa y los tubérculos andinos, así como buscar nuevos mercados que le generen ingresos a los asociados y sus familias."
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       3. SECCIÓN QUIÉNES SOMOS (TARJETAS BLANCAS SÓLIDAS)
       ======================================================================== -->
  <section id="quienes-somos" class="py-5">
    <div class="container py-lg-4">
      <div class="row align-items-center g-5">
        
        <div class="col-lg-6">
          <div class="card card-rural p-4 p-md-5 text-center d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: #ffffff;">
            <img src="assets/img/logo.png" alt="Emblema Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="img-fluid mb-4" style="max-height: 220px;">
            <div class="badge px-4 py-2.5 rounded-pill fw-bold fs-6" style="background-color: var(--surface-2); color: var(--primary); border: 2px solid var(--border);">
              Personería Jurídica &bull; Cámara de Comercio
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <span class="section-eyebrow">Trayectoria Asociativa</span>
          <h2 class="section-title">Más de 25 Años de Vocación y Trabajo Solidario</h2>
          <p class="fs-5 mb-4" style="line-height: 1.7;">
            Somos legalmente la <strong><?php echo e(EMPRESA_NOMBRE); ?></strong>, identificada con NIT <strong><?php echo e(EMPRESA_NIT); ?></strong>, constituida legalmente en el año <strong><?php echo e(EMPRESA_ANO_CONSTITUCION); ?></strong> y reconocida bajo la sigla <strong><?php echo e(EMPRESA_SIGLA); ?></strong>.
          </p>
          <p class="text-muted fs-5 mb-4" style="line-height: 1.7;">
            Fundada en el municipio de Carmen de Carupa, la asociación reúne a 32 familias campesinas dedicadas a la ganadería de leche de alta montaña y a la agricultura andina, consolidando una economía solidaria transparente basada en pesajes exactos, liquidaciones quincenales justas y apoyo mutuo comunitario.
          </p>

          <div class="card p-4 rounded-4 border-2" style="background-color: var(--surface-2); border-color: var(--border);">
            <div class="d-flex align-items-center gap-4">
              <span class="display-3 fw-bold text-primary counter-animated" data-target="32">32</span>
              <div>
                <h4 class="fw-bold mb-1 text-dark">Asociados Activos</h4>
                <span class="text-muted">Familias vinculadas al sistema de acopio y pesaje quincenal.</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ========================================================================
       4. NUESTRA TIERRA Y NUESTRA FAMILIA (BLOQUES SÓLIDOS)
       ======================================================================== -->
  <section class="py-5" style="background-color: var(--surface-2);" id="territorio-comunidad">
    <div class="container py-lg-4">

      <div class="row align-items-center g-5 mb-5">
        <div class="col-lg-6">
          <div class="card card-rural p-0 overflow-hidden border-2">
            <img src="assets/img/tierra.svg" width="800" height="500" alt="Terreno propio de ASOAGROALIZAL en Vereda Alizal" class="img-fluid w-100" style="max-height: 380px; object-fit: cover;" loading="lazy">
          </div>
        </div>
        <div class="col-lg-6">
          <span class="section-eyebrow">Territorio y Arraigo</span>
          <h2 class="section-title">Nuestra Tierra: Vereda Alizal</h2>
          <p class="fs-5 text-dark mb-3" style="line-height: 1.7;">
            Sede principal en la <strong>Finca El Recuerdo</strong>, Carmen de Carupa, Cundinamarca.
          </p>
          <p class="text-muted fs-5 mb-0" style="line-height: 1.7;">
            La asociación cuenta con <strong>terreno propio</strong> destinado a la infraestructura comunitaria de acopio, conservación lechera y módulos agrícolas. Enclavada en la alta montaña de Carupa, la vereda Alizal ofrece condiciones óptimas para el pastoreo, con praderas fértiles que permiten obtener una leche fresca de alta inocuidad.
          </p>
        </div>
      </div>

      <div class="row align-items-center g-5 pt-3">
        <div class="col-lg-6 order-2 order-lg-1">
          <span class="section-eyebrow">Comunidad y Cooperación</span>
          <h2 class="section-title">Nuestra Familia Campesina</h2>
          <p class="fs-5 text-dark mb-3" style="line-height: 1.7;">
            32 familias comprometidas con la calidad y la disciplina del ordeño diario.
          </p>
          <p class="text-muted fs-5 mb-4" style="line-height: 1.7;">
            Cada entrega de leche diaria representa el trabajo honesto de productores rurales. Fomentamos la participación equitativa, la capacitación constante y la transparencia en cada pesaje de mañana y tarde, fortaleciendo el tejido asociativo veredal sin intermediarios.
          </p>
          <div class="p-3 rounded-4 bg-white border border-2 d-flex align-items-center gap-3">
            <i class="bi bi-shield-check text-primary fs-3"></i>
            <span class="fw-bold text-dark">Participación democrática y registro seguro de entregas</span>
          </div>
        </div>
        <div class="col-lg-6 order-1 order-lg-2">
          <div class="card card-rural p-0 overflow-hidden border-2">
            <img src="assets/img/familia.svg" width="600" height="400" alt="32 Familias Campesinas Asociadas en Carmen de Carupa" class="img-fluid w-100" style="max-height: 380px; object-fit: cover;" loading="lazy">
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       5. INFRAESTRUCTURA PROPIA (TARJETAS SÓLIDAS Y CHIPS)
       ======================================================================== -->
  <section id="infraestructura" class="py-5">
    <div class="container py-lg-4">
      
      <div class="text-center mb-5">
        <span class="section-eyebrow">Capacidad Colectiva</span>
        <h2 class="section-title">Infraestructura Propia de la Asociación</h2>
        <p class="section-lead mx-auto">
          Instalaciones y equipamiento colectivo gestionado por los 32 asociados para optimizar la cadena láctea y agrícola.
        </p>
      </div>

      <!-- 3 Tarjetas de Columnas Sólidas -->
      <div class="row g-4 mb-4">
        <div class="col-md-4">
          <div class="card card-feature-col">
            <img src="assets/img/infra-tanques.svg" width="600" height="400" alt="Tanque de Enfriamiento en Acero Inoxidable" class="img-fluid rounded-4 mb-3 border border-2" loading="lazy">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-snow2 text-primary fs-3"></i>
              <h4 class="fw-bold mb-0 text-dark">Tanques de Enfriamiento</h4>
            </div>
            <p class="text-muted fs-6 mb-0" style="line-height: 1.6;">
              Tanque colectivo en acero inoxidable para la conservación en frío de la leche entregada en los turnos de mañana y tarde, asegurando la inocuidad y frescura del producto.
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card-feature-col">
            <img src="assets/img/infra-laboratorio.svg" width="600" height="400" alt="Laboratorio de Cultivo de Tejidos Vegetales" class="img-fluid rounded-4 mb-3 border border-2" loading="lazy">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-flower1 text-primary fs-3"></i>
              <h4 class="fw-bold mb-0 text-dark">Laboratorio de Tejidos</h4>
            </div>
            <p class="text-muted fs-6 mb-0" style="line-height: 1.6;">
              Laboratorio de cultivo de tejidos vegetales para la propagación limpia y mejoramiento de semillas de papa y tubérculos andinos con valor agregado para las familias.
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card-feature-col">
            <img src="assets/img/infra-invernadero.svg" width="600" height="400" alt="Invernaderos y Módulos Forrajeros" class="img-fluid rounded-4 mb-3 border border-2" loading="lazy">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-tree text-primary fs-3"></i>
              <h4 class="fw-bold mb-0 text-dark">Invernaderos y Forrajes</h4>
            </div>
            <p class="text-muted fs-6 mb-0" style="line-height: 1.6;">
              Invernaderos y módulos forrajeros para la evaluación de pastos de corte adaptados al frío de Carmen de Carupa y protección de cultivos bajo cubierta.
            </p>
          </div>
        </div>
      </div>

      <!-- Chips de Equipamiento Complementario -->
      <div class="card p-4 rounded-4 border-2" style="background-color: var(--surface-2); border-color: var(--border);">
        <h5 class="fw-bold text-dark mb-3">
          <i class="bi bi-tools text-primary me-2"></i> Equipamiento y Dotación Colectiva Adicional:
        </h5>
        <div class="d-flex flex-wrap gap-2">
          <span class="badge p-3 rounded-pill fw-bold text-dark fs-6" style="background-color: #ffffff; border: 2px solid var(--border);"><i class="bi bi-box-seam text-primary me-1"></i> Cantinas lecheras de recolección</span>
          <span class="badge p-3 rounded-pill fw-bold text-dark fs-6" style="background-color: #ffffff; border: 2px solid var(--border);"><i class="bi bi-thermometer-snow text-primary me-1"></i> Nevera de conservación</span>
          <span class="badge p-3 rounded-pill fw-bold text-dark fs-6" style="background-color: #ffffff; border: 2px solid var(--border);"><i class="bi bi-truck text-primary me-1"></i> Carguero de transporte</span>
          <span class="badge p-3 rounded-pill fw-bold text-dark fs-6" style="background-color: #ffffff; border: 2px solid var(--border);"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Terreno propio de la asociación</span>
          <span class="badge p-3 rounded-pill fw-bold text-dark fs-6" style="background-color: #ffffff; border: 2px solid var(--border);"><i class="bi bi-display text-primary me-1"></i> Computadores e impresoras administrativas</span>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       6. BUENAS PRÁCTICAS DE ORDEÑO (BPO) Y CALIDAD
       ======================================================================== -->
  <section id="enfoque-bpo" class="py-5" style="background-color: var(--surface-2);">
    <div class="container py-lg-4">
      
      <div class="text-center mb-5">
        <span class="section-eyebrow">Mejora Continua</span>
        <h2 class="section-title">Líneas de Trabajo en Buenas Prácticas de Ordeño (BPO)</h2>
        <p class="section-lead mx-auto">
          Directrices y líneas de acción técnica implementadas de manera constante en cada finca asociada.
        </p>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="card card-feature-col h-100">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center mb-3" style="width: 52px; height: 52px; background-color: var(--primary); font-size: 1.4rem;">
              <i class="bi bi-clock-history"></i>
            </div>
            <h4 class="fw-bold mb-2 text-dark">Ordeño a Hora Fija</h4>
            <p class="text-muted fs-6 mb-0" style="line-height: 1.6;">
              Rutinas consistentes de ordeño diario (mañana y tarde) favoreciendo la salud del hato y la regularidad del acopio.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-feature-col h-100">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center mb-3" style="width: 52px; height: 52px; background-color: var(--accent); font-size: 1.4rem;">
              <i class="bi bi-stars"></i>
            </div>
            <h4 class="fw-bold mb-2 text-dark">Incentivo por Calidad</h4>
            <p class="text-muted fs-6 mb-0" style="line-height: 1.6;">
              Esquemas de estímulo basados en inocuidad y parámetros composicionales de la leche, premiando el esmero técnico.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-feature-col h-100">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center mb-3" style="width: 52px; height: 52px; background-color: var(--primary); font-size: 1.4rem;">
              <i class="bi bi-clipboard2-pulse"></i>
            </div>
            <h4 class="fw-bold mb-2 text-dark">Diagnósticos en Finca</h4>
            <p class="text-muted fs-6 mb-0" style="line-height: 1.6;">
              Pruebas rutinarias individuales para monitorear la salud de las vacas y prevenir problemas de calidad.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-feature-col h-100">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center mb-3" style="width: 52px; height: 52px; background-color: var(--primary); font-size: 1.4rem;">
              <i class="bi bi-mortarboard"></i>
            </div>
            <h4 class="fw-bold mb-2 text-dark">Capacitación Continua</h4>
            <p class="text-muted fs-6 mb-0" style="line-height: 1.6;">
              Talleres técnicos periódicos, fertilización de praderas y cumplimiento del reglamento interno lácteo.
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       7. ALIADOS INSTITUCIONALES (SOLO TEXTO)
       ======================================================================== -->
  <section id="aliados" class="py-5">
    <div class="container py-4 text-center">
      <span class="section-eyebrow">Cooperación y Fortalecimiento</span>
      <h2 class="section-title">Aliados Institucionales</h2>
      <p class="section-lead mx-auto mb-4">
        Agradecemos el acompañamiento técnico, organizativo y de desarrollo rural brindado por entidades aliadas en nuestra trayectoria asociativa:
      </p>

      <div class="d-flex flex-wrap justify-content-center gap-4 align-items-center">
        <div class="card p-3 px-4 rounded-pill border-2 fw-bold text-primary fs-5 shadow-sm" style="border-color: var(--border);">
          <i class="bi bi-building me-2 text-muted"></i> Corporación PBA
        </div>
        <div class="card p-3 px-4 rounded-pill border-2 fw-bold text-primary fs-5 shadow-sm" style="border-color: var(--border);">
          <i class="bi bi-heart-fill me-2 text-danger"></i> Fundación Bolívar Davivienda
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       8. CONTACTO Y UBICACIÓN (ETIQUETAS SIEMPRE VISIBLES ENCIMA)
       ======================================================================== -->
  <section id="contacto" class="py-5" style="background-color: var(--surface-2);">
    <div class="container py-lg-4">

      <div class="text-center mb-5">
        <span class="section-eyebrow">Canal Abierto</span>
        <h2 class="section-title">Canales de Contacto y Ubicación</h2>
        <p class="section-lead mx-auto">
          Comunícate directamente con la administración de ASOAGROALIZAL o radica tus consultas comunitarias mediante el formulario web.
        </p>
      </div>

      <div class="row g-5">
        
        <!-- Formulario con Etiquetas Siempre Visibles Encima -->
        <div class="col-lg-6">
          <div class="card card-rural p-4 p-md-5 h-100">
            <h3 class="fw-bold mb-3 text-primary">
              <i class="bi bi-envelope-paper me-2"></i> Envíanos un Mensaje
            </h3>
            <p class="text-muted fs-6 mb-4">
              Diligencia tus datos de contacto y tu mensaje. Recibirás respuesta de la administración.
            </p>

            <form action="index.php#contacto" method="POST" id="form-contacto">
              <?php echo campoCsrf(); ?>

              <!-- Campo Honeypot Oculto Anti-Spam -->
              <div class="d-none" aria-hidden="true">
                <label for="website_hp">Si eres humano deja este campo vacío</label>
                <input type="text" id="website_hp" name="website_hp" tabindex="-1" autocomplete="off">
              </div>

              <!-- Etiqueta Permanente: Nombre Completo -->
              <div class="mb-4">
                <label for="nombre" class="form-label">Nombre y Apellidos *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Escribe tu nombre completo" required>
              </div>

              <!-- Etiqueta Permanente: Correo o Celular -->
              <div class="mb-4">
                <label for="contacto" class="form-label">Teléfono Celular o Correo Electrónico *</label>
                <input type="text" class="form-control" id="contacto" name="contacto" placeholder="Ej: 3185235690 o correo@ejemplo.com" required>
              </div>

              <!-- Etiqueta Permanente: Mensaje -->
              <div class="mb-4">
                <label for="mensaje" class="form-label">Motivo de tu consulta o mensaje *</label>
                <textarea class="form-control" placeholder="Escribe tu mensaje con claridad..." id="mensaje" name="mensaje" required></textarea>
              </div>

              <!-- Checkbox Obligatorio Tratamiento de Datos (Ley 1581 de 2012) -->
              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" value="1" id="acepta_datos" name="acepta_datos" style="width: 24px; height: 24px;" required>
                <label class="form-check-label ms-2 text-dark fs-6" for="acepta_datos">
                  Autorizo el tratamiento de mis datos de acuerdo con la 
                  <a href="javascript:void(0)" class="fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#modalPoliticaDatos">
                    Política de Tratamiento de Datos (Ley 1581 de 2012)
                  </a>.
                </label>
              </div>

              <!-- Botón Enorme de 56px -->
              <button type="submit" name="accion_contacto" id="btn-enviar-contacto" class="btn btn-touch btn-aso-primary w-100 fs-5">
                <i class="bi bi-send-fill me-2 fs-5"></i>
                <span>Enviar Mensaje</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Tarjeta Institucional y Mapa Embebido -->
        <div class="col-lg-6">
          <div class="card card-rural p-4 p-md-5 h-100 d-flex flex-column justify-content-between">
            <div>
              <h3 class="fw-bold mb-4 text-primary">
                <i class="bi bi-geo-alt-fill me-2"></i> Información Oficial
              </h3>

              <div class="d-flex flex-column gap-3 mb-4">
                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle text-white p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--primary);">
                    <i class="bi bi-pin-map-fill fs-5"></i>
                  </div>
                  <div>
                    <strong class="d-block text-dark fs-5">Sede Principal</strong>
                    <span class="text-muted"><?php echo e(EMPRESA_UBICACION); ?></span>
                  </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle text-white p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--primary);">
                    <i class="bi bi-envelope-fill fs-5"></i>
                  </div>
                  <div>
                    <strong class="d-block text-dark fs-5">Correo Institucional</strong>
                    <a href="mailto:<?php echo e(EMPRESA_CORREO); ?>" class="text-primary fw-bold">
                      <?php echo e(EMPRESA_CORREO); ?>
                    </a>
                  </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle text-white p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--primary);">
                    <i class="bi bi-telephone-fill fs-5"></i>
                  </div>
                  <div>
                    <strong class="d-block text-dark fs-5">Teléfono Oficial Único</strong>
                    <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="text-primary fw-bold fs-5">
                      <?php echo e(EMPRESA_TELEFONO); ?>
                    </a>
                  </div>
                </div>
              </div>

              <div class="alert p-3 rounded-4 mb-4" style="background-color: var(--surface-2); border: 2px solid var(--border);">
                <i class="bi bi-info-circle-fill text-primary me-2 fs-5"></i>
                <strong class="text-dark">Aviso Comunitario:</strong>
                <p class="mb-0 text-muted mt-1 small">
                  Por políticas de bioseguridad, reserva operativa y protección comunitaria, la asociación no publica horarios de acopio ni de atención al público en canales web abiertos.
                </p>
              </div>
            </div>

            <!-- Mapa Embebido de la Vereda Alizal -->
            <div class="rounded-4 overflow-hidden border border-2 shadow-sm" style="height: 230px;">
              <iframe 
                title="Ubicación Vereda Alizal, Carmen de Carupa"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15893.59381640947!2d-73.9168435!3d5.3524675!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e40428d0e7408f9%3A0xb6ee08945a898b9a!2sCarmen%20de%20Carupa%2C%20Cundinamarca!5e0!3m2!1ses!2sco!4v1714000000000!5m2!1ses!2sco" 
                width="100%" 
                height="100%" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
              </iframe>
            </div>

          </div>
        </div>

      </div>

    </div>
  </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const formContacto = document.getElementById('form-contacto');
  if (formContacto) {
    formContacto.addEventListener('submit', async function (e) {
      e.preventDefault();

      const btn = document.getElementById('btn-enviar-contacto');
      const nombre = document.getElementById('nombre').value.trim();
      const contacto = document.getElementById('contacto').value.trim();
      const mensaje = document.getElementById('mensaje').value.trim();
      const aceptaDatos = document.getElementById('acepta_datos').checked;
      const honeypot = document.getElementById('website_hp').value;
      const csrfToken = formContacto.querySelector('input[name="csrf_token"]').value;

      if (!nombre || !contacto || !mensaje) {
        AsoagroUI.showWarning('Campos Incompletos', 'Por favor diligencia todos los campos obligatorios.');
        return;
      }

      if (!aceptaDatos) {
        AsoagroUI.showWarning('Autorización Requerida', 'Debes autorizar el tratamiento de datos personales para continuar.');
        return;
      }

      btn.disabled = true;
      btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Enviando mensaje...`;

      try {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('nombre', nombre);
        formData.append('contacto', contacto);
        formData.append('mensaje', mensaje);
        formData.append('website_hp', honeypot);

        const res = await fetch('api/consultas.php', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        });

        const data = await res.json();

        if (res.ok && data.ok) {
          AsoagroUI.showSuccess('¡Mensaje Enviado!', data.message || 'Tu consulta ha sido registrada exitosamente.');
          formContacto.reset();
        } else {
          AsoagroUI.showError('Error al Enviar', data.message || 'Ocurrió un error registrando tu consulta.');
        }
      } catch (err) {
        formContacto.submit();
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-send-fill me-2 fs-5"></i> <span>Enviar Mensaje</span>`;
      }
    });
  }
});
</script>

<?php
// Inclusión del pie de página global legal y scripts
require_once __DIR__ . '/includes/footer.php';
?>
