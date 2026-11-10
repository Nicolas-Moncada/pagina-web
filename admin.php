<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: admin.php
 * Propósito: Panel de Administración General con Planilla Matricial Horizontal tipo Excel.
 * Requisitos: Planilla Matricial con edición inline, navegación por teclado, pegado Excel,
 *             totales reactivos en servidor, DataTables solo en listas (asociados/quincenas/buzón).
 * Fuente Oficial: docs/FICHA_OFICIAL_ASOAGROALIZAL.md
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/api/auth_middleware.php';

// 1. CONTROL DE ACCESO ESTRICTO (ROL ADMINISTRADOR VERIFICADO POR MIDDLEWARE)
$usuario = requireAdminAuth(false);
$pdo = getDB();

// 2. DETECCIÓN Y PARÁMETROS DE QUINCENA
$quincenaActual = calcularQuincenaActual();
$fechaInicioSel = $_GET['fechaInicio'] ?? $quincenaActual['fechaInicio'];
$fechaFinSel    = $_GET['fechaFin'] ?? $quincenaActual['fechaFin'];
$pestañaActiva  = $_GET['tab'] ?? 'planilla';

// Validar fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicioSel) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinSel)) {
    $fechaInicioSel = $quincenaActual['fechaInicio'];
    $fechaFinSel    = $quincenaActual['fechaFin'];
}

// Quincenas anterior y siguiente para navegación rápida
$qAnt = calcularQuincenaVecina($fechaInicioSel, $fechaFinSel, -1);
$qSig = calcularQuincenaVecina($fechaInicioSel, $fechaFinSel, 1);

