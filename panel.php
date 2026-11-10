<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: panel.php
 * Propósito: Portal del Ganadero Asociado (Dashboard Financiero estilo Central Ganadera):
 *            - Header del Asociado con datos de Finca, Cédula y Quincena Activa.
 *            - 4 KPIs de Producción: Litros Quincena, Promedio Diario, Tarifa Pactada y Liquidación Estimada.
 *            - Gráfica de Tendencia de Entrega Diaria con ApexCharts (Curvas AM vs PM).
 *            - Planilla quincenal solo lectura de alto contraste con impresión de Colilla.
 *            - Módulo F-PQRS con estado corporativo y respuesta oficial de la administración.
 *            - 100% CERO EMOJIS, iconografía Lucide Icons monocromática (stroke-width: 1.5px).
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

// 4. PESAJES DE HOY PARA ESTE GANADERO (PROTECCIÓN IDOR ESTRICTA)
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

$diasQuincena = generarDiasQuincena($fechaInicioSel, $fechaFinSel);

// Datos para ApexCharts
$fechasGrafica = [];
$serieManana = [];
$serieTarde = [];
foreach ($diasQuincena as $d) {
    $f = $d['fecha'];
    $fechasGrafica[] = date('d/m', strtotime($f));
    $serieManana[] = (float)($mapaPesajes[$f]['MANANA'] ?? 0.0);
    $serieTarde[] = (float)($mapaPesajes[$f]['TARDE'] ?? 0.0);
}

// 6. HISTORIAL DE QUINCENAS DISPONIBLES
$stmtListaQ = $pdo->query("SELECT fechaInicio, fechaFin, precioLitro, cerrada FROM quincena ORDER BY fechaInicio DESC LIMIT 24");
$quincenasDisponibles = $stmtListaQ->fetchAll();

if (empty($quincenasDisponibles)) {
    $quincenasDisponibles = [[
        'fechaInicio' => $quincenaActual['fechaInicio'],
        'fechaFin'    => $quincenaActual['fechaFin'],
        'precioLitro' => 0,
        'cerrada'     => 0
    ]];
}

// 7. VERIFICAR INCONFORMIDAD / RECLAMO DE ESTA QUINCENA (1 POR QUINCENA)
$stmtAlerta = $pdo->prepare("SELECT id, mensaje, litrosAlMomento, estado, respuestaAdmin, respondidoEn, respondidoPor, creadoEn 
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

                registrarAuditoria($pdo, 'INCONFORMIDAD_RADICADA', $usuario['id'], $usuario['cedula'], "Periodo: $fechaInicioSel a $fechaFinSel");
                setFlashAlert('success', '¡Inconformidad Registrada!', 'Tu reclamo ha sido radicado oficialmente ante la administración.');
                header("Location: panel.php?fechaInicio={$fechaInicioSel}&fechaFin={$fechaFinSel}");
                exit;
            } catch (Exception $e) {
                error_log("Error guardando alerta: " . $e->getMessage());
                $errorReclamo = 'Ocurrió un error al registrar el reclamo. Por favor intenta más tarde.';
            }
        }
    }
}

