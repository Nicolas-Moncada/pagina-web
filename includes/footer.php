<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/footer.php
 * Propósito: Pie de página corporativo multi-columna inspirado en Central Ganadera:
 *            - Horarios oficiales de acopio (Turno Mañana y Tarde).
 *            - Línea ética, canales de contacto directo y WhatsApp.
 *            - Modales de política de datos (Ley 1581 de 2012) y términos.
 * =========================================================================================
 */
?>
  <!-- FOOTER CORPORATIVO MULTI-COLUMNA (Estilo Central Ganadera) -->
  <footer class="footer-corporate-agro mt-auto">
    <div class="container py-4">
      <div class="row g-4 mb-4">
        
        <!-- Columna 1: Identidad Corporativa -->
        <div class="col-lg-4 col-md-6">
          <div class="d-flex align-items-center gap-3 mb-3">
            <img src="assets/img/logo.webp" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" class="bg-white p-1 rounded-3" style="max-height: 48px;" width="300" height="208" loading="lazy">
            <div>
              <h5 class="fw-bold mb-0 text-white"><?php echo e(EMPRESA_SIGLA); ?></h5>
              <small class="text-warning text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Control Lechero</small>
            </div>
          </div>
          <p class="small text-light opacity-75 mb-2">
            <?php echo e(EMPRESA_NOMBRE); ?>
          </p>
          <p class="small text-light opacity-90 mb-1">
            <strong class="text-warning">NIT:</strong> <?php echo e(EMPRESA_NIT); ?> &bull; 
            <span>Cámara de Comercio de Bogotá</span>
          </p>
          <p class="small text-light opacity-75 mb-0">
            <i class="fa-solid fa-location-dot text-warning me-1.5"></i> <?php echo e(EMPRESA_UBICACION); ?>
          </p>
        </div>

        <!-- Columna 2: Horarios de Acopio y Operación -->
        <div class="col-lg-3 col-md-6">
          <h6 class="footer-heading">Horarios de Acopio</h6>
          <ul class="list-unstyled small mb-0 text-light opacity-85">
            <li class="mb-2 d-flex align-items-center gap-2">
              <span class="badge" style="background-color: var(--accent-gold); color: #fff;">Mañana ☀️</span>
              <span>5:30 AM &ndash; 7:30 AM</span>
            </li>
            <li class="mb-2 d-flex align-items-center gap-2">
              <span class="badge" style="background-color: var(--accent-dusk); color: #fff;">Tarde 🌅</span>
              <span>4:00 PM &ndash; 5:30 PM</span>
            </li>
            <li class="text-light opacity-75 mt-2">
              <i class="fa-solid fa-circle-check text-success me-1"></i> Operación continua los 365 días del año con cadena de frío a 4°C.
            </li>
          </ul>
        </div>

        <!-- Columna 3: Atención y Línea Ética -->
        <div class="col-lg-3 col-md-6">
          <h6 class="footer-heading">Atención y Línea Ética</h6>
          <ul class="list-unstyled small mb-0 text-light opacity-85">
            <li class="mb-2">
              <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-phone text-warning"></i>
                <span>Tel: <?php echo e(EMPRESA_TELEFONO); ?></span>
              </a>
            </li>
            <li class="mb-2">
              <a href="https://wa.me/57<?php echo e(EMPRESA_TELEFONO); ?>" target="_blank" rel="noopener noreferrer" class="d-inline-flex align-items-center gap-2">
                <i class="fa-brands fa-whatsapp text-success fs-6"></i>
                <span>WhatsApp Oficial</span>
              </a>
            </li>
            <li class="mb-2">
              <a href="mailto:<?php echo e(EMPRESA_CORREO); ?>" class="d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-envelope text-warning"></i>
                <span><?php echo e(EMPRESA_CORREO); ?></span>
              </a>
            </li>
            <li>
              <a href="index.php#contacto" class="d-inline-flex align-items-center gap-2 text-warning fw-bold">
                <i class="fa-solid fa-file-pen"></i>
                <span>Radicar F-PQRS / Reclamo</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Columna 4: Enlaces y Portal Transaccional -->
        <div class="col-lg-2 col-md-6">
          <h6 class="footer-heading">Acceso Seguro</h6>
          <ul class="list-unstyled small mb-3">
            <li class="mb-1.5"><a href="index.php#inicio">Inicio</a></li>
            <li class="mb-1.5"><a href="index.php#areas">Nuestras Áreas</a></li>
            <li class="mb-1.5"><a href="index.php#precio">Precio Quincena</a></li>
            <li class="mb-1.5"><a href="registro.php">Nuevo Asociado</a></li>
            <li class="mb-1.5"><a href="login.php" class="text-warning fw-bold">Portal Asociados &rarr;</a></li>
          </ul>
          <a href="javascript:window.scrollTo({top:0, behavior:'smooth'})" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 text-uppercase fw-bold" style="font-size: 0.72rem;">
            <i class="fa-solid fa-arrow-up me-1"></i> Subir
          </a>
        </div>

      </div>

      <hr style="border-color: rgba(255, 255, 255, 0.12);" class="my-3">

      <!-- Barra de Copyright y Políticas Legales -->
      <div class="row align-items-center text-center text-md-start small text-light opacity-75">
        <div class="col-md-7 mb-2 mb-md-0">
          Plataforma Oficial de Control Lechero &copy; <?php echo date('Y'); ?> <strong><?php echo e(EMPRESA_SIGLA); ?></strong>. Carmen de Carupa, Cundinamarca.
        </div>
        <div class="col-md-5 text-md-end">
          <a href="#modalPoliticaDatos" class="text-light me-2 text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalPoliticaDatos">
            Política de Datos
          </a>
          <span>&bull;</span>
          <a href="#modalTerminos" class="text-light ms-2 text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalTerminos">
            Términos y Condiciones
          </a>
        </div>
      </div>
    </div>
  </footer>

  <!-- MODAL: POLÍTICA DE TRATAMIENTO DE DATOS PERSONALES (LEY 1581 DE 2012) -->
  <div class="modal fade" id="modalPoliticaDatos" tabindex="-1" aria-labelledby="modalDatosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header text-white p-3 p-md-4" style="background-color: var(--primary-emerald);">
          <h5 class="modal-title fw-bold text-white" id="modalDatosLabel">
            <i class="fa-solid fa-shield-halved me-2 text-warning"></i> Política de Tratamiento de Datos Personales
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-4 text-dark" style="line-height: 1.8;">
          <h6 class="fw-bold text-dark">1. Responsable del Tratamiento</h6>
          <p>La <strong><?php echo e(EMPRESA_NOMBRE); ?></strong> (en adelante "ASOAGROALIZAL"), identificada con NIT <strong><?php echo e(EMPRESA_NIT); ?></strong>, con sede en <?php echo e(EMPRESA_UBICACION); ?> y correo electrónico <?php echo e(EMPRESA_CORREO); ?>, informa que los datos personales recolectados en esta plataforma son tratados de conformidad con la Ley Estatutaria 1581 de 2012 y el Decreto 1377 de 2013.</p>
          
          <h6 class="fw-bold text-dark">2. Finalidad del Tratamiento</h6>
          <p>Los datos suministrados por los asociados y visitantes serán utilizados exclusivamente para: a) El registro, liquidación quincenal y control lechero interno de la asociación; b) La comunicación de asambleas, capacitaciones y jornadas técnicas; c) La atención de consultas y PQRS radicadas en el sitio web; d) El cumplimiento de obligaciones tributarias, comerciales y sanitarias ante las autoridades colombianas.</p>

          <h6 class="fw-bold text-dark">3. Derechos del Titular</h6>
          <p>Como titular de los datos personales, le asiste el derecho a conocer, actualizar, rectificar y suprimir su información, así como a revocar la autorización otorgada mediante solicitud escrita remitida al correo electrónico oficial: <?php echo e(EMPRESA_CORREO); ?>.</p>
        </div>
        <div class="modal-footer p-3 bg-light">
          <button type="button" class="btn text-white px-4 rounded-3 fw-bold" style="background-color: var(--primary-emerald);" data-bs-dismiss="modal">Entendido</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: TÉRMINOS Y CONDICIONES DE USO -->
  <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header text-white p-3 p-md-4" style="background-color: var(--primary-emerald);">
          <h5 class="modal-title fw-bold text-white" id="modalTerminosLabel">
            <i class="fa-solid fa-file-contract me-2 text-warning"></i> Términos y Condiciones de Uso
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-4 text-dark" style="line-height: 1.8;">
          <h6 class="fw-bold text-dark">1. Naturaleza del Sistema</h6>
          <p>La plataforma tecnológica de <strong><?php echo e(EMPRESA_SIGLA); ?></strong> es una herramienta interna de gestión asociativa destinada al registro de pesajes de leche, seguimiento de Buenas Prácticas de Ordeño (BPO) y liquidaciones quincenales para sus 32 asociados.</p>

          <h6 class="fw-bold text-dark">2. Responsabilidad de Credenciales</h6>
          <p>Cada usuario es responsable del uso exclusivo y confidencial de su contraseña o acceso mediante Google OAuth. Las acciones realizadas bajo su sesión se presumen efectuadas por el titular registrado.</p>

          <h6 class="fw-bold text-dark">3. Reglas de Pesaje y Liquidación</h6>
          <p>Los pesajes se registran diariamente en los turnos oficiales de Mañana (M) y Tarde (T). Las inconformidades sobre los litros liquidados podrán radicarse a través del buzón F-PQRS dentro de los 3 días hábiles posteriores al cierre de la quincena respectiva.</p>
        </div>
        <div class="modal-footer p-3 bg-light">
          <button type="button" class="btn text-white px-4 rounded-3 fw-bold" style="background-color: var(--primary-emerald);" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts Globales Requeridos -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- SheetJS para Exportación a Excel (.xlsx) -->
  <?php if (!empty($usarDataTables) || !empty($usarSheetJS)): ?>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <?php endif; ?>

  <!-- DataTables JS (si aplica) -->
  <?php if (!empty($usarDataTables)): ?>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
  <?php endif; ?>

  <!-- Script Corporativo General -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sticky navbar shadow al hacer scroll
      const navbar = document.querySelector('.navbar-agro');
      if (navbar) {
        window.addEventListener('scroll', function() {
          if (window.scrollY > 30) {
            navbar.classList.add('scrolled');
          } else {
            navbar.classList.remove('scrolled');
          }
        });
      }
    });
  </script>
</body>
</html>