// 3. PROCESAMIENTO DE ACCIONES VÍA POST CON PROTECCIÓN CSRF (Para formularios tradicionales)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecibido = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($tokenRecibido)) {
        setFlashAlert('error', 'Error de Seguridad', 'El token CSRF ha vencido. Por favor recarga la página.');
        header("Location: admin.php?tab={$pestañaActiva}&fechaInicio={$fechaInicioSel}&fechaFin={$fechaFinSel}");
        exit;
    }

    $accion = $_POST['accion'] ?? '';

    // ACCIÓN: REGISTRO RÁPIDO TRADICIONAL DE PESAJE
    if ($accion === 'guardar_pesaje_rapido') {
        $ganaderoId = trim($_POST['usuarioId'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        $turno = trim($_POST['turno'] ?? '');
        $litros = (float)str_replace(',', '.', trim($_POST['litros'] ?? '0'));

        if (empty($ganaderoId) || empty($fecha) || empty($turno) || $litros < 0) {
            setFlashAlert('warning', 'Campos Incompletos', 'Verifica ganadero, fecha, turno y litros.');
        } elseif (!in_array($turno, ['MANANA', 'TARDE'])) {
            setFlashAlert('error', 'Turno Inválido', 'El turno debe ser Mañana o Tarde.');
        } else {
            try {
                $entregaId = generarUUID('ent');
                $stmt = $pdo->prepare("INSERT INTO entrega (id, usuarioId, fecha, turno, litros, creadoEn)
                                       VALUES (?, ?, ?, ?, ?, NOW())
                                       ON DUPLICATE KEY UPDATE litros = VALUES(litros)");
                $stmt->execute([$entregaId, $ganaderoId, $fecha, $turno, $litros]);

                $stmtR = $pdo->prepare("INSERT INTO registros_leche (id, asociadoId, fecha, turno, litros, creadoEn, actualizadoEn)
                                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                                        ON DUPLICATE KEY UPDATE litros = VALUES(litros), actualizadoEn = NOW()");
                $stmtR->execute([$entregaId, $ganaderoId, $fecha, $turno, $litros]);

                setFlashAlert('success', '¡Pesaje Guardado!', "Se registraron " . formatoLitros($litros) . " exitosamente.");
            } catch (Exception $e) {
                error_log("Error guardando pesaje: " . $e->getMessage());
                setFlashAlert('error', 'Error en BD', 'No fue posible guardar el pesaje.');
            }
        }
        header("Location: admin.php?tab=planilla&fechaInicio={$fechaInicioSel}&fechaFin={$fechaFinSel}");
        exit;
    }

    // ACCIÓN: CREAR O EDITAR ASOCIADO
    if ($accion === 'guardar_ganadero') {
        $asocId   = trim($_POST['asociadoId'] ?? '');
        $cedula   = normalizarCedula($_POST['cedula'] ?? '');
        $nombre   = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $finca    = trim($_POST['finca'] ?? 'Vereda Alizal');
        $password = trim($_POST['password'] ?? '');
        $activo   = isset($_POST['activo']) ? 1 : 0;

        if (empty($cedula) || empty($nombre)) {
            setFlashAlert('warning', 'Campos Requeridos', 'La cédula y el nombre completo son obligatorios.');
        } else {
            try {
                if (!empty($asocId)) {
                    $chk = $pdo->prepare("SELECT id FROM usuario WHERE cedula = ? AND id != ? LIMIT 1");
                    $chk->execute([$cedula, $asocId]);
                    if ($chk->fetch()) {
                        setFlashAlert('error', 'Cédula Duplicada', 'Ya existe otro ganadero con este número de cédula.');
                    } else {
                        if (!empty($password)) {
                            $hash = hashPassword($password);
                            $upd = $pdo->prepare("UPDATE usuario SET cedula = ?, nombre = ?, telefono = ?, finca = ?, passwordHash = ?, activo = ?, sessionVersion = sessionVersion + 1 WHERE id = ?");
                            $upd->execute([$cedula, $nombre, $telefono, $finca, $hash, $activo, $asocId]);
                        } else {
                            $upd = $pdo->prepare("UPDATE usuario SET cedula = ?, nombre = ?, telefono = ?, finca = ?, activo = ? WHERE id = ?");
                            $upd->execute([$cedula, $nombre, $telefono, $finca, $activo, $asocId]);
                        }
                        setFlashAlert('success', 'Asociado Actualizado', 'Los datos del ganadero se actualizaron con éxito.');
                    }
                } else {
                    $chk = $pdo->prepare("SELECT id FROM usuario WHERE cedula = ? LIMIT 1");
                    $chk->execute([$cedula]);
                    if ($chk->fetch()) {
                        setFlashAlert('error', 'Cédula Registrada', 'Ya existe un usuario con este número de cédula.');
                    } else {
                        $claveAsignar = !empty($password) ? $password : '123456';
                        $hash = hashPassword($claveAsignar);
                        $newId = generarUUID('usr');

                        $ins = $pdo->prepare("INSERT INTO usuario (id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, sessionVersion, creadoEn)
                                              VALUES (?, ?, ?, ?, ?, ?, 'GANADERO', ?, 1, 1, NOW())");
                        $ins->execute([$newId, $cedula, $nombre, $telefono, $finca, $hash, $activo]);
                        setFlashAlert('success', '¡Asociado Creado!', "Ganadero registrado correctamente con cifrado Argon2id. Clave temporal asignada: {$claveAsignar}");
                    }
                }
            } catch (Exception $e) {
                error_log("Error gestionando ganadero: " . $e->getMessage());
                setFlashAlert('error', 'Error en BD', 'No se pudo guardar la información del asociado.');
            }
        }
        header("Location: admin.php?tab=ganaderos");
        exit;
    }

    // ACCIÓN: REESTABLECER CLAVE A TEMPORAL
    if ($accion === 'reset_clave_ganadero') {
        $asocId = trim($_POST['asociadoId'] ?? '');
        if (!empty($asocId)) {
            try {
                $claveTemp = '123456';
                $hash = hashPassword($claveTemp);
                $upd = $pdo->prepare("UPDATE usuario SET passwordHash = ?, debeCambiarPassword = 1, sessionVersion = sessionVersion + 1 WHERE id = ?");
                $upd->execute([$hash, $asocId]);
                setFlashAlert('success', 'Clave Reestablecida', "Se asignó la clave temporal '{$claveTemp}' con cambio obligatorio en su próximo acceso.");
            } catch (Exception $e) {
                setFlashAlert('error', 'Error', 'No se pudo reestablecer la clave.');
            }
        }
        header("Location: admin.php?tab=ganaderos");
        exit;
    }

    // ACCIÓN: FIJAR O ACTUALIZAR PRECIO DE QUINCENA
    if ($accion === 'fijar_precio_quincena') {
        $fInicio = trim($_POST['fechaInicio'] ?? '');
        $fFin    = trim($_POST['fechaFin'] ?? '');
        $precio  = (float)str_replace(',', '.', trim($_POST['precioLitro'] ?? '0'));
        $cerrada = isset($_POST['cerrada']) ? 1 : 0;

        if (empty($fInicio) || empty($fFin) || $precio < 0) {
            setFlashAlert('warning', 'Datos Inválidos', 'Rango de fechas y precio mayor o igual a cero requeridos.');
        } else {
            try {
                $qId = generarUUID('q');
                $stmtQIns = $pdo->prepare("INSERT INTO quincena (id, fechaInicio, fechaFin, precioLitro, cerrada, creadaEn)
                                           VALUES (?, ?, ?, ?, ?, NOW())
                                           ON DUPLICATE KEY UPDATE precioLitro = VALUES(precioLitro), cerrada = VALUES(cerrada)");
                $stmtQIns->execute([$qId, $fInicio, $fFin, $precio, $cerrada]);
                setFlashAlert('success', 'Precio Guardado', "Tarifa de " . formatoMonedaCOP($precio) . " fijada correctamente.");
            } catch (Exception $e) {
                setFlashAlert('error', 'Error', 'No se pudo actualizar el precio de la quincena.');
            }
        }
        header("Location: admin.php?tab=quincenas&fechaInicio={$fInicio}&fechaFin={$fFin}");
        exit;
    }

    // ACCIÓN: ATENDER RECLAMO
    if ($accion === 'atender_reclamo') {
        $alertaId = trim($_POST['alertaId'] ?? '');
        $nuevoEstado = trim($_POST['estado'] ?? 'REVISADO');
        if (!empty($alertaId)) {
            $pdo->prepare("UPDATE alertaquincena SET estado = ?, atendida = 1 WHERE id = ?")->execute([$nuevoEstado, $alertaId]);
            setFlashAlert('success', 'Estado Actualizado', "La inconformidad ha pasado al estado: {$nuevoEstado}");
        }
        header("Location: admin.php?tab=buzon");
        exit;
    }

    // ACCIÓN: ATENDER CONSULTA
    if ($accion === 'atender_consulta') {
        $conId = trim($_POST['consultaId'] ?? '');
        if (!empty($conId)) {
            $pdo->prepare("UPDATE consulta SET atendida = 1 WHERE id = ?")->execute([$conId]);
            setFlashAlert('success', 'Consulta Atendida', 'La consulta pública se marcó como atendida.');
        }
        header("Location: admin.php?tab=buzon");
        exit;
    }
}

// 4. DATOS GENERALES DE LA QUINCENA SELECCIONADA
$stmtQAct = $pdo->prepare("SELECT precioLitro, cerrada FROM quincena WHERE fechaInicio = ? AND fechaFin = ? LIMIT 1");
$stmtQAct->execute([$fechaInicioSel, $fechaFinSel]);
$quincenaSelData = $stmtQAct->fetch();
$precioLitroActual = $quincenaSelData ? (float)$quincenaSelData['precioLitro'] : 0.0;
$quincenaEstaCerrada = !empty($quincenaSelData['cerrada']);

// Lista de quincenas históricas para selector
$stmtQAll = $pdo->query("SELECT fechaInicio, fechaFin, precioLitro, cerrada FROM quincena ORDER BY fechaInicio DESC LIMIT 36");
$quincenasDisponibles = $stmtQAll->fetchAll();

// Si la quincena seleccionada no está en la BD, incorporarla a la lista en memoria
$encontrada = false;
foreach ($quincenasDisponibles as $qItem) {
    if ($qItem['fechaInicio'] === $fechaInicioSel && $qItem['fechaFin'] === $fechaFinSel) {
        $encontrada = true;
        break;
    }
}
if (!$encontrada) {
    array_unshift($quincenasDisponibles, [
        'fechaInicio' => $fechaInicioSel,
        'fechaFin'    => $fechaFinSel,
        'precioLitro' => $precioLitroActual,
        'cerrada'     => $quincenaEstaCerrada ? 1 : 0
    ]);
}

// 5. CARGA DE ASOCIADOS ACTIVOS Y DÍAS PARA LA PLANILLA MATRICIAL
$ganaderosPlanilla = $pdo->query("SELECT id, cedula, nombre, finca, activo FROM usuario WHERE rol = 'GANADERO' AND activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$totalGanaderosActivos = count($ganaderosPlanilla);

// Todos los ganaderos para la pestaña de gestión
$listaTodosGanaderos = $pdo->query("SELECT id, cedula, nombre, telefono, finca, activo, creadoEn FROM usuario WHERE rol = 'GANADERO' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Días que componen la quincena
$diasQuincena = generarDiasQuincena($fechaInicioSel, $fechaFinSel);

// 6. CARGAR PEMAJES DE LA QUINCENA Y CONSTRUIR MAPA MATRICIAL
$stmtEntQ = $pdo->prepare("SELECT usuarioId, fecha, turno, litros FROM entrega WHERE fecha >= ? AND fecha <= ?");
$stmtEntQ->execute([$fechaInicioSel, $fechaFinSel]);

$mapaMatriz = [];
$totalesPorGanadero = [];
$totalesPorColumna = [];
$granTotalMatriz = 0.0;

while ($r = $stmtEntQ->fetch()) {
    $uId   = $r['usuarioId'];
    $fecha = $r['fecha'];
    $turno = $r['turno'];
    $litros = (float)$r['litros'];

    $mapaMatriz[$uId]["{$fecha}_{$turno}"] = $litros;
    $totalesPorGanadero[$uId] = ($totalesPorGanadero[$uId] ?? 0.0) + $litros;
    $totalesPorColumna["{$fecha}_{$turno}"] = ($totalesPorColumna["{$fecha}_{$turno}"] ?? 0.0) + $litros;
    $granTotalMatriz += $litros;
}

$totalLiquidacionGeneral = $precioLitroActual ? ($granTotalMatriz * $precioLitroActual) : 0.0;
$cantDias = count($diasQuincena);
$promedioDiarioGeneral = ($cantDias > 0) ? ($granTotalMatriz / $cantDias) : 0.0;

// CÁLCULO DE KPIS AVANZADOS EN TIEMPO REAL
$fechaHoy = date('Y-m-d');
$fechaAyer = date('Y-m-d', strtotime('-1 day'));

$litrosHoyM = (float)($totalesPorColumna["{$fechaHoy}_MANANA"] ?? 0.0);
$litrosHoyT = (float)($totalesPorColumna["{$fechaHoy}_TARDE"] ?? 0.0);
$litrosHoyTotal = $litrosHoyM + $litrosHoyT;

$litrosAyerM = (float)($totalesPorColumna["{$fechaAyer}_MANANA"] ?? 0.0);
$litrosAyerT = (float)($totalesPorColumna["{$fechaAyer}_TARDE"] ?? 0.0);
$litrosAyerTotal = $litrosAyerM + $litrosAyerT;
$variacionAyer = ($litrosAyerTotal > 0) ? round((($litrosHoyTotal - $litrosAyerTotal) / $litrosAyerTotal) * 100, 1) : 0.0;

// Ganaderos que han entregado hoy vs pendientes de entrega
$ganaderosEntregaronHoy = 0;
foreach ($ganaderosPlanilla as $g) {
    if (isset($mapaMatriz[$g['id']]["{$fechaHoy}_MANANA"]) || isset($mapaMatriz[$g['id']]["{$fechaHoy}_TARDE"])) {
        $ganaderosEntregaronHoy++;
    }
}
$ganaderosPendientesHoy = max(0, $totalGanaderosActivos - $ganaderosEntregaronHoy);

// 7. CONSULTAS PARA BUZÓN (Con soporte de respuestas oficiales)
$stmtAlertas = $pdo->query("SELECT a.id, a.usuarioId, a.fechaInicio, a.fechaFin, a.mensaje, a.litrosAlMomento, a.estado, a.respuestaAdmin, a.respondidoEn, a.respondidoPor, a.creadoEn,
                                   u.nombre AS ganaderoNombre, u.cedula AS ganaderoCedula
                            FROM alertaquincena a
                            JOIN usuario u ON a.usuarioId = u.id
                            ORDER BY a.creadoEn DESC");
$listaAlertas = $stmtAlertas->fetchAll();

$stmtConsultas = $pdo->query("SELECT id, nombre, contacto, mensaje, fecha, atendida FROM consulta ORDER BY fecha DESC LIMIT 50");
$listaConsultas = $stmtConsultas->fetchAll();

// Configuración de header compartido
$tituloPagina = 'Panel de Administración | ' . EMPRESA_SIGLA;
$usarDataTables = true; // Solo para listas tipo tabla (ganaderos, precios, buzon)
$navbarFluido = true;

require_once __DIR__ . '/includes/header.php';
?>

<!-- BARRA DE PESTAÑAS PRINCIPALES (MÓDULOS DEL ADMINISTRADOR) -->
<div class="tabbar-premium bg-white border-bottom py-2.5 shadow-xs">
  <div class="container-fluid px-3 px-md-4">
    <ul class="nav nav-pills gap-2 flex-wrap" id="pills-tab" role="tablist">
      <li class="nav-item">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'planilla') ? 'active text-white' : ''; ?> fw-bold" href="admin.php?tab=planilla&fechaInicio=<?php echo $fechaInicioSel; ?>&fechaFin=<?php echo $fechaFinSel; ?>">
          <i class="fa-solid fa-table me-1.5"></i> Planilla Matricial de Pesajes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'ganaderos') ? 'active text-white' : ''; ?> fw-bold" href="admin.php?tab=ganaderos">
          <i class="fa-solid fa-users me-1.5"></i> Gestión de Asociados (<?php echo count($listaTodosGanaderos); ?>)
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'quincenas') ? 'active text-white' : ''; ?> fw-bold" href="admin.php?tab=quincenas&fechaInicio=<?php echo $fechaInicioSel; ?>&fechaFin=<?php echo $fechaFinSel; ?>">
          <i class="fa-solid fa-calendar-check me-1.5"></i> Precios y Quincenas
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'buzon') ? 'active text-white' : ''; ?> fw-bold position-relative" href="admin.php?tab=buzon">
          <i class="fa-solid fa-envelope-open-text me-1.5"></i> Buzón F-PQRS
          <?php if (count($listaAlertas) > 0): ?>
            <span class="badge rounded-pill bg-danger ms-1"><?php echo count($listaAlertas); ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link nav-link-custom fw-bold" onclick="abrirModalBitacora()">
          <i class="fa-solid fa-clock-rotate-left me-1.5 text-warning"></i> Bitácora de Auditoría
        </button>
      </li>
    </ul>
  </div>
</div>

<main class="container-fluid px-3 px-md-4 py-4 flex-grow-1">

  <!-- ====================================================================
       PESTAÑA 1: PLANILLA MATRICIAL HORIZONTAL TIPO EXCEL
       ==================================================================== -->
  <?php if ($pestañaActiva === 'planilla'): ?>

    <!-- TARJETAS DE RESUMEN SUPERIORES (4 KPIS AVANZADOS ESTILO CENTRAL GANADERA) -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border-top: 4px solid var(--primary-emerald) !important;">
          <small class="text-uppercase fw-bold text-muted" style="font-size: 0.76rem; letter-spacing: 0.05em;">Acopio Hoy</small>
          <div class="fs-2 fw-bold my-1 tabular-nums" id="kpi-litros-hoy" style="color: var(--primary-emerald);">
            <?php echo number_format($litrosHoyTotal, 1, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">L</span>
          </div>
          <small class="text-muted d-block">
            <span class="badge-shift-m py-0.5 px-2">Turno AM: <?php echo number_format($litrosHoyM, 1, ',', '.'); ?> L</span> &bull; 
            <span class="badge-shift-t py-0.5 px-2">Turno PM: <?php echo number_format($litrosHoyT, 1, ',', '.'); ?> L</span>
          </small>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border-top: 4px solid <?php echo $variacionAyer >= 0 ? 'var(--secondary-green)' : '#DC2626'; ?> !important;">
          <small class="text-uppercase fw-bold text-muted" style="font-size: 0.76rem; letter-spacing: 0.05em;">Variación vs Ayer</small>
          <div class="fs-2 fw-bold my-1 tabular-nums" style="color: <?php echo $variacionAyer >= 0 ? '#15803d' : '#b91c1c'; ?>;">
            <?php echo ($variacionAyer >= 0 ? '+' : '') . number_format($variacionAyer, 1, ',', '.'); ?>%
          </div>
          <small class="text-muted d-block">Ayer: <?php echo number_format($litrosAyerTotal, 1, ',', '.'); ?> L acopiados</small>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-white h-100" style="background: linear-gradient(135deg, var(--primary-emerald) 0%, #166534 100%);">
          <div class="d-flex align-items-center justify-content-between">
            <small class="text-uppercase fw-bold text-warning" style="font-size: 0.76rem; letter-spacing: 0.05em;">Total Quincena a Liquidar</small>
            <i class="fa-solid fa-coins text-warning"></i>
          </div>
          <div class="fs-2 fw-bold my-1 tabular-nums text-white" id="kpi-liquidacion">
            <?php echo formatoMonedaCOP($totalLiquidacionGeneral); ?>
          </div>
          <small class="text-light opacity-85 d-block">Tarifa: <?php echo $precioLitroActual ? formatoMonedaCOP($precioLitroActual) . ' / L' : 'Por fijar'; ?></small>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border-top: 4px solid #0284C7 !important;">
          <small class="text-uppercase fw-bold text-muted" style="font-size: 0.76rem; letter-spacing: 0.05em;">Entregas de Hoy</small>
          <div class="fs-2 fw-bold my-1 tabular-nums text-dark">
            <?php echo $ganaderosEntregaronHoy; ?> <small class="fs-6 text-muted fw-normal">/ <?php echo $totalGanaderosActivos; ?></small>
          </div>
          <small class="text-muted d-block"><span class="text-success fw-bold"><?php echo $ganaderosEntregaronHoy; ?> entregaron</span> &bull; <span class="text-warning fw-bold"><?php echo $ganaderosPendientesHoy; ?> pendientes</span></small>
        </div>
      </div>
    </div>

    <!-- AVISO SI LA QUINCENA ESTÁ CERRADA -->
    <?php if ($quincenaEstaCerrada): ?>
      <div class="alert alert-warning rounded-4 shadow-sm py-2 px-3 mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-lock-fill fs-4 text-warning"></i>
        <div>
          <strong>Quincena Cerrada y Liquidada:</strong> Esta quincena se encuentra en modo de <em>solo lectura</em>. No se permiten ediciones inline ni inserciones. Para editar pesajes, debes reabrirla en la pestaña <strong>Precios de Quincena</strong>.
        </div>
      </div>
    <?php endif; ?>

    <!-- BARRA DE CONTROL DE LA PLANILLA (Selector, Anterior/Siguiente, Búsqueda, Exportar) -->
    <div class="card card-rural p-3 mb-3">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <!-- Navegación de Quincenas -->
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="admin.php?tab=planilla&fechaInicio=<?php echo $qAnt['fechaInicio']; ?>&fechaFin=<?php echo $qAnt['fechaFin']; ?>" class="btn btn-touch btn-outline-secondary btn-sm rounded-pill" title="Quincena anterior">
            <i class="bi bi-chevron-left"></i> Anterior
          </a>

          <select class="form-select form-select-touch form-select-sm" style="min-width: 280px;" onchange="const p = this.value.split('|'); location.href='admin.php?tab=planilla&fechaInicio=' + p[0] + '&fechaFin=' + p[1];">
            <?php foreach ($quincenasDisponibles as $q): ?>
              <?php $sel = ($q['fechaInicio'] === $fechaInicioSel && $q['fechaFin'] === $fechaFinSel) ? 'selected' : ''; ?>
              <option value="<?php echo e($q['fechaInicio'] . '|' . $q['fechaFin']); ?>" <?php echo $sel; ?>>
                <?php echo formatoFechaEspanol($q['fechaInicio']); ?> al <?php echo formatoFechaEspanol($q['fechaFin']); ?>
                <?php echo !empty($q['cerrada']) ? ' (Cerrada)' : ' (En curso)'; ?>
              </option>
            <?php endforeach; ?>
          </select>

          <a href="admin.php?tab=planilla&fechaInicio=<?php echo $qSig['fechaInicio']; ?>&fechaFin=<?php echo $qSig['fechaFin']; ?>" class="btn btn-touch btn-outline-secondary btn-sm rounded-pill" title="Quincena siguiente">
            Siguiente <i class="bi bi-chevron-right"></i>
          </a>

          <span class="badge <?php echo $quincenaEstaCerrada ? 'bg-secondary' : 'bg-primary'; ?> px-3 py-2 rounded-pill ms-lg-2">
            <?php echo $quincenaEstaCerrada ? 'Cerrada' : 'Abierta para edición'; ?>
          </span>

          <!-- Botón de Cierre / Reapertura de Quincena -->
          <?php if ($quincenaEstaCerrada): ?>
            <button type="button" class="btn btn-touch btn-warning btn-sm rounded-pill px-3 shadow-sm ms-lg-1" onclick="solicitarReaperturaQuincena()" title="Reabrir esta quincena con validación de clave">
              <i class="bi bi-unlock-fill me-1"></i> Reabrir Quincena
            </button>
          <?php else: ?>
            <button type="button" class="btn btn-touch btn-outline-danger btn-sm rounded-pill px-3 shadow-sm ms-lg-1" onclick="solicitarCierreQuincena()" title="Cerrar y congelar edición inline de esta quincena">
              <i class="bi bi-lock-fill me-1"></i> Cerrar Quincena
            </button>
          <?php endif; ?>
        </div>

        <!-- Buscador en tiempo real de ganaderos y exportación -->
        <div class="d-flex flex-wrap align-items-center gap-2">
          <div class="input-group input-group-sm" style="min-width: 220px;">
            <span class="input-group-text bg-transparent border-2 text-primary">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" id="filtro-matriz" class="form-control form-control-touch form-control-sm border-start-0" placeholder="Buscar ganadero o cédula...">
          </div>

          <div class="d-flex align-items-center gap-2">
            <button type="button" id="btn-importar-excel" class="btn btn-touch btn-sm rounded-pill px-3 shadow-sm text-white fw-bold" style="background-color: var(--primary-emerald);" <?php echo $quincenaEstaCerrada ? 'disabled' : ''; ?> title="Importar pesajes desde Excel (.xlsx, .xls) o CSV">
              <i class="fa-solid fa-file-arrow-up me-1"></i> Importar Excel
            </button>
            <input type="file" id="input-importar-excel" accept=".xlsx, .xls, .csv" class="d-none">

            <button type="button" id="btn-exportar-xlsx" class="btn btn-touch btn-sm rounded-pill px-3 shadow-sm fw-bold text-white" style="background-color: var(--accent-gold); border: none;" title="Descargar planilla completa en formato Excel (.xlsx)">
              <i class="fa-solid fa-file-excel me-1"></i> Excel (.xlsx)
            </button>

            <button type="button" id="btn-exportar-csv" class="btn btn-touch btn-outline-success btn-sm rounded-pill px-3 fw-bold" title="Descargar planilla en formato CSV">
              <i class="fa-solid fa-file-csv me-1"></i> CSV
            </button>

            <button type="button" id="btn-bitacora" class="btn btn-touch btn-outline-secondary btn-sm rounded-pill px-3 fw-bold" onclick="abrirModalBitacora()" title="Consultar historial de auditoría y bitácora de cambios">
              <i class="fa-solid fa-clock-rotate-left me-1 text-warning"></i> Bitácora
            </button>
          </div>
        </div>
      </div>

      <!-- Leyenda de Uso -->
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 mt-2 border-top border-secondary-subtle small text-muted">
        <div>
          <span class="me-3"><strong class="text-amber-700">Turno AM:</strong> Mañana</span>
          <span class="me-3"><strong class="text-blue-700">Turno PM:</strong> Tarde</span>
          <span class="me-3"><strong>Enter:</strong> Baja celda &bull; <strong>Tab / Flechas:</strong> Navega &bull; <strong>Esc:</strong> Cancela</span>
          <span><strong>Pegar desde Excel:</strong> Selecciona una celda y presiona Ctrl + V para pegar en bloque</span>
        </div>
        <div id="contador-filas-visibles" class="fw-bold text-primary">
          <?php echo $totalGanaderosActivos; ?> asociados visibles
        </div>
      </div>
    </div>

    <!-- CONTENEDOR DE LA PLANILLA MATRICIAL HORIZONTAL -->
    <div class="matrix-container mb-4">
      <div class="matrix-scroll-wrapper">
        <table class="matrix-table" id="tabla-matriz">
          <thead>
            <!-- Fila Nivel 1: Fechas -->
            <tr>
              <th rowspan="2" class="col-sticky-name align-middle">
                ASOCIADO / CÉDULA
              </th>
              <?php foreach ($diasQuincena as $d): ?>
                <?php 
                  $esHoy = ($d['fecha'] === date('Y-m-d'));
                  $w = (int)(new DateTime($d['fecha']))->format('w');
                  $esFinSemana = ($w === 0 || $w === 6);
                ?>
                <th colspan="2" class="<?php echo $esHoy ? 'col-today' : ($esFinSemana ? 'col-weekend' : ''); ?> border-end border-success-subtle">
                  <div><?php echo e($d['etiqueta']); ?></div>
                  <?php if ($esHoy): ?>
                    <span class="badge bg-warning text-dark px-1.5 py-0 rounded-pill" style="font-size: 0.65rem;">HOY</span>
                  <?php endif; ?>
                </th>
              <?php endforeach; ?>
              <th rowspan="2" class="col-sticky-total align-middle text-end">
                TOTAL (L)
              </th>
            </tr>
            <!-- Fila Nivel 2: Turnos -->
            <tr>
              <?php foreach ($diasQuincena as $d): ?>
                <th class="py-1 px-1 bg-warning-subtle text-dark">AM</th>
                <th class="py-1 px-1 bg-danger-subtle text-dark col-border-day">PM</th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody id="tbody-matriz">
            <?php if (empty($ganaderosPlanilla)): ?>
              <tr>
                <td colspan="<?php echo (count($diasQuincena) * 2) + 2; ?>" class="text-center py-5 text-muted">
                  <i class="bi bi-people display-4 d-block mb-2"></i>
                  No hay ganaderos activos registrados en el sistema. Agrega asociados en la pestaña correspondiente.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($ganaderosPlanilla as $idx => $g): ?>
                <?php $totG = $totalesPorGanadero[$g['id']] ?? 0.0; ?>
                <tr class="fila-matriz" data-uid="<?php echo e($g['id']); ?>" data-cedula="<?php echo e($g['cedula']); ?>" data-idx="<?php echo $idx; ?>" data-texto="<?php echo strtolower(e($g['nombre'] . ' ' . $g['cedula'])); ?>">
                  <!-- Columna Asociado Fija a la Izquierda -->
                  <td class="col-sticky-name">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <div class="overflow-hidden">
                        <div class="fw-bold text-truncate" style="max-width: 170px;" title="<?php echo e($g['nombre']); ?>">
                          <?php echo e($g['nombre']); ?>
                        </div>
                        <div class="text-muted font-monospace small">
                          C.C. <?php echo e($g['cedula']); ?><?php if (!empty($g['finca'])) echo ' &bull; ' . e($g['finca']); ?>
                        </div>
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-success p-1 px-2 rounded-pill no-print flex-shrink-0" title="Generar Colilla de Liquidación" onclick="abrirColillaPago('<?php echo e($g['id']); ?>', '<?php echo e(addslashes($g['nombre'])); ?>', '<?php echo e($g['cedula']); ?>', '<?php echo e(addslashes($g['finca'] ?? 'Vereda Alizal')); ?>', <?php echo (float)$totG; ?>)">
                        <i class="bi bi-receipt"></i>
                      </button>
                    </div>
                  </td>

                  <!-- Celdas de Pesaje Día a Día (Mañana / Tarde) -->
                  <?php foreach ($diasQuincena as $cIdx => $d): ?>
                    <?php 
                      $esHoy = ($d['fecha'] === date('Y-m-d'));
                      $w = (int)(new DateTime($d['fecha']))->format('w');
                      $esFinSemana = ($w === 0 || $w === 6);
                      $claseColExtra = $esHoy ? 'col-today' : ($esFinSemana ? 'col-weekend' : '');

                      $valM = $mapaMatriz[$g['id']]["{$d['fecha']}_MANANA"] ?? null;
                      $valT = $mapaMatriz[$g['id']]["{$d['fecha']}_TARDE"] ?? null;
                    ?>
                    <!-- Celda Mañana -->
                    <td class="matrix-cell <?php echo $claseColExtra; ?>" data-uid="<?php echo e($g['id']); ?>" data-fecha="<?php echo e($d['fecha']); ?>" data-turno="MANANA" data-r="<?php echo $idx; ?>" data-c="<?php echo ($cIdx * 2); ?>">
                      <input type="text" inputmode="decimal" 
                             class="matrix-input <?php echo ($valM === null ? 'is-empty' : ($valM == 0 ? 'is-zero' : '')); ?>" 
                             value="<?php echo ($valM !== null ? number_format($valM, 1, '.', '') : ''); ?>" 
                             placeholder="-" 
                             <?php echo $quincenaEstaCerrada ? 'disabled' : ''; ?> 
                             autocomplete="off" spellcheck="false">
                    </td>

                    <!-- Celda Tarde -->
                    <td class="matrix-cell col-border-day <?php echo $claseColExtra; ?>" data-uid="<?php echo e($g['id']); ?>" data-fecha="<?php echo e($d['fecha']); ?>" data-turno="TARDE" data-r="<?php echo $idx; ?>" data-c="<?php echo ($cIdx * 2 + 1); ?>">
                      <input type="text" inputmode="decimal" 
                             class="matrix-input <?php echo ($valT === null ? 'is-empty' : ($valT == 0 ? 'is-zero' : '')); ?>" 
                             value="<?php echo ($valT !== null ? number_format($valT, 1, '.', '') : ''); ?>" 
                             placeholder="-" 
                             <?php echo $quincenaEstaCerrada ? 'disabled' : ''; ?> 
                             autocomplete="off" spellcheck="false">
                    </td>
                  <?php endforeach; ?>

                  <!-- Total Quincena del Ganadero Fijo a la Derecha -->
                  <td class="col-sticky-total text-end" id="tot-g-<?php echo e($g['id']); ?>">
                    <?php echo number_format($totG, 1, ',', '.'); ?> L
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <!-- Fila de Totales de Columna Fija Abajo (Totales Diarios Unificados con Colspan 2) -->
          <tfoot>
            <tr>
              <td class="col-sticky-name text-uppercase fw-bold">
                TOTAL POR DÍA (L)
              </td>
              <?php foreach ($diasQuincena as $d): ?>
                <?php 
                  $totColM = $totalesPorColumna["{$d['fecha']}_MANANA"] ?? 0.0;
                  $totColT = $totalesPorColumna["{$d['fecha']}_TARDE"] ?? 0.0;
                  $totDia  = $totColM + $totColT;
                ?>
                <td colspan="2" class="text-center fw-bold col-border-day total-dia-cell" id="tot-dia-<?php echo e($d['fecha']); ?>" data-fecha="<?php echo e($d['fecha']); ?>">
                  <?php echo $totDia > 0 ? number_format($totDia, 1, ',', '.') : '-'; ?>
                </td>
              <?php endforeach; ?>
              <td class="col-sticky-total text-end fw-bold" id="gran-total-matriz">
                <?php echo number_format($granTotalMatriz, 1, ',', '.'); ?> L
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  <!-- ====================================================================
       PESTAÑA 2: GESTIÓN DE ASOCIADOS
       ==================================================================== -->
  <?php elseif ($pestañaActiva === 'ganaderos'): ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-bold text-success mb-1">
          <i class="bi bi-people-fill me-2"></i> Gestión de Ganaderos Asociados
        </h4>
        <p class="text-muted small mb-0">Afiliación, actualización de fincas, datos de contacto y estado de asociados.</p>
      </div>

      <button type="button" class="btn btn-touch btn-aso-primary shadow" data-bs-toggle="modal" data-bs-target="#modalNuevoGanadero">
        <i class="bi bi-person-plus-fill me-1"></i> Agregar Nuevo Asociado
      </button>
    </div>

    <div class="card card-rural border-0 shadow overflow-hidden">
      <div class="card-body p-3 p-md-4">
        <div class="table-responsive">
          <table id="tabla-gestion-ganaderos" class="table table-rural table-hover align-middle w-100">
            <thead>
              <tr>
                <th>Nombre Completo</th>
                <th>Cédula</th>
                <th>Teléfono</th>
                <th>Finca / Predio</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($listaTodosGanaderos as $g): ?>
                <tr>
                  <td class="fw-bold text-success fs-6"><?php echo e($g['nombre']); ?></td>
                  <td class="font-monospace fw-semibold"><?php echo e($g['cedula']); ?></td>
                  <td><?php echo e($g['telefono'] ?: 'No registrado'); ?></td>
                  <td><?php echo e($g['finca'] ?: 'Vereda Alizal'); ?></td>
                  <td class="text-center">
                    <?php if (!empty($g['activo'])): ?>
                      <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill fw-bold">Activo</span>
                    <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill fw-bold">Inactivo</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="d-inline-flex gap-2">
                      <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1" onclick="editarGanadero(<?php echo htmlspecialchars(json_encode($g), ENT_QUOTES, 'UTF-8'); ?>)">
                        <i class="bi bi-pencil-fill me-1"></i> Editar
                      </button>
                      <form action="admin.php?tab=ganaderos" method="POST" onsubmit="return confirm('¿Reestablecer clave a temporal (123456) para este asociado?');" class="d-inline">
                        <?php echo campoCsrf(); ?>
                        <input type="hidden" name="accion" value="reset_clave_ganadero">
                        <input type="hidden" name="asociadoId" value="<?php echo e($g['id']); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-2 py-1" title="Reestablecer clave a 123456">
                          <i class="bi bi-key-fill"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  <!-- ====================================================================
       PESTAÑA 3: PRECIOS DE QUINCENA
       ==================================================================== -->
  <?php elseif ($pestañaActiva === 'quincenas'): ?>

    <div class="row g-4 mb-4">
      <div class="col-lg-5">
        <div class="card card-rural p-4 shadow-sm border-2 border-success-subtle">
          <h5 class="fw-bold text-success mb-3">
            <i class="bi bi-tag-fill me-1"></i> Fijar Precio y Estado de Quincena
          </h5>

          <form action="admin.php?tab=quincenas" method="POST">
            <?php echo campoCsrf(); ?>
            <input type="hidden" name="accion" value="fijar_precio_quincena">

            <div class="mb-3">
              <label for="fechaInicio" class="form-label small fw-bold text-muted">Fecha Inicio de Quincena *</label>
              <input type="date" name="fechaInicio" id="fechaInicio" class="form-control form-control-touch" value="<?php echo e($fechaInicioSel); ?>" required>
            </div>

            <div class="mb-3">
              <label for="fechaFin" class="form-label small fw-bold text-muted">Fecha Fin de Quincena *</label>
              <input type="date" name="fechaFin" id="fechaFin" class="form-control form-control-touch" value="<?php echo e($fechaFinSel); ?>" required>
            </div>

            <div class="mb-3">
              <label for="precioLitro" class="form-label small fw-bold text-muted">Precio Oficial por Litro ($ COP) *</label>
              <input type="number" step="1" name="precioLitro" id="precioLitro" class="form-control form-control-touch fw-extrabold fs-4 text-success" value="<?php echo $precioLitroActual ?: 2250; ?>" required placeholder="Ej: 2250">
              <small class="text-muted">Tarifa base para liquidación a los ganaderos.</small>
            </div>

            <div class="form-check form-switch mb-4">
              <input class="form-check-input" type="checkbox" role="switch" id="cerrada" name="cerrada" <?php echo $quincenaEstaCerrada ? 'checked' : ''; ?>>
              <label class="form-check-label fw-bold small text-muted" for="cerrada">Marcar Quincena como Cerrada y Liquidada</label>
            </div>

            <button type="submit" class="btn btn-touch btn-aso-primary w-100 shadow">
              <i class="bi bi-check-circle-fill me-1"></i> Guardar Configuración
            </button>
          </form>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card card-rural p-4 shadow-sm">
          <h5 class="fw-bold text-success mb-3">
            <i class="bi bi-clock-history me-1"></i> Historial de Periodos Quincenales
          </h5>

          <div class="table-responsive">
            <table id="tabla-quincenas-historial" class="table table-hover align-middle w-100">
              <thead class="table-light">
                <tr>
                  <th>Periodo</th>
                  <th class="text-end">Precio / Litro</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($quincenasDisponibles as $qd): ?>
                  <tr>
                    <td>
                      <strong><?php echo formatoFechaEspanol($qd['fechaInicio']); ?></strong> al 
                      <strong><?php echo formatoFechaEspanol($qd['fechaFin']); ?></strong>
                    </td>
                    <td class="text-end fw-bold text-success">
                      <?php echo formatoMonedaCOP($qd['precioLitro']); ?>
                    </td>
                    <td class="text-center">
                      <?php if (!empty($qd['cerrada'])): ?>
                        <span class="badge bg-secondary">Cerrada</span>
                      <?php else: ?>
                        <span class="badge bg-success">Activa</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <a href="admin.php?tab=quincenas&fechaInicio=<?php echo $qd['fechaInicio']; ?>&fechaFin=<?php echo $qd['fechaFin']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                        Configurar
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  <!-- ====================================================================
       PESTAÑA 4: BANDEJA DE QUEJAS Y CONSULTAS
       ==================================================================== -->
  <?php elseif ($pestañaActiva === 'buzon'): ?>

    <!-- Subsección Inconformidades Quincenales -->
    <div class="card card-rural border-0 shadow mb-4 overflow-hidden">
      <div class="card-header bg-warning text-dark p-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
          <i class="bi bi-shield-exclamation me-2"></i> Inconformidades Quincenales Radicadas por Ganaderos
        </h5>
        <span class="badge bg-dark text-white rounded-pill px-3 py-1"><?php echo count($listaAlertas); ?> Reclamos</span>
      </div>

      <div class="card-body p-3 p-md-4">
        <?php if (empty($listaAlertas)): ?>
          <div class="text-center py-4 text-muted">
            <i class="bi bi-check2-circle fs-1 text-success"></i>
            <p class="mb-0 fw-bold mt-2">No hay inconformidades pendientes en el sistema.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table id="tabla-alertas-admin" class="table table-hover align-middle w-100">
              <thead class="table-light">
                <tr>
                  <th>Fecha</th>
                  <th>Ganadero</th>
                  <th>Periodo</th>
                  <th class="text-end">Litros</th>
                  <th>Detalle Inconformidad</th>
                  <th>Respuesta Oficial</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($listaAlertas as $alt): ?>
                  <?php
                    $badgeClass = 'bg-danger';
                    if ($alt['estado'] === 'EN_REVISION') $badgeClass = 'bg-warning text-dark';
                    elseif ($alt['estado'] === 'RESUELTO' || $alt['estado'] === 'ATENDIDO') $badgeClass = 'bg-success';
                  ?>
                  <tr id="fila-alerta-<?php echo e($alt['id']); ?>">
                    <td class="small text-muted"><?php echo formatoFechaEspanol($alt['creadoEn']); ?></td>
                    <td>
                      <strong><?php echo e($alt['ganaderoNombre']); ?></strong><br>
                      <small class="text-muted font-monospace">C.C. <?php echo e($alt['ganaderoCedula']); ?></small>
                    </td>
                    <td class="small"><?php echo e($alt['fechaInicio']); ?> al <?php echo e($alt['fechaFin']); ?></td>
                    <td class="text-end fw-bold text-success"><?php echo formatoLitros($alt['litrosAlMomento']); ?></td>
                    <td class="small fst-italic" style="max-width: 250px;">
                      "<?php echo e($alt['mensaje']); ?>"
                    </td>
                    <td class="small" style="max-width: 250px;" id="celda-resp-<?php echo e($alt['id']); ?>">
                      <?php if (!empty($alt['respuestaAdmin'])): ?>
                        <div class="p-2 rounded bg-light border border-success-subtle text-dark">
                          <strong><?php echo e($alt['respondidoPor'] ?: 'Admin'); ?>:</strong> <?php echo e($alt['respuestaAdmin']); ?>
                        </div>
                      <?php else: ?>
                        <span class="text-muted fst-italic">Sin respuesta registrada aún</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-1" id="badge-estado-<?php echo e($alt['id']); ?>">
                        <?php echo e($alt['estado']); ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="abrirModalRespuestaReclamo('<?php echo e($alt['id']); ?>', '<?php echo e(addslashes($alt['ganaderoNombre'])); ?>', '<?php echo e($alt['estado']); ?>', '<?php echo e(addslashes($alt['mensaje'])); ?>', '<?php echo e(addslashes($alt['respuestaAdmin'] ?? '')); ?>')">
                        <i class="bi bi-chat-left-dots-fill me-1"></i> Responder
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Subsección Consultas de Contacto Web -->
    <div class="card card-rural border-0 shadow overflow-hidden">
      <div class="card-header bg-success text-white p-3">
        <h5 class="fw-bold mb-0">
          <i class="bi bi-envelope-open-fill me-2"></i> Mensajes Recibidos desde la Landing Web
        </h5>
      </div>
      <div class="card-body p-3 p-md-4">
        <?php if (empty($listaConsultas)): ?>
          <div class="text-center py-4 text-muted">
            <p class="mb-0">No se han recibido consultas públicas recientes.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table id="tabla-consultas-admin" class="table table-hover align-middle w-100">
              <thead class="table-light">
                <tr>
                  <th>Fecha</th>
                  <th>Nombre</th>
                  <th>Contacto</th>
                  <th>Mensaje</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($listaConsultas as $c): ?>
                  <tr>
                    <td class="small text-muted"><?php echo formatoFechaEspanol($c['fecha']); ?></td>
                    <td class="fw-bold"><?php echo e($c['nombre']); ?></td>
                    <td class="small"><?php echo e($c['contacto']); ?></td>
                    <td class="small"><?php echo e($c['mensaje']); ?></td>
                    <td class="text-center">
                      <?php if (!empty($c['atendida'])): ?>
                        <span class="badge bg-success-subtle text-success">Atendida</span>
                      <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis">Pendiente</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php if (empty($c['atendida'])): ?>
                        <form action="admin.php?tab=buzon" method="POST" class="d-inline">
                          <?php echo campoCsrf(); ?>
                          <input type="hidden" name="accion" value="atender_consulta">
                          <input type="hidden" name="consultaId" value="<?php echo e($c['id']); ?>">
                          <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3">
                            Marcar Atendida
                          </button>
                        </form>
                      <?php else: ?>
                        <span class="text-muted small"><i class="bi bi-check-circle-fill text-success"></i> Lista</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php endif; ?>

</main>

<!-- MODAL: AGREGAR / EDITAR GANADERO ASOCIADO -->
<div class="modal fade" id="modalNuevoGanadero" tabindex="-1" aria-labelledby="modalGanaderoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header bg-success text-white p-4">
        <h5 class="modal-title fw-bold" id="modalGanaderoLabel">
          <i class="bi bi-person-plus-fill me-2"></i> Asociado Ganadero
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form action="admin.php?tab=ganaderos" method="POST" id="form-ganadero">
        <?php echo campoCsrf(); ?>
        <input type="hidden" name="accion" value="guardar_ganadero">
        <input type="hidden" name="asociadoId" id="modal_asociado_id" value="">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="modal_cedula" class="form-label small fw-bold text-muted">Cédula de Ciudadanía *</label>
            <input type="text" name="cedula" id="modal_cedula" class="form-control form-control-touch" required placeholder="Número de cédula">
          </div>

          <div class="mb-3">
            <label for="modal_nombre" class="form-label small fw-bold text-muted">Nombre Completo *</label>
            <input type="text" name="nombre" id="modal_nombre" class="form-control form-control-touch" required placeholder="Nombre del ganadero">
          </div>

          <div class="mb-3">
            <label for="modal_telefono" class="form-label small fw-bold text-muted">Teléfono de Contacto</label>
            <input type="text" name="telefono" id="modal_telefono" class="form-control form-control-touch" placeholder="Ej: 3101234567">
          </div>

          <div class="mb-3">
            <label for="modal_finca" class="form-label small fw-bold text-muted">Finca / Predio</label>
            <input type="text" name="finca" id="modal_finca" class="form-control form-control-touch" placeholder="Ej: Finca La Esperanza">
          </div>

          <div class="mb-3">
            <label for="modal_password" class="form-label small fw-bold text-muted">Contraseña (Dejar en blanco para no cambiar)</label>
            <input type="password" name="password" id="modal_password" class="form-control form-control-touch" placeholder="Por defecto: 123456">
          </div>

          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="modal_activo" name="activo" checked>
            <label class="form-check-label fw-bold small text-muted" for="modal_activo">Asociado Activo para Recolección</label>
          </div>
        </div>

        <div class="modal-footer p-3 bg-body-tertiary">
          <button type="button" class="btn btn-touch btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-touch btn-aso-primary rounded-pill px-4">
            <i class="bi bi-save-fill me-1"></i> Guardar Asociado
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL 1: CONFIRMAR CIERRE DE QUINCENA -->
<div class="modal fade" id="modalConfirmarCierreQuincena" tabindex="-1" aria-labelledby="modalCierreLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header bg-danger text-white p-4">
        <h5 class="modal-title fw-bold" id="modalCierreLabel">
          <i class="bi bi-lock-fill me-2"></i> Cerrar y Liquidar Quincena
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-warning rounded-3 small mb-3">
          <strong>Atención:</strong> Al cerrar la quincena <strong><?php echo formatoFechaEspanol($fechaInicioSel); ?> al <?php echo formatoFechaEspanol($fechaFinSel); ?></strong>, la planilla quedará en modo de <em>solo lectura</em> y se congelará la edición inline.
        </div>
        <p class="small text-muted mb-3">Para autorizar este cierre, por favor ingresa tu contraseña de Administrador:</p>
        <div class="mb-3">
          <label class="form-label small fw-bold">Contraseña de Administrador *</label>
          <input type="password" id="pass-cierre-quincena" class="form-control form-control-touch" placeholder="Tu contraseña actual" autocomplete="current-password">
        </div>
      </div>
      <div class="modal-footer p-3 bg-body-tertiary">
        <button type="button" class="btn btn-touch btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-touch btn-danger rounded-pill px-4" onclick="confirmarAccionQuincena('cerrar')">
          <i class="bi bi-lock-fill me-1"></i> Confirmar Cierre
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 2: CONFIRMAR REAPERTURA DE QUINCENA -->
<div class="modal fade" id="modalConfirmarReaperturaQuincena" tabindex="-1" aria-labelledby="modalReaperturaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header bg-warning text-dark p-4">
        <h5 class="modal-title fw-bold" id="modalReaperturaLabel">
          <i class="bi bi-unlock-fill me-2"></i> Reabrir Quincena para Edición
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-info rounded-3 small mb-3">
          Al reabrir la quincena <strong><?php echo formatoFechaEspanol($fechaInicioSel); ?> al <?php echo formatoFechaEspanol($fechaFinSel); ?></strong>, se habilitará nuevamente la edición inline y el pegado de pesajes.
        </div>
        <p class="small text-muted mb-3">Ingresa tu contraseña de Administrador para confirmar:</p>
        <div class="mb-3">
          <label class="form-label small fw-bold">Contraseña de Administrador *</label>
          <input type="password" id="pass-reapertura-quincena" class="form-control form-control-touch" placeholder="Tu contraseña actual" autocomplete="current-password">
        </div>
      </div>
      <div class="modal-footer p-3 bg-body-tertiary">
        <button type="button" class="btn btn-touch btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-touch btn-warning text-dark rounded-pill px-4" onclick="confirmarAccionQuincena('reabrir')">
          <i class="bi bi-unlock-fill me-1"></i> Confirmar Reapertura
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 3: BITÁCORA DE CAMBIOS / HISTORIAL DE AUDITORÍA -->
<div class="modal fade" id="modalBitacoraAuditoria" tabindex="-1" aria-labelledby="modalBitacoraLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header bg-dark text-white p-4">
        <h5 class="modal-title fw-bold" id="modalBitacoraLabel">
          <i class="bi bi-clock-history me-2"></i> Historial de Auditoría y Bitácora de Cambios
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4">
        <p class="small text-muted mb-3">Registro de modificaciones de pesajes, tarifas, cierres y accesos:</p>
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle small w-100" id="tabla-bitacora">
            <thead class="table-dark">
              <tr>
                <th>Fecha / Hora</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Campo Modificado</th>
                <th>Valor Anterior</th>
                <th>Valor Nuevo</th>
                <th>Detalles</th>
                <th>IP</th>
              </tr>
            </thead>
            <tbody id="tbody-bitacora">
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                  <span class="spinner-border spinner-border-sm me-2"></span> Cargando registros de auditoría...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer p-3 bg-body-tertiary d-flex justify-content-between">
        <small class="text-muted"><i class="bi bi-shield-lock-fill text-success me-1"></i> Trazabilidad Completa</small>
        <button type="button" class="btn btn-touch btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 4: COLILLA DE PAGO / RECIBO DE LIQUIDACIÓN QUINCENAL -->
<div class="modal fade" id="modalColillaPago" tabindex="-1" aria-labelledby="modalColillaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header bg-success text-white p-4 no-print">
        <h5 class="modal-title fw-bold" id="modalColillaLabel">
          <i class="bi bi-receipt me-2"></i> Recibo de Liquidación Quincenal
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4">
        <!-- ÁREA IMPRIMIBLE DE LA COLILLA -->
        <div id="colilla-imprimible" class="p-3 p-md-4 border rounded-3 bg-white">
          <!-- Membrete Oficial -->
          <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
            <div class="d-flex align-items-center gap-3">
              <img src="assets/img/logo.png" alt="Logo" style="max-height: 55px; width: auto;" class="img-fluid">
              <div>
                <h5 class="fw-bold mb-0 text-success"><?php echo e(EMPRESA_SIGLA); ?></h5>
                <small class="text-muted d-block"><?php echo e(EMPRESA_NOMBRE); ?></small>
                <small class="fw-bold text-dark">NIT: <?php echo e(EMPRESA_NIT); ?> &bull; <?php echo e(EMPRESA_UBICACION); ?></small>
              </div>
            </div>
            <div class="text-end">
              <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 border border-success-subtle rounded-pill">
                Colilla de Liquidación
              </span>
              <small class="d-block text-muted mt-1">Fecha Emisión: <?php echo date('d/m/Y'); ?></small>
            </div>
          </div>

          <!-- Datos del Ganadero y Periodo -->
          <div class="row g-2 mb-3 p-3 rounded-3" style="background-color: var(--surface-2);">
            <div class="col-sm-6">
              <small class="text-muted d-block">Ganadero Asociado:</small>
              <strong class="fs-6 text-dark" id="colilla-nombre">-</strong>
            </div>
            <div class="col-sm-6 text-sm-end">
              <small class="text-muted d-block">Cédula de Ciudadanía:</small>
              <strong class="font-monospace fs-6 text-dark" id="colilla-cedula">-</strong>
            </div>
            <div class="col-sm-6">
              <small class="text-muted d-block">Finca / Predio:</small>
              <span class="fw-bold text-dark" id="colilla-finca">-</span>
            </div>
            <div class="col-sm-6 text-sm-end">
              <small class="text-muted d-block">Periodo Quincenal:</small>
              <span class="fw-bold text-primary" id="colilla-periodo"><?php echo formatoFechaEspanol($fechaInicioSel); ?> al <?php echo formatoFechaEspanol($fechaFinSel); ?></span>
            </div>
          </div>

          <!-- Desglose Diario Mañana / Tarde -->
          <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm text-center align-middle mb-0" id="colilla-tabla-dias">
              <thead class="table-light small">
                <tr>
                  <th>Fecha</th>
                  <th>Día</th>
                  <th>Turno AM (L)</th>
                  <th>Turno PM (L)</th>
                  <th>Total Día (L)</th>
                </tr>
              </thead>
              <tbody id="colilla-tbody-dias" class="small">
                <!-- Se llena dinámicamente con JavaScript -->
              </tbody>
            </table>
          </div>

          <!-- Resumen Financiero Quincenal -->
          <div class="row g-2 p-3 bg-light rounded-3 border mb-4">
            <div class="col-4 text-center border-end">
              <small class="text-muted d-block text-uppercase fw-bold">Total Litros</small>
              <strong class="fs-5 text-dark" id="colilla-total-litros">0,0 L</strong>
            </div>
            <div class="col-4 text-center border-end">
              <small class="text-muted d-block text-uppercase fw-bold">Precio por Litro</small>
              <strong class="fs-5 text-dark" id="colilla-precio-litro"><?php echo $precioLitroActual ? formatoMonedaCOP($precioLitroActual) : '$ 0'; ?></strong>
            </div>
            <div class="col-4 text-center">
              <small class="text-muted d-block text-uppercase fw-bold text-success">Neto a Liquidar</small>
              <strong class="fs-4 text-success" id="colilla-total-neto">$ 0</strong>
            </div>
          </div>

          <!-- Firmas -->
          <div class="row pt-4 mt-4 border-top text-center text-muted small">
            <div class="col-6">
              <div class="border-top border-dark mx-auto pt-2" style="max-width: 200px;">
                Firma Administrador<br>
                <small class="text-muted"><?php echo e($usuario['nombre']); ?></small>
              </div>
            </div>
            <div class="col-6">
              <div class="border-top border-dark mx-auto pt-2" style="max-width: 200px;">
                Firma Ganadero Asociado<br>
                <small class="text-muted">Recibido Conforme</small>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer p-3 bg-body-tertiary no-print d-flex justify-content-between">
        <button type="button" class="btn btn-touch btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-touch btn-success rounded-pill px-4" onclick="window.print()">
          <i class="bi bi-printer-fill me-1"></i> Imprimir / Guardar como PDF
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 5: GESTIONAR RESPUESTA A RECLAMO -->
<div class="modal fade" id="modalRespuestaReclamo" tabindex="-1" aria-labelledby="modalRespReclamoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header bg-primary text-white p-4">
        <h5 class="modal-title fw-bold" id="modalRespReclamoLabel">
          <i class="bi bi-chat-left-dots-fill me-2"></i> Responder Inconformidad Quincenal
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="resp-alerta-id" value="">
        <div class="mb-3 p-3 bg-light rounded-3 border">
          <small class="text-muted d-block">Ganadero Asociado:</small>
          <strong class="text-dark d-block mb-2" id="resp-ganadero-nombre">-</strong>
          <small class="text-muted d-block">Mensaje de inconformidad radicado:</small>
          <p class="small fst-italic mb-0 text-dark" id="resp-mensaje-reclamo">-</p>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold text-dark">Estado del Reclamo *</label>
          <select id="resp-nuevo-estado" class="form-select form-select-touch">
            <option value="PENDIENTE">Pendiente</option>
            <option value="EN_REVISION">En Revisión</option>
            <option value="RESUELTO">Resuelto</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold text-dark">Respuesta Oficial del Administrador *</label>
          <textarea id="resp-texto-admin" class="form-control" rows="4" placeholder="Escribe la explicación o resolución oficial que el ganadero verá en su panel..."></textarea>
        </div>
      </div>
      <div class="modal-footer p-3 bg-body-tertiary">
        <button type="button" class="btn btn-touch btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-touch btn-primary rounded-pill px-4" onclick="guardarRespuestaReclamo()">
          <i class="bi bi-send-fill me-1"></i> Guardar Respuesta
        </button>
      </div>
    </div>
  </div>
</div>

<!-- SheetJS para importación de Excel/CSV en cliente -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<!-- SCRIPTS DE LA PLANILLA MATRICIAL Y LISTAS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const CSRF_TOKEN = '<?php echo generarCsrfToken(); ?>';
  const FECHA_INICIO = '<?php echo $fechaInicioSel; ?>';
  const FECHA_FIN = '<?php echo $fechaFinSel; ?>';
  const PRECIO_LITRO = <?php echo (float)$precioLitroActual; ?>;
  const QUINCENA_CERRADA = <?php echo $quincenaEstaCerrada ? 'true' : 'false'; ?>;

  // 1. INICIALIZAR DATATABLES ÚNICAMENTE EN LISTAS (NUNCA EN LA PLANILLA MATRICIAL)
  if (typeof $ !== 'undefined' && $.fn.DataTable) {
    const dtLang = window.AsoagroUI?.dataTablesSpanish || {};

    if ($('#tabla-gestion-ganaderos').length) {
      $('#tabla-gestion-ganaderos').DataTable({ language: dtLang, pageLength: 20, responsive: true, order: [[0, 'asc']] });
    }
    if ($('#tabla-quincenas-historial').length) {
      $('#tabla-quincenas-historial').DataTable({ language: dtLang, pageLength: 15, responsive: true, order: [[0, 'desc']] });
    }
    if ($('#tabla-alertas-admin').length) {
      $('#tabla-alertas-admin').DataTable({ language: dtLang, pageLength: 15, responsive: true, order: [[0, 'desc']] });
    }
    if ($('#tabla-consultas-admin').length) {
      $('#tabla-consultas-admin').DataTable({ language: dtLang, pageLength: 15, responsive: true, order: [[0, 'desc']] });
    }
  }

  // 2. LÓGICA DE LA PLANILLA MATRICIAL HORIZONTAL TIPO EXCEL
  const tablaMatriz = document.getElementById('tabla-matriz');
  if (tablaMatriz && !QUINCENA_CERRADA) {
    const inputs = Array.from(tablaMatriz.querySelectorAll('.matrix-input'));

    inputs.forEach((input) => {
      // Guardar valor inicial para detectar cambios o revertir con Esc
      input.dataset.prev = input.value.trim();

      // Al enfocar: seleccionar texto para sobreescribir rápido
      input.addEventListener('focus', function () {
        this.select();
      });

      // Navegación por teclado (Tab, Enter, Flechas, Esc)
      input.addEventListener('keydown', function (e) {
        const td = this.closest('td');
        const r = parseInt(td.dataset.r, 10);
        const c = parseInt(td.dataset.c, 10);

        if (e.key === 'Escape') {
          this.value = this.dataset.prev || '';
          this.blur();
          return;
        }

        if (e.key === 'Enter') {
          e.preventDefault();
          // Enter: bajar a la misma columna de la fila siguiente
          const target = tablaMatriz.querySelector(`td[data-r="${r + 1}"][data-c="${c}"] input`);
          if (target) {
            target.focus();
          } else {
            this.blur();
          }
          return;
        }

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          const target = tablaMatriz.querySelector(`td[data-r="${r + 1}"][data-c="${c}"] input`);
          if (target) target.focus();
          return;
        }

        if (e.key === 'ArrowUp') {
          e.preventDefault();
          const target = tablaMatriz.querySelector(`td[data-r="${r - 1}"][data-c="${c}"] input`);
          if (target) target.focus();
          return;
        }

        if (e.key === 'ArrowRight' && (this.selectionEnd === this.value.length || this.value === '')) {
          const target = tablaMatriz.querySelector(`td[data-r="${r}"][data-c="${c + 1}"] input`);
          if (target) {
            e.preventDefault();
            target.focus();
          }
          return;
        }

        if (e.key === 'ArrowLeft' && (this.selectionStart === 0 || this.value === '')) {
          const target = tablaMatriz.querySelector(`td[data-r="${r}"][data-c="${c - 1}"] input`);
          if (target) {
            e.preventDefault();
            target.focus();
          }
          return;
        }
      });

      // Al perder el foco (blur): enviar al servidor si cambió el valor
      input.addEventListener('blur', function () {
        const actual = this.value.trim().replace(',', '.');
        const previo = (this.dataset.prev || '').trim().replace(',', '.');

        if (actual === previo) return;

        // Validar formato numérico si no está vacío
        if (actual !== '' && (isNaN(parseFloat(actual)) || parseFloat(actual) < 0 || parseFloat(actual) > 9999)) {
          alert('Por favor ingresa un número de litros válido (entre 0 y 9.999).');
          this.value = this.dataset.prev || '';
          return;
        }

        guardarCeldaInline(this, actual);
      });

      // Pegar desde Excel / Portapapeles (Paste Handler en bloque)
      input.addEventListener('paste', function (e) {
        const clipboard = (e.clipboardData || window.clipboardData).getData('text');
        if (!clipboard) return;

        // Si contiene tabulaciones (\t) o saltos de línea (\n), es un bloque de celdas
        if (clipboard.includes('\t') || clipboard.includes('\n')) {
          e.preventDefault();
          pegarBloqueExcel(this, clipboard);
        }
      });
    });
  }

  // Guardar celda individual con fetch y feedback visual
  async function guardarCeldaInline(input, valorNuevo) {
    const td = input.closest('td');
    const uId = td.dataset.uid;
    const fecha = td.dataset.fecha;
    const turno = td.dataset.turno;

    // Actualizar clases de vacío o cero de forma inmediata
    if (valorNuevo === '') {
      input.className = 'matrix-input is-empty';
    } else if (parseFloat(valorNuevo) === 0) {
      input.className = 'matrix-input is-zero';
    } else {
      input.className = 'matrix-input';
    }

    // Feedback: Guardando
    td.classList.remove('is-saved', 'is-error');
    td.classList.add('is-saving');

    try {
      const resp = await fetch('api/guardar_pesaje_matriz.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          csrf_token: CSRF_TOKEN,
          fechaInicio: FECHA_INICIO,
          fechaFin: FECHA_FIN,
          usuarioId: uId,
          fecha: fecha,
          turno: turno,
          litros: valorNuevo === '' ? null : parseFloat(valorNuevo)
        })
      });

      const res = await resp.json();
      td.classList.remove('is-saving');

      if (resp.ok && res.ok) {
        // Feedback: Guardado con éxito
        td.classList.add('is-saved');
        input.dataset.prev = valorNuevo;
        if (valorNuevo !== '') {
          input.value = parseFloat(valorNuevo).toFixed(1);
        }

        // Actualizar totales en pantalla con los valores oficiales devueltos por el servidor
        actualizarTotalesUI(uId, `${fecha}_${turno}`, res.totalGanadero, res.totalColumna, res.granTotal);
      } else {
        td.classList.add('is-error');
        alert(res.error || 'Error al guardar el pesaje.');
        input.value = input.dataset.prev || '';
      }
    } catch (err) {
      td.classList.remove('is-saving');
      td.classList.add('is-error');
      console.error(err);
      alert('Error de conexión con el servidor. Verifica tu red.');
      input.value = input.dataset.prev || '';
    }
  }

  // Pegar bloque copiado desde Excel
  async function pegarBloqueExcel(celdaOrigen, textoPegado) {
    const tdOrigen = celdaOrigen.closest('td');
    const rBase = parseInt(tdOrigen.dataset.r, 10);
    const cBase = parseInt(tdOrigen.dataset.c, 10);

    const lineas = textoPegado.trim().split(/\r\n|\r|\n/);
    const itemsLote = [];
    const celdasAfectadas = [];

    lineas.forEach((linea, rOff) => {
      const celdasTexto = linea.split('\t');
      celdasTexto.forEach((valStr, cOff) => {
        const td = tablaMatriz.querySelector(`td[data-r="${rBase + rOff}"][data-c="${cBase + cOff}"]`);
        if (td) {
          const inp = td.querySelector('input');
          const valLim = valStr.trim().replace(',', '.');
          const uId = td.dataset.uid;
          const fecha = td.dataset.fecha;
          const turno = td.dataset.turno;

          let litrosVal = null;
          if (valLim !== '') {
            const num = parseFloat(valLim);
            if (!isNaN(num) && num >= 0 && num <= 9999) {
              litrosVal = num;
            }
          }

          itemsLote.push({
            usuarioId: uId,
            fecha: fecha,
            turno: turno,
            litros: litrosVal
          });

          celdasAfectadas.push({ td, inp, litrosVal });
          td.classList.add('is-saving');
        }
      });
    });

    if (itemsLote.length === 0) return;

    try {
      const resp = await fetch('api/guardar_pesaje_matriz.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          csrf_token: CSRF_TOKEN,
          action: 'batch',
          fechaInicio: FECHA_INICIO,
          fechaFin: FECHA_FIN,
          items: itemsLote
        })
      });

      const res = await resp.json();

      celdasAfectadas.forEach(({ td, inp, litrosVal }) => {
        td.classList.remove('is-saving');
        if (resp.ok && res.ok) {
          td.classList.add('is-saved');
          if (litrosVal !== null) {
            inp.value = litrosVal.toFixed(1);
            inp.dataset.prev = inp.value;
            inp.className = (litrosVal === 0) ? 'matrix-input is-zero' : 'matrix-input';
          } else {
            inp.value = '';
            inp.dataset.prev = '';
            inp.className = 'matrix-input is-empty';
          }
        } else {
          td.classList.add('is-error');
        }
      });

      if (resp.ok && res.ok) {
        // Actualizar totales masivos recibidos
        if (res.totalesGanaderos) {
          for (const [gId, totG] of Object.entries(res.totalesGanaderos)) {
            const el = document.getElementById(`tot-g-${gId}`);
            if (el) el.textContent = formatLitros(totG);
          }
        }
        // Actualizar celdas unificadas de cada día
        const fechasLote = new Set(itemsLote.map(i => i.fecha));
        fechasLote.forEach(f => recalcularTotalDiaDOM(f));

        if (res.granTotal !== undefined) {
          actualizarGranTotalUI(res.granTotal);
        }

        if (window.AsoagroUI) {
          window.AsoagroUI.showSuccess('Pegado Exitoso', res.mensaje || 'Datos pegados desde Excel guardados en lote.');
        }
      } else {
        alert(res.error || 'Error al procesar el lote pegado.');
      }
    } catch (err) {
      console.error(err);
      celdasAfectadas.forEach(({ td }) => {
        td.classList.remove('is-saving');
        td.classList.add('is-error');
      });
      alert('Error al guardar el bloque pegado.');
    }
  }

  // Recalcular y actualizar la celda unificada de un día en el pie de tabla
  function recalcularTotalDiaDOM(fecha) {
    const elTotDia = document.getElementById(`tot-dia-${fecha}`);
    if (!elTotDia || !tablaMatriz) return;
    let suma = 0;
    const celdas = tablaMatriz.querySelectorAll(`td[data-fecha="${fecha}"] input`);
    celdas.forEach(inp => {
      const v = parseFloat(inp.value);
      if (!isNaN(v) && v > 0) {
        suma += v;
      }
    });
    elTotDia.textContent = suma > 0 ? (suma.toFixed(1)).replace('.', ',') : '-';
  }

  // Actualizar totales en DOM
  function actualizarTotalesUI(uId, colKey, totalG, totalCol, granTot) {
    const elTotG = document.getElementById(`tot-g-${uId}`);
    if (elTotG && totalG !== undefined) {
      elTotG.textContent = formatLitros(totalG);
    }

    if (colKey) {
      const fecha = colKey.split('_')[0];
      recalcularTotalDiaDOM(fecha);
    }

    if (granTot !== undefined) {
      actualizarGranTotalUI(granTot);
    }
  }

  function actualizarGranTotalUI(granTot) {
    const elGT = document.getElementById('gran-total-matriz');
    if (elGT) elGT.textContent = formatLitros(granTot);

    const kpiGT = document.getElementById('kpi-gran-total');
    if (kpiGT) kpiGT.textContent = formatLitros(granTot);

    const kpiLiq = document.getElementById('kpi-liquidacion');
    if (kpiLiq && PRECIO_LITRO > 0) {
      kpiLiq.textContent = window.AsoagroUI?.formatCOP(granTot * PRECIO_LITRO) || `$ ${Math.round(granTot * PRECIO_LITRO)}`;
    }

    const diasCount = <?php echo (int)$cantDias; ?>;
    const kpiProm = document.getElementById('kpi-promedio-diario');
    if (kpiProm && diasCount > 0) {
      kpiProm.textContent = (granTot / diasCount).toFixed(1).replace('.', ',') + ' L';
    }
  }

  function formatLitros(val) {
    if (val === null || val === undefined) return '0,0 L';
    return (parseFloat(val).toFixed(1)).replace('.', ',') + ' L';
  }

  // 3. FILTRO EN VIVO DE GANADEROS EN LA PLANILLA
  const filtroInput = document.getElementById('filtro-matriz');
  if (filtroInput && tablaMatriz) {
    filtroInput.addEventListener('input', function () {
      const q = this.value.trim().toLowerCase();
      const filas = tablaMatriz.querySelectorAll('tbody tr.fila-matriz');
      let visibles = 0;

      filas.forEach((f) => {
        const texto = f.dataset.texto || '';
        if (!q || texto.includes(q)) {
          f.style.display = '';
          visibles++;
        } else {
          f.style.display = 'none';
        }
      });

      const contador = document.getElementById('contador-filas-visibles');
      if (contador) {
        contador.textContent = `${visibles} asociados visibles`;
      }
    });
  }

  // 4. DESCARGAR PLANILLA MATRICIAL EN CSV
  const btnCsv = document.getElementById('btn-exportar-csv');
  if (btnCsv && tablaMatriz) {
    btnCsv.addEventListener('click', function () {
      let csv = [];
      const trs = tablaMatriz.querySelectorAll('tr');

      trs.forEach((tr) => {
        if (tr.style.display === 'none') return;
        const row = [];
        tr.querySelectorAll('th, td').forEach((cell) => {
          const inp = cell.querySelector('input');
          let text = inp ? inp.value.trim() : cell.innerText.trim();
          text = text.replace(/"/g, '""');
          row.push(`"${text}"`);
        });
        csv.push(row.join(';'));
      });

      const csvContent = '\uFEFF' + csv.join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.setAttribute('href', url);
      link.setAttribute('download', `Planilla_Matricial_${FECHA_INICIO}_a_${FECHA_FIN}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });
  }

  // 4b. DESCARGAR PLANILLA MATRICIAL EN EXCEL (.XLSX) CON SHEETJS
  const btnXlsx = document.getElementById('btn-exportar-xlsx');
  if (btnXlsx && tablaMatriz) {
    btnXlsx.addEventListener('click', function () {
      if (typeof XLSX === 'undefined') {
        alert('Librería SheetJS no disponible.');
        return;
      }
      try {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(tablaMatriz, { raw: false });
        XLSX.utils.book_append_sheet(wb, ws, 'Control Lechero');
        XLSX.writeFile(wb, `Planilla_Matricial_${FECHA_INICIO}_a_${FECHA_FIN}.xlsx`);
      } catch (err) {
        console.error(err);
        alert('Error al generar archivo Excel: ' + err.message);
      }
    });
  }

  // 5. IMPORTAR PLANILLA MATRICIAL DESDE EXCEL (.XLSX, .XLS, .CSV)
  const btnImportarExcel = document.getElementById('btn-importar-excel');
  const inputImportarExcel = document.getElementById('input-importar-excel');

  if (btnImportarExcel && inputImportarExcel && tablaMatriz && !QUINCENA_CERRADA) {
    btnImportarExcel.addEventListener('click', () => {
      inputImportarExcel.value = '';
      inputImportarExcel.click();
    });

    inputImportarExcel.addEventListener('change', async function (e) {
      const file = e.target.files[0];
      if (!file) return;

      const ext = file.name.split('.').pop().toLowerCase();
      if (!['xlsx', 'xls', 'csv'].includes(ext)) {
        if (window.AsoagroUI) {
          window.AsoagroUI.showWarning('Formato No Soportado', 'Por favor seleccione un archivo .xlsx, .xls o .csv');
        } else {
          alert('Por favor seleccione un archivo .xlsx, .xls o .csv');
        }
        return;
      }

      btnImportarExcel.disabled = true;
      const originalHtml = btnImportarExcel.innerHTML;
      btnImportarExcel.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';

      try {
        let rows = [];

        if (ext === 'csv') {
          const text = await file.text();
          const lines = text.split(/\r\n|\r|\n/);
          rows = lines.map(line => {
            if (!line.trim()) return [];
            const sep = line.includes(';') ? ';' : ',';
            const regex = new RegExp(`(?:^|${sep})(?:"([^"]*(?:""[^"]*)*)"|([^"${sep}]*))`, 'g');
            const cols = [];
            let m;
            while ((m = regex.exec(line)) !== null) {
              cols.push((m[1] !== undefined ? m[1].replace(/""/g, '"') : m[2] || '').trim());
            }
            return cols;
          }).filter(r => r.length > 0);
        } else {
          if (typeof XLSX === 'undefined') {
            throw new Error('Librería SheetJS aún no ha cargado. Verifique su conexión a internet.');
          }
          const data = await file.arrayBuffer();
          const workbook = XLSX.read(data, { type: 'array' });
          const firstSheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[firstSheetName];
          rows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
        }

        if (!rows || rows.length === 0) {
          throw new Error('El archivo seleccionado no contiene filas o está vacío.');
        }

        const diasQuincenaList = <?php echo json_encode(array_values($diasQuincena)); ?>;
        const totalDias = diasQuincenaList.length;

        // Mapa de ganaderos en el DOM
        const filasAsociados = Array.from(tablaMatriz.querySelectorAll('tbody tr.fila-matriz'));
        const mapaGanaderos = new Map();

        filasAsociados.forEach(tr => {
          const uId = tr.dataset.uid;
          const cedula = (tr.dataset.cedula || '').trim();
          if (cedula) mapaGanaderos.set(cedula, { uId, tr });

          const divNombre = tr.querySelector('.col-sticky-name div.fw-bold');
          if (divNombre) {
            const nomLimpio = divNombre.textContent.trim().toLowerCase();
            mapaGanaderos.set(nomLimpio, { uId, tr });
          }
        });

        const itemsLote = [];
        const celdasAfectadas = [];
        let asociadosIdentificados = 0;

        rows.forEach(row => {
          if (!row || row.length === 0) return;
          const textoFila = row.join(' ').toLowerCase();

          // Omitir filas de encabezado o pie de totales
          if (textoFila.includes('asociado') || textoFila.includes('cédula') || textoFila.includes('cedula') || textoFila.includes('totales por') || textoFila.includes('total por día') || textoFila.includes('total por dia')) {
            return;
          }

          let ganaderoEncontrado = null;
          const col0 = String(row[0] || '').trim();

          // Buscar cédula numérica en columna 0
          const matchCed = col0.match(/\b(\d{6,11})\b/);
          if (matchCed && mapaGanaderos.has(matchCed[1])) {
            ganaderoEncontrado = mapaGanaderos.get(matchCed[1]);
          } else if (mapaGanaderos.has(col0)) {
            ganaderoEncontrado = mapaGanaderos.get(col0);
          } else if (row[1] && mapaGanaderos.has(String(row[1]).trim())) {
            ganaderoEncontrado = mapaGanaderos.get(String(row[1]).trim());
          } else {
            for (const [key, val] of mapaGanaderos.entries()) {
              if (col0.toLowerCase().includes(key) || key.includes(col0.toLowerCase())) {
                ganaderoEncontrado = val;
                break;
              }
            }
          }

          if (!ganaderoEncontrado) return;
          asociadosIdentificados++;

          const uId = ganaderoEncontrado.uId;
          const tr = ganaderoEncontrado.tr;

          let cOffset = 1;
          if (row.length > (totalDias * 2 + 1) && !isNaN(parseFloat(String(row[2]).replace(',', '.')))) {
            cOffset = 2;
          }

          diasQuincenaList.forEach((d, dIdx) => {
            const fecha = d.fecha;
            const valM_raw = row[cOffset + (dIdx * 2)];
            const valT_raw = row[cOffset + (dIdx * 2) + 1];

            const procesarValor = (raw, turno) => {
              if (raw === undefined || raw === null) return;
              const strVal = String(raw).trim().replace(',', '.');
              const td = tr.querySelector(`td[data-fecha="${fecha}"][data-turno="${turno}"]`);
              if (!td) return;
              const inp = td.querySelector('input');

              if (strVal === '-' || strVal === '') {
                itemsLote.push({ usuarioId: uId, fecha: fecha, turno: turno, litros: null });
                celdasAfectadas.push({ td, inp, litrosVal: null });
              } else {
                const num = parseFloat(strVal);
                if (!isNaN(num) && num >= 0 && num <= 9999) {
                  itemsLote.push({ usuarioId: uId, fecha: fecha, turno: turno, litros: num });
                  celdasAfectadas.push({ td, inp, litrosVal: num });
                }
              }
            };

            procesarValor(valM_raw, 'MANANA');
            procesarValor(valT_raw, 'TARDE');
          });
        });

        if (itemsLote.length === 0) {
          throw new Error('No se encontraron registros de pesajes coincidentes con los asociados de esta quincena.');
        }

        // Marcar celdas como guardando
        celdasAfectadas.forEach(({ td }) => td.classList.add('is-saving'));

        // Enviar lote al backend
        const resp = await fetch('api/guardar_pesaje_matriz.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            csrf_token: CSRF_TOKEN,
            action: 'batch',
            fechaInicio: FECHA_INICIO,
            fechaFin: FECHA_FIN,
            items: itemsLote
          })
        });

        const res = await resp.json();

        celdasAfectadas.forEach(({ td, inp, litrosVal }) => {
          td.classList.remove('is-saving');
          if (resp.ok && res.ok) {
            td.classList.add('is-saved');
            if (litrosVal !== null) {
              inp.value = litrosVal.toFixed(1);
              inp.dataset.prev = inp.value;
              inp.className = (litrosVal === 0) ? 'matrix-input is-zero' : 'matrix-input';
            } else {
              inp.value = '';
              inp.dataset.prev = '';
              inp.className = 'matrix-input is-empty';
            }
          } else {
            td.classList.add('is-error');
          }
        });

        if (resp.ok && res.ok) {
          if (res.totalesGanaderos) {
            for (const [gId, totG] of Object.entries(res.totalesGanaderos)) {
              const el = document.getElementById(`tot-g-${gId}`);
              if (el) el.textContent = formatLitros(totG);
            }
          }

          const fechasLote = new Set(itemsLote.map(i => i.fecha));
          fechasLote.forEach(f => recalcularTotalDiaDOM(f));

          if (res.granTotal !== undefined) {
            actualizarGranTotalUI(res.granTotal);
          }

          if (window.AsoagroUI) {
            window.AsoagroUI.showSuccess(
              'Importación Exitosa',
              `Se procesaron y guardaron ${res.actualizados || itemsLote.length} pesajes correspondientes a ${asociadosIdentificados} asociados.`
            );
          } else {
            alert(`Importación Exitosa: Se guardaron ${res.actualizados || itemsLote.length} pesajes de ${asociadosIdentificados} asociados.`);
          }
        } else {
          throw new Error(res.error || 'Error al guardar los datos importados.');
        }

      } catch (err) {
        console.error('Error importando archivo:', err);
        celdasAfectadas.forEach(({ td }) => {
          td.classList.remove('is-saving');
          td.classList.add('is-error');
        });
        if (window.AsoagroUI) {
          window.AsoagroUI.showError('Error en Importación', err.message || 'No fue posible importar el archivo.');
        } else {
          alert('Error en importación: ' + (err.message || 'No fue posible procesar el archivo.'));
        }
      } finally {
        btnImportarExcel.disabled = false;
        btnImportarExcel.innerHTML = originalHtml;
      }
    });
  }
});