$tituloPagina = 'Portal del Ganadero | ' . EMPRESA_SIGLA;
$usarDataTables = true;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container-fluid px-3 px-md-4 py-4 flex-grow-1">

  <!-- ==============================================================
       1. HEADER DEL ASOCIADO (ESTILO CORPORATIVO CENTRAL GANADERA)
       ============================================================== -->
  <div class="card border border-slate-200 shadow-sm rounded-2xl p-4 p-md-5 text-white mb-4" style="background-color: var(--brand-primary, #0A2E1C);">
    <div class="row align-items-center g-3">
      <div class="col-lg-8">
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
          <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-900/60 text-emerald-200 border border-emerald-700/50">
            <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
            <span>Productor Asociado</span>
          </span>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white text-slate-900 font-mono">
            C.C. <?php echo e($usuario['cedula']); ?>
          </span>
          <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
            <i data-lucide="check" style="width: 13px; height: 13px;"></i>
            <span>Cuenta Activa</span>
          </span>
        </div>
        <h2 class="font-bold mb-1 text-white text-2xl md:text-3xl">
          ¡Bienvenido, <?php echo e($usuario['nombre']); ?>!
        </h2>
        <p class="text-xs text-slate-300 mb-0 flex items-center gap-2 flex-wrap">
          <span class="flex items-center gap-1"><i data-lucide="map-pin" class="text-amber-400" style="width: 14px; height: 14px;"></i> Predio: <strong><?php echo e($usuario['finca'] ?? 'Vereda Alizal'); ?></strong></span>
          <span>&bull;</span>
          <span>Sede: Carmen de Carupa, Cundinamarca</span>
        </p>
      </div>

      <!-- Selector de Quincena Rápido -->
      <div class="col-lg-4 text-lg-end">
        <form action="panel.php" method="GET" class="d-inline-flex flex-column align-items-lg-end gap-1">
          <label for="filtro-quincena" class="text-xs text-slate-300 font-semibold">Seleccionar Periodo Quincenal:</label>
          <select id="filtro-quincena" class="form-select form-select-sm fw-bold border-0 shadow-sm rounded-lg" style="min-width: 260px;" onchange="const p = this.value.split('|'); location.href='panel.php?fechaInicio=' + p[0] + '&fechaFin=' + p[1];">
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
    </div>
  </div>

  <!-- ==============================================================
       2. ENTREGAS DE HOY (TURNO AM / TURNO PM / TOTAL)
       ============================================================== -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <h5 class="font-bold text-slate-900 mb-0 flex items-center gap-2">
        <i data-lucide="calendar" class="text-emerald-800" style="width: 18px; height: 18px;"></i>
        <span>Entregas Registradas Hoy &ndash; <?php echo date('d/m/Y'); ?></span>
      </h5>
    </div>

    <!-- Turno AM -->
    <div class="col-md-4">
      <div class="card border border-slate-200 shadow-sm rounded-xl p-3.5 h-100 bg-white" style="border-top: 3px solid #D97706 !important;">
        <div class="flex items-center justify-between mb-2">
          <span class="badge-shift-m">Turno AM (Mañana)</span>
          <span class="text-xs text-slate-500 font-medium">5:30 AM &ndash; 7:30 AM</span>
        </div>
        <div class="flex items-baseline gap-2 my-1">
          <span class="text-3xl font-bold text-slate-900 font-mono"><?php echo number_format($litrosMananaHoy, 1, ',', '.'); ?></span>
          <span class="text-xs font-semibold text-slate-500">Litros</span>
        </div>
        <span class="text-xs text-slate-500 block">
          <?php echo ($litrosMananaHoy > 0) ? 'Registrado en tanque' : 'Pendiente de entrega'; ?>
        </span>
      </div>
    </div>

    <!-- Turno PM -->
    <div class="col-md-4">
      <div class="card border border-slate-200 shadow-sm rounded-xl p-3.5 h-100 bg-white" style="border-top: 3px solid #1E40AF !important;">
        <div class="flex items-center justify-between mb-2">
          <span class="badge-shift-t">Turno PM (Tarde)</span>
          <span class="text-xs text-slate-500 font-medium">4:00 PM &ndash; 5:30 PM</span>
        </div>
        <div class="flex items-baseline gap-2 my-1">
          <span class="text-3xl font-bold text-slate-900 font-mono"><?php echo number_format($litrosTardeHoy, 1, ',', '.'); ?></span>
          <span class="text-xs font-semibold text-slate-500">Litros</span>
        </div>
        <span class="text-xs text-slate-500 block">
          <?php echo ($litrosTardeHoy > 0) ? 'Registrado en tanque' : 'Pendiente de entrega'; ?>
        </span>
      </div>
    </div>

    <!-- Total del Día -->
    <div class="col-md-4">
      <div class="card border border-slate-200 shadow-sm rounded-xl p-3.5 h-100 bg-white" style="border-top: 3px solid #14532D !important;">
        <div class="flex items-center justify-between mb-2">
          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
            Total Hoy
          </span>
          <span class="text-xs text-emerald-700 font-semibold">Suma de Turnos</span>
        </div>
        <div class="flex items-baseline gap-2 my-1">
          <span class="text-3xl font-bold text-emerald-800 font-mono"><?php echo number_format($totalHoy, 1, ',', '.'); ?></span>
          <span class="text-xs font-semibold text-slate-500">Litros</span>
        </div>
        <span class="text-xs text-slate-500 block">Cadena de frío continua a 4°C</span>
      </div>
    </div>
  </div>

  <!-- ==============================================================
       3. 4 TARJETAS KPI DE LIQUIDACIÓN QUINCENAL (MINIMALISTAS)
       ============================================================== -->
  <div class="row g-3 mb-4">
    <!-- Litros Acumulados -->
    <div class="col-lg-3 col-sm-6">
      <div class="card border border-slate-200 shadow-sm rounded-xl p-3.5 bg-white h-100">
        <span class="text-xs uppercase tracking-wider font-semibold text-slate-500">Litros Quincena</span>
        <div class="text-2xl font-bold text-slate-900 font-mono my-1">
          <?php echo number_format($totalLitrosQuincena, 1, ',', '.'); ?> <span class="text-xs font-normal text-slate-500">L</span>
        </div>
        <span class="text-xs text-slate-500">Total entregado en el periodo</span>
      </div>
    </div>

    <!-- Promedio Diario -->
    <div class="col-lg-3 col-sm-6">
      <div class="card border border-slate-200 shadow-sm rounded-xl p-3.5 bg-white h-100">
        <span class="text-xs uppercase tracking-wider font-semibold text-slate-500">Promedio Diario</span>
        <div class="text-2xl font-bold text-slate-900 font-mono my-1">
          <?php echo number_format($promedioDiario, 1, ',', '.'); ?> <span class="text-xs font-normal text-slate-500">L / día</span>
        </div>
        <span class="text-xs text-slate-500"><?php echo $cantidadDiasConEntrega; ?> de <?php echo count($diasQuincena); ?> días con pesaje</span>
      </div>
    </div>

    <!-- Tarifa por Litro -->
    <div class="col-lg-3 col-sm-6">
      <div class="card border border-slate-200 shadow-sm rounded-xl p-3.5 bg-white h-100">
        <span class="text-xs uppercase tracking-wider font-semibold text-slate-500">Precio Oficial</span>
        <div class="text-2xl font-bold text-amber-600 font-mono my-1">
          $<?php echo number_format($precioLitro, 0, ',', '.'); ?> <span class="text-xs font-normal text-slate-500">COP</span>
        </div>
        <span class="text-xs text-slate-500"><?php echo $quincenaCerrada ? 'Tarifa Liquidada' : 'Tarifa Vigente'; ?></span>
      </div>
    </div>

    <!-- PAGO ESTIMADO EN COP -->
    <div class="col-lg-3 col-sm-6">
      <div class="card border-0 shadow-sm rounded-xl p-3.5 text-white h-100" style="background: linear-gradient(135deg, var(--brand-primary, #0A2E1C) 0%, #14532D 100%);">
        <div class="flex items-center justify-between">
          <span class="text-xs uppercase tracking-wider font-bold text-amber-400">Pago Estimado</span>
          <i data-lucide="circle-dollar-sign" class="text-amber-400" style="width: 18px; height: 18px;"></i>
        </div>
        <div class="text-2xl font-bold text-white font-mono my-1">
          $<?php echo number_format($totalPagarEstimado, 0, ',', '.'); ?>
        </div>
        <span class="text-xs text-slate-300">
          <?php echo $quincenaCerrada ? 'Neto a recibir liquidado' : 'Sujeto a pesajes restantes'; ?>
        </span>
      </div>
    </div>
  </div>

  <!-- ==============================================================
       GRÁFICA DE TENDENCIA DE ENTREGA (APEXCHARTS)
       ============================================================== -->
  <div class="card border border-slate-200 shadow-sm rounded-2xl p-4 bg-white mb-4">
    <div class="flex justify-between items-center mb-3">
      <div>
        <h6 class="font-bold text-slate-900 mb-0 flex items-center gap-2">
          <i data-lucide="trending-up" class="text-emerald-800" style="width: 18px; height: 18px;"></i>
          <span>Curva de Entregas por Turno (AM vs PM)</span>
        </h6>
        <span class="text-xs text-slate-500">Evolución diaria de litros durante la quincena seleccionada</span>
      </div>
      <div class="flex items-center gap-3 text-xs font-semibold">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span> Turno AM</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span> Turno PM</span>
      </div>
    </div>
    <div id="grafica-produccion" style="min-height: 280px;"></div>
  </div>

  <!-- ==============================================================
       4. PLANILLA QUINCENAL DETALLADA (SOLO LECTURA DE ALTO CONTRASTE)
       ============================================================== -->
  <div class="card border border-slate-200 shadow-sm rounded-2xl overflow-hidden bg-white mb-4">
    <div class="p-3.5 p-md-4 flex flex-wrap justify-between items-center gap-3 border-b border-slate-200 bg-slate-50">
      <div>
        <h5 class="font-bold text-slate-900 mb-0 flex items-center gap-2">
          <i data-lucide="table" class="text-emerald-800" style="width: 18px; height: 18px;"></i>
          <span>Planilla Quincenal de Pesajes Día a Día</span>
        </h5>
        <span class="text-xs text-slate-500">
          Periodo del <?php echo formatoFechaEspanol($fechaInicioSel); ?> al <?php echo formatoFechaEspanol($fechaFinSel); ?>
        </span>
      </div>

      <!-- Botón de Acción: Imprimir Colilla -->
      <div>
        <button type="button" class="btn btn-sm btn-outline-success font-semibold rounded-lg px-3 py-1.5 inline-flex items-center gap-1.5" onclick="abrirModalColilla()">
          <i data-lucide="printer" style="width: 15px; height: 15px;"></i>
          <span>Imprimir Colilla de Pago</span>
        </button>
      </div>
    </div>

    <div class="table-responsive p-3 p-md-4">
      <table id="tabla-ganadero-pesajes" class="table table-hover align-middle w-100">
        <thead class="table-light">
          <tr>
            <th class="fw-bold" style="background-color: var(--brand-primary, #0A2E1C); color: #fff;">Fecha de Entrega</th>
            <th class="fw-bold" style="background-color: var(--brand-primary, #0A2E1C); color: #fff;">Día</th>
            <th class="text-center fw-bold" style="background-color: var(--brand-primary, #0A2E1C); color: #fff;">Turno AM</th>
            <th class="text-center fw-bold" style="background-color: var(--brand-primary, #0A2E1C); color: #fff;">Turno PM</th>
            <th class="text-end fw-bold" style="background-color: var(--brand-primary, #0A2E1C); color: #fff;">Total Diario</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $sumaM = 0.0;
          $sumaT = 0.0;
          foreach ($diasQuincena as $diaObj):
              $f = $diaObj['fecha'];
              $m = $mapaPesajes[$f]['MANANA'] ?? 0.0;
              $t = $mapaPesajes[$f]['TARDE']  ?? 0.0;
              $tot = $m + $t;
              $sumaM += $m;
              $sumaT += $t;
              $esHoy = ($f === $hoyStr);
          ?>
            <tr class="<?php echo $esHoy ? 'table-warning fw-bold' : ''; ?>">
              <td class="fw-bold">
                <?php echo formatoFechaEspanol($f); ?>
                <?php if ($esHoy): ?>
                  <span class="badge bg-warning text-dark ms-1.5 font-semibold">HOY</span>
                <?php endif; ?>
              </td>
              <td class="text-muted"><?php echo e($diaObj['diaSemana']); ?></td>
              <td class="text-center font-mono">
                <?php if ($m > 0): ?>
                  <span class="badge-shift-m"><?php echo number_format($m, 1, ',', '.'); ?> L</span>
                <?php else: ?>
                  <span class="text-muted opacity-50">&ndash;</span>
                <?php endif; ?>
              </td>
              <td class="text-center font-mono">
                <?php if ($t > 0): ?>
                  <span class="badge-shift-t"><?php echo number_format($t, 1, ',', '.'); ?> L</span>
                <?php else: ?>
                  <span class="text-muted opacity-50">&ndash;</span>
                <?php endif; ?>
              </td>
              <td class="text-end font-bold font-mono text-emerald-800">
                <?php echo ($tot > 0) ? number_format($tot, 1, ',', '.') . ' L' : '<span class="text-muted opacity-50">&ndash;</span>'; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="bg-slate-50 border-t-2 border-slate-300">
          <tr>
            <th colspan="2" class="text-end fw-bold text-uppercase">Totales de la Quincena:</th>
            <th class="text-center fw-bold font-mono text-amber-700">
              <?php echo number_format($sumaM, 1, ',', '.'); ?> L
            </th>
            <th class="text-center fw-bold font-mono text-blue-700">
              <?php echo number_format($sumaT, 1, ',', '.'); ?> L
            </th>
            <th class="text-end fw-bold font-mono text-emerald-900 text-lg">
              <?php echo number_format($totalLitrosQuincena, 1, ',', '.'); ?> L
            </th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- ==============================================================
       5. MÓDULO F-PQRS (BUZÓN DE INCONFORMIDADES 1 POR QUINCENA)
       ============================================================== -->
  <div class="card border border-slate-200 shadow-sm rounded-2xl p-4 bg-white" id="reclamo">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div>
        <h5 class="font-bold text-slate-900 mb-1 flex items-center gap-2">
          <i data-lucide="alert-triangle" class="text-amber-500" style="width: 18px; height: 18px;"></i>
          <span>Buzón F-PQRS &bull; Inconformidades con Pesajes</span>
        </h5>
        <p class="text-xs text-slate-500 mb-0">
          Por política asociativa, puedes radicar <strong>un (1) reclamo formal</strong> por quincena para verificación de litros.
        </p>
      </div>

      <div>
        <?php if ($alertaExistente): ?>
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-300">
            <span>Estado:</span>
            <strong class="<?php echo $alertaExistente['estado'] === 'RESUELTO' ? 'text-emerald-700' : ($alertaExistente['estado'] === 'EN_REVISION' ? 'text-amber-700' : 'text-blue-700'); ?>">
              <?php echo e($alertaExistente['estado']); ?>
            </strong>
          </span>
        <?php else: ?>
          <button type="button" class="btn btn-sm text-white font-semibold px-3 py-2 rounded-lg inline-flex items-center gap-1.5 shadow-sm" style="background-color: var(--accent-gold, #D97706);" data-bs-toggle="modal" data-bs-target="#modalReclamo">
            <i data-lucide="edit-3" style="width: 15px; height: 15px;"></i>
            <span>Radicar Inconformidad</span>
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Si ya existe un reclamo en esta quincena -->
    <?php if ($alertaExistente): ?>
      <div class="mt-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
        <strong class="text-slate-900 block mb-1 flex items-center gap-1.5">
          <i data-lucide="info" class="text-blue-600" style="width: 14px; height: 14px;"></i>
          <span>Reclamo Radicado el <?php echo formatoFechaEspanol($alertaExistente['creadoEn']); ?>:</span>
        </strong>
        <p class="mb-1 text-slate-700 italic">"<?php echo e($alertaExistente['mensaje']); ?>"</p>
        <span class="text-slate-500">Litros en planilla al radicar: <?php echo formatoLitros($alertaExistente['litrosAlMomento']); ?></span>
      </div>

      <!-- Respuesta Oficial del Administrador -->
      <?php if (!empty($alertaExistente['respuestaAdmin'])): ?>
        <div class="mt-3 p-3.5 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-950">
          <div class="flex items-center justify-between mb-2">
            <strong class="flex items-center gap-1.5 text-xs text-emerald-900">
              <i data-lucide="check-circle" class="text-emerald-700" style="width: 16px; height: 16px;"></i>
              <span>Respuesta Oficial de la Administración:</span>
            </strong>
            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-200 text-emerald-900">
              <?php echo e($alertaExistente['estado']); ?>
            </span>
          </div>
          <p class="mb-2 text-xs text-slate-800 leading-relaxed"><?php echo nl2br(e($alertaExistente['respuestaAdmin'])); ?></p>
          <span class="text-xs text-slate-500 block">
            Atendido por: <strong><?php echo e($alertaExistente['respondidoPor'] ?: 'Administrador General'); ?></strong>
            <?php if (!empty($alertaExistente['respondidoEn'])): ?> &bull; <?php echo formatoFechaEspanol($alertaExistente['respondidoEn']); ?><?php endif; ?>
          </span>
        </div>
      <?php else: ?>
        <div class="mt-2 text-xs text-slate-500 flex items-center gap-1">
          <i data-lucide="clock" class="text-amber-500" style="width: 14px; height: 14px;"></i>
          <span>Tu reclamo está siendo verificado con la planilla física del acopio.</span>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($errorReclamo)): ?>
      <div class="alert alert-danger rounded-lg py-2 px-3 text-xs font-semibold mt-3 mb-0 flex items-center gap-1.5 border border-red-200">
        <i data-lucide="alert-circle" class="text-red-600" style="width: 14px; height: 14px;"></i>
        <span><?php echo e($errorReclamo); ?></span>
      </div>
    <?php endif; ?>
  </div>

