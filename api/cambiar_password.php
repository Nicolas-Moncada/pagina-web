<?php
// ================================================================
// CAMBIO OBLIGATORIO DE CONTRASEÑA EN PRIMER INGRESO
// Mínimo 10 caracteres: Mayúscula, Minúscula, Número y Símbolo
// ================================================================

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$user = requireAuth();
$data = getRequestData();

$passActual = $data['passwordActual'] ?? ($data['actualPassword'] ?? '');
$passNueva = $data['passwordNueva'] ?? ($data['nuevaPassword'] ?? '');
$passConfirm = $data['passwordConfirm'] ?? ($data['confirmarPassword'] ?? ($data['nuevaPasswordConfirm'] ?? $passNueva));

if (empty($passActual) || empty($passNueva) || empty($passConfirm)) {
    jsonResponse(['error' => 'Todos los campos son obligatorios.'], 400);
}

if ($passNueva !== $passConfirm) {
    jsonResponse(['error' => 'La nueva contraseña y su confirmación no coinciden.'], 400);
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, cedula, rol, passwordHash FROM asociados WHERE id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $dbUser = $stmt->fetch();

    if (!$dbUser) {
        $stmt2 = $pdo->prepare("SELECT id, cedula, rol, passwordHash FROM usuario WHERE id = ? LIMIT 1");
        $stmt2->execute([$user['id']]);
        $dbUser = $stmt2->fetch();
    }

    if (!$dbUser || !password_verify($passActual, $dbUser['passwordHash'])) {
        registrarAuditoria($pdo, 'CAMBIO_PASSWORD_FALLIDO_ACTUAL_ERRONEA', $user['id'], $user['cedula'] ?? null);
        jsonResponse(['error' => 'La contraseña actual ingresada es incorrecta.'], 401);
    }

    // Validación estricta según rol y comprobación de que sea distinta a la actual
    $checkPolitica = validarPoliticaPassword($passNueva, $dbUser['rol'] ?? $user['rol'], $dbUser['passwordHash']);
    if (!$checkPolitica['valido']) {
        jsonResponse(['error' => $checkPolitica['error']], 400);
    }

    // Nuevo hash seguro
    $nuevoHash = password_hash($passNueva, PASSWORD_BCRYPT);

    // Actualizar contraseña y bandera de cambio obligatorio
    $pdo->prepare("UPDATE asociados SET passwordHash = ?, debeCambiarPassword = 0, sessionVersion = sessionVersion + 1 WHERE id = ?")
        ->execute([$nuevoHash, $user['id']]);
    $pdo->prepare("UPDATE usuario SET passwordHash = ?, debeCambiarPassword = 0, sessionVersion = sessionVersion + 1 WHERE id = ?")
        ->execute([$nuevoHash, $user['id']]);

    // Obtener nueva versión de sesión para mantener la sesión actual activa y cerrar todas las demás
    $stmtVer = $pdo->prepare("SELECT sessionVersion FROM asociados WHERE id = ? LIMIT 1");
    $stmtVer->execute([$user['id']]);
    $newVersion = (int)$stmtVer->fetchColumn();

    $_SESSION['usuario']['debeCambiarPassword'] = 0;
    $_SESSION['usuario']['sessionVersion'] = $newVersion;

    registrarAuditoria($pdo, 'CAMBIO_PASSWORD_EXITOSO', $user['id'], $user['cedula'] ?? null, 'Otras sesiones cerradas');

    $redirectUrl = ($user['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php';

    jsonResponse([
        'ok' => true,
        'mensaje' => '¡Tu contraseña ha sido actualizada exitosamente! Las demás sesiones han sido cerradas.',
        'redirectUrl' => $redirectUrl
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error al procesar el cambio de contraseña: ' . $e->getMessage()], 500);
}
