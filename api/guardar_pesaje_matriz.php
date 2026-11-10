<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: api/guardar_pesaje_matriz.php
 * Propósito: Endpoint seguro para actualización inline y por lotes (Excel) de la planilla.
 * Seguridad: Solo ADMIN, CSRF, Bloqueo si liquidada, Transacciones y Recálculo en Servidor.
 * =========================================================================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/auth_middleware.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Control de acceso estricto (solo Administrador verificado por middleware)
$usuarioAdmin = requireAdminAuth(false);
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

// 2. Validación de Token CSRF
$tokenRecibido = $data['csrf_token'] ?? '';
if (!validarCsrfToken($tokenRecibido)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'La sesión de seguridad ha expirado. Recarga la página.']);
    exit;
}

$fechaInicioQ = trim($data['fechaInicio'] ?? '');
$fechaFinQ    = trim($data['fechaFin'] ?? '');

if (empty($fechaInicioQ) || empty($fechaFinQ)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Rango de fechas de quincena requerido.']);
    exit;
}

// 3. Verificar si la quincena está cerrada / liquidada (bloqueo estricto)
$stmtQ = $pdo->prepare("SELECT cerrada FROM quincena WHERE fechaInicio = ? AND fechaFin = ? LIMIT 1");
$stmtQ->execute([$fechaInicioQ, $fechaFinQ]);
$quincenaRow = $stmtQ->fetch();

