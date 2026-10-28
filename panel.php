<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: panel.php
 * Propósito: Portal del Ganadero Asociado (SSR con PDO, DataTables BS5, Reclamos 1xQ).
 * Fuente Oficial: docs/FICHA_OFICIAL_ASOAGROALIZAL.md
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// 1. CONTROL DE ACCESO ESTRICTO (ROL GANADERO O ADMIN)
$usuario = requireAuth('GANADERO');
$pdo = getDB();

// 2. DETECCIÓN O SELECCIÓN DEL PERIODO QUINCENAL
$quincenaActual = calcularQuincenaActual();
$fechaInicioSel = $_GET['fechaInicio'] ?? $quincenaActual['fechaInicio'];
$fechaFinSel    = $_GET['fechaFin'] ?? $quincenaActual['fechaFin'];

// Validar formato de fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicioSel) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinSel)) {
    $fechaInicioSel = $quincenaActual['fechaInicio'];
    $fechaFinSel    = $quincenaActual['fechaFin'];
}

// 3. CONSULTAR PRECIO OFICIAL DE LA QUINCENA
$stmtQ = $pdo->prepare("SELECT precioLitro, cerrada FROM quincena WHERE fechaInicio = ? AND fechaFin = ? LIMIT 1");
$stmtQ->execute([$fechaInicioSel, $fechaFinSel]);
$quincenaDB = $stmtQ->fetch();
$precioLitro = ($quincenaDB && isset($quincenaDB['precioLitro'])) ? (float)$quincenaDB['precioLitro'] : 0.0;
$quincenaCerrada = !empty($quincenaDB['cerrada']);

// 4. PESAJES DE HOY PARA ESTE GANADERO (PROTECCIÓN IDOR: USO ESTRICTO DE $_SESSION)
$hoyStr = date('Y-m-d');
$stmtHoy = $pdo->prepare("SELECT turno, litros FROM entrega WHERE usuarioId = ? AND fecha = ?");
$stmtHoy->execute([$usuario['id'], $hoyStr]);
$entregasHoy = $stmtHoy->fetchAll();

$litrosMananaHoy = 0.0;
$litrosTardeHoy  = 0.0;
foreach ($entregasHoy as $eh) {
    if ($eh['turno'] === 'MANANA') $litrosMananaHoy = (float)$eh['litros'];
    if ($eh['turno'] === 'TARDE')  $litrosTardeHoy  = (float)$eh['litros'];
}
$totalHoy = $litrosMananaHoy + $litrosTardeHoy;

