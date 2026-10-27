<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: admin.php
 * Propósito: Panel de Administración General (SSR con PDO, DataTables BS5, filtros y exportación).
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

// 2. DETECCIÓN Y FILTROS DE QUINCENA, ASOCIADO Y TURNO
$quincenaActual = calcularQuincenaActual();
$fechaInicioSel = $_GET['fechaInicio'] ?? $quincenaActual['fechaInicio'];
$fechaFinSel    = $_GET['fechaFin'] ?? $quincenaActual['fechaFin'];
$filtroUsuario  = trim($_GET['usuarioId'] ?? '');
$filtroTurno    = trim($_GET['turno'] ?? '');
$pestañaActiva  = $_GET['tab'] ?? 'planilla';

// 3. PROCESAMIENTO DE ACCIONES VÍA POST CON PROTECCIÓN CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecibido = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($tokenRecibido)) {
        setFlashAlert('error', 'Error de Seguridad', 'El token CSRF ha vencido. Por favor recarga la página.');
        header("Location: admin.php?tab={$pestañaActiva}&fechaInicio={$fechaInicioSel}&fechaFin={$fechaFinSel}");
        exit;
    }

    $accion = $_POST['accion'] ?? '';

    // ACCIÓN A: REGISTRAR O ACTUALIZAR PESAJE DE LECHE
    if ($accion === 'guardar_pesaje') {
        $ganaderoId = trim($_POST['usuarioId'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        $turno = trim($_POST['turno'] ?? '');
        $litros = (float)str_replace(',', '.', trim($_POST['litros'] ?? '0'));

        if (empty($ganaderoId) || empty($fecha) || empty($turno) || $litros <= 0) {
            setFlashAlert('warning', 'Campos Incompletos', 'Verifica que hayas seleccionado ganadero, fecha, turno y litros válidos (> 0).');
        } elseif (!in_array($turno, ['MANANA', 'TARDE'])) {
            setFlashAlert('error', 'Turno Inválido', 'El turno debe ser Mañana o Tarde.');
        } else {
            try {
                $entregaId = generarUUID('ent');
                $stmt = $pdo->prepare("INSERT INTO entrega (id, usuarioId, fecha, turno, litros, creadoEn, actualizadoEn)
                                       VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                                       ON DUPLICATE KEY UPDATE litros = VALUES(litros), actualizadoEn = NOW()");
                $stmt->execute([$entregaId, $ganaderoId, $fecha, $turno, $litros]);

                registrarAuditoria($pdo, 'PESAJE_GUARDADO', $usuario['id'], $usuario['cedula'], "Ganadero: {$ganaderoId}, Fecha: {$fecha}, Turno: {$turno}, Litros: {$litros}");
                setFlashAlert('success', '¡Pesaje Guardado!', "Se registraron " . formatoLitros($litros) . " exitosamente.");
            } catch (Exception $e) {
                error_log("Error guardando pesaje: " . $e->getMessage());
                setFlashAlert('error', 'Error en Base de Datos', 'No fue posible guardar el pesaje.');
            }
        }
        header("Location: admin.php?tab=planilla&fechaInicio={$fechaInicioSel}&fechaFin={$fechaFinSel}");
        exit;
    }

    // ACCIÓN B: ELIMINAR UN PESAJE
    if ($accion === 'eliminar_pesaje') {
        $idEntrega = trim($_POST['entregaId'] ?? '');
        if (!empty($idEntrega)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM entrega WHERE id = ?");
                $stmt->execute([$idEntrega]);
                registrarAuditoria($pdo, 'PESAJE_ELIMINADO', $usuario['id'], $usuario['cedula'], "Entrega ID: {$idEntrega}");
                setFlashAlert('success', 'Pesaje Eliminado', 'El registro de pesaje fue borrado de la base de datos.');
            } catch (Exception $e) {
                error_log("Error eliminando pesaje: " . $e->getMessage());
                setFlashAlert('error', 'Error', 'No se pudo eliminar el pesaje.');
            }
        }
        header("Location: admin.php?tab=planilla&fechaInicio={$fechaInicioSel}&fechaFin={$fechaFinSel}");
        exit;
    }

    // ACCIÓN C: CREAR O EDITAR GANADERO ASOCIADO
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
                    // Editar asociado existente
                    $chk = $pdo->prepare("SELECT id FROM usuario WHERE cedula = ? AND id != ? LIMIT 1");
                    $chk->execute([$cedula, $asocId]);
                    if ($chk->fetch()) {
                        setFlashAlert('error', 'Cédula Duplicada', 'Ya existe otro ganadero registrado con este número de cédula.');
                    } else {
                        if (!empty($password)) {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            $upd = $pdo->prepare("UPDATE usuario SET cedula = ?, nombre = ?, telefono = ?, finca = ?, passwordHash = ?, activo = ?, sessionVersion = sessionVersion + 1 WHERE id = ?");
                            $upd->execute([$cedula, $nombre, $telefono, $finca, $hash, $activo, $asocId]);
                        } else {
                            $upd = $pdo->prepare("UPDATE usuario SET cedula = ?, nombre = ?, telefono = ?, finca = ?, activo = ? WHERE id = ?");
                            $upd->execute([$cedula, $nombre, $telefono, $finca, $activo, $asocId]);
                        }
                        registrarAuditoria($pdo, 'GANADERO_ACTUALIZADO', $usuario['id'], $usuario['cedula'], "ID: {$asocId}, Cédula: {$cedula}");
                        setFlashAlert('success', 'Asociado Actualizado', 'Los datos del ganadero se actualizaron con éxito.');
                    }
                } else {
                    // Crear nuevo asociado
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

                        registrarAuditoria($pdo, 'GANADERO_CREADO', $usuario['id'], $usuario['cedula'], "Nuevo Ganadero: {$nombre} (C.C. {$cedula})");
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

    // ACCIÓN D: REESTABLECER CLAVE TEMPORAL
    if ($accion === 'reset_clave_ganadero') {
        $asocId = trim($_POST['asociadoId'] ?? '');
        if (!empty($asocId)) {
            try {
                $claveTemp = '123456';
                $hash = password_hash($claveTemp, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE usuario SET passwordHash = ?, debeCambiarPassword = 1, sessionVersion = sessionVersion + 1 WHERE id = ?");
                $upd->execute([$hash, $asocId]);

                registrarAuditoria($pdo, 'RESET_CLAVE_GANADERO', $usuario['id'], $usuario['cedula'], "Ganadero ID: $asocId");
                setFlashAlert('success', 'Clave Reestablecida', "Se ha asignado la clave temporal '{$claveTemp}' y se forzará cambio en su próximo ingreso.");
            } catch (Exception $e) {
                setFlashAlert('error', 'Error', 'No se pudo reestablecer la clave.');
            }
        }
        header("Location: admin.php?tab=ganaderos");
        exit;
    }

    // ACCIÓN E: FIJAR O ACTUALIZAR PRECIO DE LA QUINCENA
    if ($accion === 'fijar_precio_quincena') {
        $fInicio = trim($_POST['fechaInicio'] ?? '');
        $fFin    = trim($_POST['fechaFin'] ?? '');
        $precio  = (float)str_replace(',', '.', trim($_POST['precioLitro'] ?? '0'));
        $cerrada = isset($_POST['cerrada']) ? 1 : 0;

        if (empty($fInicio) || empty($fFin) || $precio < 0) {
            setFlashAlert('warning', 'Datos Inválidos', 'Debes ingresar un rango de fechas válido y un precio mayor o igual a cero.');
        } else {
            try {
                $qId = generarUUID('q');
                $stmtQIns = $pdo->prepare("INSERT INTO quincena (id, fechaInicio, fechaFin, precioLitro, cerrada, creadaEn)
                                           VALUES (?, ?, ?, ?, ?, NOW())
                                           ON DUPLICATE KEY UPDATE precioLitro = VALUES(precioLitro), cerrada = VALUES(cerrada)");
                $stmtQIns->execute([$qId, $fInicio, $fFin, $precio, $cerrada]);

                registrarAuditoria($pdo, 'PRECIO_QUINCENA_ACTUALIZADO', $usuario['id'], $usuario['cedula'], "Periodo: {$fInicio} al {$fFin}, Precio: {$precio}");
                setFlashAlert('success', 'Precio Configurado', "Tarifa de " . formatoMonedaCOP($precio) . " fijada para el periodo seleccionado.");
            } catch (Exception $e) {
                error_log("Error fijando precio: " . $e->getMessage());
                setFlashAlert('error', 'Error', 'No se pudo actualizar el precio de la quincena.');
            }
        }
        header("Location: admin.php?tab=quincenas&fechaInicio={$fInicio}&fechaFin={$fFin}");
        exit;
    }

    // ACCIÓN F: ATENDER INCONFORMIDAD
    if ($accion === 'atender_reclamo') {
        $alertaId = trim($_POST['alertaId'] ?? '');
        $nuevoEstado = trim($_POST['estado'] ?? 'REVISADO');
        if (!empty($alertaId)) {
            try {
                $updAlt = $pdo->prepare("UPDATE alertaquincena SET estado = ?, atendida = 1 WHERE id = ?");
                $updAlt->execute([$nuevoEstado, $alertaId]);
                setFlashAlert('success', 'Estado Actualizado', "La inconformidad ha pasado al estado: {$nuevoEstado}");
            } catch (Exception $e) {
                setFlashAlert('error', 'Error', 'No se pudo actualizar el estado del reclamo.');
            }
        }
        header("Location: admin.php?tab=buzon");
        exit;
    }

    // ACCIÓN G: ATENDER CONSULTA WEB
    if ($accion === 'atender_consulta') {
        $conId = trim($_POST['consultaId'] ?? '');
        if (!empty($conId)) {
            try {
                $updCon = $pdo->prepare("UPDATE consulta SET atendida = 1 WHERE id = ?");
                $updCon->execute([$conId]);
                setFlashAlert('success', 'Consulta Atendida', 'La consulta pública se ha marcado como resuelta.');
            } catch (Exception $e) {
                setFlashAlert('error', 'Error', 'No se pudo actualizar la consulta.');
            }
        }
        header("Location: admin.php?tab=buzon");
        exit;
    }
}

// 4. CONSULTA DE QUINCENAS EXISTENTES PARA SELECTORES
$stmtQAll = $pdo->query("SELECT fechaInicio, fechaFin, precioLitro, cerrada FROM quincena ORDER BY fechaInicio DESC LIMIT 24");
$quincenasDisponibles = $stmtQAll->fetchAll();
if (empty($quincenasDisponibles)) {
    $quincenasDisponibles = [[
        'fechaInicio' => $quincenaActual['fechaInicio'],
        'fechaFin'    => $quincenaActual['fechaFin'],
        'precioLitro' => 2250,
        'cerrada'     => 0
    ]];
}

// Precio de la quincena activa filtrada
$stmtQAct = $pdo->prepare("SELECT precioLitro, cerrada FROM quincena WHERE fechaInicio = ? AND fechaFin = ? LIMIT 1");
$stmtQAct->execute([$fechaInicioSel, $fechaFinSel]);
$quincenaSelData = $stmtQAct->fetch();
$precioLitroActual = $quincenaSelData ? (float)$quincenaSelData['precioLitro'] : 0.0;
$quincenaEstaCerrada = !empty($quincenaSelData['cerrada']);

// 5. CONSULTA GENERAL DE ASOCIADOS GANADEROS
$stmtGanaderos = $pdo->query("SELECT id, cedula, nombre, telefono, finca, activo, creadoEn FROM usuario WHERE rol = 'GANADERO' ORDER BY nombre ASC");
$listaGanaderos = $stmtGanaderos->fetchAll();

// 6. CONSULTA DE ENTREGAS FILTRADAS CON JOINS Y CONSULTAS PREPARADAS (NINGUNA TABLA ESTÁTICA)
$sqlEntregas = "SELECT e.id, e.usuarioId, e.fecha, e.turno, e.litros, e.creadoEn, 
                       u.nombre AS ganaderoNombre, u.cedula AS ganaderoCedula, u.finca
                FROM entrega e
                JOIN usuario u ON e.usuarioId = u.id
                WHERE e.fecha >= :fIni AND e.fecha <= :fFin";
$paramsEntregas = [
    ':fIni' => $fechaInicioSel,
    ':fFin' => $fechaFinSel
];

if (!empty($filtroUsuario)) {
    $sqlEntregas .= " AND e.usuarioId = :uid";
    $paramsEntregas[':uid'] = $filtroUsuario;
}

if (!empty($filtroTurno) && in_array($filtroTurno, ['MANANA', 'TARDE'])) {
    $sqlEntregas .= " AND e.turno = :turno";
    $paramsEntregas[':turno'] = $filtroTurno;
}

$sqlEntregas .= " ORDER BY e.fecha DESC, e.turno DESC, u.nombre ASC";

$stmtEnt = $pdo->prepare($sqlEntregas);
$stmtEnt->execute($paramsEntregas);
$entregasFiltradas = $stmtEnt->fetchAll();

// 7. CÁLCULO DE MÉTRICAS RESUMEN QUINCENALES
$totalLitrosQuincena = 0.0;
$ganaderosConEntrega = [];
$diasRegistrados = [];

foreach ($entregasFiltradas as $ef) {
    $totalLitrosQuincena += (float)$ef['litros'];
    $ganaderosConEntrega[$ef['usuarioId']] = true;
    $diasRegistrados[$ef['fecha']] = true;
}

$totalGanaderosActivos = count($listaGanaderos);
$cantidadGanaderosEntregando = count($ganaderosConEntrega);
$cantDias = count($diasRegistrados);
$promedioDiarioGeneral = ($cantDias > 0) ? ($totalLitrosQuincena / $cantDias) : 0.0;
$totalLiquidacionGeneral = $precioLitroActual ? ($totalLitrosQuincena * $precioLitroActual) : 0.0;

// 8. CONSULTA DE INCONFORMIDADES Y MENSAJES DE CONTACTO
$stmtAlertas = $pdo->query("SELECT a.id, a.usuarioId, a.fechaInicio, a.fechaFin, a.mensaje, a.litrosAlMomento, a.estado, a.creadoEn,
                                   u.nombre AS ganaderoNombre, u.cedula AS ganaderoCedula
                            FROM alertaquincena a
                            JOIN usuario u ON a.usuarioId = u.id
                            ORDER BY a.creadoEn DESC");
$listaAlertas = $stmtAlertas->fetchAll();

$stmtConsultas = $pdo->query("SELECT id, nombre, contacto, mensaje, fecha, atendida FROM consulta ORDER BY fecha DESC LIMIT 50");
$listaConsultas = $stmtConsultas->fetchAll();

// Configuración de vista para header compartido
$tituloPagina = 'Panel de Administración | ' . EMPRESA_SIGLA;
$usarDataTables = true;
$navbarFluido = true;

require_once __DIR__ . '/includes/header.php';
?>

<!-- ========================================================================
     BARRA DE PESTAÑAS PRINCIPALES (MÓDULOS DEL ADMINISTRADOR)
     ======================================================================== -->
<div class="bg-body-tertiary border-bottom py-2">
  <div class="container-fluid px-3 px-md-4">
    <ul class="nav nav-pills gap-2 flex-wrap" id="pills-tab" role="tablist">
      <li class="nav-item" role="presentation">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'planilla') ? 'active bg-success text-white' : ''; ?> fw-bold" href="admin.php?tab=planilla&fechaInicio=<?php echo $fechaInicioSel; ?>&fechaFin=<?php echo $fechaFinSel; ?>">
          <i class="bi bi-clipboard2-data-fill me-1"></i> Planilla Quincenal de Pesajes
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'ganaderos') ? 'active bg-success text-white' : ''; ?> fw-bold" href="admin.php?tab=ganaderos">
          <i class="bi bi-people-fill me-1"></i> Gestión de Asociados (<?php echo $totalGanaderosActivos; ?>)
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'quincenas') ? 'active bg-success text-white' : ''; ?> fw-bold" href="admin.php?tab=quincenas&fechaInicio=<?php echo $fechaInicioSel; ?>&fechaFin=<?php echo $fechaFinSel; ?>">
          <i class="bi bi-cash-coin me-1"></i> Precios de Quincena
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link nav-link-custom <?php echo ($pestañaActiva === 'buzon') ? 'active bg-success text-white' : ''; ?> fw-bold position-relative" href="admin.php?tab=buzon">
          <i class="bi bi-inbox-fill me-1"></i> Buzón de Inconformidades y Consultas
          <?php if (count($listaAlertas) > 0): ?>
            <span class="badge rounded-pill bg-danger ms-1">
              <?php echo count($listaAlertas); ?>
            </span>
          <?php endif; ?>
        </a>
      </li>
    </ul>
  </div>
</div>

<!-- ========================================================================
     CONTENIDO PRINCIPAL
     ======================================================================== -->
<main class="container-fluid px-3 px-md-4 py-4 flex-grow-1">

  <!-- ====================================================================
       PESTAÑA 1: PLANILLA QUINCENAL DE LITROS
       ==================================================================== -->
  <?php if ($pestañaActiva === 'planilla'): ?>

    <!-- 4 TARJETAS RESUMEN SUPERIORES -->
    <div class="row g-3 mb-4">
      <!-- Litros de la Quincena -->
      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 p-md-4 text-center border-start border-4 border-success">
          <small class="text-muted fw-bold text-uppercase">Litros Recolectados</small>
          <div class="display-6 fw-extrabold text-success my-2">
            <?php echo number_format($totalLitrosQuincena, 1, ',', '.'); ?> L
          </div>
          <small class="text-muted">Total acopiado en el periodo</small>
        </div>
      </div>

      <!-- Promedio Diario -->
      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 p-md-4 text-center border-start border-4 border-primary">
          <small class="text-muted fw-bold text-uppercase">Promedio Diario</small>
          <div class="display-6 fw-extrabold text-primary my-2">
            <?php echo number_format($promedioDiarioGeneral, 1, ',', '.'); ?> L
          </div>
          <small class="text-muted">Litros/día promedio</small>
        </div>
      </div>

      <!-- Total a Liquidar (COP) -->
      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 p-md-4 text-center border-start border-4 border-warning">
          <small class="text-muted fw-bold text-uppercase">Total a Liquidar</small>
          <div class="display-6 fw-extrabold text-warning-emphasis my-2">
            <?php echo formatoMonedaCOP($totalLiquidacionGeneral); ?>
          </div>
          <small class="text-muted">A precio de <?php echo formatoMonedaCOP($precioLitroActual); ?> / L</small>
        </div>
      </div>

      <!-- Asociados Entregando -->
      <div class="col-md-3 col-6">
        <div class="card card-rural p-3 p-md-4 text-center border-start border-4 border-info">
          <small class="text-muted fw-bold text-uppercase">Ganaderos con Entrega</small>
          <div class="display-6 fw-extrabold text-info-emphasis my-2">
            <?php echo $cantidadGanaderosEntregando; ?> / <?php echo $totalGanaderosActivos; ?>
          </div>
          <small class="text-muted">Asociados activos en quincena</small>
        </div>
      </div>
    </div>

    <!-- FORMULARIO DE REGISTRO RÁPIDO DE PESAJE -->
    <div class="card card-rural p-4 mb-4 border-2 border-success-subtle shadow-sm">
      <h5 class="fw-bold text-success mb-3">
        <i class="bi bi-plus-circle-fill me-1"></i> Registrar o Modificar Pesaje de Entrega
      </h5>

      <form action="admin.php?tab=planilla&fechaInicio=<?php echo $fechaInicioSel; ?>&fechaFin=<?php echo $fechaFinSel; ?>" method="POST" class="row g-3 align-items-end">
        <?php echo campoCsrf(); ?>
        <input type="hidden" name="accion" value="guardar_pesaje">

        <div class="col-md-4">
          <label for="usuarioId" class="form-label fw-bold small text-muted">Ganadero Asociado *</label>
          <select name="usuarioId" id="usuarioId" class="form-select form-select-touch" required>
            <option value="">-- Seleccionar Ganadero --</option>
            <?php foreach ($listaGanaderos as $g): ?>
              <option value="<?php echo e($g['id']); ?>" <?php echo ($filtroUsuario === $g['id']) ? 'selected' : ''; ?>>
                <?php echo e($g['nombre']); ?> &bull; C.C. <?php echo e($g['cedula']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label for="fecha" class="form-label fw-bold small text-muted">Fecha de Entrega *</label>
          <input type="date" name="fecha" id="fecha" class="form-control form-control-touch" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="col-md-2">
          <label for="turno" class="form-label fw-bold small text-muted">Turno *</label>
          <select name="turno" id="turno" class="form-select form-select-touch" required>
            <option value="MANANA">☀️ Mañana</option>
            <option value="TARDE">🌅 Tarde</option>
          </select>
        </div>

        <div class="col-md-2">
          <label for="litros" class="form-label fw-bold small text-muted">Litros (L) *</label>
          <input type="number" step="0.1" min="0.1" name="litros" id="litros" class="form-control form-control-touch fw-bold fs-5 text-success" placeholder="Ej: 85.5" required>
        </div>

        <div class="col-md-1">
          <button type="submit" class="btn btn-touch btn-aso-primary w-100 shadow" title="Guardar pesaje">
            <i class="bi bi-save-fill"></i>
          </button>
        </div>
      </form>
    </div>

    <!-- BARRA DE FILTROS POR QUINCENA, ASOCIADO Y TURNO -->
    <div class="card card-rural p-3 mb-4 bg-body-tertiary">
      <form action="admin.php" method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="tab" value="planilla">

        <div class="col-md-4">
          <label class="form-label small fw-bold text-muted mb-1">Filtrar Quincena:</label>
          <select name="quincena_selector" class="form-select form-select-sm" onchange="const p = this.value.split('|'); location.href='admin.php?tab=planilla&fechaInicio=' + p[0] + '&fechaFin=' + p[1] + '&usuarioId=<?php echo urlencode($filtroUsuario); ?>&turno=<?php echo urlencode($filtroTurno); ?>';">
            <?php foreach ($quincenasDisponibles as $q): ?>
              <?php $sel = ($q['fechaInicio'] === $fechaInicioSel && $q['fechaFin'] === $fechaFinSel) ? 'selected' : ''; ?>
              <option value="<?php echo e($q['fechaInicio'] . '|' . $q['fechaFin']); ?>" <?php echo $sel; ?>>
                <?php echo formatoFechaEspanol($q['fechaInicio']); ?> al <?php echo formatoFechaEspanol($q['fechaFin']); ?>
                <?php echo !empty($q['cerrada']) ? ' (Cerrada)' : ' (Activa)'; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-bold text-muted mb-1">Filtrar Ganadero:</label>
          <select name="usuarioId" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">-- Todos los Asociados --</option>
            <?php foreach ($listaGanaderos as $g): ?>
              <option value="<?php echo e($g['id']); ?>" <?php echo ($filtroUsuario === $g['id']) ? 'selected' : ''; ?>>
                <?php echo e($g['nombre']); ?> (C.C. <?php echo e($g['cedula']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-bold text-muted mb-1">Filtrar Turno:</label>
          <select name="turno" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">-- Todos los Turnos --</option>
            <option value="MANANA" <?php echo ($filtroTurno === 'MANANA') ? 'selected' : ''; ?>>☀️ Mañana</option>
            <option value="TARDE" <?php echo ($filtroTurno === 'TARDE') ? 'selected' : ''; ?>>🌅 Tarde</option>
          </select>
        </div>

        <div class="col-md-1 text-end mt-auto">
          <a href="admin.php?tab=planilla&fechaInicio=<?php echo $fechaInicioSel; ?>&fechaFin=<?php echo $fechaFinSel; ?>" class="btn btn-sm btn-outline-secondary w-100 rounded-pill" title="Limpiar filtros">
            <i class="bi bi-x-circle"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- TABLA GENERAL DE PESAJES CON DATATABLES BOOTSTRAP 5 -->
    <div class="card card-rural border-0 shadow overflow-hidden">
      <div class="card-header bg-success text-white p-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
          <i class="bi bi-table me-2"></i> Planilla Oficial de Pesajes de la Quincena
        </h5>
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
          <?php echo count($entregasFiltradas); ?> Registros Filtrados
        </span>
      </div>

      <div class="card-body p-3 p-md-4">
        <?php if (empty($entregasFiltradas)): ?>
          <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h5 class="fw-bold mt-3">No hay registros de pesajes para los filtros seleccionados</h5>
            <p class="text-muted">Utiliza el formulario superior para registrar entregas de leche en esta quincena.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table id="tabla-admin-entregas" class="table table-rural table-hover align-middle w-100">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Ganadero Asociado</th>
                  <th>Cédula</th>
                  <th>Finca</th>
                  <th class="text-center">Turno</th>
                  <th class="text-end">Litros Entregados</th>
                  <th class="text-end">Valor Liquidado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $sumaTotalLitrosTabla = 0.0;
                $sumaTotalPesosTabla = 0.0;
                foreach ($entregasFiltradas as $row): 
                    $litrosRow = (float)$row['litros'];
                    $valorRow = $precioLitroActual ? ($litrosRow * $precioLitroActual) : 0.0;
                    $sumaTotalLitrosTabla += $litrosRow;
                    $sumaTotalPesosTabla += $valorRow;
                ?>
                  <tr>
                    <td><strong><?php echo e($row['fecha']); ?></strong></td>
                    <td class="fw-bold text-success"><?php echo e($row['ganaderoNombre']); ?></td>
                    <td class="font-monospace small"><?php echo e($row['ganaderoCedula']); ?></td>
                    <td class="small text-muted"><?php echo e($row['finca'] ?? 'Vereda Alizal'); ?></td>
                    <td class="text-center">
                      <?php if ($row['turno'] === 'MANANA'): ?>
                        <span class="badge-turno-manana">☀️ Mañana</span>
                      <?php else: ?>
                        <span class="badge-turno-tarde">🌅 Tarde</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end fw-extrabold text-success fs-6">
                      <?php echo number_format($litrosRow, 1, ',', '.'); ?> L
                    </td>
                    <td class="text-end fw-bold text-muted">
                      <?php echo formatoMonedaCOP($valorRow); ?>
                    </td>
                    <td class="text-center">
                      <form action="admin.php?tab=planilla&fechaInicio=<?php echo $fechaInicioSel; ?>&fechaFin=<?php echo $fechaFinSel; ?>" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este pesaje de <?php echo $litrosRow; ?> L?');" class="d-inline">
                        <?php echo campoCsrf(); ?>
                        <input type="hidden" name="accion" value="eliminar_pesaje">
                        <input type="hidden" name="entregaId" value="<?php echo e($row['id']); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-1" title="Eliminar registro">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="5" class="text-end text-uppercase">Totales en Pantalla:</th>
                  <th class="text-end text-success fs-5"><?php echo number_format($sumaTotalLitrosTabla, 1, ',', '.'); ?> L</th>
                  <th class="text-end text-warning-emphasis fs-5"><?php echo formatoMonedaCOP($sumaTotalPesosTabla); ?></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php endif; ?>
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
              <?php foreach ($listaGanaderos as $g): ?>
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
                        <button type="submit" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-2 py-1" title="Reestablecer clave">
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
            <i class="bi bi-tag-fill me-1"></i> Fijar Precio Oficial de Quincena
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
              <i class="bi bi-check-circle-fill me-1"></i> Guardar Precio Oficial
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
            <table class="table table-hover align-middle">
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
                        Editar
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
            <table class="table table-hover align-middle">
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
            <table class="table table-hover align-middle">
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

<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
      // DataTables Planilla General
      if ($('#tabla-admin-entregas').length) {
        $('#tabla-admin-entregas').DataTable({
          language: window.AsoagroUI?.dataTablesSpanish || {},
          pageLength: 25,
          responsive: true,
          order: [[0, 'desc']],
          dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
          buttons: [
            {
              extend: 'excelHtml5',
              text: '<i class="bi bi-file-earmark-excel-fill text-success me-1"></i> Excel',
              className: 'btn btn-outline-success btn-sm rounded-pill shadow-sm me-1',
              title: 'Planilla_General_AsoagroAlisal_<?php echo $fechaInicioSel; ?>'
            },
            {
              extend: 'pdfHtml5',
              text: '<i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i> PDF',
              className: 'btn btn-outline-danger btn-sm rounded-pill shadow-sm me-1',
              title: 'Planilla_Oficial_AsoagroAlisal'
            },
            {
              extend: 'print',
              text: '<i class="bi bi-printer-fill text-primary me-1"></i> Imprimir',
              className: 'btn btn-outline-primary btn-sm rounded-pill shadow-sm'
            }
          ]
        });
      }

      // DataTables Gestión de Ganaderos
      if ($('#tabla-gestion-ganaderos').length) {
        $('#tabla-gestion-ganaderos').DataTable({
          language: window.AsoagroUI?.dataTablesSpanish || {},
          pageLength: 20,
          responsive: true,
          order: [[0, 'asc']]
        });
      }
    }
  });

  // Helper para editar un ganadero en el modal
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
