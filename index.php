<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: index.php
 * Propósito: Portal público institucional corporativo:
 *            - Paleta Bosque Esmeralda (#0F4C3A), Salvia (#F4F7F5) y Cobre Dorado (#D97706).
 *            - Hero inmersivo con tarjetas flotantes superpuestas (Overlap Cards).
 *            - 4 Accesos Directos Operativos, Áreas BPO, Misión y Visión Oficial, Aliados.
 *            - Galería del Campo e Infraestructura con Modal Lightbox.
 *            - Formulario seguro F-PQRS con CSRF, Honeypot y persistencia en `consulta`.
 *            - 100% CERO EMOJIS, iconografía Lucide Icons monocromática (1.5px).
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

// 2. Consulta de litros totales recibidos (tabla entrega o registros_leche)
$totalLitros = 0.0;
try {
    $stmtL = $pdo->query("SELECT COALESCE(SUM(litros), 0) AS total FROM entrega");
    if ($rowL = $stmtL->fetch(PDO::FETCH_ASSOC)) {
        $totalLitros = (float)$rowL['total'];
    }
    if ($totalLitros <= 0) {
        $stmtL2 = $pdo->query("SELECT COALESCE(SUM(litros), 0) AS total FROM registros_leche");
        if ($rowL2 = $stmtL2->fetch(PDO::FETCH_ASSOC)) {
            $totalLitros = (float)$rowL2['total'];
        }
    }
} catch (Exception $e) {}
if ($totalLitros <= 0) {
    $totalLitros = 26355;
}

// 3. Manejo seguro del formulario de Contacto / Radicación F-PQRS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_contacto'])) {
    if (!empty($_POST['website_hp'])) {
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
        setFlashAlert('success', 'Mensaje Radicado', 'Gracias por comunicarse con ASOAGROALIZAL. Su solicitud ha sido registrada formalmente.');
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
       1. HERO SECTION CORPORATIVO (BOSQUE ESMERALDA + COBRE DORADO)
       ======================================================================== -->
  <section id="inicio" class="hero-corporate">
    <img src="assets/img/hero-ganado.webp" alt="Ganadería lechera en pasturas de la Vereda Alizal" class="hero-bg-media" width="1600" height="1063" loading="eager" fetchpriority="high">
    <div class="hero-overlay-dark"></div>

    <div class="container hero-content py-4">
      <div class="row align-items-center">
        <div class="col-xl-9 col-lg-10">

          <!-- Eyebrow Pill -->
          <div class="hero-eyebrow mb-3">
            <i data-lucide="map-pin" style="width: 15px; height: 15px; color: #FCD34D;"></i>
            <span>CARMEN DE CARUPA &bull; VEREDA ALIZAL &bull; NIT <?php echo e(EMPRESA_NIT); ?></span>
          </div>

          <!-- Titular Principal -->
          <h1 class="hero-title-main">
            Asociación Agropecuaria Alizal &ndash; <br>
            <span class="text-gold">Impulsando la Producción Láctea</span> con Control y Transparencia
          </h1>

          <!-- Descripción -->
          <p class="hero-description">
            Plataforma oficial de control lechero para los 32 productores asociados de ASOAGROALIZAL.
            Registro diario riguroso en dos turnos (AM y PM), buenas prácticas de ordeño BPO y liquidación quincenal oportuna.
          </p>

          <!-- Botones de Acción Inmediata -->
          <div class="d-flex flex-wrap gap-3 pt-1">
            <?php if ($usuarioActual): ?>
              <?php $urlDestino = ($usuarioActual['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
              <a href="<?php echo $urlDestino; ?>" class="btn-cta-gold">
                <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                <span>Ir a Mi Panel de Control</span>
              </a>
            <?php else: ?>
              <a href="login.php" class="btn-cta-gold">
                <i data-lucide="log-in" style="width: 18px; height: 18px;"></i>
                <span>Ingresar como Asociado</span>
              </a>
              <a href="#areas" class="btn-cta-outline">
                <i data-lucide="layers" style="width: 18px; height: 18px;"></i>
                <span>Nuestras Áreas Operativas</span>
              </a>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       2. TARJETAS FLOTANTES SUPERPUESTAS (OVERLAP CARDS)
       ======================================================================== -->
  <section class="container hero-overlap-container">
    <div class="row g-4 justify-content-center">

      <!-- Tarjeta 1: Precio Oficial en Quincena -->
      <div class="col-lg-4 col-md-6">
        <div class="overlap-card overlap-card-gold" id="precio">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="overlap-card-icon icon-bg-gold mb-0">
              <i data-lucide="circle-dollar-sign" style="width: 24px; height: 24px;"></i>
            </div>
            <span class="badge rounded-pill fw-bold px-3 py-1" style="background-color: var(--accent-gold-soft); color: var(--accent-gold-hover);">
              Tarifa Vigente
            </span>
          </div>
          <span class="overlap-card-label mt-2">Precio Oficial por Litro</span>
          <div class="overlap-card-value tabular-nums">
            $<?php echo number_format($precioLitro, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">COP / Litro</span>
          </div>
          <p class="small text-muted mb-3 d-flex align-items-center gap-1.5">
            <i data-lucide="calendar-check" style="width: 15px; height: 15px; color: var(--accent-gold-hover);"></i>
            <span><?php echo e($quincenaTexto); ?></span>
          </p>
          <div class="mt-auto pt-2 border-top">
            <a href="login.php" class="text-decoration-none fw-bold small d-inline-flex align-items-center gap-1" style="color: var(--accent-gold-hover);">
              <span>Ver tabla de liquidación completa</span>
              <i data-lucide="arrow-right" style="width: 15px; height: 15px;"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Tarjeta 2: Acopio Reportado -->
      <div class="col-lg-4 col-md-6">
        <div class="overlap-card overlap-card-emerald">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="overlap-card-icon icon-bg-emerald mb-0">
              <i data-lucide="droplet" style="width: 24px; height: 24px;"></i>
            </div>
            <span class="badge rounded-pill fw-bold px-3 py-1" style="background-color: #DCFCE7; color: var(--brand-secondary);">
              Turnos AM y PM
            </span>
          </div>
          <span class="overlap-card-label mt-2">Acopio Acumulado Reportado</span>
          <div class="overlap-card-value tabular-nums">
            <?php echo number_format($totalLitros, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">Litros Totales</span>
          </div>
          <p class="small text-muted mb-3 d-flex align-items-center gap-1.5">
            <i data-lucide="shield-check" style="width: 15px; height: 15px; color: var(--brand-secondary);"></i>
            <span>Control individual trazable para los <?php echo EMPRESA_ASOCIADOS_TOTAL; ?> productores asociados.</span>
          </p>
          <div class="mt-auto pt-2 border-top">
            <a href="login.php" class="text-decoration-none fw-bold small d-inline-flex align-items-center gap-1" style="color: var(--brand-secondary);">
              <span>Consultar mis entregas diarias</span>
              <i data-lucide="arrow-right" style="width: 15px; height: 15px;"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Tarjeta 3: Calidad y Cadena de Frío -->
      <div class="col-lg-4 col-md-6">
        <div class="overlap-card overlap-card-dusk">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="overlap-card-icon icon-bg-dusk mb-0">
              <i data-lucide="thermometer-snowflake" style="width: 24px; height: 24px;"></i>
            </div>
            <span class="badge rounded-pill fw-bold px-3 py-1" style="background-color: var(--accent-teal-soft); color: var(--accent-teal);">
              Cadena de Frío
            </span>
          </div>
          <span class="overlap-card-label mt-2">Tanques de Frío y Análisis</span>
          <div class="overlap-card-value">
            Calidad BPO <span class="fs-6 fw-normal text-muted">Certificada</span>
          </div>
          <p class="small text-muted mb-3 d-flex align-items-center gap-1.5">
            <i data-lucide="check-circle-2" style="width: 15px; height: 15px; color: var(--accent-teal);"></i>
            <span>Inspección rutinaria de inocuidad, densidad y parámetros composicionales.</span>
          </p>
          <div class="mt-auto pt-2 border-top">
            <a href="#infraestructura" class="text-decoration-none fw-bold small d-inline-flex align-items-center gap-1" style="color: var(--accent-teal);">
              <span>Conocer nuestra infraestructura técnica</span>
              <i data-lucide="arrow-right" style="width: 15px; height: 15px;"></i>
            </a>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       3. ACCESOS DIRECTOS OPERATIVOS (4 TARJETAS)
       ======================================================================== -->
  <section class="py-5" style="background-color: var(--bg-canvas);">
    <div class="container py-2">
      <div class="text-center mb-5">
        <span class="section-badge">Módulos del Sistema</span>
        <h2 class="section-title-agro">Accesos Directos Operativos</h2>
        <p class="section-subtitle-agro mx-auto mb-0">
          Herramientas transaccionales seguras creadas para optimizar la labor del productor lechero y la administración.
        </p>
      </div>

      <div class="row g-4">
        <div class="col-lg-3 col-sm-6">
          <a href="login.php" class="quick-access-card text-decoration-none">
            <div class="quick-access-icon" style="background-color: #DCFCE7; color: var(--brand-secondary);">
              <i data-lucide="clipboard-list" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="quick-access-title">Resumen de Acopio</h3>
            <p class="quick-access-desc">
              Consulte el histórico de litros entregados en la quincena actual y anteriores con discriminación por turno AM y PM.
            </p>
            <span class="quick-access-link">
              Ver entregas <i data-lucide="chevron-right" style="width: 15px; height: 15px;"></i>
            </span>
          </a>
        </div>

        <div class="col-lg-3 col-sm-6">
          <a href="login.php" class="quick-access-card text-decoration-none">
            <div class="quick-access-icon" style="background-color: var(--accent-gold-soft); color: var(--accent-gold-hover);">
              <i data-lucide="lock" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="quick-access-title">Portal del Asociado</h3>
            <p class="quick-access-desc">
              Acceso individual protegido con Google OAuth o cifrado Argon2id para verificar su liquidación en pesos COP.
            </p>
            <span class="quick-access-link" style="color: var(--accent-gold-hover);">
              Iniciar sesión <i data-lucide="chevron-right" style="width: 15px; height: 15px;"></i>
            </span>
          </a>
        </div>

        <div class="col-lg-3 col-sm-6">
          <a href="login.php" class="quick-access-card text-decoration-none">
            <div class="quick-access-icon" style="background-color: var(--accent-dusk-soft); color: var(--accent-dusk);">
              <i data-lucide="file-text" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="quick-access-title">Colillas de Pago</h3>
            <p class="quick-access-desc">
              Genere e imprima su comprobante de pago quincenal con desglose diario por turno y tarifa pactada.
            </p>
            <span class="quick-access-link" style="color: var(--accent-dusk);">
              Generar recibo <i data-lucide="chevron-right" style="width: 15px; height: 15px;"></i>
            </span>
          </a>
        </div>

        <div class="col-lg-3 col-sm-6">
          <a href="#contacto" class="quick-access-card text-decoration-none">
            <div class="quick-access-icon" style="background-color: #FEE2E2; color: #B91C1C;">
              <i data-lucide="mail" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="quick-access-title">Realiza tu F-PQRS</h3>
            <p class="quick-access-desc">
              Buzón oficial de inquietudes, reclamos de pesaje y sugerencias con seguimiento y respuesta trazable.
            </p>
            <span class="quick-access-link" style="color: #B91C1C;">
              Radicar solicitud <i data-lucide="chevron-right" style="width: 15px; height: 15px;"></i>
            </span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       4. NUESTRAS ÁREAS OPERATIVAS
       ======================================================================== -->
  <section id="areas" class="py-5 bg-white border-top border-bottom" style="border-color: var(--border-subtle) !important;">
    <div class="container py-3">
      <div class="row align-items-center mb-5">
        <div class="col-lg-7">
          <span class="section-badge">Cadena Productiva</span>
          <h2 class="section-title-agro">Nuestras Áreas de Operación</h2>
          <p class="section-subtitle-agro mb-0">
            Garantizamos trazabilidad integral desde las rutinas de ordeño en finca hasta la entrega formal a la industria.
          </p>
        </div>
        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
          <span class="badge py-2 px-3 rounded-pill bg-light text-dark border d-inline-flex align-items-center gap-1.5">
            <i data-lucide="check" style="width: 15px; height: 15px; color: var(--brand-secondary);"></i>
            <span><?php echo EMPRESA_ASOCIADOS_TOTAL; ?> Productores Asociados &bull; Vereda Alizal</span>
          </span>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="feature-box">
            <div class="feature-box-icon">
              <i data-lucide="truck" style="width: 24px; height: 24px;"></i>
            </div>
            <h4 class="fw-bold mb-2 h5" style="color: var(--brand-primary);">1. Acopio Técnico</h4>
            <p class="text-muted small mb-0">
              Recepción diaria organizada en turnos AM y PM con pesaje verificado en báscula y registro digital inmediato en planilla.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="feature-box">
            <div class="feature-box-icon" style="background-color: var(--accent-gold-soft); color: var(--accent-gold-hover);">
              <i data-lucide="flask-conical" style="width: 24px; height: 24px;"></i>
            </div>
            <h4 class="fw-bold mb-2 h5" style="color: var(--brand-primary);">2. Control de Calidad BPO</h4>
            <p class="text-muted small mb-0">
              Diagnósticos individuales, pruebas rutinarias y reglamento interno lácteo orientado a inocuidad y parámetros composicionales.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="feature-box">
            <div class="feature-box-icon" style="background-color: var(--accent-dusk-soft); color: var(--accent-dusk);">
              <i data-lucide="badge-dollar-sign" style="width: 24px; height: 24px;"></i>
            </div>
            <h4 class="fw-bold mb-2 h5" style="color: var(--brand-primary);">3. Liquidación Transparente</h4>
            <p class="text-muted small mb-0">
              Cálculo automático de ingresos quincenales según tarifa por litro pactada en asamblea, respaldado por colillas digitales.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="feature-box">
            <div class="feature-box-icon" style="background-color: var(--accent-teal-soft); color: var(--accent-teal);">
              <i data-lucide="sprout" style="width: 24px; height: 24px;"></i>
            </div>
            <h4 class="fw-bold mb-2 h5" style="color: var(--brand-primary);">4. Sostenibilidad Agrícola</h4>
            <p class="text-muted small mb-0">
              Manejo y fertilización técnica de praderas, pastos de corte, invernaderos forrajeros y laboratorio de tejidos vegetales.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       5. QUIÉNES SOMOS, INFRAESTRUCTURA, MISIÓN Y VISIÓN OFICIAL
       ======================================================================== -->
  <section id="quienes-somos" class="py-5" style="background-color: var(--bg-canvas);">
    <div class="container py-3">
      <div class="row g-5 align-items-center mb-5">
        <div class="col-lg-6">
          <span class="section-badge">Trayectoria desde <?php echo EMPRESA_ANO_CONSTITUCION; ?></span>
          <h2 class="section-title-agro">Unión Campesina para el Progreso Agropecuario</h2>
          <p class="text-muted mb-4" style="line-height: 1.75;">
            La <strong><?php echo e(EMPRESA_NOMBRE); ?></strong> (<strong><?php echo e(EMPRESA_SIGLA); ?></strong>) es una organización constituida en el año 2000 con personería jurídica ante la Cámara de Comercio, que une a <?php echo EMPRESA_ASOCIADOS_TOTAL; ?> familias productoras de la Vereda Alizal en Carmen de Carupa.
          </p>

          <div class="row g-3 mb-4">
            <div class="col-sm-4">
              <div class="p-3 bg-white rounded-3 border text-center">
                <div class="fw-bold fs-4 tabular-nums" style="color: var(--brand-primary);"><?php echo EMPRESA_ASOCIADOS_TOTAL; ?></div>
                <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Asociados</div>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 bg-white rounded-3 border text-center">
                <div class="fw-bold fs-4 tabular-nums" style="color: var(--accent-gold-hover);">25+ Años</div>
                <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Trayectoria</div>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 bg-white rounded-3 border text-center">
                <div class="fw-bold fs-4 tabular-nums" style="color: var(--accent-teal);">100%</div>
                <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Trazabilidad</div>
              </div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2 p-3 bg-white rounded-3 border">
            <i data-lucide="award" style="width: 22px; height: 22px; color: var(--accent-gold-hover);"></i>
            <span class="small fw-semibold text-dark">Aliados Institucionales: <strong>Corporación PBA</strong> y <strong>Fundación Bolívar Davivienda</strong>.</span>
          </div>
        </div>

        <div class="col-lg-6" id="infraestructura">
          <div class="card border rounded-4 overflow-hidden shadow-sm bg-white">
            <img src="assets/img/infra-tanque.webp" alt="Tanque de enfriamiento de acero inoxidable en ASOAGROALIZAL" class="img-fluid" style="aspect-ratio: 16/10; object-fit: cover;">
            <div class="card-body p-4">
              <h5 class="fw-bold mb-2 d-flex align-items-center gap-2" style="color: var(--brand-primary);">
                <i data-lucide="snowflake" style="width: 20px; height: 20px; color: var(--accent-teal);"></i>
                <span>Infraestructura Colectiva Propia</span>
              </h5>
              <p class="small text-muted mb-3">
                La asociación cuenta con terreno propio, tanque de enfriamiento en acero inoxidable, cantinas de recolección, nevera de conservación, carguero de transporte, laboratorio de tejidos vegetales e invernaderos forrajeros.
              </p>
              <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-light text-dark border px-2.5 py-1">Tanque Acero Inoxidable</span>
                <span class="badge bg-light text-dark border px-2.5 py-1">Cantinas y Nevera</span>
                <span class="badge bg-light text-dark border px-2.5 py-1">Laboratorio Tejidos</span>
                <span class="badge bg-light text-dark border px-2.5 py-1">Invernaderos</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Misión y Visión Oficial -->
      <div id="mision-vision" class="row g-4 pt-2">
        <div class="col-md-6">
          <div class="card h-100 border rounded-4 p-4 bg-white shadow-sm" style="border-top: 4px solid var(--brand-primary) !important;">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="p-2 rounded-3" style="background-color: #E6F2EC; color: var(--brand-primary);">
                <i data-lucide="compass" style="width: 20px; height: 20px;"></i>
              </div>
              <h4 class="fw-bold mb-0 h5" style="color: var(--brand-primary);">Misión Institucional</h4>
            </div>
            <p class="text-muted mb-0" style="line-height: 1.7;">
              &ldquo;Asoagroalizal es una organización que busca unir esfuerzos para la producción sostenible de alimentos que permita el mejoramiento de la calidad de vida de las familias de la vereda de Alizal.&rdquo;
            </p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100 border rounded-4 p-4 bg-white shadow-sm" style="border-top: 4px solid var(--brand-accent) !important;">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="p-2 rounded-3" style="background-color: var(--accent-gold-soft); color: var(--accent-gold-hover);">
                <i data-lucide="telescope" style="width: 20px; height: 20px;"></i>
              </div>
              <h4 class="fw-bold mb-0 h5" style="color: var(--brand-primary);">Visión 2030</h4>
            </div>
            <p class="text-muted mb-0" style="line-height: 1.7;">
              &ldquo;Para el año 2030 Asoagroalizal busca llegar a transformar el 50% de su producción láctea y darle valor agregado a otros productos como la papa y los tubérculos andinos, así como buscar nuevos mercados que le generen ingresos a los asociados y sus familias.&rdquo;
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       6. GALERÍA FOTOGRÁFICA CON LIGHTBOX MODAL
       ======================================================================== -->
  <section id="galeria" class="py-5 bg-white border-top border-bottom" style="border-color: var(--border-subtle) !important;">
    <div class="container py-3">
      <div class="text-center mb-5">
        <span class="section-badge">Nuestra Labor</span>
        <h2 class="section-title-agro">Galería del Campo y Centro de Acopio</h2>
        <p class="section-subtitle-agro mx-auto mb-0">
          Registro visual de nuestras fincas asociadas, cadena de frío y proyectos productivos en la Vereda Alizal.
        </p>
      </div>

      <div class="row g-4">
        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/hero-ganado.webp" data-img-title="Pasturas de Alta Montaña - Vereda Alizal">
            <img src="assets/img/hero-ganado.webp" alt="Pasturas y vacas lecheras" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold text-white mb-1">Pasturas de Alta Montaña</h6>
              <small class="d-inline-flex align-items-center gap-1"><i data-lucide="zoom-in" style="width: 14px; height: 14px;"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/infra-tanque.webp" data-img-title="Tanque de Enfriamiento en Acero Inoxidable">
            <img src="assets/img/infra-tanque.webp" alt="Tanque de refrigeración láctea" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold text-white mb-1">Cadena de Frío en Acero Inoxidable</h6>
              <small class="d-inline-flex align-items-center gap-1"><i data-lucide="zoom-in" style="width: 14px; height: 14px;"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/infra-laboratorio.webp" data-img-title="Laboratorio de Tejidos Vegetales y Pruebas">
            <img src="assets/img/infra-laboratorio.webp" alt="Laboratorio y análisis técnico" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold text-white mb-1">Laboratorio y Análisis Técnico</h6>
              <small class="d-inline-flex align-items-center gap-1"><i data-lucide="zoom-in" style="width: 14px; height: 14px;"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/finca-andina.webp" data-img-title="Paisaje Rural Andino - Carmen de Carupa">
            <img src="assets/img/finca-andina.webp" alt="Finca lechera en Carmen de Carupa" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold text-white mb-1">Fincas Asociadas</h6>
              <small class="d-inline-flex align-items-center gap-1"><i data-lucide="zoom-in" style="width: 14px; height: 14px;"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <!-- Foto 5 -->
        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/card-sanidad.webp" data-img-title="Ganadería Lechera y Bienestar Animal BPO">
            <img src="assets/img/card-sanidad.webp" alt="Bienestar animal y ganado lechero en pasturas" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold text-white mb-1">Bienestar Animal BPO</h6>
              <small class="d-inline-flex align-items-center gap-1"><i data-lucide="zoom-in" style="width: 14px; height: 14px;"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>

        <div class="col-md-4 col-sm-6">
          <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modalLightbox" data-img-src="assets/img/infra-invernadero.webp" data-img-title="Invernaderos y Módulos Forrajeros Sostenibles">
            <img src="assets/img/infra-invernadero.webp" alt="Invernadero y producción complementaria" loading="lazy">
            <div class="gallery-overlay">
              <h6 class="fw-bold text-white mb-1">Invernaderos y Sostenibilidad</h6>
              <small class="d-inline-flex align-items-center gap-1"><i data-lucide="zoom-in" style="width: 14px; height: 14px;"></i> Clic para ampliar</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MODAL LIGHTBOX -->
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
  <section id="contacto" class="py-5" style="background-color: var(--bg-canvas);">
    <div class="container py-3">
      <div class="row g-5">
        
        <div class="col-lg-5">
          <span class="section-badge">Atención al Productor</span>
          <h2 class="section-title-agro">Radica tu Solicitud o F-PQRS</h2>
          <p class="text-muted mb-4" style="line-height: 1.7;">
            Canal institucional para consultas de productores asociados, solicitudes comerciales o novedades administrativas.
          </p>

          <div class="d-flex flex-column gap-3 mb-4">
            <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border">
              <div class="overlap-card-icon icon-bg-emerald mb-0">
                <i data-lucide="phone" style="width: 20px; height: 20px;"></i>
              </div>
              <div>
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.72rem;">Teléfono Oficial</small>
                <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="fw-bold text-dark text-decoration-none fs-5 font-mono">
                  <?php echo e(EMPRESA_TELEFONO); ?>
                </a>
              </div>
            </div>

            <?php if (defined('WHATSAPP_ACTIVO') && WHATSAPP_ACTIVO): ?>
              <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border">
                <div class="overlap-card-icon icon-bg-gold mb-0">
                  <i data-lucide="message-circle" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                  <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.72rem;">WhatsApp Oficial</small>
                  <a href="https://wa.me/57<?php echo e(EMPRESA_TELEFONO); ?>" target="_blank" rel="noopener noreferrer" class="fw-bold text-success text-decoration-none fs-5">
                    Chat Directo
                  </a>
                </div>
              </div>
            <?php else: ?>
              <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border">
                <div class="overlap-card-icon icon-bg-gold mb-0">
                  <i data-lucide="map-pin" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                  <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.72rem;">Sede Principal</small>
                  <span class="fw-bold text-dark d-block small"><?php echo e(EMPRESA_UBICACION); ?></span>
                </div>
              </div>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border">
              <div class="overlap-card-icon icon-bg-dusk mb-0">
                <i data-lucide="mail" style="width: 20px; height: 20px;"></i>
              </div>
              <div>
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.72rem;">Correo Electrónico</small>
                <a href="mailto:<?php echo e(EMPRESA_CORREO); ?>" class="fw-bold text-dark text-decoration-none">
                  <?php echo e(EMPRESA_CORREO); ?>
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card border rounded-4 p-4 p-md-5 bg-white shadow-sm">
            <h4 class="fw-bold mb-1" style="color: var(--brand-primary);">Formulario Único de Contacto / Reclamos</h4>
            <p class="small text-muted mb-4">Diligencie los campos requeridos para asignar un registro de atención interna.</p>

            <form action="index.php#contacto" method="POST" autocomplete="off">
              <input type="hidden" name="csrf_token" value="<?php echo e(generarCsrfToken()); ?>">
              <input type="hidden" name="accion_contacto" value="1">
              
              <div style="display:none;" aria-hidden="true">
                <label for="website_hp">Si es humano deje este campo vacío:</label>
                <input type="text" id="website_hp" name="website_hp" tabindex="-1">
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label for="nombre" class="form-label fw-semibold small text-dark">Nombre Completo *</label>
                  <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. Juan Pérez" required minlength="3" maxlength="120">
                </div>

                <div class="col-md-6">
                  <label for="contacto" class="form-label fw-semibold small text-dark">Teléfono o Celular *</label>
                  <input type="tel" class="form-control" id="contacto" name="contacto" placeholder="Ej. 3101234567" required minlength="7" maxlength="50">
                </div>

                <div class="col-12">
                  <label for="asunto" class="form-label fw-semibold small text-dark">Tipo de Solicitud *</label>
                  <select class="form-select" id="asunto" name="asunto" required>
                    <option value="Consulta General" selected>Consulta General / Asociación</option>
                    <option value="Reclamo de Pesaje Quincenal">F-PQRS: Reclamo sobre Pesaje o Litros</option>
                    <option value="Inconformidad de Pago">F-PQRS: Inconformidad sobre Liquidación ($ COP)</option>
                    <option value="Solicitud de Certificado">Solicitud de Certificado Comercial</option>
                  </select>
                </div>

                <div class="col-12">
                  <label for="mensaje" class="form-label fw-semibold small text-dark">Mensaje o Detalle *</label>
                  <textarea class="form-control" id="mensaje" name="mensaje" rows="4" placeholder="Describa claramente su solicitud..." required minlength="10" maxlength="1500"></textarea>
                </div>

                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="acepta_datos" name="acepta_datos" value="1" required>
                    <label class="form-check-label small text-muted" for="acepta_datos">
                      Autorizo a ASOAGROALIZAL para el tratamiento de mis datos personales según la <a href="#modalPoliticaDatos" data-bs-toggle="modal" class="fw-bold text-success text-decoration-none">Ley 1581 de 2012</a>.
                    </label>
                  </div>
                </div>

                <div class="col-12 mt-3">
                  <button type="submit" class="btn-auth-submit py-3">
                    <i data-lucide="send" style="width: 18px; height: 18px;"></i>
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
  document.addEventListener('DOMContentLoaded', function() {
    const lightboxModal = document.getElementById('modalLightbox');
    if (lightboxModal) {
      lightboxModal.addEventListener('show.bs.modal', function(event) {
        const trigger = event.relatedTarget;
        document.getElementById('lightboxImg').src = trigger.getAttribute('data-img-src');
        document.getElementById('lightboxCaption').textContent = trigger.getAttribute('data-img-title');
      });
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