if ($quincenaRow && !empty($quincenaRow['cerrada'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Esta quincena está marcada como CERRADA y liquidada. No se permite editar pesajes.']);
    exit;
}

$action = $data['action'] ?? 'single';

// --------------------------------------------------------------------------
// MODO A: ACTUALIZACIÓN POR LOTES (PEGAR DESDE EXCEL / CLIPBOARD)
// --------------------------------------------------------------------------
if ($action === 'batch') {
    $items = $data['items'] ?? [];
    if (!is_array($items) || empty($items)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No se recibieron datos para procesar en lote.']);
        exit;
    }

    $pdo->beginTransaction();
    $ganaderosAfectados = [];
    $columnasAfectadas = [];
    $actualizados = 0;

    try {
        $stmtGanaderoActivo = $pdo->prepare("SELECT id FROM usuario WHERE id = ? AND rol = 'GANADERO' AND activo = 1 LIMIT 1");
        $stmtUpsertEntrega = $pdo->prepare("INSERT INTO entrega (id, usuarioId, fecha, turno, litros, creadoEn)
                                            VALUES (?, ?, ?, ?, ?, NOW())
                                            ON DUPLICATE KEY UPDATE litros = VALUES(litros)");
        $stmtUpsertReg = $pdo->prepare("INSERT INTO registros_leche (id, asociadoId, fecha, turno, litros, creadoEn, actualizadoEn)
                                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                                        ON DUPLICATE KEY UPDATE litros = VALUES(litros), actualizadoEn = NOW()");
        $stmtDeleteEntrega = $pdo->prepare("DELETE FROM entrega WHERE usuarioId = ? AND fecha = ? AND turno = ?");
        $stmtDeleteReg = $pdo->prepare("DELETE FROM registros_leche WHERE asociadoId = ? AND fecha = ? AND turno = ?");

        foreach ($items as $item) {
            $uId   = trim($item['usuarioId'] ?? '');
            $fecha = trim($item['fecha'] ?? '');
            $turno = trim($item['turno'] ?? '');
            $valRaw = $item['litros'];

            if (empty($uId) || empty($fecha) || !in_array($turno, ['MANANA', 'TARDE'])) {
                continue;
            }

            // Validar que la fecha pertenezca al periodo
            if ($fecha < $fechaInicioQ || $fecha > $fechaFinQ) {
                continue;
            }

            // Validar que el ganadero esté activo
            $stmtGanaderoActivo->execute([$uId]);
            if (!$stmtGanaderoActivo->fetch()) {
                continue;
            }

            $ganaderosAfectados[$uId] = true;
            $columnasAfectadas["{$fecha}_{$turno}"] = ['fecha' => $fecha, 'turno' => $turno];

            if ($valRaw === null || $valRaw === '') {
                // Eliminar registro
                $stmtDeleteEntrega->execute([$uId, $fecha, $turno]);
                $stmtDeleteReg->execute([$uId, $fecha, $turno]);
                $actualizados++;
            } else {
                $litros = (float)str_replace(',', '.', (string)$valRaw);
                if ($litros < 0 || $litros > 9999) {
                    continue;
                }
                $nuevoId = generarUUID('ent');
                $stmtUpsertEntrega->execute([$nuevoId, $uId, $fecha, $turno, $litros]);
                $stmtUpsertReg->execute([$nuevoId, $uId, $fecha, $turno, $litros]);
                $actualizados++;
            }
        }

        $pdo->commit();

        // Recalcular totales oficiales de ganaderos y columnas afectadas
        $totalesGanaderos = [];
        $stmtTotG = $pdo->prepare("SELECT SUM(litros) FROM entrega WHERE usuarioId = ? AND fecha >= ? AND fecha <= ?");
        foreach (array_keys($ganaderosAfectados) as $gId) {
            $stmtTotG->execute([$gId, $fechaInicioQ, $fechaFinQ]);
            $totalesGanaderos[$gId] = (float)($stmtTotG->fetchColumn() ?: 0.0);
        }

        $totalesColumnas = [];
        $stmtTotC = $pdo->prepare("SELECT SUM(litros) FROM entrega WHERE fecha = ? AND turno = ?");
        foreach ($columnasAfectadas as $k => $c) {
            $stmtTotC->execute([$c['fecha'], $c['turno']]);
            $totalesColumnas[$k] = (float)($stmtTotC->fetchColumn() ?: 0.0);
        }

        // Totales Diarios Unificados (M + T)
        $totalesDias = [];
        $stmtTotD = $pdo->prepare("SELECT SUM(litros) FROM entrega WHERE fecha = ?");
        foreach ($columnasAfectadas as $c) {
            if (!isset($totalesDias[$c['fecha']])) {
                $stmtTotD->execute([$c['fecha']]);
                $totalesDias[$c['fecha']] = (float)($stmtTotD->fetchColumn() ?: 0.0);
            }
        }

        $stmtGranTot = $pdo->prepare("SELECT SUM(litros) FROM entrega WHERE fecha >= ? AND fecha <= ?");
        $stmtGranTot->execute([$fechaInicioQ, $fechaFinQ]);
        $granTotal = (float)($stmtGranTot->fetchColumn() ?: 0.0);

        registrarAuditoria($pdo, 'PLANILLA_LOTE_EXCEL', $usuarioAdmin['id'], $usuarioAdmin['cedula'], "Actualizados: $actualizados pesajes");
        registrarBitacoraCambio($pdo, 'IMPORTAR_EXCEL_LOTE', 'entrega', $fechaInicioQ . '_' . $fechaFinQ, 'litros', null, null, "Importación masiva: $actualizados pesajes actualizados", $usuarioAdmin['id']);

        echo json_encode([
            'ok' => true,
            'mensaje' => "Se actualizaron {$actualizados} casillas correctamente.",
            'actualizados' => $actualizados,
            'totalesGanaderos' => $totalesGanaderos,
            'totalesColumnas' => $totalesColumnas,
            'totalesDias' => $totalesDias,
            'granTotal' => $granTotal
        ]);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error en guardado por lote de planilla: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al guardar los datos en lote.']);
        exit;
    }
}

// --------------------------------------------------------------------------
// MODO B: ACTUALIZACIÓN INDIVIDUAL DE CELDA INLINE
// --------------------------------------------------------------------------
$usuarioId = trim($data['usuarioId'] ?? '');
$fecha     = trim($data['fecha'] ?? '');
$turno     = trim($data['turno'] ?? '');
$valRaw    = $data['litros'] ?? null;

if (empty($usuarioId) || empty($fecha) || !in_array($turno, ['MANANA', 'TARDE'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parámetros de pesaje incompletos.']);
    exit;
}

if ($fecha < $fechaInicioQ || $fecha > $fechaFinQ) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'La fecha no corresponde a la quincena seleccionada.']);
    exit;
}

// Validar ganadero activo
$stmtG = $pdo->prepare("SELECT id, nombre FROM usuario WHERE id = ? AND rol = 'GANADERO' AND activo = 1 LIMIT 1");
$stmtG->execute([$usuarioId]);
$ganadero = $stmtG->fetch();
if (!$ganadero) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El ganadero asociado no está activo en el sistema.']);
    exit;
}

try {
    // Obtener valor anterior para registro de auditoría
    $stmtPrev = $pdo->prepare("SELECT litros FROM entrega WHERE usuarioId = ? AND fecha = ? AND turno = ? LIMIT 1");
    $stmtPrev->execute([$usuarioId, $fecha, $turno]);
    $valorPrevio = $stmtPrev->fetchColumn();

    $nuevoValor = null;

    if ($valRaw === null || $valRaw === '') {
        // Eliminar registro
        $pdo->prepare("DELETE FROM entrega WHERE usuarioId = ? AND fecha = ? AND turno = ?")->execute([$usuarioId, $fecha, $turno]);
        $pdo->prepare("DELETE FROM registros_leche WHERE asociadoId = ? AND fecha = ? AND turno = ?")->execute([$usuarioId, $fecha, $turno]);
    } else {
        $litros = (float)str_replace(',', '.', (string)$valRaw);
        if ($litros < 0 || $litros > 9999) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La cantidad de litros debe ser mayor o igual a 0 y menor a 9.999 L.']);
            exit;
        }

        $nuevoValor = $litros;
        $idEnt = generarUUID('ent');

        $stmtE = $pdo->prepare("INSERT INTO entrega (id, usuarioId, fecha, turno, litros, creadoEn)
                                VALUES (?, ?, ?, ?, ?, NOW())
                                ON DUPLICATE KEY UPDATE litros = VALUES(litros)");
        $stmtE->execute([$idEnt, $usuarioId, $fecha, $turno, $litros]);

        $stmtR = $pdo->prepare("INSERT INTO registros_leche (id, asociadoId, fecha, turno, litros, creadoEn, actualizadoEn)
                                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                                ON DUPLICATE KEY UPDATE litros = VALUES(litros), actualizadoEn = NOW()");
        $stmtR->execute([$idEnt, $usuarioId, $fecha, $turno, $litros]);
    }

    // Registrar en Bitácora de Auditoría si el valor cambió efectivamente
    $valPrevioNum = ($valorPrevio !== false && $valorPrevio !== null) ? (float)$valorPrevio : null;
    if ($valPrevioNum !== $nuevoValor) {
        $txtAnt = ($valPrevioNum !== null) ? number_format($valPrevioNum, 1) . ' L' : 'Vacío';
        $txtNue = ($nuevoValor !== null) ? number_format($nuevoValor, 1) . ' L' : 'Eliminado';
        registrarBitacoraCambio(
            $pdo,
            'EDITAR_PESAJE',
            'entrega',
            $usuarioId,
            "{$fecha} [{$turno}]",
            $txtAnt,
            $txtNue,
            "Pesaje {$turno} ({$fecha}) modificado para {$ganadero['nombre']}",
            $usuarioAdmin['id']
        );
    }

    // Recálculo seguro en servidor
    $stmtTotG = $pdo->prepare("SELECT SUM(litros) FROM entrega WHERE usuarioId = ? AND fecha >= ? AND fecha <= ?");
    $stmtTotG->execute([$usuarioId, $fechaInicioQ, $fechaFinQ]);
    $totalGanadero = (float)($stmtTotG->fetchColumn() ?: 0.0);

    $stmtTotC = $pdo->prepare("SELECT SUM(litros) FROM entrega WHERE fecha = ? AND turno = ?");
    $stmtTotC->execute([$fecha, $turno]);
    $totalColumna = (float)($stmtTotC->fetchColumn() ?: 0.0);

    // Total del Día Unificado (Mañana + Tarde)
    $stmtTotDia = $pdo->prepare("SELECT SUM(litros) FROM entrega WHERE fecha = ?");
    $stmtTotDia->execute([$fecha]);
    $totalDia = (float)($stmtTotDia->fetchColumn() ?: 0.0);

    $stmtGranTot = $pdo->prepare("SELECT SUM(litros) FROM entrega WHERE fecha >= ? AND fecha <= ?");
    $stmtGranTot->execute([$fechaInicioQ, $fechaFinQ]);
    $granTotal = (float)($stmtGranTot->fetchColumn() ?: 0.0);

    echo json_encode([
        'ok' => true,
        'litros' => $nuevoValor,
        'totalGanadero' => $totalGanadero,
        'totalColumna' => $totalColumna,
        'totalDia' => $totalDia,
        'granTotal' => $granTotal
    ]);
    exit;
} catch (Exception $e) {
    error_log("Error en api/guardar_pesaje_matriz: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Ocurrió un error al guardar el pesaje en la base de datos.']);
    exit;
}
