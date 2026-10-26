<?php
// ================================================================
// RESTABLECIMIENTO DE CONTRASEÑA CON TOKEN DE UN SOLO USO
// Valida vigencia de 15 minutos, hash criptográfico e invalida sesiones
// ================================================================

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Referrer-Policy: no-referrer');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$pdo = getDB();
$data = getRequestData();

$token = trim($data['token'] ?? '');
$passNueva = $data['passwordNueva'] ?? ($data['nuevaPassword'] ?? '');
$passConfirm = $data['passwordConfirm'] ?? ($data['confirmarPassword'] ?? '');

if (empty($token) || strlen($token) !== 64) {
    jsonResponse(['error' => 'Token de seguridad inválido o con formato incorrecto.'], 400);
}

if (empty($passNueva) || empty($passConfirm)) {
    jsonResponse(['error' => 'Por favor ingresa y confirma tu nueva contraseña.'], 400);
}

if ($passNueva !== $passConfirm) {
    jsonResponse(['error' => 'La nueva contraseña y su confirmación no coinciden.'], 400);
}

try {
    $tokenHash = hash('sha256', $token);

    // 1. Verificar token válido, no usado y no expirado
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_recuperacion 
                           WHERE tokenHash = ? AND usado = 0 AND tokenExpira > NOW() 
                           LIMIT 1");
    $stmt->execute([$tokenHash]);
    $solicitud = $stmt->fetch();

    if (!$solicitud) {
        jsonResponse([
            'error' => 'El enlace de restablecimiento es inválido, ha expirado (más de 15 minutos) o ya fue utilizado. Por favor solicita uno nuevo.'
        ], 400);
    }

    $usuarioId = $solicitud['usuarioId'];

    // 2. Obtener usuario
    $stmtU = $pdo->prepare("SELECT id, cedula, nombre, rol, passwordHash FROM asociados WHERE id = ? LIMIT 1");
    $stmtU->execute([$usuarioId]);
    $user = $stmtU->fetch();

    if (!$user) {
        $stmtU2 = $pdo->prepare("SELECT id, cedula, nombre, rol, passwordHash FROM usuario WHERE id = ? LIMIT 1");
        $stmtU2->execute([$usuarioId]);
        $user = $stmtU2->fetch();
    }

    if (!$user) {
        jsonResponse(['error' => 'Usuario no encontrado en el sistema.'], 404);
    }

    // 3. Validar política de contraseñas según rol
    $politica = validarPoliticaPassword($passNueva, $user['rol'], $user['passwordHash']);
    if (!$politica['valido']) {
        jsonResponse(['error' => $politica['error']], 400);
    }

    // 4. Actualizar contraseña e invalidar todas las sesiones previas
    $nuevoHash = password_hash($passNueva, PASSWORD_BCRYPT);

    $pdo->prepare("UPDATE asociados SET passwordHash = ?, debeCambiarPassword = 0, sessionVersion = sessionVersion + 1 WHERE id = ?")
        ->execute([$nuevoHash, $usuarioId]);

    $pdo->prepare("UPDATE usuario SET passwordHash = ?, debeCambiarPassword = 0, sessionVersion = sessionVersion + 1 WHERE id = ?")
        ->execute([$nuevoHash, $usuarioId]);

    // 5. Quemar token actual y anular cualquier otro token pendiente de este usuario
    $pdo->prepare("UPDATE solicitudes_recuperacion SET usado = 1, estado = 'COMPLETADA', atendidoEn = NOW() WHERE id = ?")
        ->execute([$solicitud['id']]);

    $pdo->prepare("UPDATE solicitudes_recuperacion SET usado = 1, estado = 'EXPIRADA' WHERE usuarioId = ? AND id != ? AND usado = 0")
        ->execute([$usuarioId, $solicitud['id']]);

    // 6. Registrar en auditoría
    registrarAuditoria($pdo, 'RESTABLECIMIENTO_TOKEN_COMPLETADO', $usuarioId, $user['cedula'], 'Rol: ' . $user['rol']);

    jsonResponse([
        'ok' => true,
        'mensaje' => '¡Tu contraseña ha sido restablecida exitosamente! Todas las sesiones anteriores han sido cerradas.',
        'redirectUrl' => 'login.php'
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error al procesar el restablecimiento: ' . $e->getMessage()], 500);
}