// Helper para modal de editar ganadero
function editarGanadero(g) {
  document.getElementById('modalGanaderoLabel').innerHTML = '<i class="bi bi-pencil-fill me-2"></i> Editar Ganadero Asociado';
  document.getElementById('modal_asociado_id').value = g.id;
  document.getElementById('modal_cedula').value = g.cedula;
  document.getElementById('modal_nombre').value = g.nombre;
  document.getElementById('modal_telefono').value = g.telefono || '';
  document.getElementById('modal_finca').value = g.finca || '';
  document.getElementById('modal_password').value = '';
  document.getElementById('modal_activo').checked = (parseInt(g.activo) === 1);

  const modal = new bootstrap.Modal(document.getElementById('modalNuevoGanadero'));
  modal.show();
}

// 1. CIERRE Y REAPERTURA DE QUINCENAS CON CLAVE DE ADMINISTRADOR
function solicitarCierreQuincena() {
  document.getElementById('pass-cierre-quincena').value = '';
  const modal = new bootstrap.Modal(document.getElementById('modalConfirmarCierreQuincena'));
  modal.show();
}

function solicitarReaperturaQuincena() {
  document.getElementById('pass-reapertura-quincena').value = '';
  const modal = new bootstrap.Modal(document.getElementById('modalConfirmarReaperturaQuincena'));
  modal.show();
}

