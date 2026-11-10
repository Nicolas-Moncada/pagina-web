<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: api/gestionar_quincena.php
 * Propósito: Cierre y reapertura segura de quincenas con confirmación de clave de administrador.
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

$csrfToken    = $data['csrf_token'] ?? '';
$accion       = trim($data['accion'] ?? '');
$fechaInicio  = trim($data['fechaInicio'] ?? '');
$fechaFin     = trim($data['fechaFin'] ?? '');
$adminPassword = (string)($data['password'] ?? '');

if (!validarCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido o vencido. Recarga la página.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($fechaInicio) || empty($fechaFin) || !in_array($accion, ['cerrar', 'reabrir'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parámetros de quincena o acción inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($adminPassword)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Debes ingresar tu contraseña de Administrador para confirmar esta acción.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Verificar contraseña del administrador en caliente
try {
    $stmtAdmin = $pdo->prepare("SELECT passwordHash, nombre FROM usuario WHERE id = ? AND rol = 'ADMIN' LIMIT 1");
    $stmtAdmin->execute([$usuarioAdmin['id']]);
    $adminRow = $stmtAdmin->fetch();

    if (!$adminRow || !password_verify($adminPassword, $adminRow['passwordHash'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Contraseña de Administrador incorrecta. Acción cancelada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $nuevoEstadoCerrada = ($accion === 'cerrar') ? 1 : 0;
    $nombreAdmin = $adminRow['nombre'] ?: $usuarioAdmin['nombre'];

    // 2. Actualizar estado en tabla quincena
    $stmtUpd = $pdo->prepare("UPDATE quincena SET cerrada = ?, cerradaPor = ?, cerradaEn = ? WHERE fechaInicio = ? AND fechaFin = ?");
    $stmtUpd->execute([
        $nuevoEstadoCerrada,
        ($nuevoEstadoCerrada ? $nombreAdmin : null),
        ($nuevoEstadoCerrada ? date('Y-m-d H:i:s') : null),
        $fechaInicio,
        $fechaFin
    ]);

    // También actualizar en configuracion_quincena si existiera
    try {
        $pdo->prepare("UPDATE configuracion_quincena SET cerrada = ? WHERE fechaInicio = ? AND fechaFin = ?")->execute([$nuevoEstadoCerrada, $fechaInicio, $fechaFin]);
    } catch (Exception $e) {}

    // 3. Registrar en Bitácora de Auditoría
    $accionBitacora = ($nuevoEstadoCerrada ? 'CERRAR_QUINCENA' : 'REABRIR_QUINCENA');
    $detalles = ($nuevoEstadoCerrada
        ? "Quincena {$fechaInicio} a {$fechaFin} CERRADA por {$nombreAdmin}"
        : "Quincena {$fechaInicio} a {$fechaFin} REABIERTA por {$nombreAdmin}");

    registrarBitacoraCambio(
        $pdo,
        $accionBitacora,
        'quincena',
        "{$fechaInicio}_{$fechaFin}",
        'cerrada',
        ($nuevoEstadoCerrada ? 'Abierta' : 'Cerrada'),
        ($nuevoEstadoCerrada ? 'Cerrada' : 'Abierta'),
        $detalles,
        $usuarioAdmin['id']
    );

    $msg = ($nuevoEstadoCerrada
        ? "La quincena ha sido CERRADA y liquidada exitosamente. La edición inline queda congelada."
        : "La quincena ha sido REABIERTA exitosamente. Ya es posible editar los pesajes nuevamente.");

    echo json_encode([
        'ok' => true,
        'cerrada' => (bool)$nuevoEstadoCerrada,
        'mensaje' => $msg
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en api/gestionar_quincena.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno procesando la solicitud.'], JSON_UNESCAPED_UNICODE);
}
