<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: index.php
 * Propósito: Landing page corporativa institucional con estética agri-tech, inspirada en
 *            principios editoriales contemporáneos: ritmo vertical amplio, tipografía serif/sans,
 *            héroe a ancho completo con tarjetas de vidrio, bloques alternados imagen/texto
 *            y fidelidad absoluta a la Ficha Oficial de ASOAGROALIZAL.
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

// Manejo de envío alternativo por POST directo (Fallback si el navegador no tiene JavaScript activo)
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
    $aceptaDatos = !empty($_POST['acepta_datos']);

    if (empty($nombre) || empty($contacto) || empty($mensaje) || !$aceptaDatos) {
        setFlashAlert('warning', 'Campos Requeridos', 'Por favor diligencia todos los campos y autoriza el tratamiento de datos.');
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
       1. HÉROE A ANCHO COMPLETO (INMERSIVO CON OVERLAY VERDE Y TARJETAS DE VIDRIO)
       ======================================================================== -->
  <section class="hero-fullwidth position-relative">
    <div class="hero-overlay"></div>
    <div class="container hero-inner py-4">
      
      <div class="row align-items-center g-5 mb-5">
        <div class="col-lg-8">
          <div class="hero-tag mb-3">
            <i class="bi bi-geo-alt-fill text-warning"></i>
            <span><?php echo e(EMPRESA_SIGLA); ?> &bull; Carmen de Carupa, Cundinamarca</span>
          </div>

          <h1 class="hero-title">
            Uniendo Esfuerzos para la Producción Sostenible y el Progreso Campesino
          </h1>

          <p class="hero-support">
            Plataforma oficial de control lechero, pesajes diarios sistemáticos por turnos (☀️ Mañana y 🌅 Tarde) y fomento de Buenas Prácticas de Ordeño en la Vereda Alizal.
          </p>

          <div class="d-flex flex-wrap gap-3 align-items-center">
            <?php if ($usuarioActual): ?>
              <?php $urlDestino = ($usuarioActual['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
              <a href="<?php echo $urlDestino; ?>" class="btn btn-touch btn-aso-accent btn-lg px-4 shadow">
                <i class="bi bi-speedometer2"></i>
                <span>Mi Panel de Control</span>
              </a>
            <?php else: ?>
              <a href="login.php" class="btn btn-touch btn-aso-accent btn-lg px-4 shadow">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Acceso al Sistema</span>
              </a>
            <?php endif; ?>

            <a href="#quienes-somos" class="btn btn-touch btn-outline-light btn-lg px-4">
              <i class="bi bi-info-circle"></i>
              <span>Conocer la Asociación</span>
            </a>
          </div>
        </div>

        <div class="col-lg-4 d-none d-lg-block text-center">
          <div class="p-4 rounded-4" style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <img src="assets/img/logo.png" alt="Emblema Institucional <?php echo e(EMPRESA_SIGLA); ?>" class="img-fluid mb-3" style="max-height: 200px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));">
            <h5 class="fw-bold text-white mb-1"><?php echo e(EMPRESA_SIGLA); ?></h5>
            <small class="text-white-50 d-block mb-2">Vereda Alizal &bull; Finca El Recuerdo</small>
            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold">NIT <?php echo e(EMPRESA_NIT); ?></span>
          </div>
        </div>
      </div>

      <!-- Tarjetas de Vidrio Inferiores: MISIÓN Y VISIÓN (Texto Exacto de la Ficha) -->
      <div class="row g-4 pt-4 border-top border-white border-opacity-20" id="mision-vision">
        <div class="col-md-6">
          <div class="glass-card-hero">
            <span class="glass-card-tag"><i class="bi bi-compass me-1"></i> Misión Institucional</span>
            <h3 class="glass-card-title">Nuestro Propósito Colectivo</h3>
            <p class="glass-card-text">
              "Asoagroalizal es una organización que busca unir esfuerzos para la producción sostenible de alimentos que permita el mejoramiento de la calidad de vida de las familias de la vereda de Alizal."
            </p>
          </div>
        </div>

        <div class="col-md-6">
          <div class="glass-card-hero">
            <span class="glass-card-tag"><i class="bi bi-eye me-1"></i> Visión Hacia el 2030</span>
            <h3 class="glass-card-title">Nuestro Horizonte Productivo</h3>
            <p class="glass-card-text">
              "Para el año 2030 Asoagroalizal busca llegar a transformar el 50% de su producción láctea y darle valor agregado a otros productos como la papa y los tubérculos andinos, así como buscar nuevos mercados que le generen ingresos a los asociados y sus familias."
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       2. SECCIÓN QUIÉNES SOMOS (BLOQUE IMAGEN + TEXTO)
       ======================================================================== -->
  <section id="quienes-somos" class="py-5">
    <div class="container py-lg-4">
      <div class="row align-items-center g-5">
        
        <div class="col-lg-6">
          <div class="feature-media-wrapper p-4 p-md-5 text-center d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background: radial-gradient(circle, var(--surface-2) 0%, var(--surface) 100%);">
            <img src="assets/img/logo.png" alt="Emblema Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="img-fluid mb-4" style="max-height: 220px; filter: drop-shadow(0 12px 24px rgba(17,80,30,0.18));">
            <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold">
              Constitución Legal: Año <?php echo e(EMPRESA_ANO_CONSTITUCION); ?> (Más de 25 Años)
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <span class="section-eyebrow">Trayectoria Asociativa</span>
          <h2 class="section-title">Más de 25 Años de Vocación y Trabajo Solidario</h2>
          <p class="section-lead mb-4">
            Somos legalmente la <strong><?php echo e(EMPRESA_NOMBRE); ?></strong>, identificada con NIT <strong><?php echo e(EMPRESA_NIT); ?></strong>, con personería jurídica reconocida ante la Cámara de Comercio y representada bajo la sigla <strong><?php echo e(EMPRESA_SIGLA); ?></strong>.
          </p>
          <p class="text-muted mb-4">
            Fundada en el año 2000 en el municipio de Carmen de Carupa, la asociación reúne el esfuerzo de familias campesinas dedicadas a la producción lechera de alta montaña y a la agricultura andina, consolidando una organización comunitaria basada en la ayuda mutua, la honestidad y la sostenibilidad económica.
          </p>

          <!-- Contador Animado de 32 Asociados -->
          <div class="d-flex align-items-center gap-4 p-4 rounded-4" style="background-color: var(--surface-2); border: 1px solid var(--border-subtle);">
            <div class="display-3 fw-bold text-success counter-animated" data-target="<?php echo e(EMPRESA_ASOCIADOS_TOTAL); ?>">0</div>
            <div>
              <h5 class="fw-bold mb-1 text-success">Asociados Activos</h5>
              <small class="text-muted d-block">Familias vinculadas a la plataforma oficial de pesaje y liquidación quincenal.</small>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ========================================================================
       3. BLOQUES ALTERNADOS: NUESTRA TIERRA Y NUESTRA FAMILIA
       ======================================================================== -->
  <section class="py-5" style="background-color: var(--surface-2);" id="territorio-comunidad">
    <div class="container py-lg-4">

      <!-- Bloque 1: Nuestra Tierra (Imagen Izquierda, Texto Derecha) -->
      <div class="row align-items-center g-5 mb-5 pb-4">
        <div class="col-lg-6">
          <div class="feature-media-wrapper">
            <img src="assets/img/tierra.svg" alt="Terreno propio de ASOAGROALIZAL en Vereda Alizal" class="feature-media-img" loading="lazy">
          </div>
        </div>
        <div class="col-lg-6">
          <span class="section-eyebrow">Territorio y Arraigo</span>
          <h2 class="section-title">Nuestra Tierra: Vereda Alizal</h2>
          <p class="section-lead mb-3">
            Sede principal en la Finca El Recuerdo, Carmen de Carupa, Cundinamarca.
          </p>
          <p class="text-muted mb-0">
            La asociación cuenta con <strong>terreno propio</strong> destinado a la infraestructura de acopio, enfriamiento de leche y módulos de biotecnología. Enclavada en los paisajes fértiles de Carmen de Carupa, la vereda Alizal ofrece condiciones óptimas para el pastoreo, rodeada de alisos nativos y praderas de ladera que dan origen a una leche fresca, inocua y de sobresalientes características nutritivas.
          </p>
        </div>
      </div>

      <!-- Bloque 2: Nuestra Familia (Texto Izquierda, Imagen Derecha) -->
      <div class="row align-items-center g-5 pt-4">
        <div class="col-lg-6 order-2 order-lg-1">
          <span class="section-eyebrow">Comunidad y Cooperación</span>
          <h2 class="section-title">Nuestra Familia Campesina</h2>
          <p class="section-lead mb-3">
            32 asociados comprometidos con el trabajo colaborativo y la calidad diaria.
          </p>
          <p class="text-muted mb-4">
            Detrás de cada cantina de leche entregada hay una familia campesina que madruga con disciplina. Promovemos la participación equitativa, la capacitación técnica continua y la transparencia absoluta en cada pesaje de los turnos de mañana y tarde, fortaleciendo el tejido social de la vereda sin intermediarios.
          </p>
          <div class="d-flex align-items-center gap-2 text-success fw-bold small">
            <i class="bi bi-shield-check fs-5"></i>
            <span>Participación democrática y registro formal de entregas lecheras</span>
          </div>
        </div>
        <div class="col-lg-6 order-1 order-lg-2">
          <div class="feature-media-wrapper">
            <img src="assets/img/familia.svg" alt="32 Familias Campesinas Asociadas en Carmen de Carupa" class="feature-media-img" loading="lazy">
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       4. SECCIÓN INFRAESTRUCTURA PROPIA
       ======================================================================== -->
  <section id="infraestructura" class="py-5">
    <div class="container py-lg-4">
      
      <div class="text-center mb-5">
        <span class="section-eyebrow">Capacidad Colectiva</span>
        <h2 class="section-title">Infraestructura Propia de la Asociación</h2>
        <p class="section-lead mx-auto">
          Equipamiento tecnológico e instalaciones comunitarias al servicio de los 32 asociados para la preservación de la leche y el impulso de la agricultura local.
        </p>
      </div>

      <!-- 3 Tarjetas de Columnas Principales -->
      <div class="row g-4 mb-4">
        <div class="col-md-4">
          <div class="card-feature-col">
            <div class="mb-3">
              <img src="assets/img/infra-tanques.svg" alt="Tanque de Enfriamiento en Acero Inoxidable" class="img-fluid rounded-4 mb-3 border border-subtle" loading="lazy">
            </div>
            <div class="card-feature-icon">
              <i class="bi bi-snow2"></i>
            </div>
            <h4 class="fw-bold mb-2">Tanques de Enfriamiento</h4>
            <p class="text-muted small mb-0">
              Tanque colectivo de enfriamiento en acero inoxidable para la recepción y conservación inmediata de la leche de los turnos de mañana y tarde, asegurando la cadena de frío e inocuidad alimentaria.
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card-feature-col">
            <div class="mb-3">
              <img src="assets/img/infra-laboratorio.svg" alt="Laboratorio de Cultivo de Tejidos Vegetales" class="img-fluid rounded-4 mb-3 border border-subtle" loading="lazy">
            </div>
            <div class="card-feature-icon">
              <i class="bi bi-flower1"></i>
            </div>
            <h4 class="fw-bold mb-2">Laboratorio de Tejidos</h4>
            <p class="text-muted small mb-0">
              Laboratorio de cultivo de tejidos vegetales para la propagación limpia y biotecnología aplicada a semillas de papa y tubérculos andinos con valor agregado para las familias productoras.
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card-feature-col">
            <div class="mb-3">
              <img src="assets/img/infra-invernadero.svg" alt="Invernaderos y Módulos Forrajeros" class="img-fluid rounded-4 mb-3 border border-subtle" loading="lazy">
            </div>
            <div class="card-feature-icon">
              <i class="bi bi-tree"></i>
            </div>
            <h4 class="fw-bold mb-2">Invernaderos y Forrajes</h4>
            <p class="text-muted small mb-0">
              Invernaderos y módulos forrajeros adaptados al clima de Carmen de Carupa, facilitando la evaluación de pastos de corte, nutrición bovina sustentable y protección de cultivos bajo cubierta.
            </p>
          </div>
        </div>
      </div>

      <!-- Chips de Equipamiento Complementario de la Ficha -->
      <div class="p-4 rounded-4" style="background-color: var(--surface-2); border: 1px solid var(--border-subtle);">
        <h6 class="fw-bold text-success mb-3">
          <i class="bi bi-tools me-2"></i> Equipamiento y Dotación Colectiva Adicional:
        </h6>
        <div class="d-flex flex-wrap">
          <span class="infra-chip"><i class="bi bi-box-seam text-success"></i> Cantinas lecheras de recolección</span>
          <span class="infra-chip"><i class="bi bi-thermometer-snow text-primary"></i> Nevera de conservación</span>
          <span class="infra-chip"><i class="bi bi-truck text-warning"></i> Carguero de transporte</span>
          <span class="infra-chip"><i class="bi bi-geo-alt-fill text-danger"></i> Terreno propio de la asociación</span>
          <span class="infra-chip"><i class="bi bi-display text-info"></i> Computadores e impresoras para administración</span>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       5. SECCIÓN CALIDAD Y BUENAS PRÁCTICAS DE ORDEÑO (BPO)
       ======================================================================== -->
  <section id="enfoque-bpo" class="py-5" style="background-color: var(--surface-2);">
    <div class="container py-lg-4">
      
      <div class="text-center mb-5">
        <span class="section-eyebrow">Mejora Continua</span>
        <h2 class="section-title">Líneas de Trabajo en Buenas Prácticas de Ordeño (BPO)</h2>
        <p class="section-lead mx-auto">
          Implementación sistemática de directrices técnicas para promover la inocuidad, la higiene y el bienestar ganadero en cada finca campesina.
        </p>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="card-feature-col bg-surface">
            <div class="card-feature-icon">
              <i class="bi bi-clock-history"></i>
            </div>
            <h5 class="fw-bold mb-2">Ordeño a Hora Fija</h5>
            <p class="text-muted small mb-0">
              Rutinas consistentes de ordeño diario en turnos fijos de mañana y tarde, favoreciendo la estabilidad fisiológica del hato y la regularidad del acopio.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card-feature-col bg-surface">
            <div class="card-feature-icon">
              <i class="bi bi-stars"></i>
            </div>
            <h5 class="fw-bold mb-2">Incentivo por Calidad</h5>
            <p class="text-muted small mb-0">
              Esquemas de estímulo basados en inocuidad y parámetros composicionales de la leche, premiando el esmero y cumplimiento técnico de cada productor.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card-feature-col bg-surface">
            <div class="card-feature-icon">
              <i class="bi bi-clipboard2-pulse"></i>
            </div>
            <h5 class="fw-bold mb-2">Diagnósticos en Finca</h5>
            <p class="text-muted small mb-0">
              Pruebas rutinarias individuales para el monitoreo de la leche en cada predio, previniendo mastitis y garantizando altos estándares de acopio.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card-feature-col bg-surface">
            <div class="card-feature-icon">
              <i class="bi bi-mortarboard"></i>
            </div>
            <h5 class="fw-bold mb-2">Capacitación Continua</h5>
            <p class="text-muted small mb-0">
              Talleres comunitarios permanentes, fertilización técnica de praderas y adopción rigurosa del reglamento interno lácteo entre los asociados.
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       6. SECCIÓN ALIADOS INSTITUCIONALES (SOLO TEXTO)
       ======================================================================== -->
  <section id="aliados" class="py-5">
    <div class="container py-4 text-center">
      <span class="section-eyebrow">Cooperación y Fortalecimiento</span>
      <h2 class="section-title">Aliados Institucionales</h2>
      <p class="section-lead mx-auto mb-4">
        Agradecemos el acompañamiento técnico, organizativo y de desarrollo rural brindado por entidades aliadas en nuestra trayectoria asociativa:
      </p>

      <div class="d-flex flex-wrap justify-content-center gap-4 align-items-center">
        <div class="p-3 px-4 rounded-pill border border-subtle bg-surface fw-bold text-success fs-5 shadow-sm">
          <i class="bi bi-building me-2 text-muted"></i> Corporación PBA
        </div>
        <div class="p-3 px-4 rounded-pill border border-subtle bg-surface fw-bold text-success fs-5 shadow-sm">
          <i class="bi bi-heart-fill me-2 text-danger"></i> Fundación Bolívar Davivienda
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       7. SECCIÓN CONTACTO Y UBICACIÓN OFICIAL
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
        
        <!-- Columna 1: Formulario con Floating Labels y Protección Ley 1581 -->
        <div class="col-lg-6">
          <div class="card card-rural p-4 p-md-5 h-100">
            <h4 class="fw-bold mb-3 text-success">
              <i class="bi bi-envelope-paper me-2"></i> Envíanos un Mensaje
            </h4>
            <p class="text-muted small mb-4">
              Diligencia tus datos de contacto y mensaje. Recibirás respuesta de la administración de la asociación.
            </p>

            <form action="index.php#contacto" method="POST" id="form-contacto">
              <?php echo campoCsrf(); ?>

              <!-- Campo Honeypot Oculto Anti-Spam -->
              <div class="d-none" aria-hidden="true">
                <label for="website_hp">Si eres humano deja este campo vacío</label>
                <input type="text" id="website_hp" name="website_hp" tabindex="-1" autocomplete="off">
              </div>

              <!-- Floating Label: Nombre Completo -->
              <div class="form-floating mb-3">
                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre completo" required>
                <label for="nombre">Nombre y Apellidos *</label>
              </div>

              <!-- Floating Label: Correo o Teléfono -->
              <div class="form-floating mb-3">
                <input type="text" class="form-control" id="contacto" name="contacto" placeholder="Correo o celular" required>
                <label for="contacto">Correo Electrónico o Teléfono de Contacto *</label>
              </div>

              <!-- Floating Label: Mensaje o Consulta -->
              <div class="form-floating mb-3">
                <textarea class="form-control" placeholder="Escribe tu mensaje aquí" id="mensaje" name="mensaje" style="height: 140px;" required></textarea>
                <label for="mensaje">Motivo de tu consulta o mensaje *</label>
              </div>

              <!-- Checkbox Obligatorio Tratamiento de Datos (Ley 1581 de 2012) -->
              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" value="1" id="acepta_datos" name="acepta_datos" required>
                <label class="form-check-label small text-muted" for="acepta_datos">
                  Autorizo el tratamiento de mis datos personales de acuerdo con la 
                  <a href="javascript:void(0)" class="text-success fw-bold text-decoration-underline" data-bs-toggle="modal" data-bs-target="#modalPoliticaDatos">
                    Política de Tratamiento de Datos (Ley 1581 de 2012)
                  </a>.
                </label>
              </div>

              <button type="submit" name="accion_contacto" id="btn-enviar-contacto" class="btn btn-touch btn-aso-primary w-100 shadow">
                <i class="bi bi-send-fill me-1"></i>
                <span>Enviar Mensaje</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Columna 2: Tarjeta Institucional y Mapa Embebido -->
        <div class="col-lg-6">
          <div class="card card-rural p-4 p-md-5 h-100 d-flex flex-column justify-content-between">
            <div>
              <h4 class="fw-bold mb-4 text-success">
                <i class="bi bi-geo-alt-fill me-2"></i> Información Oficial
              </h4>

              <div class="d-flex flex-column gap-3 mb-4">
                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-pin-map-fill"></i>
                  </div>
                  <div>
                    <strong class="d-block text-dark">Sede Principal</strong>
                    <span class="text-muted small"><?php echo e(EMPRESA_UBICACION); ?></span>
                  </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-envelope-fill"></i>
                  </div>
                  <div>
                    <strong class="d-block text-dark">Correo Electrónico Institucional</strong>
                    <a href="mailto:<?php echo e(EMPRESA_CORREO); ?>" class="text-success small fw-bold text-decoration-none">
                      <?php echo e(EMPRESA_CORREO); ?>
                    </a>
                  </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-telephone-fill"></i>
                  </div>
                  <div>
                    <strong class="d-block text-dark">Teléfono Oficial Único</strong>
                    <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="text-success small fw-bold text-decoration-none">
                      <?php echo e(EMPRESA_TELEFONO); ?>
                    </a>
                  </div>
                </div>

                <?php if (WHATSAPP_ACTIVO): ?>
                  <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                      <i class="bi bi-whatsapp"></i>
                    </div>
                    <div>
                      <strong class="d-block text-dark">Canal WhatsApp</strong>
                      <span class="text-muted small">Canal habilitado para asociados</span>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <div class="alert alert-success-subtle border border-success-subtle rounded-4 p-3 small mb-4">
                <i class="bi bi-info-circle-fill text-success me-1"></i>
                <em>Por políticas de bioseguridad, reserva operativa y protección comunitaria, la asociación no publica horarios de acopio ni de atención al público en canales web abiertos.</em>
              </div>
            </div>

            <!-- Mapa Embebido de la Vereda Alizal, Carmen de Carupa -->
            <div class="rounded-4 overflow-hidden border border-subtle shadow-sm" style="height: 220px;">
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
  // Manejo de envío asíncrono con fetch y SweetAlert2 para el formulario de contacto
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
        AsoagroUI.showWarning('Campos Incompletos', 'Por favor diligencia todos los campos requeridos.');
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
        // En caso de corte de red, enviar por formulario tradicional
        formContacto.submit();
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-send-fill me-1"></i> <span>Enviar Mensaje</span>`;
      }
    });
  }
});
</script>

<?php
// Inclusión del pie de página global legal y scripts
require_once __DIR__ . '/includes/footer.php';
?>