async function confirmarAccionQuincena(accion) {
  const inputId = (accion === 'cerrar') ? 'pass-cierre-quincena' : 'pass-reapertura-quincena';
  const pass = document.getElementById(inputId).value.trim();

  if (!pass) {
    if (window.AsoagroUI) {
      AsoagroUI.showWarning('Contraseña Requerida', 'Por favor ingresa tu contraseña de Administrador.');
    } else {
      alert('Por favor ingresa tu contraseña de Administrador.');
    }
    return;
  }

  Swal.fire({
    title: accion === 'cerrar' ? 'Cerrando quincena...' : 'Reabriendo quincena...',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  try {
    const res = await fetch('api/gestionar_quincena.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        csrf_token: '<?php echo generarCsrfToken(); ?>',
        accion: accion,
        fechaInicio: '<?php echo $fechaInicioSel; ?>',
        fechaFin: '<?php echo $fechaFinSel; ?>',
        password: pass
      })
    });
    const data = await res.json();

    if (res.ok && data.ok) {
      Swal.fire({
        icon: 'success',
        title: accion === 'cerrar' ? '¡Quincena Cerrada!' : '¡Quincena Reabierta!',
        text: data.mensaje,
        showConfirmButton: false,
        timer: 1400
      }).then(() => {
        location.reload();
      });
    } else {
      if (window.AsoagroUI) {
        AsoagroUI.showError('No Autorizado', data.error || 'Contraseña incorrecta.');
      } else {
        alert(data.error || 'Contraseña incorrecta.');
      }
    }
  } catch (err) {
    console.error(err);
    alert('Error al procesar la solicitud.');
  }
}

