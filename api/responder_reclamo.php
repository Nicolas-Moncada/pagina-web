<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: api/responder_reclamo.php
 * Propósito: Gestión interactiva de estados y respuesta oficial a inconformidades quincenales.
 * =========================================================================================
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once __DIR__ . '/auth_middleware.php';

header('Content-Type: application/json; charset=utf-8');

$usuarioAdmin = requireAdminAuth(false);
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$csrfToken     = $data['csrf_token'] ?? '';
$alertaId      = trim($data['alertaId'] ?? '');
$nuevoEstado   = trim($data['estado'] ?? 'EN_REVISION');
$respuestaText = trim($data['respuesta'] ?? '');

if (!validarCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido o vencido. Recarga la página.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($alertaId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Identificador de inconformidad requerido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$estadosPermitidos = ['PENDIENTE', 'EN_REVISION', 'RESUELTO', 'ATENDIDO'];
if (!in_array($nuevoEstado, $estadosPermitidos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Estado no reconocido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 1. Obtener datos actuales del reclamo
    $stmtAlerta = $pdo->prepare("SELECT a.id, a.usuarioId, a.estado, a.fechaInicio, a.fechaFin, a.mensaje, u.nombre AS ganaderoNombre 
                                 FROM alertaquincena a 
                                 LEFT JOIN usuario u ON u.id = a.usuarioId 
                                 WHERE a.id = ? LIMIT 1");
    $stmtAlerta->execute([$alertaId]);
    $alerta = $stmtAlerta->fetch();

    if (!$alerta) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Inconformidad no encontrada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $estadoPrevio = $alerta['estado'];
    $nombreAdmin = $usuarioAdmin['nombre'] ?: 'Administrador General';

    // 2. Actualizar en alertaquincena
    $stmtUpd = $pdo->prepare("UPDATE alertaquincena 
                              SET estado = ?, 
                                  respuestaAdmin = ?, 
                                  respondidoEn = NOW(), 
                                  respondidoPor = ?, 
                                  atendida = ? 
                              WHERE id = ?");
    $atendida = ($nuevoEstado === 'RESUELTO' || $nuevoEstado === 'ATENDIDO') ? 1 : 0;
    $stmtUpd->execute([$nuevoEstado, $respuestaText, $nombreAdmin, $atendida, $alertaId]);

    // También actualizar en tabla quejas si existiera
    try {
        $pdo->prepare("UPDATE quejas SET estado = ? WHERE id = ?")->execute([$nuevoEstado, $alertaId]);
    } catch (Exception $e) {}

    // 3. Registrar en Bitácora de Auditoría
    registrarBitacoraCambio(
        $pdo,
        'RESPONDER_RECLAMO',
        'alertaquincena',
        $alertaId,
        'estado',
        $estadoPrevio,
        $nuevoEstado,
        "Respuesta a reclamo de {$alerta['ganaderoNombre']} (Periodo {$alerta['fechaInicio']} a {$alerta['fechaFin']}). Respuesta: " . substr($respuestaText, 0, 100),
        $usuarioAdmin['id']
    );

    echo json_encode([
        'ok' => true,
        'mensaje' => "El estado del reclamo se actualizó a '{$nuevoEstado}' y la respuesta oficial fue guardada exitosamente.",
        'estado' => $nuevoEstado,
        'respuestaAdmin' => $respuestaText,
        'respondidoPor' => $nombreAdmin,
        'respondidoEn' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en api/responder_reclamo.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno al actualizar la inconformidad.'], JSON_UNESCAPED_UNICODE);
}
