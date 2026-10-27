<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: panel.php
 * Propósito: Portal del Ganadero Asociado (SSR con PDO, DataTables BS5, Reclamos 1xQ).
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

                // Enviar notificación al correo de la administración
                $adminEmail = EMPRESA_CORREO;
                $asunto = "⚠️ [Reclamo de Pesaje] " . $usuario['nombre'] . " - C.C. " . $usuario['cedula'];
                $cuerpo = "Se ha registrado una inconformidad quincenal:\n\n"
                        . "Ganadero: " . $usuario['nombre'] . "\n"
                        . "Cédula: " . $usuario['cedula'] . "\n"
                        . "Periodo: {$fechaInicioSel} al {$fechaFinSel}\n"
                        . "Litros al momento: " . formatoLitros($totalLitrosQuincena) . "\n\n"
                        . "Detalle del reclamo:\n{$mensajeReclamo}\n\n"
                        . "Fecha: " . date('Y-m-d H:i:s');
                @mail($adminEmail, $asunto, $cuerpo, "From: no-reply@asoagroalizal.local\r\n");

                setFlashAlert('success', '¡Inconformidad Registrada!', 'Tu reclamo ha sido enviado al administrador para su revisión.');
                header("Location: panel.php?fechaInicio={$fechaInicioSel}&fechaFin={$fechaFinSel}");
                exit;
            } catch (Exception $e) {
                error_log("Error guardando alerta: " . $e->getMessage());
                $errorReclamo = 'Ocurrió un error al registrar el reclamo. Por favor intenta más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal del Ganadero | <?php echo e(EMPRESA_SIGLA); ?></title>

  <!-- Script Anti-Parpadeo (Anti-FOUC) -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('asoagro_theme');
      const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const theme = savedTheme ? savedTheme : (systemDark ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', theme);
    })();
  </script>

  <!-- Favicon Oficial -->
  <link rel="icon" type="image/png" href="assets/img/logo.png">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  
  <!-- Bootstrap Icons 1.11.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- DataTables Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

  <!-- Estilos Globales de Marca AsoagroAlisal -->
  <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- ========================================================================
       CABECERA DEL PANEL DEL GANADERO
       ======================================================================== -->
  <header class="navbar navbar-expand-lg navbar-asoagro sticky-top py-2">
    <div class="container-fluid px-3 px-md-4">
      <div class="d-flex align-items-center gap-3">
        <a href="panel.php" class="d-flex align-items-center gap-2 text-decoration-none">
          <img src="assets/img/logo.png" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" class="brand-logo-img">
          <div class="d-none d-sm-block">
            <span class="fw-bold fs-6 text-success lh-1 d-block"><?php echo e(EMPRESA_SIGLA); ?></span>
            <small class="text-muted fw-semibold" style="font-size: 0.72rem;">Portal del Ganadero</small>
          </div>
        </a>
      </div>

      <!-- Información del Ganadero y Acciones -->
      <div class="d-flex align-items-center gap-2 gap-md-3">
        <!-- Badge de Identidad -->
        <div class="text-end d-none d-md-block">
          <span class="fw-bold d-block text-success"><?php echo e($usuario['nombre']); ?></span>
          <small class="text-muted font-monospace">C.C. <?php echo e($usuario['cedula']); ?> &bull; <?php echo e($usuario['finca'] ?? 'Vereda Alizal'); ?></small>
        </div>

        <!-- Toggle Modo Oscuro -->
        <button type="button" class="btn btn-touch btn-outline-success border-2 rounded-circle p-0 theme-toggle-btn" style="width: 42px; height: 42px;" aria-label="Alternar modo oscuro">
          <i class="bi bi-moon-stars-fill fs-5"></i>
        </button>

        <!-- Cambiar Contraseña -->
        <a href="cambiar_password.php" class="btn btn-touch btn-outline-secondary border-2 rounded-pill px-3 py-1 small" title="Cambiar mi clave">
          <i class="bi bi-key-fill text-warning"></i>
          <span class="d-none d-lg-inline">Clave</span>
        </a>

        <!-- Ver Sitio Público -->
        <a href="index.php" class="btn btn-touch btn-outline-success border-2 rounded-pill px-3 py-1 small d-none d-sm-inline-flex" title="Ir a la página principal">
          <i class="bi bi-globe"></i>
          <span class="d-none d-lg-inline">Sitio</span>
        </a>

        <!-- Cerrar Sesión -->
        <a href="logout.php" class="btn btn-touch btn-danger rounded-pill px-3 py-1 small fw-bold">
          <i class="bi bi-box-arrow-right"></i>
          <span class="d-none d-sm-inline">Salir</span>
        </a>
      </div>
    </div>
  </header>

  <!-- ========================================================================
       CONTENIDO PRINCIPAL
       ======================================================================== -->
  <main class="container-fluid px-3 px-md-4 py-4 flex-grow-1">

    <!-- BIENVENIDA Y ALERTA SI DEBE CAMBIAR CLAVE -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card card-rural p-3 p-md-4 bg-success text-white border-0 shadow position-relative overflow-hidden" style="background: linear-gradient(135deg, #144722 0%, #2e7d32 100%);">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
              <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">Asociado Oficial</span>
              <h2 class="display-6 fw-bold mb-1">¡Hola, <?php echo e($usuario['nombre']); ?>!</h2>
              <p class="mb-0 opacity-90">
                <i class="bi bi-geo-alt-fill text-warning me-1"></i> Finca: <strong><?php echo e($usuario['finca'] ?? 'Vereda Alizal'); ?></strong> &bull; Cédula: <strong><?php echo e($usuario['cedula']); ?></strong>
              </p>
            </div>
            <div class="text-md-end">
              <span class="badge bg-white text-dark px-3 py-2 rounded-4 fw-bold shadow-sm">
                <i class="bi bi-calendar3 me-1 text-success"></i> <?php echo formatoFechaEspanol($hoyStr); ?>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TARJETAS DE PESAJE DE HOY (MAÑANA / TARDE / TOTAL DÍA) -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <h4 class="fw-bold text-success mb-2">
          <i class="bi bi-sun-fill text-warning me-1"></i> Pesaje de Hoy (<?php echo date('d/m/Y'); ?>)
        </h4>
      </div>

      <!-- Turno Mañana -->
      <div class="col-md-4">
        <div class="card card-rural p-4 border-start border-4 border-warning h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge-turno-manana">
              <i class="bi bi-brightness-high-fill"></i> Turno Mañana
            </span>
            <small class="text-muted fw-bold">Ordeño 5:00 AM</small>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="display-5 fw-extrabold text-warning-emphasis"><?php echo number_format($litrosMananaHoy, 1, ',', '.'); ?></span>
            <span class="fs-5 fw-bold text-muted">Litros</span>
          </div>
          <small class="text-muted mt-2 d-block">Registrado en centro de acopio</small>
        </div>
      </div>

      <!-- Turno Tarde -->
      <div class="col-md-4">
        <div class="card card-rural p-4 border-start border-4 border-danger h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge-turno-tarde">
              <i class="bi bi-sunset-fill"></i> Turno Tarde
            </span>
            <small class="text-muted fw-bold">Ordeño 3:00 PM</small>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="display-5 fw-extrabold text-danger-emphasis"><?php echo number_format($litrosTardeHoy, 1, ',', '.'); ?></span>
            <span class="fs-5 fw-bold text-muted">Litros</span>
          </div>
          <small class="text-muted mt-2 d-block">Registrado en centro de acopio</small>
        </div>
      </div>

      <!-- Total del Día -->
      <div class="col-md-4">
        <div class="card card-rural p-4 border-start border-4 border-success h-100" style="background: var(--aso-secondary-light);">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-success text-white px-3 py-1 rounded-pill fw-bold">
              <i class="bi bi-droplet-fill me-1"></i> Total Entregado Hoy
            </span>
            <small class="text-success fw-bold">Suma del Día</small>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="display-5 fw-extrabold text-success"><?php echo number_format($totalHoy, 1, ',', '.'); ?></span>
            <span class="fs-5 fw-bold text-success">Litros</span>
          </div>
          <small class="text-muted mt-2 d-block">Cadena de frío activa a 4.0 °C</small>
        </div>
      </div>
    </div>

    <!-- TARJETAS RESUMEN DE LA QUINCENA -->
    <div class="row g-3 mb-4">
      <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h4 class="fw-bold text-success mb-0">
            <i class="bi bi-bar-chart-line-fill me-1"></i> Resumen de la Quincena Seleccionada
          </h4>
          <small class="text-muted">Periodo: <strong><?php echo formatoFechaEspanol($fechaInicioSel); ?></strong> al <strong><?php echo formatoFechaEspanol($fechaFinSel); ?></strong></small>
        </div>

        <!-- Selector de Quincena -->
        <form action="panel.php" method="GET" class="d-flex align-items-center gap-2">
          <label for="filtro-quincena" class="fw-bold small text-muted text-nowrap">Periodo:</label>
          <select id="filtro-quincena" class="form-select form-select-touch form-select-sm" onchange="const p = this.value.split('|'); location.href='panel.php?fechaInicio=' + p[0] + '&fechaFin=' + p[1];">
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

      <!-- Tarjeta 1: Total Litros Quincena -->
      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 p-md-4 text-center">
          <small class="text-muted fw-bold text-uppercase">Litros Acumulados</small>
          <div class="display-6 fw-extrabold text-success my-2">
            <?php echo number_format($totalLitrosQuincena, 1, ',', '.'); ?>
          </div>
          <small class="text-muted">Suma de tus entregas</small>
        </div>
      </div>

      <!-- Tarjeta 2: Promedio Diario -->
      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 p-md-4 text-center">
          <small class="text-muted fw-bold text-uppercase">Promedio Diario</small>
          <div class="display-6 fw-extrabold text-primary my-2">
            <?php echo number_format($promedioDiario, 1, ',', '.'); ?>
          </div>
          <small class="text-muted">Litros/día con entrega</small>
        </div>
      </div>

      <!-- Tarjeta 3: Precio Oficial por Litro -->
      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 p-md-4 text-center">
          <small class="text-muted fw-bold text-uppercase">Precio Oficial Litro</small>
          <div class="display-6 fw-extrabold text-warning-emphasis my-2">
            <?php echo $precioLitro > 0 ? formatoMonedaCOP($precioLitro) : '<span class="fs-5 text-muted">Por Fijar</span>'; ?>
          </div>
          <small class="text-muted"><?php echo $quincenaCerrada ? 'Tarifa Liquidada' : 'Tarifa Vigente'; ?></small>
        </div>
      </div>

      <!-- Tarjeta 4: Pago Estimado -->
      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 p-md-4 text-center border-2 border-success">
          <small class="text-muted fw-bold text-uppercase">Pago Estimado</small>
          <div class="display-6 fw-extrabold text-success my-2">
            <?php echo formatoMonedaCOP($totalPagarEstimado); ?>
          </div>
          <small class="text-muted"><?php echo $quincenaCerrada ? 'Valor Final Liquidado' : 'Sujeto al cierre quincenal'; ?></small>
        </div>
      </div>
    </div>

    <!-- SECCIÓN: TABLA DETALLADA DE PESAJES DÍA A DÍA (DATATABLES BS5 EN ESPAÑOL) -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card card-rural border-0 shadow overflow-hidden">
          <div class="card-header bg-success text-white p-3 p-md-4 d-flex justify-content-between align-items-center">
            <div>
              <h5 class="fw-bold mb-0">
                <i class="bi bi-calendar2-range me-2"></i> Planilla Detallada de Pesajes de la Quincena
              </h5>
              <small class="opacity-75">Tus pesajes registrados día a día en la Finca El Recuerdo</small>
            </div>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
              <?php echo count($entregasQuincena); ?> Registros en el Periodo
            </span>
          </div>

          <div class="card-body p-3 p-md-4">
            <?php if (empty($diasQuincena)): ?>
              <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h5 class="fw-bold mt-3">No hay fechas configuradas para esta quincena</h5>
                <p class="text-muted">Selecciona otro periodo en el menú superior.</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table id="tabla-ganadero-pesajes" class="table table-rural table-hover align-middle w-100">
                  <thead>
                    <tr>
                      <th>Fecha de Entrega</th>
                      <th>Día</th>
                      <th class="text-center">☀️ Turno Mañana</th>
                      <th class="text-center">🌅 Turno Tarde</th>
                      <th class="text-end">🥛 Total del Día</th>
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
                        <td>
                          <strong><?php echo formatoFechaEspanol($fStr); ?></strong>
                          <?php if ($esHoy): ?>
                            <span class="badge bg-warning text-dark ms-2">HOY</span>
                          <?php endif; ?>
                        </td>
                        <td><?php echo e($diaObj['diaSemana']); ?></td>
                        <td class="text-center">
                          <?php if ($mVal > 0): ?>
                            <span class="badge-turno-manana fs-6"><?php echo number_format($mVal, 1, ',', '.'); ?> L</span>
                          <?php else: ?>
                            <span class="text-muted opacity-50">-</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-center">
                          <?php if ($tVal > 0): ?>
                            <span class="badge-turno-tarde fs-6"><?php echo number_format($tVal, 1, ',', '.'); ?> L</span>
                          <?php else: ?>
                            <span class="text-muted opacity-50">-</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end fw-extrabold text-success fs-6">
                          <?php echo ($diaTotal > 0) ? number_format($diaTotal, 1, ',', '.') . ' L' : '<span class="text-muted opacity-50">-</span>'; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th colspan="2" class="text-end text-uppercase">Totales de la Quincena:</th>
                      <th class="text-center text-warning-emphasis fs-5">
                        <?php echo number_format($sumaTotalMañana, 1, ',', '.'); ?> L
                      </th>
                      <th class="text-center text-danger-emphasis fs-5">
                        <?php echo number_format($sumaTotalTarde, 1, ',', '.'); ?> L
                      </th>
                      <th class="text-end text-success fs-4">
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
        <div class="card card-rural p-4 border-2 border-warning shadow-sm">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
              <h5 class="fw-bold text-success mb-1">
                <i class="bi bi-shield-exclamation text-warning me-2"></i> ¿Tienes alguna inconformidad con tus litros en este periodo?
              </h5>
              <small class="text-muted">
                Por políticas de control lechero, solo se permite registrar <strong>UNA (1) inconformidad formal</strong> por cada quincena.
              </small>
            </div>

            <div>
              <?php if ($alertaExistente): ?>
                <div class="alert alert-info rounded-4 py-2 px-3 mb-0 small fw-bold">
                  <i class="bi bi-clock-history me-1"></i> Inconformidad radicada el <?php echo formatoFechaEspanol($alertaExistente['creadoEn']); ?>. 
                  Estado: <span class="badge bg-primary"><?php echo e($alertaExistente['estado']); ?></span>
                </div>
              <?php else: ?>
                <button type="button" class="btn btn-touch btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalReclamo">
                  <i class="bi bi-chat-left-dots-fill me-1"></i> Radicar Inconformidad
                </button>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($alertaExistente): ?>
            <div class="mt-3 p-3 bg-body-tertiary rounded-3 border small">
              <strong class="text-success d-block mb-1">Detalle del reclamo presentado:</strong>
              <p class="mb-0 text-muted fst-italic">"<?php echo e($alertaExistente['mensaje']); ?>"</p>
              <small class="opacity-75 mt-1 d-block">Litros registrados al momento del reclamo: <?php echo formatoLitros($alertaExistente['litrosAlMomento']); ?></small>
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
          <div class="modal-header bg-success text-white p-4">
            <h5 class="modal-title fw-bold" id="modalReclamoLabel">
              <i class="bi bi-shield-exclamation text-warning me-2"></i> Radicar Inconformidad Quincenal
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <form action="panel.php?fechaInicio=<?php echo e($fechaInicioSel); ?>&fechaFin=<?php echo e($fechaFinSel); ?>" method="POST">
            <?php echo campoCsrf(); ?>
            <input type="hidden" name="accion_reclamo" value="1">

            <div class="modal-body p-4">
              <p class="small text-muted mb-3">
                Escribe detalladamente las fechas, turnos o diferencias que consideres que deben ser revisadas por el administrador de la asociación.
              </p>

              <div class="mb-3">
                <label for="mensaje_reclamo" class="form-label fw-bold small text-muted">Detalle de la Inconformidad *</label>
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

  <!-- FOOTER -->
  <footer class="py-3 text-center text-muted small mt-auto border-top">
    <div class="container-fluid">
      <?php echo e(EMPRESA_SIGLA); ?> &bull; Control Lechero Campesino &bull; Carmen de Carupa, Cundinamarca &copy; <?php echo date('Y'); ?>
    </div>
  </footer>

  <!-- Scripts: jQuery + Bootstrap Bundle + DataTables + SweetAlert2 + App.js -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  
  <!-- DataTables JS & Plugins -->
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/app.js"></script>

  <script>
    $(document).ready(function() {
      // Inicializar DataTables con idioma en español y botones de exportación
      $('#tabla-ganadero-pesajes').DataTable({
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
        },
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
    });
  </script>

  <!-- SweetAlert2 Flash Messages -->
  <?php echo renderSweetAlert(); ?>

</body>
</html>