// 2. HISTORIAL DE AUDITORÍA (BITÁCORA DE CAMBIOS)
async function abrirModalBitacora() {
  const modalEl = document.getElementById('modalBitacoraAuditoria');
  const modal = new bootstrap.Modal(modalEl);
  modal.show();

  const tbody = document.getElementById('tbody-bitacora');
  tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Cargando registros de auditoría...</td></tr>';

  try {
    const res = await fetch('api/obtener_bitacora.php?limit=50');
    const data = await res.json();

    if (res.ok && data.ok && data.logs && data.logs.length > 0) {
      let html = '';
      data.logs.forEach(l => {
        let badgeAccion = 'bg-secondary';
        if (l.accion.includes('EDITAR')) badgeAccion = 'bg-primary';
        else if (l.accion.includes('CERRAR')) badgeAccion = 'bg-danger';
        else if (l.accion.includes('REABRIR')) badgeAccion = 'bg-warning text-dark';
        else if (l.accion.includes('LOGIN')) badgeAccion = 'bg-success';
        else if (l.accion.includes('EXCEL')) badgeAccion = 'bg-info text-dark';

        html += `<tr>
          <td class="font-monospace text-nowrap">${l.fecha}</td>
          <td><strong>${l.usuarioNombre}</strong></td>
          <td><span class="badge ${badgeAccion} rounded-pill">${l.accion}</span></td>
          <td class="font-monospace">${l.campo}</td>
          <td class="text-danger-emphasis">${l.valorAnterior}</td>
          <td class="text-success-emphasis fw-bold">${l.valorNuevo}</td>
          <td class="small">${l.detalles}</td>
          <td class="font-monospace text-muted">${l.ip}</td>
        </tr>`;
      });
      tbody.innerHTML = html;
    } else {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No se registran eventos de auditoría recientes.</td></tr>';
    }
  } catch (err) {
    console.error(err);
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-danger">Error cargando registros de auditoría.</td></tr>';
  }
}