</main>

<!-- MODAL RADICAR RECLAMO -->
<?php if (!$alertaExistente): ?>
  <div class="modal fade" id="modalReclamo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2xl border-0 shadow-xl overflow-hidden">
        <div class="modal-header text-white p-4" style="background-color: var(--brand-primary, #0A2E1C);">
          <h5 class="modal-title font-bold text-white text-base flex items-center gap-2">
            <i data-lucide="edit-3" class="text-amber-400" style="width: 18px; height: 18px;"></i>
            <span>Radicar Inconformidad Quincenal</span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <form action="panel.php?fechaInicio=<?php echo e($fechaInicioSel); ?>&fechaFin=<?php echo e($fechaFinSel); ?>" method="POST">
          <?php echo campoCsrf(); ?>
          <input type="hidden" name="accion_reclamo" value="1">

          <div class="modal-body p-4">
            <p class="text-slate-600 text-xs mb-3">
              Indica con claridad las fechas, turnos (AM o PM) o cantidad de litros que consideras que deben ser rectificados.
            </p>

            <div class="mb-3">
              <label for="mensaje_reclamo" class="form-label font-semibold text-xs text-slate-700">Detalle del Reclamo *</label>
              <textarea class="form-control text-sm" id="mensaje_reclamo" name="mensaje_reclamo" rows="4" required placeholder="Describe las diferencias observadas..."></textarea>
              <span class="text-xs text-slate-500 block mt-1">Mínimo 10 caracteres.</span>
            </div>
          </div>
          <div class="modal-footer p-3 bg-slate-50 border-t border-slate-200">
            <button type="button" class="btn btn-secondary rounded-lg px-3 text-xs" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn text-white rounded-lg px-4 font-semibold text-xs inline-flex items-center gap-1.5" style="background-color: var(--brand-primary, #0A2E1C);">
              <i data-lucide="send" style="width: 14px; height: 14px;"></i>
              <span>Enviar a Revisión</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- MODAL COMPROBANTE COLILLA DE PAGO -->
<div class="modal fade" id="modalColillaPago" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden">
      <div class="modal-header bg-slate-100 border-b border-slate-200 p-4">
        <h5 class="modal-title font-bold text-slate-900 text-base flex items-center gap-2">
          <i data-lucide="file-text" class="text-emerald-800" style="width: 18px; height: 18px;"></i>
          <span>Comprobante de Liquidación Quincenal</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-6" id="area-impresion-colilla">
        <!-- Encabezado de la Colilla -->
        <div class="border-b border-slate-200 pb-4 mb-4 flex justify-between items-start">
          <div>
            <h4 class="font-bold text-slate-900 mb-0"><?php echo e(EMPRESA_NOMBRE); ?></h4>
            <span class="text-xs text-slate-600 block">NIT: <?php echo e(EMPRESA_NIT); ?> &bull; Carmen de Carupa, Cundinamarca</span>
            <span class="text-xs text-slate-500 block">Centro de Acopio Vereda Alizal &bull; Tel: <?php echo e(EMPRESA_TELEFONO); ?></span>
          </div>
          <div class="text-end">
            <span class="px-2.5 py-1 rounded bg-slate-100 text-slate-800 font-mono text-xs font-bold block mb-1">COLILLA DE PAGO</span>
            <span class="text-xs text-slate-500"><?php echo date('d/m/Y H:i'); ?></span>
          </div>
        </div>

        <!-- Datos del Asociado y Período -->
        <div class="grid grid-cols-2 gap-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs mb-4">
          <div>
            <span class="text-slate-500 block">Asociado / Titular:</span>
            <strong class="text-slate-900 text-sm"><?php echo e($usuario['nombre']); ?></strong>
            <span class="text-slate-600 block font-mono">C.C. <?php echo e($usuario['cedula']); ?></span>
          </div>
          <div>
            <span class="text-slate-500 block">Predio / Finca:</span>
            <strong class="text-slate-900 text-sm"><?php echo e($usuario['finca'] ?? 'Vereda Alizal'); ?></strong>
            <span class="text-slate-600 block">Período: <?php echo formatoFechaEspanol($fechaInicioSel); ?> al <?php echo formatoFechaEspanol($fechaFinSel); ?></span>
          </div>
        </div>

        <!-- Resumen de Liquidación -->
        <div class="border border-slate-200 rounded-xl overflow-hidden mb-4 text-xs">
          <table class="w-full text-start">
            <thead class="bg-slate-100 border-b border-slate-200 text-slate-700">
              <tr>
                <th class="p-2.5 text-start font-semibold">Concepto</th>
                <th class="p-2.5 text-center font-semibold">Cantidad</th>
                <th class="p-2.5 text-end font-semibold">Valor Unitario</th>
                <th class="p-2.5 text-end font-semibold">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr>
                <td class="p-2.5">Leche Cruda Recibida (Turno AM)</td>
                <td class="p-2.5 text-center font-mono"><?php echo number_format($sumaM, 1, ',', '.'); ?> L</td>
                <td class="p-2.5 text-end font-mono">$<?php echo number_format($precioLitro, 0, ',', '.'); ?></td>
                <td class="p-2.5 text-end font-mono">$<?php echo number_format($sumaM * $precioLitro, 0, ',', '.'); ?></td>
              </tr>
              <tr>
                <td class="p-2.5">Leche Cruda Recibida (Turno PM)</td>
                <td class="p-2.5 text-center font-mono"><?php echo number_format($sumaT, 1, ',', '.'); ?> L</td>
                <td class="p-2.5 text-end font-mono">$<?php echo number_format($precioLitro, 0, ',', '.'); ?></td>
                <td class="p-2.5 text-end font-mono">$<?php echo number_format($sumaT * $precioLitro, 0, ',', '.'); ?></td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-50 border-t border-slate-200 font-bold">
              <tr>
                <td class="p-2.5 text-slate-900">TOTAL LIQUIDADO</td>
                <td class="p-2.5 text-center font-mono text-slate-900"><?php echo number_format($totalLitrosQuincena, 1, ',', '.'); ?> L</td>
                <td class="p-2.5 text-end text-slate-500 font-mono">$<?php echo number_format($precioLitro, 0, ',', '.'); ?> COP</td>
                <td class="p-2.5 text-end font-mono text-emerald-900 text-base">$<?php echo number_format($totalPagarEstimado, 0, ',', '.'); ?> COP</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="pt-8 mt-6 border-t border-slate-200 flex justify-between text-center text-xs text-slate-500">
          <div class="w-48 border-t border-slate-400 pt-1">
            <span>Firma Autorizada / Acopio</span>
          </div>
          <div class="w-48 border-t border-slate-400 pt-1">
            <span>Firma Asociado Productor</span>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-slate-50 border-t border-slate-200 p-3">
        <button type="button" class="btn btn-secondary rounded-lg px-3 text-xs" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success rounded-lg px-4 text-xs font-semibold inline-flex items-center gap-1.5" onclick="imprimirColillaDirecta()">
          <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
          <span>Imprimir Comprobante</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  function abrirModalColilla() {
    const modalEl = document.getElementById('modalColillaPago');
    if (modalEl) {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }
  }

  function imprimirColillaDirecta() {
    window.print();
  }

  document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide) {
      lucide.createIcons({ attrs: { 'stroke-width': 1.5 } });
    }

    // Inicialización de ApexCharts
    if (typeof ApexCharts !== 'undefined') {
      const options = {
        series: [
          {
            name: 'Turno AM (Mañana)',
            data: <?php echo json_encode($serieManana); ?>
          },
          {
            name: 'Turno PM (Tarde)',
            data: <?php echo json_encode($serieTarde); ?>
          }
        ],
        chart: {
          height: 280,
          type: 'area',
          fontFamily: 'Inter, sans-serif',
          toolbar: { show: false }
        },
        colors: ['#D97706', '#2563EB'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.05,
            stops: [0, 95, 100]
          }
        },
        xaxis: {
          categories: <?php echo json_encode($fechasGrafica); ?>,
          labels: { style: { fontSize: '11px', colors: '#64748B' } }
        },
        yaxis: {
          labels: {
            formatter: (val) => val.toFixed(1) + ' L',
            style: { fontSize: '11px', colors: '#64748B' }
          }
        },
        tooltip: {
          y: { formatter: (val) => val.toFixed(1) + ' Litros' }
        },
        legend: { show: false }
      };

      const chart = new ApexCharts(document.querySelector("#grafica-produccion"), options);
      chart.render();
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
