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

// 1. CONTROL DE ACCESO ESTRICTO (ROL ADMINISTRADOR)
$usuario = requireAuth('ADMIN');
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
                            $hash = password_hash($password, PASSWORD_DEFAULT);
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
                        $hash = password_hash($claveAsignar, PASSWORD_DEFAULT);
                        $newId = generarUUID('usr');

                        $ins = $pdo->prepare("INSERT INTO usuario (id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, sessionVersion, creadoEn)
                                              VALUES (?, ?, ?, ?, ?, ?, 'GANADERO', ?, 1, 1, NOW())");
                        $ins->execute([$newId, $cedula, $nombre, $telefono, $finca, $hash, $activo]);
                        setFlashAlert('success', '¡Asociado Creado!', "Ganadero registrado correctamente. Clave temporal asignada: {$claveAsignar}");
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
                $hash = password_hash($claveTemp, PASSWORD_DEFAULT);
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

// 7. CONSULTAS PARA BUZÓN
$stmtAlertas = $pdo->query("SELECT a.id, a.usuarioId, a.fechaInicio, a.fechaFin, a.mensaje, a.litrosAlMomento, a.estado, a.creadoEn,
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
<div class="bg-body-tertiary border-bottom py-2">
  <div class="container-fluid px-3 px-md-4">
    <ul class="nav nav-pills gap-2 flex-wrap" id="pills-tab" role="tablist">
      <li class="nav-item">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'planilla') ? 'active bg-success text-white' : ''; ?> fw-bold" href="admin.php?tab=planilla&fechaInicio=<?php echo $fechaInicioSel; ?>&fechaFin=<?php echo $fechaFinSel; ?>">
          <i class="bi bi-grid-3x3-gap-fill me-1"></i> Planilla Matricial de Pesajes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'ganaderos') ? 'active bg-success text-white' : ''; ?> fw-bold" href="admin.php?tab=ganaderos">
          <i class="bi bi-people-fill me-1"></i> Gestión de Asociados (<?php echo count($listaTodosGanaderos); ?>)
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'quincenas') ? 'active bg-success text-white' : ''; ?> fw-bold" href="admin.php?tab=quincenas&fechaInicio=<?php echo $fechaInicioSel; ?>&fechaFin=<?php echo $fechaFinSel; ?>">
          <i class="bi bi-cash-coin me-1"></i> Precios de Quincena
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'buzon') ? 'active bg-success text-white' : ''; ?> fw-bold position-relative" href="admin.php?tab=buzon">
          <i class="bi bi-inbox-fill me-1"></i> Buzón de Inconformidades y Consultas
          <?php if (count($listaAlertas) > 0): ?>
            <span class="badge rounded-pill bg-danger ms-1"><?php echo count($listaAlertas); ?></span>
          <?php endif; ?>
        </a>
      </li>
    </ul>
  </div>
</div>