// 3. GENERACIÓN DE COLILLA DE PAGO
function abrirColillaPago(ganaderoId, nombre, cedula, finca, totalLitros) {
  document.getElementById('colilla-nombre').textContent = nombre;
  document.getElementById('colilla-cedula').textContent = 'C.C. ' + cedula;
  document.getElementById('colilla-finca').textContent = finca || 'Vereda Alizal';
  document.getElementById('colilla-total-litros').textContent = (parseFloat(totalLitros || 0).toFixed(1)).replace('.', ',') + ' L';

  const precioLitro = <?php echo (float)$precioLitroActual; ?>;
  const neto = totalLitros * precioLitro;
  document.getElementById('colilla-precio-litro').textContent = precioLitro > 0 ? (window.AsoagroUI?.formatCOP(precioLitro) || '$ ' + precioLitro) : 'Por fijar';
  document.getElementById('colilla-total-neto').textContent = window.AsoagroUI?.formatCOP(neto) || '$ ' + Math.round(neto);

  // Obtener desglose diario desde la fila de la matriz
  const tbodyColilla = document.getElementById('colilla-tbody-dias');
  tbodyColilla.innerHTML = '';

  const trGanadero = document.querySelector(`tr.fila-matriz[data-uid="${ganaderoId}"]`);
  const diasQuincenaList = <?php echo json_encode(array_values($diasQuincena)); ?>;

  if (trGanadero) {
    let rowsHtml = '';
    diasQuincenaList.forEach((d, idx) => {
      const tdM = trGanadero.querySelector(`td[data-fecha="${d.fecha}"][data-turno="MANANA"] input`);
      const tdT = trGanadero.querySelector(`td[data-fecha="${d.fecha}"][data-turno="TARDE"] input`);

      const valM = tdM && tdM.value.trim() !== '' ? parseFloat(tdM.value) : 0.0;
      const valT = tdT && tdT.value.trim() !== '' ? parseFloat(tdT.value) : 0.0;
      const totDia = valM + valT;

      rowsHtml += `<tr>
        <td class="text-muted font-monospace">${d.fecha}</td>
        <td><strong>${d.diaSemana} ${d.dia}</strong></td>
        <td>${valM > 0 ? valM.toFixed(1).replace('.', ',') + ' L' : '-'}</td>
        <td>${valT > 0 ? valT.toFixed(1).replace('.', ',') + ' L' : '-'}</td>
        <td class="fw-bold text-success">${totDia > 0 ? totDia.toFixed(1).replace('.', ',') + ' L' : '-'}</td>
      </tr>`;
    });
    tbodyColilla.innerHTML = rowsHtml;
  }

  const modal = new bootstrap.Modal(document.getElementById('modalColillaPago'));
  modal.show();
}

