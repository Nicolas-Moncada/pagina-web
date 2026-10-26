<?php
// ================================================================
// GENERACIÓN DE CONTRASEÑA TEMPORAL PARA GANADEROS (FLUJO A)
// Exclusivo para Administrador tras verificación presencial o telefónica
// ================================================================

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$admin = requireAuth('ADMIN');
$pdo = getDB();
$data = getRequestData();

$solicitudId = trim($data['solicitudId'] ?? '');
$asociadoId = trim($data['asociadoId'] ?? '');

if (empty($solicitudId) && empty($asociadoId)) {
    jsonResponse(['error' => 'ID de solicitud o asociado requerido.'], 400);
}

try {
    // 1. Localizar asociado
    if (!empty($solicitudId)) {
        $stmtSol = $pdo->prepare("SELECT * FROM solicitudes_recuperacion WHERE id = ? LIMIT 1");
        $stmtSol->execute([$solicitudId]);
        $sol = $stmtSol->fetch();
        if (!$sol) {
            jsonResponse(['error' => 'Solicitud de restablecimiento no encontrada.'], 404);
        }
        $asociadoId = $sol['usuarioId'];
    }

    $stmtAsoc = $pdo->prepare("SELECT id, cedula, nombre, rol FROM asociados WHERE id = ? LIMIT 1");
    $stmtAsoc->execute([$asociadoId]);
    $asociado = $stmtAsoc->fetch();

    if (!$asociado) {
        $stmtU = $pdo->prepare("SELECT id, cedula, nombre, rol FROM usuario WHERE id = ? LIMIT 1");
        $stmtU->execute([$asociadoId]);
        $asociado = $stmtU->fetch();
    }

    if (!$asociado) {
        jsonResponse(['error' => 'El asociado ganadero no existe.'], 404);
    }

    if ($asociado['rol'] === 'ADMIN') {
        jsonResponse(['error' => 'No se puede generar clave temporal para cuentas de administrador desde este módulo.'], 403);
    }

    // 2. Generar contraseña temporal criptográfica de un solo uso (10 caracteres)
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $tempPass = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < 10; $i++) {
        $tempPass .= $chars[random_int(0, $max)];
    }

    $hashTemp = password_hash($tempPass, PASSWORD_BCRYPT);

    // 3. Actualizar base de datos: forzar cambio de clave e invalidar sesiones existentes
    $pdo->prepare("UPDATE asociados SET passwordHash = ?, debeCambiarPassword = 1, sessionVersion = sessionVersion + 1 WHERE id = ?")
        ->execute([$hashTemp, $asociadoId]);

    $pdo->prepare("UPDATE usuario SET passwordHash = ?, debeCambiarPassword = 1, sessionVersion = sessionVersion + 1 WHERE id = ?")
        ->execute([$hashTemp, $asociadoId]);

    // 4. Actualizar solicitudes pendientes
    if (!empty($solicitudId)) {
        $pdo->prepare("UPDATE solicitudes_recuperacion SET estado = 'ATENDIDA', atendidoPor = ?, atendidoEn = NOW() WHERE id = ?")
            ->execute([$admin['id'], $solicitudId]);
    } else {
        $pdo->prepare("UPDATE solicitudes_recuperacion SET estado = 'ATENDIDA', atendidoPor = ?, atendidoEn = NOW() WHERE usuarioId = ? AND estado = 'PENDIENTE'")
            ->execute([$admin['id'], $asociadoId]);
    }

    // 5. Registrar auditoría de seguridad
    registrarAuditoria($pdo, 'CLAVE_TEMPORAL_GENERADA', $asociado['id'], $asociado['cedula'], 'Generada por: ' . $admin['nombre']);

    jsonResponse([
        'ok' => true,
        'mensaje' => 'Contraseña temporal generada con éxito.',
        'claveTemporal' => $tempPass,
        'nombre' => $asociado['nombre'],
        'cedula' => $asociado['cedula'],
        'advertencia' => 'Esta contraseña temporal se muestra una sola vez. El ganadero será obligado a cambiarla en su siguiente ingreso.'
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error al generar la contraseña temporal: ' . $e->getMessage()], 500);
}

