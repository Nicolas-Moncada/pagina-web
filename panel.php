<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: panel.php
 * Propósito: Portal del Ganadero Asociado (Dashboard Financiero estilo Central Ganadera):
 *            - Header del Asociado con datos de Finca, Cédula y Quincena Activa.
 *            - KPIs de Producción: Turno Mañana ☀️, Tarde 🌅, Litros y Pago Estimado en COP.
 *            - Planilla quincenal solo lectura de alto contraste con impresión de Colilla.
 *            - Módulo F-PQRS con estado corporativo y respuesta oficial de la administración.
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
  <div class="card border-0 shadow-sm rounded-4 p-4 text-white mb-4" style="background-color: var(--primary-emerald);">
    <div class="row align-items-center g-3">
      <div class="col-lg-8">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="badge rounded-pill fw-bold" style="background-color: var(--accent-gold); color: #fff;">
            <i class="fa-solid fa-cow me-1"></i> Productor Asociado
          </span>
          <span class="badge rounded-pill bg-white text-dark fw-bold">
            C.C. <?php echo e($usuario['cedula']); ?>
          </span>
          <span class="badge rounded-pill bg-light text-success fw-bold">
            <i class="fa-solid fa-circle-check me-1"></i> Cuenta Activa
          </span>
        </div>
        <h2 class="fw-bold mb-1 text-white" style="font-size: 1.85rem;">
          ¡Bienvenido, <?php echo e($usuario['nombre']); ?>!
        </h2>
        <p class="small text-light opacity-85 mb-0">
          <i class="fa-solid fa-house-chimney me-1 text-warning"></i> Predio: <strong><?php echo e($usuario['finca'] ?? 'Vereda Alizal'); ?></strong> &bull; 
          <i class="fa-solid fa-location-dot me-1 text-warning"></i> Sede: Carmen de Carupa
        </p>
      </div>

      <!-- Selector de Quincena Rápido -->
      <div class="col-lg-4 text-lg-end">
        <form action="panel.php" method="GET" class="d-inline-flex flex-column align-items-lg-end gap-1">
          <label for="filtro-quincena" class="small text-light opacity-90 fw-bold">Seleccionar Periodo Quincenal:</label>
          <select id="filtro-quincena" class="form-select form-select-sm fw-bold border-0 shadow-sm" style="min-width: 260px;" onchange="const p = this.value.split('|'); location.href='panel.php?fechaInicio=' + p[0] + '&fechaFin=' + p[1];">
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
       2. TARJETAS KPI DE PRODUCCIÓN DIARIA (MAÑANA / TARDE / TOTAL)
       ============================================================== -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="fa-solid fa-calendar-day" style="color: var(--accent-gold);"></i>
        <span>Entregas Registradas Hoy &ndash; <?php echo date('d/m/Y'); ?></span>
      </h4>
    </div>

    <!-- Turno Mañana ☀️ (Ámbar) -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white" style="border-top: 4px solid var(--accent-gold) !important;">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="badge-shift-m">
            <i class="fa-solid fa-sun me-1 text-warning"></i> Turno Mañana
          </span>
          <span class="small text-muted fw-bold">5:30 AM &ndash; 7:30 AM</span>
        </div>
        <div class="d-flex align-items-baseline gap-2 my-1">
          <span class="fs-1 fw-bold text-dark tabular-nums"><?php echo number_format($litrosMananaHoy, 1, ',', '.'); ?></span>
          <span class="fs-6 fw-bold text-muted">Litros</span>
        </div>
        <small class="text-muted d-block">
          <?php echo ($litrosMananaHoy > 0) ? '<i class="fa-solid fa-circle-check text-success me-1"></i> Registrado en tanque' : '<i class="fa-regular fa-clock me-1"></i> Pendiente de entrega'; ?>
        </small>
      </div>
    </div>

    <!-- Turno Tarde 🌅 (Azul) -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white" style="border-top: 4px solid var(--accent-dusk) !important;">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="badge-shift-t">
            <i class="fa-solid fa-moon me-1 text-primary"></i> Turno Tarde
          </span>
          <span class="small text-muted fw-bold">4:00 PM &ndash; 5:30 PM</span>
        </div>
        <div class="d-flex align-items-baseline gap-2 my-1">
          <span class="fs-1 fw-bold text-dark tabular-nums"><?php echo number_format($litrosTardeHoy, 1, ',', '.'); ?></span>
          <span class="fs-6 fw-bold text-muted">Litros</span>
        </div>
        <small class="text-muted d-block">
          <?php echo ($litrosTardeHoy > 0) ? '<i class="fa-solid fa-circle-check text-success me-1"></i> Registrado en tanque' : '<i class="fa-regular fa-clock me-1"></i> Pendiente de entrega'; ?>
        </small>
      </div>
    </div>

    <!-- Total del Día (Verde Esmeralda) -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white" style="border-top: 4px solid var(--secondary-green) !important;">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="badge rounded-pill fw-bold" style="background-color: #DCFCE7; color: var(--secondary-green);">
            <i class="fa-solid fa-droplet me-1"></i> Total Hoy
          </span>
          <span class="small text-success fw-bold">Suma de Turnos</span>
        </div>
        <div class="d-flex align-items-baseline gap-2 my-1">
          <span class="fs-1 fw-bold tabular-nums" style="color: var(--secondary-green);"><?php echo number_format($totalHoy, 1, ',', '.'); ?></span>
          <span class="fs-6 fw-bold text-muted">Litros</span>
        </div>
        <small class="text-muted d-block">
          Cadena de frío activa a 4°C
        </small>
      </div>
    </div>
  </div>

  <!-- ==============================================================
       3. LIQUIDACIÓN DE LA QUINCENA EN CURSO (DASHBOARD FINANCIERO)
       ============================================================== -->
  <div class="row g-3 mb-4">
    <!-- Litros Acumulados -->
    <div class="col-lg-3 col-sm-6">
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
        <span class="small text-uppercase fw-bold text-muted">Litros Quincena</span>
        <div class="fs-2 fw-bold text-dark tabular-nums my-1">
          <?php echo number_format($totalLitrosQuincena, 1, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">L</span>
        </div>
        <small class="text-muted">Total entregado en el periodo</small>
      </div>
    </div>

    <!-- Promedio Diario -->
    <div class="col-lg-3 col-sm-6">
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
        <span class="small text-uppercase fw-bold text-muted">Promedio Diario</span>
        <div class="fs-2 fw-bold text-dark tabular-nums my-1">
          <?php echo number_format($promedioDiario, 1, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">L / día</span>
        </div>
        <small class="text-muted"><?php echo $cantidadDiasConEntrega; ?> de <?php echo count($diasQuincena); ?> días con pesaje</small>
      </div>
    </div>

    <!-- Tarifa por Litro -->
    <div class="col-lg-3 col-sm-6">
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
        <span class="small text-uppercase fw-bold text-muted">Precio Oficial</span>
        <div class="fs-2 fw-bold tabular-nums my-1" style="color: var(--accent-gold);">
          $<?php echo number_format($precioLitro, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">COP</span>
        </div>
        <small class="text-muted"><?php echo $quincenaCerrada ? 'Tarifa Liquidada' : 'Tarifa Vigente'; ?></small>
      </div>
    </div>

    <!-- PAGO ESTIMADO EN COP (DESTACADO GRANDE) -->
    <div class="col-lg-3 col-sm-6">
      <div class="card border-0 shadow-sm rounded-4 p-3 text-white h-100" style="background: linear-gradient(135deg, var(--primary-emerald) 0%, #166534 100%);">
        <div class="d-flex align-items-center justify-content-between">
          <span class="small text-uppercase fw-bold text-warning">Pago Estimado</span>
          <i class="fa-solid fa-coins text-warning"></i>
        </div>
        <div class="fs-2 fw-bold text-white tabular-nums my-1">
          $<?php echo number_format($totalPagarEstimado, 0, ',', '.'); ?>
        </div>
        <small class="text-light opacity-75">
          <?php echo $quincenaCerrada ? 'Neto a recibir liquidado' : 'Sujeto a pesajes restantes'; ?>
        </small>
      </div>
    </div>
  </div>

  <!-- ==============================================================
       4. PLANILLA QUINCENAL DETALLADA (SOLO LECTURA MEJORADA)
       ============================================================== -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="p-3.5 p-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom" style="background-color: var(--bg-surface-alt);">
      <div>
        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="fa-solid fa-table-list" style="color: var(--secondary-green);"></i>
          <span>Planilla Quincenal de Pesajes Día a Día</span>
        </h5>
        <small class="text-muted">
          Periodo del <?php echo formatoFechaEspanol($fechaInicioSel); ?> al <?php echo formatoFechaEspanol($fechaFinSel); ?>
        </small>
      </div>

      <!-- Botones de Acción: Descargar / Imprimir Colilla -->
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-success fw-bold rounded-pill px-3 py-1.5 shadow-xs" onclick="window.print()">
          <i class="fa-solid fa-print me-1.5"></i> Imprimir Colilla de Pago
        </button>
      </div>
    </div>

    <div class="table-responsive p-3 p-md-4">
      <table id="tabla-ganadero-pesajes" class="table table-hover align-middle w-100">
        <thead class="table-light">
          <tr>
            <th class="fw-bold" style="background-color: var(--primary-emerald); color: #fff;">Fecha de Entrega</th>
            <th class="fw-bold" style="background-color: var(--primary-emerald); color: #fff;">Día</th>
            <th class="text-center fw-bold" style="background-color: var(--primary-emerald); color: #fff;">☀️ Turno Mañana</th>
            <th class="text-center fw-bold" style="background-color: var(--primary-emerald); color: #fff;">🌅 Turno Tarde</th>
            <th class="text-end fw-bold" style="background-color: var(--primary-emerald); color: #fff;">🥛 Total Diario</th>
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
                  <span class="badge bg-warning text-dark ms-1.5">HOY</span>
                <?php endif; ?>
              </td>
              <td class="text-muted"><?php echo e($diaObj['diaSemana']); ?></td>
              <td class="text-center tabular-nums">
                <?php if ($m > 0): ?>
                  <span class="badge-shift-m fs-6"><?php echo number_format($m, 1, ',', '.'); ?> L</span>
                <?php else: ?>
                  <span class="text-muted opacity-50">&ndash;</span>
                <?php endif; ?>
              </td>
              <td class="text-center tabular-nums">
                <?php if ($t > 0): ?>
                  <span class="badge-shift-t fs-6"><?php echo number_format($t, 1, ',', '.'); ?> L</span>
                <?php else: ?>
                  <span class="text-muted opacity-50">&ndash;</span>
                <?php endif; ?>
              </td>
              <td class="text-end fw-bold tabular-nums" style="color: var(--primary-emerald); font-size: 1.05rem;">
                <?php echo ($tot > 0) ? number_format($tot, 1, ',', '.') . ' L' : '<span class="text-muted opacity-50">&ndash;</span>'; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot style="background-color: var(--bg-surface-alt); border-top: 2px solid var(--border-subtle);">
          <tr>
            <th colspan="2" class="text-end fw-bold text-uppercase">Totales de la Quincena:</th>
            <th class="text-center fw-bold tabular-nums fs-6" style="color: var(--accent-gold);">
              <?php echo number_format($sumaM, 1, ',', '.'); ?> L
            </th>
            <th class="text-center fw-bold tabular-nums fs-6" style="color: var(--accent-dusk);">
              <?php echo number_format($sumaT, 1, ',', '.'); ?> L
            </th>
            <th class="text-end fw-bold tabular-nums fs-5" style="color: var(--primary-emerald);">
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
  <div class="card border-0 shadow-sm rounded-4 p-4 bg-white" id="reclamo">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div>
        <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
          <i class="fa-solid fa-triangle-exclamation text-warning"></i>
          <span>Buzón F-PQRS &bull; Inconformidades con Pesajes</span>
        </h5>
        <p class="small text-muted mb-0">
          Por política asociativa, puedes radicar <strong>un (1) reclamo formal</strong> por quincena para verificación de litros.
        </p>
      </div>

      <div>
        <?php if ($alertaExistente): ?>
          <span class="badge py-2 px-3 rounded-pill bg-light text-dark border fw-bold">
            Estado: <span class="text-primary"><?php echo e($alertaExistente['estado']); ?></span>
          </span>
        <?php else: ?>
          <button type="button" class="btn btn-sm text-white fw-bold px-3 py-2 rounded-pill shadow-xs" style="background-color: var(--accent-gold);" data-bs-toggle="modal" data-bs-target="#modalReclamo">
            <i class="fa-solid fa-pen-to-square me-1"></i> Radicar Inconformidad
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Si ya existe un reclamo en esta quincena -->
    <?php if ($alertaExistente): ?>
      <div class="mt-3 p-3 rounded-3 bg-light border small">
        <strong class="text-dark d-block mb-1">
          <i class="fa-solid fa-circle-info text-primary me-1"></i> Reclamo Radicado el <?php echo formatoFechaEspanol($alertaExistente['creadoEn']); ?>:
        </strong>
        <p class="mb-1 text-dark fst-italic">"<?php echo e($alertaExistente['mensaje']); ?>"</p>
        <span class="text-muted">Litros en planilla al radicar: <?php echo formatoLitros($alertaExistente['litrosAlMomento']); ?></span>
      </div>

      <!-- Respuesta Oficial del Administrador -->
      <?php if (!empty($alertaExistente['respuestaAdmin'])): ?>
        <div class="mt-3 p-3 rounded-3 border-2 border-success bg-success-subtle text-success-emphasis">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <strong class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-circle-check text-success fs-5"></i> Respuesta Oficial de la Administración:
            </strong>
            <span class="badge bg-success text-white px-2.5 py-1 rounded-pill fw-bold">
              <?php echo e($alertaExistente['estado']); ?>
            </span>
          </div>
          <p class="mb-2 text-dark" style="line-height: 1.6;"><?php echo nl2br(e($alertaExistente['respuestaAdmin'])); ?></p>
          <small class="text-muted d-block">
            Atendido por: <strong><?php echo e($alertaExistente['respondidoPor'] ?: 'Administrador General'); ?></strong>
            <?php if (!empty($alertaExistente['respondidoEn'])): ?> &bull; <?php echo formatoFechaEspanol($alertaExistente['respondidoEn']); ?><?php endif; ?>
          </small>
        </div>
      <?php else: ?>
        <div class="mt-2 small text-muted">
          <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> Tu reclamo está siendo verificado con la planilla física del acopio.
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($errorReclamo)): ?>
      <div class="alert alert-danger rounded-3 py-2 small fw-bold mt-3 mb-0">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo e($errorReclamo); ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<!-- MODAL RADICAR RECLAMO -->
<?php if (!$alertaExistente): ?>
  <div class="modal fade" id="modalReclamo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header text-white p-4" style="background-color: var(--primary-emerald);">
          <h5 class="modal-title fw-bold text-white">
            <i class="fa-solid fa-file-pen text-warning me-2"></i> Radicar Inconformidad Quincenal
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <form action="panel.php?fechaInicio=<?php echo e($fechaInicioSel); ?>&fechaFin=<?php echo e($fechaFinSel); ?>" method="POST">
          <?php echo campoCsrf(); ?>
          <input type="hidden" name="accion_reclamo" value="1">

          <div class="modal-body p-4">
            <p class="text-muted small mb-3">
              Indica con claridad las fechas, turnos (Mañana o Tarde) o cantidad de litros que consideras que deben ser rectificados.
            </p>

            <div class="mb-3">
              <label for="mensaje_reclamo" class="form-label fw-bold small text-dark">Detalle del Reclamo *</label>
              <textarea class="form-control" id="mensaje_reclamo" name="mensaje_reclamo" rows="4" required placeholder="Describe las diferencias observadas..."></textarea>
              <small class="text-muted d-block mt-1">Mínimo 10 caracteres.</small>
            </div>
          </div>
          <div class="modal-footer p-3 bg-light">
            <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn text-white rounded-pill px-4 fw-bold" style="background-color: var(--primary-emerald);">
              <i class="fa-solid fa-paper-plane me-1"></i> Enviar a Revisión
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
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
        },
        pageLength: 20,
        responsive: true,
        order: [[0, 'asc']]
      });
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
