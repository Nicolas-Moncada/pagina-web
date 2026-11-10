<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: index.php
 * Propósito: Portal público institucional rediseñado al estilo corporativo de Central Ganadera:
 *            - Hero inmersivo de gran impacto con 3 Tarjetas Flotantes Superpuestas (Overlap Cards).
 *            - 4 Accesos Directos Operativos (Acopio, Portal Asociado, Colillas, F-PQRS).
 *            - Módulos de Nuestras Áreas, Misión/Visión, Beneficios y Galería con Lightbox.
 *            - Formulario seguro con CSRF, Honeypot y tabla de base de datos `consulta`.
 * Fuente Oficial: docs/FICHA_OFICIAL_ASOAGROALIZAL.md | NIT: 832006127-2
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$usuarioActual = getCurrentUser();
$tituloPagina = EMPRESA_SIGLA . ' · Impulsando la Producción Láctea con Control y Transparencia';

$pdo = getDB();

// 1. Consulta dinámica del precio oficial por litro en la quincena activa
$precioLitro = 2250.0;
$quincenaTexto = 'Quincena Vigente';
try {
    $stmtQ = $pdo->query("SELECT fechaInicio, fechaFin, precioLitro FROM quincena ORDER BY fechaInicio DESC LIMIT 1");
    if ($q = $stmtQ->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($q['precioLitro']) && $q['precioLitro'] > 0) {
            $precioLitro = (float)$q['precioLitro'];
        }
        if (!empty($q['fechaInicio']) && !empty($q['fechaFin'])) {
            $quincenaTexto = 'Período: ' . date('d/m/Y', strtotime($q['fechaInicio'])) . ' al ' . date('d/m/Y', strtotime($q['fechaFin']));
        }
    }
} catch (Exception $e) {}

// 2. Consulta de litros totales recibidos
$totalLitros = 26355;
try {
    $stmtL = $pdo->query("SELECT SUM(litros) AS total FROM registros_leche");
    if ($rowL = $stmtL->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($rowL['total']) && $rowL['total'] > 0) {
            $totalLitros = (float)$rowL['total'];
        }
    }
} catch (Exception $e) {}