// 5. LISTAR TODAS LAS ENTREGAS DE ESTE GANADERO EN LA QUINCENA
$stmtEntregas = $pdo->prepare("SELECT fecha, turno, litros, creadoEn 
                               FROM entrega 
                               WHERE usuarioId = ? AND fecha >= ? AND fecha <= ? 
                               ORDER BY fecha ASC, turno ASC");
$stmtEntregas->execute([$usuario['id'], $fechaInicioSel, $fechaFinSel]);
$entregasQuincena = $stmtEntregas->fetchAll();

// Mapeo matricial por fecha y cálculo de totales acumulados
$mapaPesajes = [];
$totalLitrosQuincena = 0.0;
$diasConRegistro = [];

foreach ($entregasQuincena as $eq) {
    $f = $eq['fecha'];
    $t = $eq['turno'];
    $l = (float)$eq['litros'];

    if (!isset($mapaPesajes[$f])) {
        $mapaPesajes[$f] = ['MANANA' => 0.0, 'TARDE' => 0.0, 'TOTAL' => 0.0];
    }
    $mapaPesajes[$f][$t] = $l;
    $mapaPesajes[$f]['TOTAL'] += $l;
    $totalLitrosQuincena += $l;
    $diasConRegistro[$f] = true;
}

$cantidadDiasConEntrega = count($diasConRegistro);
$promedioDiario = ($cantidadDiasConEntrega > 0) ? ($totalLitrosQuincena / $cantidadDiasConEntrega) : 0.0;
$totalPagarEstimado = $precioLitro ? ($totalLitrosQuincena * $precioLitro) : 0.0;

// Generar días completos del periodo quincenal
$diasQuincena = generarDiasQuincena($fechaInicioSel, $fechaFinSel);

// 6. HISTORIAL DE QUINCENAS DISPONIBLES PARA EL SELECTOR
$stmtListaQ = $pdo->query("SELECT fechaInicio, fechaFin, precioLitro, cerrada FROM quincena ORDER BY fechaInicio DESC LIMIT 24");
$quincenasDisponibles = $stmtListaQ->fetchAll();

// Si no hay quincenas en la BD, incluir al menos la actual
if (empty($quincenasDisponibles)) {
    $quincenasDisponibles = [[
        'fechaInicio' => $quincenaActual['fechaInicio'],
        'fechaFin'    => $quincenaActual['fechaFin'],
        'precioLitro' => 0,
        'cerrada'     => 0
    ]];
}

// 7. VERIFICAR INCONFORMIDAD / RECLAMO DE ESTA QUINCENA (LÍMITE 1 POR QUINCENA)
$stmtAlerta = $pdo->prepare("SELECT id, mensaje, litrosAlMomento, estado, creadoEn 
                             FROM alertaquincena 
                             WHERE usuarioId = ? AND fechaInicio = ? AND fechaFin = ? 
                             LIMIT 1");
$stmtAlerta->execute([$usuario['id'], $fechaInicioSel, $fechaFinSel]);
$alertaExistente = $stmtAlerta->fetch();

// 8. PROCESAR REGISTRO DE RECLAMO VÍA POST
$errorReclamo = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['accion_reclamo'])) {
    $tokenRecibido = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($tokenRecibido)) {
        $errorReclamo = 'La sesión de seguridad ha expirado. Por favor recarga la página.';
    } elseif ($alertaExistente) {
        $errorReclamo = 'Ya existe una inconformidad registrada para este periodo quincenal.';
    } else {
        $mensajeReclamo = trim($_POST['mensaje_reclamo'] ?? '');
        if (empty($mensajeReclamo) || strlen($mensajeReclamo) < 10) {
            $errorReclamo = 'Por favor detalla tu reclamo con al menos 10 caracteres.';
        } else {
            try {
                $altId = generarUUID('alt');
                $stmtInsAlt = $pdo->prepare("INSERT INTO alertaquincena 
                    (id, usuarioId, fechaInicio, fechaFin, mensaje, litrosAlMomento, estado, correoEnviado, atendida, creadoEn) 
                    VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE', 0, 0, NOW())");
                $stmtInsAlt->execute([
                    $altId,
                    $usuario['id'],
                    $fechaInicioSel,
                    $fechaFinSel,
                    $mensajeReclamo,
                    $totalLitrosQuincena
                ]);

                // Notificación administrativa
                registrarAuditoria($pdo, 'INCONFORMIDAD_RADICADA', $usuario['id'], $usuario['cedula'], "Periodo: $fechaInicioSel a $fechaFinSel");

                setFlashAlert('success', '¡Inconformidad Registrada!', 'Tu reclamo ha sido enviado al administrador para su verificación en planillas.');
                header("Location: panel.php?fechaInicio={$fechaInicioSel}&fechaFin={$fechaFinSel}");
                exit;
            } catch (Exception $e) {
                error_log("Error guardando alerta: " . $e->getMessage());
                $errorReclamo = 'Ocurrió un error al registrar el reclamo. Por favor intenta más tarde.';
            }
        }
    }
}

// Configuración de vista para header compartido
$tituloPagina = 'Portal del Ganadero | ' . EMPRESA_SIGLA;
$usarDataTables = true;
$navbarFluido = true;

require_once __DIR__ . '/includes/header.php';
?>