<main class="container-fluid px-3 px-md-4 py-4 flex-grow-1">

  <!-- ====================================================================
       PESTAÑA 1: PLANILLA MATRICIAL HORIZONTAL TIPO EXCEL
       ==================================================================== -->
  <?php if ($pestañaActiva === 'planilla'): ?>

    <!-- TARJETAS DE RESUMEN SUPERIORES -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 text-center border-start border-4 border-success">
          <small class="text-muted fw-bold text-uppercase">Litros Recolectados</small>
          <div class="display-6 fw-extrabold text-success my-1" id="kpi-gran-total">
            <?php echo number_format($granTotalMatriz, 1, ',', '.'); ?> L
          </div>
          <small class="text-muted">Total quincena en acopio</small>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 text-center border-start border-4 border-primary">
          <small class="text-muted fw-bold text-uppercase">Promedio Diario</small>
          <div class="display-6 fw-extrabold text-primary my-1" id="kpi-promedio-diario">
            <?php echo number_format($promedioDiarioGeneral, 1, ',', '.'); ?> L
          </div>
          <small class="text-muted"><?php echo $cantDias; ?> días en el periodo</small>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 text-center border-start border-4 border-warning">
          <small class="text-muted fw-bold text-uppercase">Total a Liquidar</small>
          <div class="display-6 fw-extrabold text-warning-emphasis my-1" id="kpi-liquidacion">
            <?php echo formatoMonedaCOP($totalLiquidacionGeneral); ?>
          </div>
          <small class="text-muted">Tarifa: <?php echo $precioLitroActual ? formatoMonedaCOP($precioLitroActual) . ' / L' : 'Por fijar'; ?></small>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 text-center border-start border-4 border-info">
          <small class="text-muted fw-bold text-uppercase">Asociados en Planilla</small>
          <div class="display-6 fw-extrabold text-info-emphasis my-1">
            <?php echo $totalGanaderosActivos; ?>
          </div>
          <small class="text-muted">Ganaderos activos registrados</small>
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
                <?php echo !empty($q['cerrada']) ? ' 🔒 (Cerrada)' : ' 🟢 (En curso)'; ?>
              </option>
            <?php endforeach; ?>
          </select>

          <a href="admin.php?tab=planilla&fechaInicio=<?php echo $qSig['fechaInicio']; ?>&fechaFin=<?php echo $qSig['fechaFin']; ?>" class="btn btn-touch btn-outline-secondary btn-sm rounded-pill" title="Quincena siguiente">
            Siguiente <i class="bi bi-chevron-right"></i>
          </a>

          <span class="badge <?php echo $quincenaEstaCerrada ? 'bg-secondary' : 'bg-success'; ?> px-3 py-2 rounded-pill ms-lg-2">
            <?php echo $quincenaEstaCerrada ? '🔒 Cerrada' : '🟢 Abierta para edición'; ?>
          </span>
        </div>

        <!-- Buscador en tiempo real de ganaderos y exportación -->
        <div class="d-flex flex-wrap align-items-center gap-2">
          <div class="input-group input-group-sm" style="min-width: 240px;">
            <span class="input-group-text bg-transparent border-2 text-success">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" id="filtro-matriz" class="form-control form-control-touch form-control-sm border-start-0" placeholder="Buscar por nombre o cédula...">
          </div>

          <button type="button" id="btn-exportar-csv" class="btn btn-touch btn-outline-success btn-sm rounded-pill">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> Descargar CSV
          </button>
        </div>
      </div>

      <!-- Leyenda de Uso -->
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 mt-2 border-top border-secondary-subtle small text-muted">
        <div>
          <span class="me-3"><strong class="text-warning-emphasis">☀️ Mañana:</strong> Primer turno</span>
          <span class="me-3"><strong class="text-danger-emphasis">🌅 Tarde:</strong> Segundo turno</span>
          <span class="me-3">⌨️ <strong>Enter:</strong> Baja celda &bull; <strong>Tab / Flechas:</strong> Navega &bull; <strong>Esc:</strong> Cancela</span>
          <span>📋 <strong>Pegar desde Excel:</strong> Selecciona una celda y presiona Ctrl + V para pegar en bloque</span>
        </div>
        <div id="contador-filas-visibles" class="fw-bold text-success">
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
                <th class="py-1 px-1 bg-warning-subtle text-dark">☀️ M</th>
                <th class="py-1 px-1 bg-danger-subtle text-dark col-border-day">🌅 T</th>
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
                <tr class="fila-matriz" data-uid="<?php echo e($g['id']); ?>" data-idx="<?php echo $idx; ?>" data-texto="<?php echo strtolower(e($g['nombre'] . ' ' . $g['cedula'])); ?>">
                  <!-- Columna Asociado Fija a la Izquierda -->
                  <td class="col-sticky-name">
                    <div class="fw-bold text-truncate" style="max-width: 210px;" title="<?php echo e($g['nombre']); ?>">
                      <?php echo e($g['nombre']); ?>
                    </div>
                    <div class="text-muted font-monospace small">
                      C.C. <?php echo e($g['cedula']); ?><?php if (!empty($g['finca'])) echo ' &bull; ' . e($g['finca']); ?>
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
          <!-- Fila de Totales de Columna Fija Abajo -->
          <tfoot>
            <tr>
              <td class="col-sticky-name text-uppercase">
                TOTALES POR TURNO (L)
              </td>
              <?php foreach ($diasQuincena as $d): ?>
                <?php 
                  $totColM = $totalesPorColumna["{$d['fecha']}_MANANA"] ?? 0.0;
                  $totColT = $totalesPorColumna["{$d['fecha']}_TARDE"] ?? 0.0;
                ?>
                <td class="text-center" id="tot-col-<?php echo e($d['fecha']); ?>_MANANA">
                  <?php echo $totColM > 0 ? number_format($totColM, 1, ',', '.') : '-'; ?>
                </td>
                <td class="text-center col-border-day" id="tot-col-<?php echo e($d['fecha']); ?>_TARDE">
                  <?php echo $totColT > 0 ? number_format($totColT, 1, ',', '.') : '-'; ?>
                </td>
              <?php endforeach; ?>
              <td class="col-sticky-total text-end" id="gran-total-matriz">
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
                        <span class="badge bg-secondary">🔒 Cerrada</span>
                      <?php else: ?>
                        <span class="badge bg-success">🟢 Activa</span>
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
                  <th>Fecha Radicación</th>
                  <th>Ganadero</th>
                  <th>Periodo</th>
                  <th class="text-end">Litros al Reclamar</th>
                  <th>Detalle de la Inconformidad</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($listaAlertas as $alt): ?>
                  <tr>
                    <td class="small text-muted"><?php echo formatoFechaEspanol($alt['creadoEn']); ?></td>
                    <td>
                      <strong><?php echo e($alt['ganaderoNombre']); ?></strong><br>
                      <small class="text-muted font-monospace">C.C. <?php echo e($alt['ganaderoCedula']); ?></small>
                    </td>
                    <td class="small"><?php echo e($alt['fechaInicio']); ?> al <?php echo e($alt['fechaFin']); ?></td>
                    <td class="text-end fw-bold text-success"><?php echo formatoLitros($alt['litrosAlMomento']); ?></td>
                    <td class="small fst-italic" style="max-width: 320px;">
                      "<?php echo e($alt['mensaje']); ?>"
                    </td>
                    <td class="text-center">
                      <span class="badge bg-<?php echo ($alt['estado'] === 'PENDIENTE') ? 'danger' : 'success'; ?>">
                        <?php echo e($alt['estado']); ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <form action="admin.php?tab=buzon" method="POST" class="d-inline">
                        <?php echo campoCsrf(); ?>
                        <input type="hidden" name="accion" value="atender_reclamo">
                        <input type="hidden" name="alertaId" value="<?php echo e($alt['id']); ?>">
                        <input type="hidden" name="estado" value="<?php echo ($alt['estado'] === 'PENDIENTE') ? 'ATENDIDO' : 'PENDIENTE'; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3">
                          Marcar <?php echo ($alt['estado'] === 'PENDIENTE') ? 'Atendido' : 'Pendiente'; ?>
                        </button>
                      </form>
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
        if (res.totalesColumnas) {
          for (const [colKey, totC] of Object.entries(res.totalesColumnas)) {
            const el = document.getElementById(`tot-col-${colKey}`);
            if (el) el.textContent = totC > 0 ? totC.toFixed(1) : '-';
          }
        }
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

  // Actualizar totales en DOM
  function actualizarTotalesUI(uId, colKey, totalG, totalCol, granTot) {
    const elTotG = document.getElementById(`tot-g-${uId}`);
    if (elTotG && totalG !== undefined) {
      elTotG.textContent = formatLitros(totalG);
    }

    const elTotC = document.getElementById(`tot-col-${colKey}`);
    if (elTotC && totalCol !== undefined) {
      elTotC.textContent = totalCol > 0 ? totalCol.toFixed(1) : '-';
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