// 4. GESTIÓN INTERACTIVA DE RECLAMOS
function abrirModalRespuestaReclamo(id, ganaderoNombre, estado, mensaje, respuesta) {
  document.getElementById('resp-alerta-id').value = id;
  document.getElementById('resp-ganadero-nombre').textContent = ganaderoNombre;
  document.getElementById('resp-mensaje-reclamo').textContent = `"${mensaje}"`;
  document.getElementById('resp-nuevo-estado').value = (estado === 'ATENDIDO') ? 'RESUELTO' : estado;
  document.getElementById('resp-texto-admin').value = respuesta || '';

  const modal = new bootstrap.Modal(document.getElementById('modalRespuestaReclamo'));
  modal.show();
}

async function guardarRespuestaReclamo() {
  const id = document.getElementById('resp-alerta-id').value;
  const nuevoEstado = document.getElementById('resp-nuevo-estado').value;
  const texto = document.getElementById('resp-texto-admin').value.trim();

  if (!texto) {
    alert('Por favor redacta la respuesta oficial para el ganadero.');
    return;
  }

  Swal.fire({ title: 'Guardando respuesta...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

  try {
    const res = await fetch('api/responder_reclamo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        csrf_token: '<?php echo generarCsrfToken(); ?>',
        alertaId: id,
        estado: nuevoEstado,
        respuesta: texto
      })
    });
    const data = await res.json();

    if (res.ok && data.ok) {
      // Actualizar DOM en la fila de la tabla
      const badge = document.getElementById(`badge-estado-${id}`);
      if (badge) {
        badge.textContent = nuevoEstado;
        badge.className = 'badge rounded-pill px-3 py-1 ' + (nuevoEstado === 'RESUELTO' ? 'bg-success' : (nuevoEstado === 'EN_REVISION' ? 'bg-warning text-dark' : 'bg-danger'));
      }

      const celdaResp = document.getElementById(`celda-resp-${id}`);
      if (celdaResp) {
        celdaResp.innerHTML = `<div class="p-2 rounded bg-light border border-success-subtle text-dark"><strong>${data.respondidoPor}:</strong> ${data.respuestaAdmin}</div>`;
      }

      const modalEl = document.getElementById('modalRespuestaReclamo');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();

      Swal.fire({
        icon: 'success',
        title: '¡Respuesta Guardada!',
        text: 'La resolución ha sido registrada y el ganadero ya puede verla en su panel.',
        showConfirmButton: false,
        timer: 1500
      });
    } else {
      AsoagroUI.showError('Error', data.error || 'No se pudo guardar la respuesta.');
    }
  } catch (err) {
    console.error(err);
    alert('Error al guardar la respuesta.');
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