// 3. Manejo seguro del formulario de Contacto / Radicación F-PQRS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_contacto'])) {
    if (!empty($_POST['website_hp'])) {
        // Honeypot anti-spam
        setFlashAlert('success', 'Consulta Recibida', 'Su solicitud ha sido registrada correctamente.');
        header('Location: index.php#contacto');
        exit;
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($csrfToken)) {
        setFlashAlert('error', 'Sesión Expirada', 'El token de seguridad ha caducado. Por favor recargue la página.');
        header('Location: index.php#contacto');
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $asunto = trim($_POST['asunto'] ?? 'Consulta General');
    $mensaje = trim($_POST['mensaje'] ?? '');
    $aceptaDatos = !empty($_POST['acepta_datos']);

    if (empty($nombre) || empty($contacto) || empty($mensaje) || !$aceptaDatos) {
        setFlashAlert('warning', 'Campos Requeridos', 'Por favor diligencie todos los campos obligatorios y autorice el tratamiento de datos personales.');
        header('Location: index.php#contacto');
        exit;
    }

    try {
        $conId = generarUUID('con');
        $stmtC = $pdo->prepare("INSERT INTO consulta (id, nombre, contacto, mensaje, fecha, atendida) VALUES (?, ?, ?, ?, NOW(), 0)");
        $stmtC->execute([$conId, $nombre, $contacto, "[$asunto] " . $mensaje]);
        setFlashAlert('success', '¡Mensaje Radicado!', 'Gracias por comunicarse con ASOAGROALIZAL. Su mensaje ha sido radicado formalmente.');
        header('Location: index.php#contacto');
        exit;
    } catch (Exception $e) {
        error_log("Error guardando consulta en index.php: " . $e->getMessage());
        setFlashAlert('error', 'Error al Radicar', 'Ocurrió un inconveniente procesando su solicitud. Por favor intente más tarde.');
        header('Location: index.php#contacto');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main id="contenido-principal" class="flex-grow-1">

  <!-- ========================================================================
       1. HERO SECTION CORPORATIVO (ESTILO CENTRAL GANADERA)
       ======================================================================== -->
  <section id="inicio" class="hero-corporate">
    <!-- Fondo multimedia optimizado con gradiente de contraste -->
    <img src="assets/img/hero-ganado.webp" alt="Ganadería lechera en pasturas de la Vereda Alizal" class="hero-bg-media" width="1600" height="1063" loading="eager" fetchpriority="high">
    <div class="hero-overlay-dark"></div>

    <div class="container hero-content py-5">
      <div class="row align-items-center">
        <div class="col-xl-9 col-lg-10">

          <!-- Eyebrow Pill -->
          <div class="hero-eyebrow mb-3">
            <i class="fa-solid fa-location-dot" style="color: var(--accent-gold);"></i>
            <span>CARMEN DE CARUPA &bull; VEREDA ALIZAL &bull; NIT <?php echo e(EMPRESA_NIT); ?></span>
          </div>

          <!-- Titular Principal con Impacto -->
          <h1 class="hero-title-main">
            Asociación Agropecuaria Alizal &ndash; <br>
            <span class="text-gold">Impulsando la Producción Láctea</span> con Control y Transparencia
          </h1>

          <!-- Descripción -->
          <p class="hero-description">
            Plataforma oficial de control lechero para las 32 familias productoras asociadas de ASOAGROALIZAL. 
            Registro diario riguroso en dos turnos (☀️ Mañana y 🌅 Tarde), estándares de calidad BPO y liquidación quincenal oportuna.
          </p>

          <!-- Botones de Acción Inmediata -->
          <div class="d-flex flex-wrap gap-3 pt-2">
            <?php if ($usuarioActual): ?>
              <?php $urlDestino = ($usuarioActual['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
              <a href="<?php echo $urlDestino; ?>" class="btn-cta-gold">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Ir a Mi Panel de Control</span>
              </a>
            <?php else: ?>
              <a href="login.php" class="btn-cta-gold">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Ingresar como Asociado</span>
              </a>
              <a href="#areas" class="btn-cta-outline">
                <i class="fa-solid fa-cubes-stacked"></i>
                <span>Nuestras Áreas Operativas</span>
              </a>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       2. TARJETAS FLOTANTES SUPERPUESTAS (OVERLAP CARDS AL ESTILO CENTRAL GANADERA)
       ======================================================================== -->
  <section class="container hero-overlap-container">
    <div class="row g-4 justify-content-center">

      <!-- Tarjeta 1: Precio Oficial en Quincena -->
      <div class="col-lg-4 col-md-6">
        <div class="overlap-card overlap-card-gold" id="precio">
          <div class="d-flex align-items-center justify-content-between">
            <div class="overlap-card-icon icon-bg-gold">
              <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <span class="badge rounded-pill fw-bold" style="background-color: var(--accent-gold-soft); color: var(--accent-gold-hover);">
              <i class="fa-solid fa-circle-dot fa-fade me-1 text-warning"></i> En Línea
            </span>
          </div>
          <span class="overlap-card-label">Precio Oficial por Litro</span>
          <div class="overlap-card-value tabular-nums">
            $<?php echo number_format($precioLitro, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">COP / Litro</span>
          </div>
          <p class="small text-muted mb-3">
            <i class="fa-regular fa-calendar-check me-1"></i> <?php echo e($quincenaTexto); ?>
          </p>
          <div class="mt-auto pt-2 border-top">
            <a href="login.php" class="text-decoration-none fw-bold small d-inline-flex align-items-center gap-1" style="color: var(--accent-gold);">
              <span>Ver tabla de liquidación completa</span>
              <i class="fa-solid fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Tarjeta 2: Portal InfoLeche (Pesajes Mañana y Tarde) -->
      <div class="col-lg-4 col-md-6">
        <div class="overlap-card overlap-card-emerald">
          <div class="d-flex align-items-center justify-content-between">
            <div class="overlap-card-icon icon-bg-emerald">
              <i class="fa-solid fa-glass-water-droplet"></i>
            </div>
            <span class="badge rounded-pill fw-bold" style="background-color: #DCFCE7; color: var(--secondary-green);">
              Turnos ☀️ y 🌅
            </span>
          </div>
          <span class="overlap-card-label">Acopio Acumulado Reportado</span>
          <div class="overlap-card-value tabular-nums">
            <?php echo number_format($totalLitros, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">Litros Totales</span>
          </div>
          <p class="small text-muted mb-3">
            <i class="fa-solid fa-shield-halved text-success me-1"></i> Control individual para los 32 productores asociados.
          </p>
          <div class="mt-auto pt-2 border-top">
            <a href="login.php" class="text-decoration-none fw-bold small d-inline-flex align-items-center gap-1" style="color: var(--secondary-green);">
              <span>Consultar mis entregas diarias</span>
              <i class="fa-solid fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Tarjeta 3: Calidad y Cadena de Frío a 4°C -->
      <div class="col-lg-4 col-md-6">
        <div class="overlap-card overlap-card-dusk">
          <div class="d-flex align-items-center justify-content-between">
            <div class="overlap-card-icon icon-bg-dusk">
              <i class="fa-solid fa-temperature-arrow-down"></i>
            </div>
            <span class="badge rounded-pill fw-bold" style="background-color: var(--accent-dusk-soft); color: var(--accent-dusk);">
              4°C Garantizados
            </span>
          </div>
          <span class="overlap-card-label">Tanques de Frío &amp; Análisis</span>
          <div class="overlap-card-value">
            Calidad BPO <span class="fs-6 fw-normal text-muted">Certificada</span>
          </div>
          <p class="small text-muted mb-3">
            <i class="fa-solid fa-circle-check text-primary me-1"></i> Inspección diaria de acidez, grasa y sólidos totales en centro de acopio.
          </p>
          <div class="mt-auto pt-2 border-top">
            <a href="#infraestructura" class="text-decoration-none fw-bold small d-inline-flex align-items-center gap-1" style="color: var(--accent-dusk);">
              <span>Conocer nuestra infraestructura técnica</span>
              <i class="fa-solid fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       3. SECCIÓN: ACCESOS DIRECTOS OPERATIVOS (4 TARJETAS INTERACTIVAS)
       ======================================================================== -->
  <section class="py-5" style="background-color: var(--bg-surface);">
    <div class="container py-3">
      <div class="text-center mb-5">
        <span class="section-badge">Módulos del Sistema</span>
        <h2 class="section-title-agro">Accesos Directos Operativos</h2>
        <p class="section-subtitle-agro mx-auto">
          Herramientas transaccionales seguras creadas para optimizar la labor del ganadero y la administración.
        </p>
      </div>

      <div class="row g-4">
        <!-- Tarjeta 1: Resumen de Acopio -->
        <div class="col-lg-3 col-sm-6">
          <a href="login.php" class="quick-access-card">
            <div class="quick-access-icon" style="background-color: #DCFCE7; color: var(--secondary-green);">
              <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <h3 class="quick-access-title">Resumen de Acopio</h3>
            <p class="quick-access-desc">
              Consulte el histórico de litros entregados en la quincena actual y anteriores con discriminación por turno.
            </p>
            <span class="quick-access-link">
              Ver entregas <i class="fa-solid fa-chevron-right fs-6"></i>
            </span>
          </a>
        </div>

        <!-- Tarjeta 2: Portal del Asociado -->
        <div class="col-lg-3 col-sm-6">
          <a href="login.php" class="quick-access-card">
            <div class="quick-access-icon" style="background-color: var(--accent-gold-soft); color: var(--accent-gold);">
              <i class="fa-solid fa-user-lock"></i>
            </div>
            <h3 class="quick-access-title">Portal del Asociado</h3>
            <p class="quick-access-desc">
              Acceso individual protegido con Google OAuth o contraseña para verificar su liquidación en pesos COP.
            </p>
            <span class="quick-access-link" style="color: var(--accent-gold);">
              Iniciar sesión <i class="fa-solid fa-chevron-right fs-6"></i>
            </span>
          </a>
        </div>

        <!-- Tarjeta 3: Certificados y Colillas -->
        <div class="col-lg-3 col-sm-6">
          <a href="login.php" class="quick-access-card">
            <div class="quick-access-icon" style="background-color: var(--accent-dusk-soft); color: var(--accent-dusk);">
              <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <h3 class="quick-access-title">Colillas de Pago</h3>
            <p class="quick-access-desc">
              Genere e imprima su comprobante de pago quincenal con desglose de pesajes y precio unitario pactado.
            </p>
            <span class="quick-access-link" style="color: var(--accent-dusk);">
              Descargar recibo <i class="fa-solid fa-chevron-right fs-6"></i>
            </span>
          </a>
        </div>

        <!-- Tarjeta 4: Realiza tu F-PQRS -->
        <div class="col-lg-3 col-sm-6">
          <a href="#contacto" class="quick-access-card">
            <div class="quick-access-icon" style="background-color: #FEE2E2; color: #DC2626;">
              <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <h3 class="quick-access-title">Realiza tu F-PQRS</h3>
            <p class="quick-access-desc">
              Buzón oficial de quejas, reclamos de pesaje y sugerencias con respuesta trazable y notificación formal.
            </p>
            <span class="quick-access-link" style="color: #DC2626;">
              Radicar reclamo <i class="fa-solid fa-chevron-right fs-6"></i>
            </span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       4. SECCIÓN: NUESTRAS ÁREAS OPERATIVAS (CENTRAL GANADERA INSPIRATION)
       ======================================================================== -->
  <section id="areas" class="py-5 bg-white">
    <div class="container py-4">
      <div class="row align-items-center mb-5">
        <div class="col-lg-7">
          <span class="section-badge">Cadena Productiva</span>
          <h2 class="section-title-agro">Nuestras Áreas de Operación</h2>
          <p class="section-subtitle-agro mb-0">
            Garantizamos la trazabilidad total desde el ordeño en la finca campesina hasta la entrega a la industria transformadora.
          </p>
        </div>
        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
          <span class="badge py-2 px-3 rounded-pill bg-light text-dark border">
            <i class="fa-solid fa-cow text-success me-1"></i> 32 Fincas Vinculadas &bull; Vereda Alizal
          </span>
        </div>
      </div>

      <div class="row g-4">
        <!-- Área 1: Acopio Técnico -->
        <div class="col-md-6 col-lg-3">
          <div class="feature-box">
            <div class="feature-box-icon">
              <i class="fa-solid fa-truck-droplet"></i>
            </div>
            <h4 class="fw-bold mb-2 text-dark">1. Acopio Técnico</h4>
            <p class="text-muted small mb-0">
              Recepción diaria en turnos de Mañana (5:30 AM &ndash; 7:30 AM) y Tarde (4:00 PM &ndash; 5:30 PM) con pesaje milimétrico y registro digital instantáneo.
            </p>
          </div>
        </div>

        <!-- Área 2: Control de Calidad -->
        <div class="col-md-6 col-lg-3">
          <div class="feature-box">
            <div class="feature-box-icon" style="background-color: var(--accent-gold-soft); color: var(--accent-gold);">
              <i class="fa-solid fa-vial-circle-check"></i>
            </div>
            <h4 class="fw-bold mb-2 text-dark">2. Control de Calidad</h4>
            <p class="text-muted small mb-0">
              Pruebas de alcohol, densidad, acidez y mastitis con equipos certificados para certificar leche pura, limpia y libre de adulteraciones.
            </p>
          </div>
        </div>

        <!-- Área 3: Liquidación Puntual -->
        <div class="col-md-6 col-lg-3">
          <div class="feature-box">
            <div class="feature-box-icon" style="background-color: #EFF6FF; color: #2563EB;">
              <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
            <h4 class="fw-bold mb-2 text-dark">3. Liquidación Transparente</h4>
            <p class="text-muted small mb-0">
              Pagos quincenales exactos y sin deducciones sorpresa, respaldados por la colilla de pago digital con valor unitario establecido en asamblea.
            </p>
          </div>
        </div>

        <!-- Área 4: Sostenibilidad Rural -->
        <div class="col-md-6 col-lg-3">
          <div class="feature-box">
            <div class="feature-box-icon" style="background-color: #FEF3C7; color: #D97706;">
              <i class="fa-solid fa-seedling"></i>
            </div>
            <h4 class="fw-bold mb-2 text-dark">4. Sostenibilidad</h4>
            <p class="text-muted small mb-0">
              Promoción de silvopastoreo, conservación de fuentes hídricas de alta montaña y bienestar animal bajo parámetros de la normatividad ambiental.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       5. SECCIÓN: QUIÉNES SOMOS & INFRAESTRUCTURA
       ======================================================================== -->
  <section id="quienes-somos" class="py-5" style="background-color: var(--bg-surface);">
    <div class="container py-4">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <span class="section-badge">Trayectoria &bull; Carmen de Carupa</span>
          <h2 class="section-title-agro">Unión Campesina para el Progreso Agropecuario</h2>
          <p class="text-muted mb-4" style="line-height: 1.8;">
            La <strong><?php echo e(EMPRESA_NOMBRE); ?></strong> (<strong><?php echo e(EMPRESA_SIGLA); ?></strong>) nació en la Vereda Alizal de Carmen de Carupa como una iniciativa comunitaria de pequeños y medianos productores lecheros que buscaban equidad en el pesaje, protección de su precio y acceso directo a canales comerciales formales.
          </p>

          <div class="row g-3 mb-4">
            <div class="col-sm-6">
              <div class="p-3 bg-white rounded-3 border">
                <div class="fw-bold fs-4 text-success tabular-nums">32</div>
                <div class="small text-muted fw-bold text-uppercase">Familias Campesinas</div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 bg-white rounded-3 border">
                <div class="fw-bold fs-4 text-warning tabular-nums">100%</div>
                <div class="small text-muted fw-bold text-uppercase">Trazabilidad Diaria</div>
              </div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-3">
            <i class="fa-solid fa-check-double text-success fs-4"></i>
            <span class="fw-bold text-dark">Comprometidos con el desarrollo económico y la seguridad alimentaria de Cundinamarca.</span>
          </div>
        </div>

        <div class="col-lg-6" id="infraestructura">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <img src="assets/img/infra-tanque.webp" alt="Tanque de enfriamiento de acero inoxidable en ASOAGROALIZAL" class="img-fluid" style="aspect-ratio: 16/10; object-fit: cover;">
            <div class="card-body p-4 bg-white">
              <h5 class="fw-bold text-dark mb-2">
                <i class="fa-solid fa-snowflake text-primary me-2"></i> Infraestructura de Frío y Acopio
              </h5>
              <p class="small text-muted mb-0">
                Disponemos de tanques de expansión en acero inoxidable con agitadores automáticos y plantas de respaldo para mantener la leche por debajo de 4°C de manera continua, previniendo proliferación bacteriana.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       6. GALERÍA FOTOGRÁFICA MODERNA (CON LIGHTBOX MODAL)
       ======================================================================== -->
  <section id="galeria" class="py-5 bg-white">
    <div class="container py-4">
      <div class="text-center mb-5">
        <span class="section-badge">Nuestra Labor</span>
        <h2 class="section-title-agro">Galería del Campo y Centro de Acopio</h2>
        <p class="section-subtitle-agro mx-auto">
          Imágenes reales de nuestras fincas asociadas, ordeño higiénico y laboratorios en la Vereda Alizal.
        </p>
      </div>

      <div class="row g-4">
        <!-- Foto 1 -->
        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/hero-ganado.webp" data-img-title="Pasturas de Alta Montaña - Vereda Alizal">
            <img src="assets/img/hero-ganado.webp" alt="Pasturas y vacas lecheras" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold mb-1">Pasturas de Alta Montaña</h6>
              <small><i class="fa-solid fa-magnifying-glass-plus me-1"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <!-- Foto 2 -->
        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/infra-tanque.webp" data-img-title="Tanque de Enfriamiento a 4°C">
            <img src="assets/img/infra-tanque.webp" alt="Tanque de refrigeración láctea" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold mb-1">Cadena de Frío a 4°C</h6>
              <small><i class="fa-solid fa-magnifying-glass-plus me-1"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <!-- Foto 3 -->
        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/infra-laboratorio.webp" data-img-title="Pruebas de Calidad y Laboratorio">
            <img src="assets/img/infra-laboratorio.webp" alt="Control de laboratorio y acidez" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold mb-1">Control de Acidez y Grasa</h6>
              <small><i class="fa-solid fa-magnifying-glass-plus me-1"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <!-- Foto 4 -->
        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/finca-andina.webp" data-img-title="Paisaje y Paisaje Rural Andino">
            <img src="assets/img/finca-andina.webp" alt="Finca lechera en Carmen de Carupa" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold mb-1">Fincas Asociadas</h6>
              <small><i class="fa-solid fa-magnifying-glass-plus me-1"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <!-- Foto 5 -->
        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/card-bienestar.webp" data-img-title="Bienestar Animal y Pastoreo">
            <img src="assets/img/card-bienestar.webp" alt="Bienestar animal en ordeño" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold mb-1">Bienestar Animal</h6>
              <small><i class="fa-solid fa-magnifying-glass-plus me-1"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <!-- Foto 6 -->
        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/infra-invernadero.webp" data-img-title="Infraestructura Comunitaria Sostenible">
            <img src="assets/img/infra-invernadero.webp" alt="Invernadero y producción complementaria" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold mb-1">Proyectos Sostenibles</h6>
              <small><i class="fa-solid fa-magnifying-glass-plus me-1"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MODAL LIGHTBOX PARA LA GALERÍA -->
  <div class="modal fade" id="modalLightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-transparent border-0">
        <div class="modal-body p-0 text-center position-relative">
          <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          <img id="lightboxImg" src="" alt="Vista ampliada" class="img-fluid rounded-4 shadow-lg" style="max-height: 82vh;">
          <p id="lightboxCaption" class="text-white mt-2 fw-bold"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       7. FORMULARIO CORPORATIVO DE CONTACTO Y F-PQRS
       ======================================================================== -->
  <section id="contacto" class="py-5" style="background-color: var(--bg-surface);">
    <div class="container py-4">
      <div class="row g-5">
        
        <!-- Información de Atención -->
        <div class="col-lg-5">
          <span class="section-badge">Atención al Productor</span>
          <h2 class="section-title-agro">Radica tu Solicitud o F-PQRS</h2>
          <p class="text-muted mb-4" style="line-height: 1.7;">
            Canal formal para atención de consultas de asociados, inconformidades con pesajes o solicitud de información comercial.
          </p>

          <div class="d-flex flex-column gap-3 mb-4">
            <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border">
              <div class="overlap-card-icon icon-bg-emerald mb-0">
                <i class="fa-solid fa-phone"></i>
              </div>
              <div>
                <small class="text-muted d-block fw-bold text-uppercase">Teléfono de Contacto</small>
                <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="fw-bold text-dark text-decoration-none fs-5">
                  <?php echo e(EMPRESA_TELEFONO); ?>
                </a>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border">
              <div class="overlap-card-icon icon-bg-gold mb-0">
                <i class="fa-brands fa-whatsapp"></i>
              </div>
              <div>
                <small class="text-muted d-block fw-bold text-uppercase">WhatsApp Oficial</small>
                <a href="https://wa.me/57<?php echo e(EMPRESA_TELEFONO); ?>" target="_blank" rel="noopener noreferrer" class="fw-bold text-success text-decoration-none fs-5">
                  Chat Directo 24/7
                </a>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border">
              <div class="overlap-card-icon icon-bg-dusk mb-0">
                <i class="fa-solid fa-envelope"></i>
              </div>
              <div>
                <small class="text-muted d-block fw-bold text-uppercase">Correo Electrónico</small>
                <a href="mailto:<?php echo e(EMPRESA_CORREO); ?>" class="fw-bold text-dark text-decoration-none">
                  <?php echo e(EMPRESA_CORREO); ?>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Formulario Seguro con CSRF -->
        <div class="col-lg-7">
          <div class="card border-0 shadow-md rounded-4 p-4 p-md-5 bg-white">
            <h4 class="fw-bold text-dark mb-1">Formulario Único de Contacto / Reclamos</h4>
            <p class="small text-muted mb-4">Diligencie los campos requeridos para asignarle un número de radicado interno.</p>

            <form action="index.php#contacto" method="POST" autocomplete="off">
              <!-- Token CSRF Obligatorio -->
              <input type="hidden" name="csrf_token" value="<?php echo e(generarCsrfToken()); ?>">
              <input type="hidden" name="accion_contacto" value="1">
              
              <!-- Honeypot anti-spam silencioso -->
              <div style="display:none;" aria-hidden="true">
                <label for="website_hp">Si es humano deje este campo vacío:</label>
                <input type="text" id="website_hp" name="website_hp" tabindex="-1">
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label for="nombre" class="form-label fw-bold small text-dark">Nombre Completo *</label>
                  <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. Juan Pérez" required minlength="3" maxlength="120">
                </div>

                <div class="col-md-6">
                  <label for="contacto" class="form-label fw-bold small text-dark">Teléfono o WhatsApp *</label>
                  <input type="tel" class="form-control" id="contacto" name="contacto" placeholder="Ej. 3101234567" required minlength="7" maxlength="50">
                </div>

                <div class="col-12">
                  <label for="asunto" class="form-label fw-bold small text-dark">Tipo de Solicitud *</label>
                  <select class="form-select" id="asunto" name="asunto" required>
                    <option value="Consulta General" selected>Consulta General / Asociación</option>
                    <option value="Reclamo de Pesaje Quincenal">F-PQRS: Reclamo sobre Pesaje o Litros</option>
                    <option value="Inconformidad de Pago">F-PQRS: Inconformidad sobre Liquidación ($ COP)</option>
                    <option value="Solicitud de Certificado">Solicitud de Certificado Comercial</option>
                  </select>
                </div>

                <div class="col-12">
                  <label for="mensaje" class="form-label fw-bold small text-dark">Mensaje o Detalle del Reclamo *</label>
                  <textarea class="form-control" id="mensaje" name="mensaje" rows="4" placeholder="Describa claramente su solicitud o reclamo..." required minlength="10" maxlength="1500"></textarea>
                </div>

                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="acepta_datos" name="acepta_datos" value="1" required>
                    <label class="form-check-label small text-muted" for="acepta_datos">
                      Autorizo a ASOAGROALIZAL para el tratamiento de mis datos personales según la <a href="#modalPoliticaDatos" data-bs-toggle="modal" class="fw-bold text-success text-decoration-none">Ley 1581 de 2012</a>.
                    </label>
                  </div>
                </div>

                <div class="col-12 mt-4">
                  <button type="submit" class="btn text-white w-100 py-3 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background-color: var(--primary-emerald);">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Radicar Mensaje / F-PQRS</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </section>

</main>

<script>
  // Inicialización del Lightbox para la Galería
  document.addEventListener('DOMContentLoaded', function() {
    const lightboxModal = document.getElementById('modalLightbox');
    if (lightboxModal) {
      lightboxModal.addEventListener('show.bs.modal', function(event) {
        const trigger = event.relatedTarget;
        const imgSrc = trigger.getAttribute('data-img-src');
        const imgTitle = trigger.getAttribute('data-img-title');
        document.getElementById('lightboxImg').src = imgSrc;
        document.getElementById('lightboxCaption').textContent = imgTitle;
      });
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
