<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: index.php
 * Propósito: Portal público institucional accesible, minimalista, cálido y profesional:
 *            - Tipografía Atkinson Hyperlegible, base 20px, diseño fluido y adaptativo.
 *            - Héroe inmersivo con fotografía real de campo y gradiente contrastado.
 *            - Paleta institucional: Azul marino #16375f, blanco puro #ffffff, crema #f7f4ee, oro #b56f10.
 *            - Animaciones interactivas: reveals al scroll, contadores numéricos y micro-interacciones.
 *            - Cero dependencias externas complejas (100% compatible).
 *            - Formulario con CSRF y protección de datos (Ley 1581 de 2012).
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

// Manejo de envío de formulario de contacto
$errorContacto = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_contacto'])) {
    if (!empty($_POST['website_hp'])) {
        // Honeypot anti-spam silencioso
        setFlashAlert('success', 'Consulta Recibida', 'Su mensaje ha sido registrado exitosamente.');
        header('Location: index.php#contacto');
        exit;
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($csrfToken)) {
        setFlashAlert('error', 'Sesión Expirada', 'El token de seguridad ha caducado. Por favor intente nuevamente.');
        header('Location: index.php#contacto');
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    $aceptaDatos = !empty($_POST['acepta_datos']);

    if (empty($nombre) || empty($contacto) || empty($mensaje) || !$aceptaDatos) {
        setFlashAlert('warning', 'Campos Requeridos', 'Por favor diligencie todos los campos obligatorios y autorice el tratamiento de datos.');
        header('Location: index.php#contacto');
        exit;
    }

    try {
        $pdo = getDB();
        $conId = generarUUID('con');
        $stmt = $pdo->prepare("INSERT INTO consulta (id, nombre, contacto, mensaje, fecha, atendida) VALUES (?, ?, ?, ?, NOW(), 0)");
        $stmt->execute([$conId, $nombre, $contacto, $mensaje]);
        setFlashAlert('success', '¡Mensaje Enviado!', 'Gracias por comunicarse con ASOAGROALIZAL. Su mensaje ha sido radicado con éxito.');
        header('Location: index.php#contacto');
        exit;
    } catch (Exception $e) {
        error_log("Error guardando consulta en index.php: " . $e->getMessage());
        setFlashAlert('error', 'Error al Enviar', 'Ocurrió un inconveniente procesando su solicitud. Por favor intente más tarde.');
        header('Location: index.php#contacto');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main id="contenido-principal" class="flex-grow-1">

  <!-- ========================================================================
       1. HÉROE PRINCIPAL INMERSIVO (FOTOGRAFÍA REAL + CONTRASTE OSCURO)
       ======================================================================== -->
  <section id="inicio" class="hero-asoagro position-relative d-flex align-items-center overflow-hidden">
    <!-- Imagen de fondo con overlay oscuro atmosférico -->
    <div class="hero-bg-wrapper position-absolute top-0 start-0 w-100 h-100">
      <img src="assets/img/hero-ganado.webp" alt="Ganadería lechera en pasturas de la Vereda Alizal" class="hero-bg-img w-100 h-100 object-fit-cover">
      <div class="hero-bg-overlay position-absolute top-0 start-0 w-100 h-100"></div>
    </div>

    <div class="container position-relative py-5 my-auto" style="z-index: 2;">
      <div class="row align-items-center justify-content-start">
        <div class="col-lg-9 col-xl-8 text-start text-white py-4">

          <!-- Badge / Eyebrow Pill -->
          <div class="hero-badge mb-4 reveal">
            <i class="bi bi-geo-alt-fill" style="color: #f4c78d;"></i>
            <span>CARMEN DE CARUPA &bull; VEREDA ALIZAL</span>
          </div>

          <!-- Titular Principal con Acento Dorado -->
          <h1 class="hero-title mb-4 reveal">
            Tradición Ganadera,<br>
            <span class="hero-title-highlight">Control y Transparencia</span><br>
            en Cada Quincena
          </h1>

          <!-- Subtítulo -->
          <p class="hero-subtitle mb-5 reveal">
            Plataforma oficial de control lechero para los 32 asociados de <strong class="text-white"><?php echo e(EMPRESA_SIGLA); ?></strong>. Registro diario en dos turnos (☀️ Mañana y 🌅 Tarde) y liquidación clara al alcance de su mano.
          </p>

          <!-- Botones de Acción -->
          <div class="d-flex flex-wrap gap-3 align-items-center reveal">
            <?php if ($usuarioActual): ?>
              <?php $destino = ($usuarioActual['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php'; ?>
              <a href="<?php echo $destino; ?>" class="btn-hero-primary">
                <i class="bi bi-speedometer2 fs-5"></i>
                <span>Ir a Mi Panel de Control</span>
              </a>
            <?php else: ?>
              <a href="login.php" class="btn-hero-primary">
                <i class="bi bi-box-arrow-in-right fs-5"></i>
                <span>Ingresar como Asociado</span>
              </a>
            <?php endif; ?>

            <a href="#como-funciona" class="btn-hero-secondary">
              <i class="bi bi-info-circle fs-5"></i>
              <span>¿Cómo Funciona?</span>
            </a>
          </div>

        </div>
      </div>
    </div>

    <!-- Indicador de desplazamiento (Scroll Indicator) -->
    <a href="#cifras" class="hero-scroll-indicator position-absolute bottom-0 start-50 translate-middle-x mb-4 text-white text-decoration-none text-center" aria-label="Desplazarse hacia abajo">
      <span class="d-block small text-white-50 mb-1" style="font-size: 0.85rem; letter-spacing: 0.04em;">Conozca más</span>
      <i class="bi bi-chevron-down fs-4 animate-bounce" style="color: #f4c78d;"></i>
    </a>
  </section>

  <!-- ========================================================================
       2. CIFRAS CON CONTADOR ANIMADO (FONDO CREMA CÁLIDO)
       ======================================================================== -->
  <section id="cifras" class="py-5 border-bottom" style="background-color: var(--surface-2);">
    <div class="container py-2">
      <div class="row g-4 text-center">
        
        <div class="col-6 col-lg-3">
          <div class="card card-elevate p-4 h-100 rounded-4 border-2" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="display-5 fw-bold mb-2 counter-animated" style="color: var(--primary);" data-counter-target="32">0</div>
            <div class="fw-bold text-dark fs-5">Asociados Activos</div>
            <p class="small text-muted mb-0">Familias productoras de la Vereda Alizal</p>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card card-elevate p-4 h-100 rounded-4 border-2" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="display-5 fw-bold mb-2 counter-animated" style="color: var(--primary);" data-counter-target="25" data-counter-prefix="+">0</div>
            <div class="fw-bold text-dark fs-5">Años de Trayectoria</div>
            <p class="small text-muted mb-0">Constituida legalmente desde el año 2000</p>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card card-elevate p-4 h-100 rounded-4 border-2" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="display-5 fw-bold mb-2 counter-animated" style="color: var(--primary);" data-counter-target="2">0</div>
            <div class="fw-bold text-dark fs-5">Turnos Diarios</div>
            <p class="small text-muted mb-0">Pesaje matutino ☀️ y vespertino 🌅</p>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card card-elevate p-4 h-100 rounded-4 border-2" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="display-5 fw-bold mb-2 counter-animated" style="color: var(--accent);" data-counter-target="15">0</div>
            <div class="fw-bold text-dark fs-5">Días por Quincena</div>
            <p class="small text-muted mb-0">Liquidación periódica con precio oficial</p>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ========================================================================
       3. QUIÉNES SOMOS (FOTOGRAFÍA REAL + HISTORIA)
       ======================================================================== -->
  <section id="quienes-somos" class="py-5" style="background-color: var(--surface);">
    <div class="container py-lg-4">
      <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-6">
          <div class="position-relative card-elevate rounded-4 overflow-hidden border-2" style="border: 2px solid var(--border-subtle);">
            <img src="assets/img/finca-andina.webp" alt="Paisaje de la Vereda Alizal en Carmen de Carupa" class="img-fluid w-100 object-fit-cover" style="max-height: 440px;">
            <div class="position-absolute bottom-0 start-0 w-100 p-3 text-white" style="background: linear-gradient(transparent, rgba(16, 36, 59, 0.9));">
              <span class="badge px-3 py-1.5 rounded-pill fw-bold text-white" style="background-color: var(--accent);">Vereda Alizal &bull; 2.980 m.s.n.m.</span>
              <p class="small mb-0 mt-1 opacity-90">Corazón ganadero lechero del Altiplano Cundiboyacense</p>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="section-eyebrow mb-2">
            <span class="badge px-3 py-2 rounded-pill fw-bold" style="background-color: var(--surface-2); color: var(--primary); font-size: 0.85rem; letter-spacing: 0.05em;">HISTORIA Y COMUNIDAD</span>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-3">Más de Dos Décadas Construyendo Futuro Campesino</h2>
          <div class="accent-bar mb-4" style="width: 60px; height: 4px; background: var(--accent); border-radius: 2px;"></div>

          <p class="text-muted fs-5 mb-4" style="line-height: 1.7;">
            La <strong>Asociación Campesina No Nacional de Trabajadores de la Vereda Alizal</strong> (<strong><?php echo e(EMPRESA_SIGLA); ?></strong>), con NIT <strong><?php echo e(EMPRESA_NIT); ?></strong>, agrupa a <strong>32 pequeños y medianos productores lecheros</strong> del municipio de Carmen de Carupa, Cundinamarca.
          </p>

          <p class="text-muted fs-5 mb-4" style="line-height: 1.7;">
            Nacida en el año 2000 con personería jurídica formal, nuestra asociación trabaja día a día para defender el valor del trabajo rural, garantizar un pago justo por cada litro de leche y brindar asistencia técnica y comunitaria continua.
          </p>

          <div class="row g-3 pt-2">
            <div class="col-sm-6">
              <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background-color: var(--surface-2);">
                <i class="bi bi-people-fill fs-2" style="color: var(--primary);"></i>
                <div>
                  <div class="fw-bold text-dark">32 Asociados</div>
                  <small class="text-muted">Familias vinculadas</small>
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background-color: var(--surface-2);">
                <i class="bi bi-patch-check-fill fs-2" style="color: var(--accent);"></i>
                <div>
                  <div class="fw-bold text-dark">Calidad Comprobada</div>
                  <small class="text-muted">Pruebas higiénicas</small>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- ========================================================================
       4. CÓMO FUNCIONA EL SISTEMA (PASO A PASO DEL CONTROL LECHERO)
       ======================================================================== -->
  <section id="como-funciona" class="py-5" style="background-color: var(--surface-2);">
    <div class="container py-lg-4">
      <div class="text-center mb-5">
        <span class="badge px-3 py-2 rounded-pill fw-bold mb-2" style="background-color: var(--surface); color: var(--primary); font-size: 0.85rem; letter-spacing: 0.05em;">TRANSPARENCIA QUINCENAL</span>
        <h2 class="display-6 fw-bold text-dark mb-2">¿Cómo Funciona el Control Lechero?</h2>
        <p class="text-muted fs-5 mx-auto" style="max-width: 650px;">Un proceso simple, riguroso y transparente desde el ordeño en la finca hasta su cuenta.</p>
        <div class="accent-bar mx-auto mt-3" style="width: 60px; height: 4px; background: var(--accent); border-radius: 2px;"></div>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="card card-elevate p-4 h-100 rounded-4 border-2 text-center" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-4 text-white" style="width: 72px; height: 72px; background: linear-gradient(135deg, var(--primary) 0%, #20518c 100%); font-size: 1.8rem;">
              <i class="bi bi-bucket-fill"></i>
            </div>
            <h4 class="fw-bold text-dark mb-3">1. Ordeño y Pesaje</h4>
            <p class="text-muted mb-0" style="line-height: 1.6;">
              Cada asociado entrega su leche en cantinas higienizadas en dos turnos diarios: <strong>☀️ Mañana</strong> y <strong>🌅 Tarde</strong>. Se pesa con báscula calibrada y se registra inmediatamente.
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card card-elevate p-4 h-100 rounded-4 border-2 text-center" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-4 text-white" style="width: 72px; height: 72px; background: linear-gradient(135deg, #d98d28 0%, #b56f10 100%); font-size: 1.8rem;">
              <i class="bi bi-laptop"></i>
            </div>
            <h4 class="fw-bold text-dark mb-3">2. Registro Digital</h4>
            <p class="text-muted mb-0" style="line-height: 1.6;">
              La administración digita los pesajes en la planilla matricial del sistema. El ganadero puede ingresar a su portal desde su celular y verificar sus litros día por día en tiempo real.
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card card-elevate p-4 h-100 rounded-4 border-2 text-center" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-4 text-white" style="width: 72px; height: 72px; background: linear-gradient(135deg, #1e7a44 0%, #144722 100%); font-size: 1.8rem;">
              <i class="bi bi-cash-stack"></i>
            </div>
            <h4 class="fw-bold text-dark mb-3">3. Liquidación Clara</h4>
            <p class="text-muted mb-0" style="line-height: 1.6;">
              Al cumplirse cada quincena (1-15 o 16-fin de mes), el sistema totaliza los litros acumulados y aplica la tarifa oficial por litro, emitiendo la liquidación exacta y transparente.
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       5. INFRAESTRUCTURA PRODUCTIVA (FOTOGRAFÍAS REALES)
       ======================================================================== -->
  <section id="infraestructura" class="py-5" style="background-color: var(--surface);">
    <div class="container py-lg-4">
      <div class="text-center mb-5">
        <span class="badge px-3 py-2 rounded-pill fw-bold mb-2" style="background-color: var(--surface-2); color: var(--primary); font-size: 0.85rem; letter-spacing: 0.05em;">CAPACIDAD TÉCNICA</span>
        <h2 class="display-6 fw-bold text-dark mb-2">Nuestra Infraestructura Productiva</h2>
        <p class="text-muted fs-5 mx-auto" style="max-width: 650px;">Instalaciones y equipamiento al servicio de la calidad de la leche y la producción agropecuaria.</p>
        <div class="accent-bar mx-auto mt-3" style="width: 60px; height: 4px; background: var(--accent); border-radius: 2px;"></div>
      </div>

      <div class="row g-4">
        <!-- Tarjeta 1: Tanque de Enfriamiento -->
        <div class="col-md-4">
          <div class="card card-elevate h-100 rounded-4 border-2 overflow-hidden" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="card-zoom">
              <img src="assets/img/infra-tanque.webp" alt="Tanque de enfriamiento de leche en acero inoxidable" class="img-fluid w-100 object-fit-cover" style="height: 230px;">
            </div>
            <div class="p-4">
              <span class="badge px-2.5 py-1 rounded-pill mb-2 fw-bold" style="background-color: var(--surface-2); color: var(--primary); font-size: 0.78rem;">CADENA DE FRÍO</span>
              <h4 class="fw-bold text-dark mb-2">Tanque de Enfriamiento</h4>
              <p class="small text-muted mb-0" style="line-height: 1.6;">
                Tanques de acero inoxidable grado alimentario que bajan la temperatura de la leche a 4 °C en menos de dos horas, frenando la proliferación bacteriana y preservando su calidad.
              </p>
            </div>
          </div>
        </div>

        <!-- Tarjeta 2: Invernaderos -->
        <div class="col-md-4">
          <div class="card card-elevate h-100 rounded-4 border-2 overflow-hidden" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="card-zoom">
              <img src="assets/img/infra-invernadero.webp" alt="Invernadero tecnificado para forrajes y cultivos" class="img-fluid w-100 object-fit-cover" style="height: 230px;">
            </div>
            <div class="p-4">
              <span class="badge px-2.5 py-1 rounded-pill mb-2 fw-bold" style="background-color: var(--surface-2); color: var(--primary); font-size: 0.78rem;">AGRICULTURA PROTEGIDA</span>
              <h4 class="fw-bold text-dark mb-2">Invernadero Comunitario</h4>
              <p class="small text-muted mb-0" style="line-height: 1.6;">
                Módulos de agricultura protegida para la producción escalonada de semillas limpias, forrajes de alto rendimiento y diversificación de ingresos agrícolas para las familias.
              </p>
            </div>
          </div>
        </div>

        <!-- Tarjeta 3: Laboratorio -->
        <div class="col-md-4">
          <div class="card card-elevate h-100 rounded-4 border-2 overflow-hidden" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="card-zoom">
              <img src="assets/img/infra-laboratorio.webp" alt="Laboratorio de biotecnología vegetal" class="img-fluid w-100 object-fit-cover" style="height: 230px;">
            </div>
            <div class="p-4">
              <span class="badge px-2.5 py-1 rounded-pill mb-2 fw-bold" style="background-color: var(--surface-2); color: var(--primary); font-size: 0.78rem;">BIOTECNOLOGÍA</span>
              <h4 class="fw-bold text-dark mb-2">Laboratorio y Control</h4>
              <p class="small text-muted mb-0" style="line-height: 1.6;">
                Área técnica destinada al control microbiológico básico de la leche, pruebas de mastitis y proyectos de propagación biológica y mejoramiento de pasturas de clima frío.
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       6. MISIÓN Y VISIÓN OFICIALES (TEXTOS EXACTOS DE LA FICHA)
       ======================================================================== -->
  <section id="mision-vision" class="py-5" style="background-color: var(--surface-2);">
    <div class="container py-lg-4">
      <div class="text-center mb-5">
        <span class="badge px-3 py-2 rounded-pill fw-bold mb-2" style="background-color: var(--surface); color: var(--primary); font-size: 0.85rem; letter-spacing: 0.05em;">HORIZONTE INSTITUCIONAL</span>
        <h2 class="display-6 fw-bold text-dark mb-2">Misión y Visión Oficiales</h2>
        <p class="text-muted fs-5 mx-auto" style="max-width: 650px;">El compromiso de nuestra organización campesina con el progreso de la Vereda Alizal.</p>
        <div class="accent-bar mx-auto mt-3" style="width: 60px; height: 4px; background: var(--accent); border-radius: 2px;"></div>
      </div>

      <div class="row g-4">
        <!-- Misión Oficial -->
        <div class="col-lg-6">
          <div class="card card-elevate p-4 p-md-5 h-100 rounded-4 border-3" style="background-color: var(--surface); border-color: var(--primary);">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 56px; height: 56px; background-color: var(--primary); font-size: 1.5rem;">
                <i class="bi bi-compass-fill"></i>
              </div>
              <div>
                <span class="badge px-2.5 py-1 rounded-pill fw-bold text-white mb-1" style="background-color: var(--primary);">OFICIAL</span>
                <h3 class="fw-bold text-dark mb-0">Misión</h3>
              </div>
            </div>
            <p class="fs-5 text-muted mb-0" style="line-height: 1.75; text-align: justify;">
              Somos una asociación campesina de la vereda Alizal, municipio de Carmen de Carupa, dedicada a la producción y comercialización de leche y productos agropecuarios. Trabajamos unidos para mejorar los ingresos y la calidad de vida de nuestras familias asociadas, mediante la adopción de buenas prácticas ganaderas, el fortalecimiento organizativo y la gestión de proyectos de desarrollo rural integral.
            </p>
          </div>
        </div>

        <!-- Visión Oficial 2030 -->
        <div class="col-lg-6">
          <div class="card card-elevate p-4 p-md-5 h-100 rounded-4 border-3" style="background-color: var(--surface); border-color: var(--accent);">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 56px; height: 56px; background-color: var(--accent); font-size: 1.5rem;">
                <i class="bi bi-eye-fill"></i>
              </div>
              <div>
                <span class="badge px-2.5 py-1 rounded-pill fw-bold text-white mb-1" style="background-color: var(--accent);">HORIZONTE 2030</span>
                <h3 class="fw-bold text-dark mb-0">Visión</h3>
              </div>
            </div>
            <p class="fs-5 text-muted mb-0" style="line-height: 1.75; text-align: justify;">
              Para el año 2030, ASOAGROALIZAL será reconocida a nivel municipal y regional como una asociación campesina modelo en producción lechera sostenible, con altos estándares de calidad e inocuidad. Contaremos con infraestructura propia para el acopio y transformación de nuestros productos, generando bienestar económico, equidad y arraigo campesino en nuestro territorio.
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       7. PARA EL GANADERO / BUENAS PRÁCTICAS DE ORDEÑO (BPO)
       ======================================================================== -->
  <section id="para-el-ganadero" class="py-5" style="background-color: var(--surface);">
    <div class="container py-lg-4">
      <div class="text-center mb-5">
        <span class="badge px-3 py-2 rounded-pill fw-bold mb-2" style="background-color: var(--surface-2); color: var(--primary); font-size: 0.85rem; letter-spacing: 0.05em;">CALIDAD Y SOSTENIBILIDAD</span>
        <h2 class="display-6 fw-bold text-dark mb-2">Buenas Prácticas para el Ganadero</h2>
        <p class="text-muted fs-5 mx-auto" style="max-width: 650px;">Líneas fundamentales de manejo que garantizan la máxima bonificación y sanidad de la leche.</p>
        <div class="accent-bar mx-auto mt-3" style="width: 60px; height: 4px; background: var(--accent); border-radius: 2px;"></div>
      </div>

      <div class="row g-4">
        <!-- BPO 1: Bienestar Animal -->
        <div class="col-sm-6 col-lg-3">
          <div class="card card-elevate h-100 rounded-4 border-2 overflow-hidden" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="card-zoom">
              <img src="assets/img/card-bienestar.webp" alt="Vacas pastoreando en praderas de montaña" class="img-fluid w-100 object-fit-cover" style="height: 180px;">
            </div>
            <div class="p-3 text-center">
              <h5 class="fw-bold text-dark mb-2">Bienestar Animal</h5>
              <p class="small text-muted mb-0" style="line-height: 1.5;">
                Pastoreo libre con acceso a agua limpia y sombra natural para evitar el estrés calórico y elevar la producción.
              </p>
            </div>
          </div>
        </div>

        <!-- BPO 2: Sanidad del Hato -->
        <div class="col-sm-6 col-lg-3">
          <div class="card card-elevate h-100 rounded-4 border-2 overflow-hidden" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="card-zoom">
              <img src="assets/img/card-sanidad.webp" alt="Sanidad y chequeo veterinario del ganado" class="img-fluid w-100 object-fit-cover" style="height: 180px;">
            </div>
            <div class="p-3 text-center">
              <h5 class="fw-bold text-dark mb-2">Sanidad del Hato</h5>
              <p class="small text-muted mb-0" style="line-height: 1.5;">
                Planes periódicos de vacunación oficial, desparasitación estratégica y pruebas periódicas contra mastitis.
              </p>
            </div>
          </div>
        </div>

        <!-- BPO 3: Nutrición Balanceada -->
        <div class="col-sm-6 col-lg-3">
          <div class="card card-elevate h-100 rounded-4 border-2 overflow-hidden" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="card-zoom">
              <img src="assets/img/card-nutricion.webp" alt="Pasturas andinas nutritivas" class="img-fluid w-100 object-fit-cover" style="height: 180px;">
            </div>
            <div class="p-3 text-center">
              <h5 class="fw-bold text-dark mb-2">Nutrición y Pasturas</h5>
              <p class="small text-muted mb-0" style="line-height: 1.5;">
                Rotación adecuada de potreros de Ryegrass y Trébol, sales mineralizadas y suplementos forrajeros de calidad.
              </p>
            </div>
          </div>
        </div>

        <!-- BPO 4: Higiene en el Ordeño -->
        <div class="col-sm-6 col-lg-3">
          <div class="card card-elevate h-100 rounded-4 border-2 overflow-hidden" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <div class="card-zoom">
              <img src="assets/img/card-manejo.webp" alt="Ordeño higiénico y cantinas limpias" class="img-fluid w-100 object-fit-cover" style="height: 180px;">
            </div>
            <div class="p-3 text-center">
              <h5 class="fw-bold text-dark mb-2">Higiene en el Ordeño</h5>
              <p class="small text-muted mb-0" style="line-height: 1.5;">
                Lavado y secado riguroso de pezones con papel desechable, despunte inicial y sellado antes de almacenar en cantina.
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       8. PREGUNTAS FRECUENTES (ACORDEÓN INTERACTIVO)
       ======================================================================== -->
  <section id="preguntas" class="py-5" style="background-color: var(--surface-2);">
    <div class="container py-lg-4">
      <div class="text-center mb-5">
        <span class="badge px-3 py-2 rounded-pill fw-bold mb-2" style="background-color: var(--surface); color: var(--primary); font-size: 0.85rem; letter-spacing: 0.05em;">RESOLUCIÓN DE DUDAS</span>
        <h2 class="display-6 fw-bold text-dark mb-2">Preguntas Frecuentes</h2>
        <p class="text-muted fs-5 mx-auto" style="max-width: 650px;">Respuestas claras a las dudas más comunes de nuestros asociados y comunidad.</p>
        <div class="accent-bar mx-auto mt-3" style="width: 60px; height: 4px; background: var(--accent); border-radius: 2px;"></div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="accordion accordion-asoagro shadow-sm rounded-4 overflow-hidden border-2" id="accordionFAQ" style="border: 2px solid var(--border-subtle);">
            
            <!-- Pregunta 1 -->
            <div class="accordion-item border-0 border-bottom">
              <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button fw-bold text-dark fs-5 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="background-color: var(--surface);">
                  <i class="bi bi-question-circle-fill me-2" style="color: var(--primary);"></i> ¿Cómo consulto mis litros diarios registrados?
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionFAQ">
                <div class="accordion-body text-muted fs-6 py-3" style="line-height: 1.7; background-color: var(--surface);">
                  Solo debe pulsar el botón <strong>"Ingresar"</strong> en la parte superior, escribir su número de cédula y su contraseña personal. En su panel verá de inmediato la planilla con los litros de cada día divididos en Mañana (☀️) y Tarde (🌅).
                </div>
              </div>
            </div>

            <!-- Pregunta 2 -->
            <div class="accordion-item border-0 border-bottom">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed fw-bold text-dark fs-5 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="background-color: var(--surface);">
                  <i class="bi bi-clock-history me-2" style="color: var(--primary);"></i> ¿Cuáles son los turnos de entrega y registro?
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionFAQ">
                <div class="accordion-body text-muted fs-6 py-3" style="line-height: 1.7; background-color: var(--surface);">
                  Los pesajes se realizan diariamente en dos turnos: el turno matutino (☀️ Mañana) correspondiente al primer ordeño del día, y el turno vespertino (🌅 Tarde). Ambos quedan radicados con su número respectivo en el sistema.
                </div>
              </div>
            </div>

            <!-- Pregunta 3 -->
            <div class="accordion-item border-0 border-bottom">
              <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed fw-bold text-dark fs-5 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" style="background-color: var(--surface);">
                  <i class="bi bi-calendar-check-fill me-2" style="color: var(--primary);"></i> ¿Cuándo se liquidan y pagan las quincenas?
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionFAQ">
                <div class="accordion-body text-muted fs-6 py-3" style="line-height: 1.7; background-color: var(--surface);">
                  El sistema maneja dos períodos exactos por mes: la primera quincena (días 1 al 15) y la segunda quincena (días 16 al último día del mes). Una vez concluido el ciclo, la administración verifica los acumulados y efectúa el pago sin intermediarios.
                </div>
              </div>
            </div>

            <!-- Pregunta 4 -->
            <div class="accordion-item border-0">
              <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed fw-bold text-dark fs-5 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour" style="background-color: var(--surface);">
                  <i class="bi bi-telephone-inbound-fill me-2" style="color: var(--primary);"></i> ¿Qué hago si tengo una inconformidad con mi pesaje?
                </button>
              </h2>
              <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionFAQ">
                <div class="accordion-body text-muted fs-6 py-3" style="line-height: 1.7; background-color: var(--surface);">
                  Desde su panel de ganadero puede enviar un mensaje directo de inconformidad en el recuadro de quejas, o comunicarse directamente al teléfono oficial de la asociación: <strong><?php echo e(EMPRESA_TELEFONO); ?></strong> para resolver cualquier discrepancia de inmediato.
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================
       9. CONTACTO INSTITUCIONAL (FORMULARIO CON CSRF Y DATOS VERIFICADOS)
       ======================================================================== -->
  <section id="contacto" class="py-5" style="background-color: var(--surface);">
    <div class="container py-lg-4">
      <div class="text-center mb-5">
        <span class="badge px-3 py-2 rounded-pill fw-bold mb-2" style="background-color: var(--surface-2); color: var(--primary); font-size: 0.85rem; letter-spacing: 0.05em;">CANALES DE ATENCIÓN</span>
        <h2 class="display-6 fw-bold text-dark mb-2">Comuníquese con Nuestra Asociación</h2>
        <p class="text-muted fs-5 mx-auto" style="max-width: 650px;">Atención a asociados, proveedores y entidades públicas y privadas.</p>
        <div class="accent-bar mx-auto mt-3" style="width: 60px; height: 4px; background: var(--accent); border-radius: 2px;"></div>
      </div>

      <div class="row g-4 g-lg-5">
        <!-- Columna Formulario -->
        <div class="col-lg-7">
          <div class="card card-elevate p-4 p-md-5 rounded-4 border-2" style="background-color: var(--surface); border-color: var(--border-subtle);">
            <h4 class="fw-bold text-dark mb-2">Envíenos su Mensaje o Petición</h4>
            <p class="text-muted small mb-4">Diligencie el formulario y le responderemos a la mayor brevedad.</p>

            <form action="index.php" method="POST" id="form-contacto" class="needs-validation" novalidate>
              <?php echo campoCsrf(); ?>
              <input type="hidden" name="accion_contacto" value="1">
              
              <!-- Honeypot anti-spam oculto -->
              <div class="d-none" aria-hidden="true">
                <label for="website_hp">No llenar este campo</label>
                <input type="text" id="website_hp" name="website_hp" tabindex="-1" autocomplete="off">
              </div>

              <div class="mb-3">
                <label for="contacto-nombre" class="form-label">Nombre Completo *</label>
                <input type="text" class="form-control form-control-touch" id="contacto-nombre" name="nombre" required placeholder="Ej: Don Pedro Gómez">
              </div>

              <div class="mb-3">
                <label for="contacto-tel" class="form-label">Teléfono de Contacto o Celular *</label>
                <input type="tel" class="form-control form-control-touch" id="contacto-tel" name="contacto" required placeholder="Ej: 310 123 4567">
              </div>

              <div class="mb-3">
                <label for="contacto-mensaje" class="form-label">Mensaje o Consulta *</label>
                <textarea class="form-control form-control-touch" id="contacto-mensaje" name="mensaje" rows="4" required placeholder="Escriba aquí su consulta o mensaje..." style="resize: vertical;"></textarea>
              </div>

              <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="acepta_datos" name="acepta_datos" required style="width: 20px; height: 20px;">
                <label class="form-check-label small text-muted ms-2" for="acepta_datos">
                  Autorizo el tratamiento de mis datos de conformidad con la Ley 1581 de 2012 para responder esta consulta.
                </label>
              </div>

              <button type="submit" class="btn btn-touch btn-aso-primary w-100 fs-5 py-3">
                <i class="bi bi-send-fill me-2"></i> Enviar Mensaje
              </button>
            </form>
          </div>
        </div>

        <!-- Columna Ficha y Canales Oficiales -->
        <div class="col-lg-5">
          <div class="card card-elevate p-4 p-md-5 rounded-4 border-2 h-100" style="background-color: var(--surface-2); border-color: var(--border-subtle);">
            <h4 class="fw-bold text-dark mb-4">Información Institucional</h4>

            <div class="d-flex flex-column gap-4">
              <div class="d-flex gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 48px; height: 48px; background-color: var(--primary);">
                  <i class="bi bi-geo-alt-fill fs-5"></i>
                </div>
                <div>
                  <strong class="d-block text-dark">Sede y Dirección</strong>
                  <span class="text-muted small">Vereda Alizal &bull; Finca El Recuerdo<br>Carmen de Carupa, Cundinamarca</span>
                </div>
              </div>

              <div class="d-flex gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 48px; height: 48px; background-color: var(--accent);">
                  <i class="bi bi-telephone-fill fs-5"></i>
                </div>
                <div>
                  <strong class="d-block text-dark">Teléfono Oficial Único</strong>
                  <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="fw-bold fs-5 text-decoration-none" style="color: var(--primary);">
                    <?php echo e(EMPRESA_TELEFONO); ?>
                  </a>
                  <span class="d-block text-muted small">Llamadas y atención institucional</span>
                </div>
              </div>

              <div class="d-flex gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 48px; height: 48px; background-color: var(--primary);">
                  <i class="bi bi-envelope-fill fs-5"></i>
                </div>
                <div>
                  <strong class="d-block text-dark">Correo Electrónico</strong>
                  <span class="text-muted small"><?php echo e(EMPRESA_CORREO); ?></span>
                </div>
              </div>

              <div class="d-flex gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 48px; height: 48px; background-color: var(--primary-deep);">
                  <i class="bi bi-card-text fs-5"></i>
                </div>
                <div>
                  <strong class="d-block text-dark">Personería y NIT</strong>
                  <span class="text-muted small">NIT: <strong><?php echo e(EMPRESA_NIT); ?></strong><br>Asociación Campesina Legalmente Constituida</span>
                </div>
              </div>
            </div>

            <div class="mt-4 pt-3 border-top border-secondary-subtle">
              <a href="login.php" class="btn btn-touch btn-outline-primary w-100 fw-bold">
                <i class="bi bi-box-arrow-in-right me-2"></i> Ingreso de Ganaderos Registrados
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
