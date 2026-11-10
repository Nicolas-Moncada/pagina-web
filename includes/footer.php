<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/footer.php
 * Propósito: Pie de página corporativo minimalista inspirado en Central Ganadera:
 *            - 4 columnas ejecutivas sobrias sin emojis.
 *            - Inicialización de Lucide Icons con stroke-width: 1.5px.
 *            - Modales de política de datos (Ley 1581 de 2012) y términos institucionales.
 * =========================================================================================
 */
?>
  <!-- PIE DE PÁGINA CORPORATIVO 4 COLUMNAS -->
  <footer class="corp-footer mt-auto">
    <div class="container py-4">
      <div class="row g-4 mb-4">
        
        <!-- Columna 1: Identidad Corporativa -->
        <div class="col-lg-4 col-md-6">
          <div class="d-flex align-items-center gap-2 mb-3">
            <img src="assets/img/logo.webp" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" class="bg-white p-1 rounded" style="max-height: 40px;" width="40" height="28" loading="lazy">
            <div>
              <span class="d-block font-heading font-bold text-white text-base leading-tight"><?php echo e(EMPRESA_SIGLA); ?></span>
              <span class="text-xs text-slate-400 font-mono">NIT <?php echo e(EMPRESA_NIT); ?></span>
            </div>
          </div>
          <p class="text-xs text-slate-400 mb-2 leading-relaxed">
            <?php echo e(EMPRESA_NOMBRE); ?>. Asociación de productores agropecuarios para el acopio técnico, trazabilidad sanitaria y liquidación transparente.
          </p>
          <div class="text-xs text-slate-400">
            <span class="d-block"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400 inline me-1"></i> <?php echo e(EMPRESA_UBICACION); ?></span>
            <span class="d-block mt-1"><i data-lucide="building" class="w-3.5 h-3.5 text-slate-400 inline me-1"></i> Registro Cámara de Comercio de Bogotá</span>
          </div>
        </div>

        <!-- Columna 2: Horarios de Planta y Acopio -->
        <div class="col-lg-3 col-md-6">
          <h6 class="corp-footer-title">Recepción en Planta</h6>
          <ul class="list-unstyled text-xs text-slate-300 mb-0 space-y-2">
            <li class="d-flex align-items-center justify-content-between border-b border-slate-800 pb-1.5">
              <span class="font-semibold text-slate-200">Turno Mañana (AM):</span>
              <span class="font-mono text-slate-300">05:30 &ndash; 07:30</span>
            </li>
            <li class="d-flex align-items-center justify-content-between border-b border-slate-800 pb-1.5">
              <span class="font-semibold text-slate-200">Turno Tarde (PM):</span>
              <span class="font-mono text-slate-300">16:00 &ndash; 17:30</span>
            </li>
            <li class="text-slate-400 pt-1 leading-normal">
              Acopio continuo los 365 días del año. Cadena de frío preservada por debajo de 4°C.
            </li>
          </ul>
        </div>

        <!-- Columna 3: Canales de Atención Formal -->
        <div class="col-lg-3 col-md-6">
          <h6 class="corp-footer-title">Atención y F-PQRS</h6>
          <ul class="list-unstyled text-xs text-slate-300 mb-0 space-y-2">
            <li>
              <a href="tel:<?php echo e(EMPRESA_TELEFONO); ?>" class="d-inline-flex align-items-center gap-2">
                <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Teléfono: <?php echo e(EMPRESA_TELEFONO); ?></span>
              </a>
            </li>
            <li>
              <a href="mailto:<?php echo e(EMPRESA_CORREO); ?>" class="d-inline-flex align-items-center gap-2">
                <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                <span><?php echo e(EMPRESA_CORREO); ?></span>
              </a>
            </li>
            <li>
              <a href="index.php#contacto" class="d-inline-flex align-items-center gap-2 text-slate-200 font-semibold">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-400"></i>
                <span>Radicación Electrónica F-PQRS</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Columna 4: Navegación Corporativa -->
        <div class="col-lg-2 col-md-6">
          <h6 class="corp-footer-title">Plataforma</h6>
          <ul class="list-unstyled text-xs space-y-1.5 mb-3">
            <li><a href="index.php#inicio">Inicio</a></li>
            <li><a href="index.php#areas">Normas Sanitarias</a></li>
            <li><a href="index.php#precio">Tarifa Quincenal</a></li>
            <li><a href="registro.php">Vincularse</a></li>
            <li><a href="login.php" class="text-white font-semibold">Acceso Asociados</a></li>
          </ul>
          <a href="javascript:window.scrollTo({top:0, behavior:'smooth'})" class="btn-corp-outline py-1 px-2.5 text-xs">
            <i data-lucide="arrow-up" class="w-3 h-3"></i> Subir
          </a>
        </div>

      </div>

      <hr class="border-slate-800 my-3">

      <!-- Barra de Copyright y Políticas Legales -->
      <div class="row align-items-center text-xs text-slate-400">
        <div class="col-md-7 mb-2 mb-md-0">
          Plataforma de Control Lechero &copy; <?php echo date('Y'); ?> <strong><?php echo e(EMPRESA_SIGLA); ?></strong>. Carmen de Carupa, Cundinamarca.
        </div>
        <div class="col-md-5 text-md-end space-x-3">
          <a href="#modalPoliticaDatos" class="hover:text-white" data-bs-toggle="modal" data-bs-target="#modalPoliticaDatos">
            Tratamiento de Datos
          </a>
          <span>&bull;</span>
          <a href="#modalTerminos" class="hover:text-white" data-bs-toggle="modal" data-bs-target="#modalTerminos">
            Términos de Servicio
          </a>
        </div>
      </div>
    </div>
  </footer>

  <!-- MODAL: POLÍTICA DE TRATAMIENTO DE DATOS PERSONALES (LEY 1581 DE 2012) -->
  <div class="modal fade" id="modalPoliticaDatos" tabindex="-1" aria-labelledby="modalDatosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content border rounded shadow">
        <div class="modal-header bg-slate-900 text-white p-3">
          <h6 class="modal-title font-heading font-bold text-white mb-0" id="modalDatosLabel">
            Tratamiento de Datos Personales (Ley 1581 de 2012)
          </h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-4 text-slate-800 text-sm leading-relaxed">
          <h6 class="font-bold text-slate-900 mb-2">1. Responsable del Tratamiento</h6>
          <p class="mb-3">La <strong><?php echo e(EMPRESA_NOMBRE); ?></strong> (en adelante "ASOAGROALIZAL"), con NIT <strong><?php echo e(EMPRESA_NIT); ?></strong>, informa que los datos personales recolectados en esta plataforma son tratados de conformidad con la Ley Estatutaria 1581 de 2012 y el Decreto 1377 de 2013.</p>

          <h6 class="font-bold text-slate-900 mb-2">2. Finalidad del Tratamiento</h6>
          <p class="mb-3">Los datos suministrados por los asociados y visitantes serán utilizados exclusivamente para: a) El registro, liquidación quincenal y control lechero interno de la asociación; b) La comunicación de asambleas, capacitaciones y jornadas técnicas; c) La atención de consultas y PQRS radicadas en el sitio web; d) El cumplimiento de obligaciones tributarias, comerciales y sanitarias ante las autoridades colombianas.</p>

          <h6 class="font-bold text-slate-900 mb-2">3. Derechos del Titular</h6>
          <p class="mb-0">Como titular de los datos personales, le asiste el derecho a conocer, actualizar, rectificar y suprimir su información mediante solicitud escrita remitida al correo electrónico oficial: <?php echo e(EMPRESA_CORREO); ?>.</p>
        </div>
        <div class="modal-footer p-2 bg-slate-50 border-t">
          <button type="button" class="btn-corp-primary text-xs" data-bs-dismiss="modal">Entendido</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: TÉRMINOS Y CONDICIONES DE USO -->
  <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content border rounded shadow">
        <div class="modal-header bg-slate-900 text-white p-3">
          <h6 class="modal-title font-heading font-bold text-white mb-0" id="modalTerminosLabel">
            Términos y Condiciones Institucionales
          </h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-4 text-slate-800 text-sm leading-relaxed">
          <h6 class="font-bold text-slate-900 mb-2">1. Naturaleza del Sistema</h6>
          <p class="mb-3">La plataforma tecnológica de <strong><?php echo e(EMPRESA_SIGLA); ?></strong> es una herramienta técnica de gestión asociativa destinada al registro de pesajes de leche, seguimiento de Buenas Prácticas de Ordeño (BPO) y liquidaciones quincenales para sus 32 asociados.</p>

          <h6 class="font-bold text-slate-900 mb-2">2. Procedimiento de Inconformidades (F-PQRS)</h6>
          <p class="mb-0">Las inconformidades sobre pesajes o valores liquidados podrán radicarse formalmente en el módulo F-PQRS dentro de los 3 días hábiles posteriores a la fecha de corte quincenal. La administración emitirá respuesta oficial verificable en la plataforma.</p>
        </div>
        <div class="modal-footer p-2 bg-slate-50 border-t">
          <button type="button" class="btn-corp-primary text-xs" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts Globales -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
