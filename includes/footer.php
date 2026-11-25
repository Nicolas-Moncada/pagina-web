<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/footer.php
 * Propósito: Pie de página corporativo en paleta Bosque Esmeralda (#082F24 / #D97706):
 *            - 4 columnas ejecutivas sobrias sin emojis.
 *            - Carga global de scripts (jQuery, Bootstrap, SweetAlert2, Theme.js y Lucide Icons).
 *            - Modales de política de datos (Ley 1581 de 2012) y términos institucionales.
 * =========================================================================================
 */
?>
  <!-- PIE DE PÁGINA CORPORATIVO 4 COLUMNAS -->
  <footer class="corp-footer mt-auto">
    <div class="container py-3">
      <div class="row g-4 mb-4">
        
        <!-- Columna 1: Identidad Corporativa -->
        <div class="col-lg-4 col-md-6">
          <div class="d-flex align-items-center gap-2 mb-3">
            <img src="assets/img/logo.webp" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" class="bg-white p-1 rounded" style="max-height: 40px;" width="40" height="28" loading="lazy">
            <div>
              <span class="d-block font-heading fw-bold text-white fs-6 lh-sm"><?php echo e(EMPRESA_SIGLA); ?></span>
              <span class="small font-mono" style="color: #94A3B8; font-size: 0.78rem;">NIT <?php echo e(EMPRESA_NIT); ?></span>
            </div>
          </div>
          <p class="small mb-2 lh-base" style="color: #CBD5E1;">
            <?php echo e(EMPRESA_NOMBRE); ?>. Asociación campesina para el acopio técnico, trazabilidad sanitaria y liquidación transparente.
          </p>
          <div class="small" style="color: #94A3B8;">
            <span class="d-block"><i data-lucide="map-pin" class="me-1" style="width: 14px; height: 14px; color: #FCD34D;"></i> <?php echo e(EMPRESA_UBICACION); ?></span>
            <span class="d-block mt-1"><i data-lucide="building-2" class="me-1" style="width: 14px; height: 14px; color: #6EE7B7;"></i> Personería Jurídica &bull; Cámara de Comercio</span>
          </div>
        </div>

        <!-- Columna 2: Operación y Calidad -->
        <div class="col-lg-3 col-md-6">
          <h6 class="corp-footer-title">Control de Acopio</h6>
          <ul class="list-unstyled small mb-0" style="color: #CBD5E1;">
            <li class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2" style="border-color: rgba(255,255,255,0.12) !important;">
              <span class="fw-semibold text-white">Primer Turno:</span>
              <span class="badge-shift-am">TURNO AM</span>
            </li>
            <li class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2" style="border-color: rgba(255,255,255,0.12) !important;">
              <span class="fw-semibold text-white">Segundo Turno:</span>
              <span class="badge-shift-pm">TURNO PM</span>
            </li>
            <li style="color: #94A3B8; font-size: 0.82rem; line-height: 1.5;">
              Acopio diario para 32 productores asociados con conservación en tanque de enfriamiento de acero inoxidable.
            </li>
          </ul>
        </div>

        <!-- Columna 3: Canales de Atención Formal -->
        <div class="col-lg-3 col-md-6">
          <h6 class="corp-footer-title">Atención y F-PQRS</h6>
          <ul class="list-unstyled small mb-0 d-flex flex-column gap-2" style="color: #CBD5E1;">
            <li>
              <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="d-inline-flex align-items-center gap-2 text-white">
                <i data-lucide="phone" style="width: 15px; height: 15px; color: #FCD34D;"></i>
                <span>Teléfono: <?php echo e(EMPRESA_TELEFONO); ?></span>
              </a>
            </li>
            <li>
              <a href="mailto:<?php echo e(EMPRESA_CORREO); ?>" class="d-inline-flex align-items-center gap-2 text-white">
                <i data-lucide="mail" style="width: 15px; height: 15px; color: #FCD34D;"></i>
                <span><?php echo e(EMPRESA_CORREO); ?></span>
              </a>
            </li>
            <li>
              <a href="index.php#contacto" class="d-inline-flex align-items-center gap-2 fw-semibold" style="color: #6EE7B7;">
                <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                <span>Radicación Electrónica F-PQRS</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Columna 4: Navegación Corporativa -->
        <div class="col-lg-2 col-md-6">
          <h6 class="corp-footer-title">Plataforma</h6>
          <ul class="list-unstyled small d-flex flex-column gap-1 mb-3">
            <li><a href="index.php#inicio">Inicio</a></li>
            <li><a href="index.php#areas">Áreas Operativas</a></li>
            <li><a href="index.php#mision-vision">Misión y Visión</a></li>
            <li><a href="registro.php">Vincularse</a></li>
            <li><a href="login.php" class="text-white fw-semibold">Acceso Asociados</a></li>
          </ul>
          <a href="javascript:window.scrollTo({top:0, behavior:'smooth'})" class="btn-corp-outline py-1 px-2.5" style="font-size: 0.78rem;">
            <i data-lucide="arrow-up" style="width: 14px; height: 14px;"></i> Subir
          </a>
        </div>

      </div>

      <hr style="border-color: rgba(255,255,255,0.14);" class="my-3">

      <!-- Barra de Copyright y Políticas Legales -->
      <div class="row align-items-center small" style="color: #94A3B8;">
        <div class="col-md-7 mb-2 mb-md-0">
          Plataforma de Control Lechero &copy; <?php echo date('Y'); ?> <strong class="text-white"><?php echo e(EMPRESA_SIGLA); ?></strong>. Carmen de Carupa, Cundinamarca.
        </div>
        <div class="col-md-5 text-md-end d-flex justify-content-md-end gap-3">
          <a href="#modalPoliticaDatos" data-bs-toggle="modal" data-bs-target="#modalPoliticaDatos">
            Tratamiento de Datos
          </a>
          <span>&bull;</span>
          <a href="#modalTerminos" data-bs-toggle="modal" data-bs-target="#modalTerminos">
            Términos de Servicio
          </a>
        </div>
      </div>
    </div>
  </footer>

  <!-- MODAL: POLÍTICA DE TRATAMIENTO DE DATOS PERSONALES (LEY 1581 DE 2012) -->
  <div class="modal fade" id="modalPoliticaDatos" tabindex="-1" aria-labelledby="modalDatosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content border rounded-4 shadow">
        <div class="modal-header text-white p-3" style="background-color: var(--brand-primary);">
          <h6 class="modal-title font-heading fw-bold text-white mb-0" id="modalDatosLabel">
            Tratamiento de Datos Personales (Ley 1581 de 2012)
          </h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-4 text-dark small lh-lg">
          <h6 class="fw-bold mb-2">1. Responsable del Tratamiento</h6>
          <p class="mb-3">La <strong><?php echo e(EMPRESA_NOMBRE); ?></strong> (en adelante "ASOAGROALIZAL"), con NIT <strong><?php echo e(EMPRESA_NIT); ?></strong>, informa que los datos personales recolectados en esta plataforma son tratados de conformidad con la Ley Estatutaria 1581 de 2012 y el Decreto 1377 de 2013.</p>

          <h6 class="fw-bold mb-2">2. Finalidad del Tratamiento</h6>
          <p class="mb-3">Los datos suministrados por los asociados y visitantes serán utilizados exclusivamente para: a) El registro, liquidación quincenal y control lechero interno de la asociación; b) La comunicación de asambleas, capacitaciones y jornadas técnicas; c) La atención de consultas y PQRS radicadas en el sitio web; d) El cumplimiento de obligaciones tributarias, comerciales y sanitarias ante las autoridades colombianas.</p>

          <h6 class="fw-bold mb-2">3. Derechos del Titular</h6>
          <p class="mb-0">Como titular de los datos personales, le asiste el derecho a conocer, actualizar, rectificar y suprimir su información mediante solicitud escrita remitida al correo electrónico oficial: <?php echo e(EMPRESA_CORREO); ?>.</p>
        </div>
        <div class="modal-footer p-2 bg-light border-top">
          <button type="button" class="btn-corp-primary" data-bs-dismiss="modal">Entendido</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: TÉRMINOS Y CONDICIONES DE USO -->
  <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content border rounded-4 shadow">
        <div class="modal-header text-white p-3" style="background-color: var(--brand-primary);">
          <h6 class="modal-title font-heading fw-bold text-white mb-0" id="modalTerminosLabel">
            Términos y Condiciones Institucionales
          </h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-4 text-dark small lh-lg">
          <h6 class="fw-bold mb-2">1. Naturaleza del Sistema</h6>
          <p class="mb-3">La plataforma tecnológica de <strong><?php echo e(EMPRESA_SIGLA); ?></strong> es una herramienta técnica de gestión asociativa destinada al registro de pesajes de leche, seguimiento de Buenas Prácticas de Ordeño (BPO) y liquidaciones quincenales para sus 32 asociados.</p>

          <h6 class="fw-bold mb-2">2. Procedimiento de Inconformidades (F-PQRS)</h6>
          <p class="mb-0">Las inconformidades sobre pesajes o valores liquidados podrán radicarse formalmente en el módulo F-PQRS (máximo 1 reclamo por quincena) dentro de los 3 días hábiles posteriores a la fecha de corte quincenal. La administración emitirá respuesta oficial verificable en la plataforma.</p>
        </div>
        <div class="modal-footer p-2 bg-light border-top">
          <button type="button" class="btn-corp-primary" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts Globales -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/theme.js"></script>

  <?php if (!empty($usarDataTables) || !empty($usarSheetJS)): ?>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <?php endif; ?>

  <?php if (!empty($usarDataTables)): ?>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
  <?php endif; ?>

  <!-- Inicialización Lucide Icons -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof lucide !== 'undefined') {
        lucide.createIcons({
          attrs: { 'stroke-width': 1.5 }
        });
      }
    });
  </script>
</body>
</html>
