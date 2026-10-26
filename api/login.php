<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/captcha.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$data = getRequestData();
$cedula = trim($data['cedula'] ?? '');
$password = trim($data['password'] ?? '');
$captcha = trim($data['captcha'] ?? '');
$captchaToken = $data['captchaToken'] ?? null;
$ip = getClientIP();

// 1. Verificación de Rate Limiting (Bloqueo tras 5 intentos fallidos)
$pdo = getDB();
$cedulaNormalizada = normalizarCedula($cedula);
$cedulaConsulta = $cedulaNormalizada ?: preg_replace('/\D/', '', $cedula);

$rateLimit = verificarRateLimitLogin($pdo, $cedulaConsulta, $ip);
if ($rateLimit['bloqueado']) {
    jsonResponse(['error' => $rateLimit['mensaje']], 429);
}

// 2. Verificación de CAPTCHA
if (empty($captcha) && empty($captchaToken)) {
    jsonResponse(['error' => 'Por favor completa el código de seguridad (CAPTCHA).'], 400);
}

if (!validarCaptchaServidor($captcha, $captchaToken)) {
    jsonResponse(['error' => 'Código de seguridad (CAPTCHA) incorrecto o vencido. Por favor resuelve el nuevo desafío.'], 400);
}

// 3. Validación de campos de credenciales
if (empty($cedulaNormalizada) || empty($password)) {
    registrarIntentoFallido($pdo, $cedulaConsulta, $ip);
    jsonResponse(['error' => 'Cédula o contraseña incorrecta.'], 401);
}

try {
    // 4. Buscar usuario
    $stmt = $pdo->prepare("SELECT id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, COALESCE(sessionVersion, 1) AS sessionVersion FROM asociados WHERE cedula = ? LIMIT 1");
    $stmt->execute([$cedulaNormalizada]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt2 = $pdo->prepare("SELECT id, cedula, nombre, telefono, '' AS finca, passwordHash, rol, activo, debeCambiarPassword, COALESCE(sessionVersion, 1) AS sessionVersion FROM usuario WHERE cedula = ? LIMIT 1");
        $stmt2->execute([$cedulaNormalizada]);
        $user = $stmt2->fetch();
    }

    if (!$user) {
        registrarIntentoFallido($pdo, $cedulaNormalizada, $ip);
        registrarAuditoria($pdo, 'LOGIN_FALLIDO_USUARIO_INEXISTENTE', null, $cedulaNormalizada);
        jsonResponse(['error' => 'Cédula o contraseña incorrecta.'], 401);
    }

    // 5. Validar contraseña
    if (!password_verify($password, $user['passwordHash'])) {
        registrarIntentoFallido($pdo, $cedulaNormalizada, $ip);
        registrarAuditoria($pdo, 'LOGIN_FALLIDO_PASSWORD_ERRONEA', $user['id'], $user['cedula']);
        jsonResponse(['error' => 'Cédula o contraseña incorrecta.'], 401);
    }

    // 6. Verificar si está activo
    if (!$user['activo']) {
        jsonResponse(['error' => 'Tu usuario se encuentra inactivo. Comunícate con la administración.'], 403);
    }

    // 7. Éxito: Limpiar intentos fallidos y regenerar ID de sesión
    limpiarIntentosFallidos($pdo, $cedulaNormalizada, $ip);
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id' => $user['id'],
        'cedula' => $user['cedula'],
        'nombre' => $user['nombre'],
        'telefono' => $user['telefono'],
        'rol' => $user['rol'],
        'debeCambiarPassword' => (int)($user['debeCambiarPassword'] ?? 0),
        'sessionVersion' => (int)($user['sessionVersion'] ?? 1),
    ];

    registrarAuditoria($pdo, 'LOGIN_EXITOSO', $user['id'], $user['cedula'], 'Rol: ' . $user['rol']);

    if (!empty($user['debeCambiarPassword'])) {
        $redirectUrl = 'cambiar_password.php';
    } else {
        $redirectUrl = ($user['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php';
    }

    jsonResponse([
        'ok' => true,
        'mensaje' => 'Inicio de sesión exitoso',
        'debeCambiarPassword' => !empty($user['debeCambiarPassword']),
        'redirect' => $redirectUrl,
        'redirectUrl' => $redirectUrl,
        'usuario' => [
            'id' => $user['id'],
            'cedula' => $user['cedula'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol'],
            'debeCambiarPassword' => !empty($user['debeCambiarPassword'])
        ]
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error al procesar el inicio de sesión.'], 500);
}