<main class="container-fluid px-3 px-md-4 py-4 flex-grow-1">

  <!-- BIENVENIDA Y RESUMEN DEL GANADERO EN AZUL MARINO SÓLIDO -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card card-rural p-4 border-0 shadow text-white" style="background: var(--grad-primary); border: none;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
            <span class="badge px-3 py-1.5 rounded-pill fw-bold mb-2" style="background-color: var(--accent); color: #ffffff;">Asociado Oficial</span>
            <h2 class="display-6 fw-bold mb-1 text-white">¡Hola, <?php echo e($usuario['nombre']); ?>!</h2>
            <p class="mb-0 text-white-50" style="font-size: 1.05rem;">
              <i class="bi bi-geo-alt-fill text-warning me-1"></i> Finca: <strong class="text-white"><?php echo e($usuario['finca'] ?? 'Vereda Alizal'); ?></strong> &bull; Cédula: <strong class="text-white"><?php echo e($usuario['cedula']); ?></strong>
            </p>
          </div>
          <div class="text-md-end">
            <span class="badge bg-white text-dark px-3 py-2.5 rounded-4 fw-bold shadow-sm" style="font-size: 0.95rem; border: 1.5px solid var(--border);">
              <i class="bi bi-calendar3 me-1 text-primary"></i> <?php echo formatoFechaEspanol($hoyStr); ?>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- TARJETAS DE PESAJE DE HOY (MAÑANA / TARDE / TOTAL DÍA) CON BARRAS DE COMPARACIÓN -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <h3 class="fw-bold text-dark mb-2">
        <i class="bi bi-sun-fill text-warning me-2"></i> Pesaje de Hoy (<?php echo date('d/m/Y'); ?>)
      </h3>
    </div>

    <!-- Turno Mañana -->
    <div class="col-md-4">
      <div class="card card-rural p-4 border-start border-4 border-warning h-100">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge px-3 py-1.5 rounded-pill fw-bold" style="background-color: #fef3c7; color: #78350f;">
            <i class="bi bi-brightness-high-fill me-1"></i> Turno Mañana
          </span>
          <span class="fw-bold text-muted">☀️ Mañana</span>
        </div>
        <div class="d-flex align-items-baseline gap-2 my-2">
          <span class="display-5 fw-bold text-dark"><?php echo number_format($litrosMananaHoy, 1, ',', '.'); ?></span>
          <span class="fs-5 fw-bold text-muted">Litros</span>
        </div>
        <div class="progress-milk-container mt-2">
          <?php $pctM = ($totalHoy > 0) ? min(100, round(($litrosMananaHoy / $totalHoy) * 100)) : 0; ?>
          <div class="progress-bar-milk" style="width: <?php echo $pctM; ?>%; background-color: var(--accent);">
            <?php echo $pctM > 15 ? $pctM . '%' : ''; ?>
          </div>
        </div>
        <small class="text-muted mt-2 d-block">Registrado en acopio</small>
      </div>
    </div>

    <!-- Turno Tarde -->
    <div class="col-md-4">
      <div class="card card-rural p-4 border-start border-4 border-danger h-100">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge px-3 py-1.5 rounded-pill fw-bold" style="background-color: #fee2e2; color: #991b1b;">
            <i class="bi bi-sunset-fill me-1"></i> Turno Tarde
          </span>
          <span class="fw-bold text-muted">🌅 Tarde</span>
        </div>
        <div class="d-flex align-items-baseline gap-2 my-2">
          <span class="display-5 fw-bold text-dark"><?php echo number_format($litrosTardeHoy, 1, ',', '.'); ?></span>
          <span class="fs-5 fw-bold text-muted">Litros</span>
        </div>
        <div class="progress-milk-container mt-2">
          <?php $pctT = ($totalHoy > 0) ? min(100, round(($litrosTardeHoy / $totalHoy) * 100)) : 0; ?>
          <div class="progress-bar-milk" style="width: <?php echo $pctT; ?>%; background-color: var(--danger);">
            <?php echo $pctT > 15 ? $pctT . '%' : ''; ?>
          </div>
        </div>
        <small class="text-muted mt-2 d-block">Registrado en acopio</small>
      </div>
    </div>

    <!-- Total del Día -->
    <div class="col-md-4">
      <div class="card card-rural p-4 border-start border-4 border-primary h-100" style="background-color: var(--surface-2);">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge px-3 py-1.5 rounded-pill fw-bold text-white" style="background-color: var(--primary);">
            <i class="bi bi-droplet-fill me-1"></i> Total Entregado Hoy
          </span>
          <span class="fw-bold text-primary">Suma del Día</span>
        </div>
        <div class="d-flex align-items-baseline gap-2 my-2">
          <span class="display-5 fw-bold text-primary"><?php echo number_format($totalHoy, 1, ',', '.'); ?></span>
          <span class="fs-5 fw-bold text-primary">Litros</span>
        </div>
        <div class="progress-milk-container mt-2">
          <div class="progress-bar-milk" style="width: <?php echo $totalHoy > 0 ? '100%' : '0%'; ?>; background-color: var(--primary);">
            <?php echo $totalHoy > 0 ? '100% REGISTRADO' : 'SIN REGISTRO'; ?>
          </div>
        </div>
        <small class="text-muted mt-2 d-block">Cadena de frío activa</small>
      </div>
    </div>
  </div>

  <!-- TARJETAS RESUMEN DE LA QUINCENA CON BARRAS DE PROGRESO DE ALTO CONTRASTE -->
  <div class="row g-3 mb-4">
    <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div>
        <h3 class="fw-bold text-dark mb-0">
          <i class="bi bi-bar-chart-line-fill me-2 text-primary"></i> Resumen de la Quincena Seleccionada
        </h3>
        <span class="text-muted" style="font-size: 1.05rem;">Periodo: <strong><?php echo formatoFechaEspanol($fechaInicioSel); ?></strong> al <strong><?php echo formatoFechaEspanol($fechaFinSel); ?></strong></span>
      </div>

      <!-- Selector de Quincena -->
      <form action="panel.php" method="GET" class="d-flex align-items-center gap-2">
        <label for="filtro-quincena" class="fw-bold text-dark text-nowrap mb-0">Periodo:</label>
        <select id="filtro-quincena" class="form-select form-select-touch" onchange="const p = this.value.split('|'); location.href='panel.php?fechaInicio=' + p[0] + '&fechaFin=' + p[1];">
          <?php foreach ($quincenasDisponibles as $q): ?>
            <?php $sel = ($q['fechaInicio'] === $fechaInicioSel && $q['fechaFin'] === $fechaFinSel) ? 'selected' : ''; ?>
            <option value="<?php echo e($q['fechaInicio'] . '|' . $q['fechaFin']); ?>" <?php echo $sel; ?>>
              <?php echo formatoFechaEspanol($q['fechaInicio']); ?> al <?php echo formatoFechaEspanol($q['fechaFin']); ?>
              <?php echo !empty($q['cerrada']) ? ' (Cerrada)' : ' (En Curso)'; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <!-- Barra de Progreso Quincenal de Alto Contraste -->
    <div class="col-12">
      <div class="card card-rural p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold text-dark">
            <i class="bi bi-speedometer2 text-primary me-2"></i> Avance de Entregas en la Quincena (Días con Registro):
          </span>
          <span class="badge px-3 py-1 rounded-pill fw-bold text-white fs-6" style="background-color: var(--primary);">
            <?php echo $cantidadDiasConEntrega; ?> de <?php echo count($diasQuincena); ?> días
          </span>
        </div>
        <div class="progress-milk-container" style="height: 34px;">
          <?php 
            $totalDiasPeriodo = max(1, count($diasQuincena));
            $pctAvanceQuincena = min(100, round(($cantidadDiasConEntrega / $totalDiasPeriodo) * 100));
          ?>
          <div class="progress-bar-milk" style="width: <?php echo $pctAvanceQuincena; ?>%;">
            <?php echo $pctAvanceQuincena; ?>% COMPLETADO
          </div>
        </div>
      </div>
    </div>

    <!-- Tarjeta 1: Total Litros Quincena -->
    <div class="col-md-3 col-6">
      <div class="card card-rural p-4 text-center h-100">
        <small class="text-muted fw-bold text-uppercase">Litros Acumulados</small>
        <div class="display-6 fw-bold text-primary my-2">
          <?php echo number_format($totalLitrosQuincena, 1, ',', '.'); ?> L
        </div>
        <small class="text-muted">Total de tus pesajes</small>
      </div>
    </div>

    <!-- Tarjeta 2: Promedio Diario -->
    <div class="col-md-3 col-6">
      <div class="card card-rural p-4 text-center h-100">
        <small class="text-muted fw-bold text-uppercase">Promedio Diario</small>
        <div class="display-6 fw-bold text-dark my-2">
          <?php echo number_format($promedioDiario, 1, ',', '.'); ?> L
        </div>
        <small class="text-muted">Litros por día con entrega</small>
      </div>
    </div>

    <!-- Tarjeta 3: Precio Oficial por Litro -->
    <div class="col-md-3 col-6">
      <div class="card card-rural p-4 text-center h-100">
        <small class="text-muted fw-bold text-uppercase">Precio Oficial Litro</small>
        <div class="display-6 fw-bold my-2" style="color: var(--accent);">
          <?php echo $precioLitro > 0 ? formatoMonedaCOP($precioLitro) : '<span class="fs-5 text-muted">Por Fijar</span>'; ?>
        </div>
        <small class="text-muted"><?php echo $quincenaCerrada ? 'Tarifa Liquidada' : 'Tarifa Vigente'; ?></small>
      </div>
    </div>

    <!-- Tarjeta 4: Pago Estimado -->
    <div class="col-md-3 col-6">
      <div class="card card-rural p-4 text-center h-100 border-2" style="border-color: var(--primary) !important;">
        <small class="text-muted fw-bold text-uppercase">Pago Estimado</small>
        <div class="display-6 fw-bold text-primary my-2">
          <?php echo formatoMonedaCOP($totalPagarEstimado); ?>
        </div>
        <small class="text-muted"><?php echo $quincenaCerrada ? 'Valor Final Liquidado' : 'Sujeto al cierre quincenal'; ?></small>
      </div>
    </div>
  </div>

  <!-- SECCIÓN: TABLA DETALLADA DE PESAJES DÍA A DÍA -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card card-rural border-0 shadow overflow-hidden p-0">
        <div class="p-4 d-flex justify-content-between align-items-center text-white" style="background: var(--grad-primary);">
          <div>
            <h4 class="fw-bold mb-0 text-white">
              <i class="bi bi-calendar2-range me-2"></i> Planilla Detallada de Pesajes de la Quincena
            </h4>
            <small class="text-white-50">Tus pesajes registrados día a día en la Finca El Recuerdo</small>
          </div>
          <span class="badge px-3 py-2 rounded-pill fw-bold text-white fs-6" style="background-color: var(--accent);">
            <?php echo count($entregasQuincena); ?> Registros en el Periodo
          </span>
        </div>

        <div class="card-body p-4">
          <?php if (empty($diasQuincena)): ?>
            <div class="text-center py-5">
              <i class="bi bi-inbox display-1 text-muted"></i>
              <h5 class="fw-bold mt-3">No hay fechas configuradas para esta quincena</h5>
              <p class="text-muted">Selecciona otro periodo en el menú superior.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table id="tabla-ganadero-pesajes" class="table table-hover align-middle w-100" style="font-size: 1rem;">
                <thead style="background-color: var(--table-head-bg); color: #ffffff;">
                  <tr>
                    <th style="background-color: var(--primary); color: #ffffff; padding: 1rem;">Fecha de Entrega</th>
                    <th style="background-color: var(--primary); color: #ffffff; padding: 1rem;">Día</th>
                    <th class="text-center" style="background-color: var(--primary); color: #ffffff; padding: 1rem;">☀️ Turno Mañana</th>
                    <th class="text-center" style="background-color: var(--primary); color: #ffffff; padding: 1rem;">🌅 Turno Tarde</th>
                    <th class="text-end" style="background-color: var(--primary); color: #ffffff; padding: 1rem;">🥛 Total del Día</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $sumaTotalMañana = 0.0;
                  $sumaTotalTarde  = 0.0;
                  foreach ($diasQuincena as $diaObj): 
                      $fStr = $diaObj['fecha'];
                      $mVal = $mapaPesajes[$fStr]['MANANA'] ?? 0.0;
                      $tVal = $mapaPesajes[$fStr]['TARDE']  ?? 0.0;
                      $diaTotal = $mVal + $tVal;
                      $sumaTotalMañana += $mVal;
                      $sumaTotalTarde  += $tVal;
                      $esHoy = ($fStr === $hoyStr);
                  ?>
                    <tr class="<?php echo $esHoy ? 'table-warning fw-bold' : ''; ?>">
                      <td style="padding: 1rem;">
                        <strong><?php echo formatoFechaEspanol($fStr); ?></strong>
                        <?php if ($esHoy): ?>
                          <span class="badge bg-warning text-dark ms-2">HOY</span>
                        <?php endif; ?>
                      </td>
                      <td style="padding: 1rem;"><?php echo e($diaObj['diaSemana']); ?></td>
                      <td class="text-center" style="padding: 1rem;">
                        <?php if ($mVal > 0): ?>
                          <span class="badge px-3 py-1.5 rounded-pill fw-bold fs-6" style="background-color: #fef3c7; color: #78350f;"><?php echo number_format($mVal, 1, ',', '.'); ?> L</span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center" style="padding: 1rem;">
                        <?php if ($tVal > 0): ?>
                          <span class="badge px-3 py-1.5 rounded-pill fw-bold fs-6" style="background-color: #fee2e2; color: #991b1b;"><?php echo number_format($tVal, 1, ',', '.'); ?> L</span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end fw-bold text-primary fs-5" style="padding: 1rem;">
                        <?php echo ($diaTotal > 0) ? number_format($diaTotal, 1, ',', '.') . ' L' : '<span class="text-muted">-</span>'; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot style="background-color: var(--surface-2); border-top: 3px solid var(--border);">
                  <tr>
                    <th colspan="2" class="text-end text-uppercase" style="padding: 1.2rem;">Totales de la Quincena:</th>
                    <th class="text-center fs-5" style="padding: 1.2rem; color: var(--accent);">
                      <?php echo number_format($sumaTotalMañana, 1, ',', '.'); ?> L
                    </th>
                    <th class="text-center fs-5 text-danger" style="padding: 1.2rem;">
                      <?php echo number_format($sumaTotalTarde, 1, ',', '.'); ?> L
                    </th>
                    <th class="text-end text-primary fs-4 fw-bold" style="padding: 1.2rem;">
                      <?php echo number_format($totalLitrosQuincena, 1, ',', '.'); ?> L
                    </th>
                  </tr>
                </tfoot>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- SECCIÓN DE INCONFORMIDAD / RECLAMOS (LÍMITE 1 POR QUINCENA) -->
  <div class="row">
    <div class="col-12">
      <div class="card card-rural p-4 border-2 shadow-sm" style="border-color: var(--accent) !important;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
            <h4 class="fw-bold text-dark mb-1">
              <i class="bi bi-shield-exclamation text-warning me-2"></i> ¿Tienes alguna inconformidad con tus litros en este periodo?
            </h4>
            <span class="text-muted">
              Por políticas de control lechero, solo se permite registrar <strong>UNA (1) inconformidad formal</strong> por cada quincena.
            </span>
          </div>

          <div>
            <?php if ($alertaExistente): ?>
              <div class="alert alert-info rounded-4 py-2 px-3 mb-0 small fw-bold">
                <i class="bi bi-clock-history me-1"></i> Inconformidad radicada el <?php echo formatoFechaEspanol($alertaExistente['creadoEn']); ?>. 
                Estado: <span class="badge bg-primary"><?php echo e($alertaExistente['estado']); ?></span>
              </div>
            <?php else: ?>
              <button type="button" class="btn btn-touch btn-aso-accent rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalReclamo">
                <i class="bi bi-chat-left-dots-fill me-1"></i> Radicar Inconformidad
              </button>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($alertaExistente): ?>
          <div class="mt-3 p-3 bg-light rounded-3 border small">
            <strong class="text-primary d-block mb-1">Detalle del reclamo presentado:</strong>
            <p class="mb-0 text-dark fst-italic">"<?php echo e($alertaExistente['mensaje']); ?>"</p>
            <small class="text-muted mt-1 d-block">Litros registrados al momento del reclamo: <?php echo formatoLitros($alertaExistente['litrosAlMomento']); ?></small>
          </div>
        <?php endif; ?>

        <?php if (!empty($errorReclamo)): ?>
          <div class="alert alert-danger rounded-4 py-2 small fw-bold mt-3 mb-0">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo e($errorReclamo); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</main>

