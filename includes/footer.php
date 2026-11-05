<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/footer.php
 * Propósito: Pie de página legal unificado, modales de términos y scripts optimizados.
 * =========================================================================================
 */
?>
  <!-- Pie de Página Institucional y Legal -->
  <footer class="footer-corporate py-5 mt-auto">
    <div class="container py-3">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7 text-center text-lg-start">
          <div class="d-flex align-items-center gap-3 justify-content-center justify-content-lg-start mb-2">
            <img src="assets/img/logo.webp" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" style="max-height: 48px; aspect-ratio: 300 / 208;" width="300" height="208" loading="lazy">
            <h4 class="fw-bold mb-0 text-white"><?php echo e(EMPRESA_SIGLA); ?></h4>
          </div>
          <p class="small text-white-50 mb-1">
            <?php echo e(EMPRESA_NOMBRE); ?>
          </p>
          <p class="small mb-1 text-white">
            <span class="text-warning fw-bold">NIT: <?php echo e(EMPRESA_NIT); ?></span> &bull; 
            <span>Inscripción ante la Cámara de Comercio</span>
          </p>
          <p class="small text-white-50 mb-0">
            <i class="bi bi-geo-alt-fill text-warning me-1"></i> <?php echo e(EMPRESA_UBICACION); ?> &bull; 
            <i class="bi bi-telephone-fill text-white me-1"></i> Teléfono Oficial: <?php echo e(EMPRESA_TELEFONO); ?>
          </p>
        </div>

        <div class="col-lg-5 text-center text-lg-end">
          <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-end mb-2">
            <a href="#modalPoliticaDatos" class="text-white text-decoration-none small hover-underline" data-bs-toggle="modal" data-bs-target="#modalPoliticaDatos">
              Política de Tratamiento de Datos
            </a>
            <span class="opacity-50">|</span>
            <a href="#modalTerminos" class="text-white text-decoration-none small hover-underline" data-bs-toggle="modal" data-bs-target="#modalTerminos">
              Términos y Condiciones
            </a>
            <span class="opacity-50">|</span>
            <a href="javascript:window.scrollTo({top:0, behavior:'smooth'})" class="text-warning text-decoration-none small hover-underline">
              Subir ↑
            </a>
          </div>
          <p class="small text-white-50 mb-0">
            Plataforma de Control Lechero &copy; <?php echo date('Y'); ?>. Todos los derechos reservados.
          </p>
        </div>
      </div>
    </div>
  </footer>

  <!-- MODAL: POLÍTICA DE TRATAMIENTO DE DATOS PERSONALES (LEY 1581 DE 2012) -->
  <div class="modal fade" id="modalPoliticaDatos" tabindex="-1" aria-labelledby="modalDatosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header text-white p-3 p-md-4" style="background-color: var(--primary);">
          <h5 class="modal-title fw-bold text-white" id="modalDatosLabel">
            <i class="bi bi-shield-check me-2"></i> Política de Tratamiento de Datos Personales
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-4 text-dark" style="line-height: 1.8;">
          <div class="alert alert-warning py-2 px-3 small fw-bold mb-3">
            ⚠️ <em>BORRADOR – validar con asesor legal</em>
          </div>
          <h6 class="fw-bold text-dark">1. Responsable del Tratamiento</h6>
          <p>La <strong><?php echo e(EMPRESA_NOMBRE); ?></strong> (en adelante "ASOAGROALIZAL"), identificada con NIT <strong><?php echo e(EMPRESA_NIT); ?></strong>, con sede en <?php echo e(EMPRESA_UBICACION); ?> y correo electrónico <?php echo e(EMPRESA_CORREO); ?>, informa que los datos personales recolectados en esta plataforma son tratados de conformidad con la Ley Estatutaria 1581 de 2012 y el Decreto 1377 de 2013.</p>
          
          <h6 class="fw-bold text-dark">2. Finalidad del Tratamiento</h6>
          <p>Los datos suministrados por los asociados y visitantes serán utilizados única y exclusivamente para: a) El registro, liquidación quincenal y control lechero interno de la asociación; b) La comunicación de asambleas, capacitaciones y jornadas técnicas; c) La atención de consultas y PQRS radicadas en el sitio web; d) El cumplimiento de obligaciones tributarias, comerciales y sanitarias ante las autoridades colombianas.</p>

          <h6 class="fw-bold text-dark">3. Derechos del Titular</h6>
          <p>Como titular de los datos personales, le asiste el derecho a conocer, actualizar, rectificar y suprimir su información, así como a revocar la autorización otorgada mediante solicitud escrita remitida al correo electrónico oficial: <?php echo e(EMPRESA_CORREO); ?>.</p>
        </div>
        <div class="modal-footer p-3 bg-light">
          <button type="button" class="btn btn-touch btn-aso-primary px-4" data-bs-dismiss="modal">Entendido</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: TÉRMINOS Y CONDICIONES DE USO -->
  <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header text-white p-3 p-md-4" style="background-color: var(--primary);">
          <h5 class="modal-title fw-bold text-white" id="modalTerminosLabel">
            <i class="bi bi-file-earmark-text me-2"></i> Términos y Condiciones de Uso
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-4 text-dark" style="line-height: 1.8;">
          <div class="alert alert-warning py-2 px-3 small fw-bold mb-3">
            ⚠️ <em>BORRADOR – validar con asesor legal</em>
          </div>
          <h6 class="fw-bold text-dark">1. Naturaleza del Sistema</h6>
          <p>La plataforma tecnológica de <strong><?php echo e(EMPRESA_SIGLA); ?></strong> es una herramienta interna de gestión asociativa destinada al registro de pesajes de leche, seguimiento de Buenas Prácticas de Ordeño (BPO) y liquidaciones quincenales para sus 32 asociados.</p>

          <h6 class="fw-bold text-dark">2. Uso de Credenciales y Reserva Operativa</h6>
          <p>El usuario y contraseña asignados a cada asociado son personales e intransferibles. Queda prohibida la divulgación de credenciales a terceros. Toda liquidación mostrada tiene carácter de planilla y liquidación interna de control lechero.</p>

          <h6 class="fw-bold text-dark">3. Reserva de Información y Bioseguridad</h6>
          <p>Por razones de seguridad comunitaria y bioseguridad sanitaria, no se publican en canales abiertos horarios de acopio ni cifras financieras privadas de la comunidad campesina.</p>
        </div>
        <div class="modal-footer p-3 bg-light">
          <button type="button" class="btn btn-touch btn-aso-primary px-4" data-bs-dismiss="modal">Entendido</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Scripts Globales Optimizados (Defer y CDN Fijado) -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" defer></script>
  
  <?php if (!empty($usarDataTables)): ?>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" defer></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" defer></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js" defer></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js" defer></script>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
  <script src="assets/js/theme.js" defer></script>

  <!-- SweetAlert2 Notificaciones Flash -->
  <?php echo renderSweetAlert(); ?>

</body>
</html>