<!-- MODAL PARA RADICAR RECLAMO -->
<?php if (!$alertaExistente): ?>
  <div class="modal fade" id="modalReclamo" tabindex="-1" aria-labelledby="modalReclamoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header text-white p-4" style="background-color: var(--primary);">
          <h5 class="modal-title fw-bold text-white" id="modalReclamoLabel">
            <i class="bi bi-shield-exclamation text-warning me-2"></i> Radicar Inconformidad Quincenal
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <form action="panel.php?fechaInicio=<?php echo e($fechaInicioSel); ?>&fechaFin=<?php echo e($fechaFinSel); ?>" method="POST">
          <?php echo campoCsrf(); ?>
          <input type="hidden" name="accion_reclamo" value="1">

          <div class="modal-body p-4">
            <p class="text-muted mb-3">
              Escribe detalladamente las fechas, turnos o diferencias que consideres que deben ser revisadas por el administrador de la asociación.
            </p>

            <div class="mb-3">
              <label for="mensaje_reclamo" class="form-label">Detalle de la Inconformidad *</label>
              <textarea class="form-control form-control-touch" id="mensaje_reclamo" name="mensaje_reclamo" rows="4" required placeholder="Describe las fechas y litros que requieres verificar..."></textarea>
              <small class="text-muted d-block mt-1">Mínimo 10 caracteres.</small>
            </div>

            <div class="p-3 bg-warning-subtle rounded-3 small text-warning-emphasis">
              <i class="bi bi-info-circle-fill me-1"></i> Al enviar, el mensaje quedará registrado en la base de datos y se notificará de inmediato a la administración.
            </div>
          </div>
          <div class="modal-footer p-3 bg-body-tertiary">
            <button type="button" class="btn btn-touch btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-touch btn-aso-primary rounded-pill px-4">
              <i class="bi bi-send-fill me-1"></i> Enviar Reclamo
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
      $('#tabla-ganadero-pesajes').DataTable({
        language: window.AsoagroUI?.dataTablesSpanish || {},
        pageLength: 20,
        responsive: true,
        order: [[0, 'asc']],
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
          {
            extend: 'excelHtml5',
            text: '<i class="bi bi-file-earmark-excel-fill text-success me-1"></i> Excel',
            className: 'btn btn-outline-success btn-sm rounded-pill shadow-sm me-1',
            title: 'Mis_Pesajes_<?php echo e($usuario["cedula"]); ?>_<?php echo $fechaInicioSel; ?>'
          },
          {
            extend: 'pdfHtml5',
            text: '<i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i> PDF',
            className: 'btn btn-outline-danger btn-sm rounded-pill shadow-sm me-1',
            title: 'Planilla_Lechera_<?php echo e($usuario["nombre"]); ?>'
          },
          {
            extend: 'print',
            text: '<i class="bi bi-printer-fill text-primary me-1"></i> Imprimir',
            className: 'btn btn-outline-primary btn-sm rounded-pill shadow-sm'
          }
        ]
      });
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
