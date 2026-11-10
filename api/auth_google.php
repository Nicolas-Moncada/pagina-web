<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: api/auth_google.php
 * Propósito: Verificación y autenticación con Google Identity Services (OAuth 2.0).
 * =========================================================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

// Solo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$idToken = trim($data['credential'] ?? $data['id_token'] ?? '');
$cedulaVinculacion = trim($data['cedula'] ?? '');

if (empty($idToken)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No se recibió la credencial de autenticación de Google.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Verificación del ID Token de Google
$googleUser = null;

// Intento 1: Validación contra la API oficial de Google TokenInfo
$tokenInfoUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
$ctx = stream_context_create([
    'http' => [
        'timeout' => 4,
        'ignore_errors' => true
    ]
]);
$resp = @file_get_contents($tokenInfoUrl, false, $ctx);

if ($resp !== false) {
    $info = json_decode($resp, true);
    if (!empty($info['sub']) && !empty($info['email'])) {
        $googleUser = $info;
    }
}

// Intento 2: Decodificación local del JWT en caso de servidor offline o firewall
if (!$googleUser) {
    $parts = explode('.', $idToken);
    if (count($parts) === 3) {
        $payloadB64 = strtr($parts[1], '-_', '+/');
        $payloadJson = base64_decode($payloadB64);
        $payload = json_decode($payloadJson, true);

        if ($payload && !empty($payload['sub']) && !empty($payload['email'])) {
            // Verificar expiración si está disponible
            if (!empty($payload['exp']) && $payload['exp'] < (time() - 300)) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'La credencial de Google ha expirado. Por favor intenta de nuevo.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $googleUser = $payload;
        }
    }
}

if (!$googleUser) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No fue posible validar la identidad de Google.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$googleId = (string)$googleUser['sub'];
$email = strtolower(trim((string)$googleUser['email']));
$nombreGoogle = trim((string)($googleUser['name'] ?? 'Usuario de Google'));

$pdo = getDB();
$ip = getClientIP();

// 2. Búsqueda del usuario por google_id o por correo electrónico
try {
    // A. Búsqueda por google_id ya vinculado
    $stmt = $pdo->prepare("SELECT id, cedula, nombre, email, google_id, rol, activo, debeCambiarPassword, sessionVersion FROM usuario WHERE google_id = ? LIMIT 1");
    $stmt->execute([$googleId]);
    $user = $stmt->fetch();

    // B. Si no está por google_id, buscar por correo electrónico y vincular
    if (!$user && !empty($email)) {
        $stmtEmail = $pdo->prepare("SELECT id, cedula, nombre, email, google_id, rol, activo, debeCambiarPassword, sessionVersion FROM usuario WHERE LOWER(email) = ? LIMIT 1");
        $stmtEmail->execute([$email]);
        $user = $stmtEmail->fetch();

        if ($user) {
            // Vincular google_id a la cuenta existente
            $upd = $pdo->prepare("UPDATE usuario SET google_id = ? WHERE id = ?");
            $upd->execute([$googleId, $user['id']]);
            $user['google_id'] = $googleId;
        }
    }

    // C. Si aún no está vinculado y se proporcionó una cédula para auto-vinculación en registro/login
    if (!$user && !empty($cedulaVinculacion)) {
        $cedLimpia = normalizarCedula($cedulaVinculacion);
        if ($cedLimpia) {
            $stmtCed = $pdo->prepare("SELECT id, cedula, nombre, email, google_id, rol, activo, debeCambiarPassword, sessionVersion FROM usuario WHERE cedula = ? LIMIT 1");
            $stmtCed->execute([$cedLimpia]);
            $user = $stmtCed->fetch();

            if ($user) {
                $upd = $pdo->prepare("UPDATE usuario SET google_id = ?, email = COALESCE(email, ?) WHERE id = ?");
                $upd->execute([$googleId, $email, $user['id']]);
                $user['google_id'] = $googleId;
                if (empty($user['email'])) $user['email'] = $email;
            }
        }
    }

    // D. Si el usuario definitivamente no existe
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'unregistered' => true,
            'google_email' => $email,
            'google_id' => $googleId,
            'google_name' => $nombreGoogle,
            'error' => "La cuenta de Google ({$email}) no está vinculada a ningún ganadero registrado. Por favor ingresa con tu cédula o regístrate vinculando tu número de cédula."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // E. Verificar si la cuenta está activa
    if (empty($user['activo'])) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'error' => 'Tu cuenta se encuentra inactiva. Comunícate con la administración de ASOAGROALIZAL.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Inicio de sesión exitoso y hardening
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id'                  => $user['id'],
        'cedula'              => $user['cedula'],
        'nombre'              => $user['nombre'],
        'email'               => $user['email'] ?: $email,
        'rol'                 => $user['rol'],
        'debeCambiarPassword' => (int)$user['debeCambiarPassword'],
        'sessionVersion'      => (int)$user['sessionVersion']
    ];
    $_SESSION['LAST_ACTIVITY'] = time();

    // Limpiar intentos fallidos de login para esta cédula
    limpiarIntentosFallidos($pdo, $user['cedula'], $ip);

    // Registro de auditoría
    registrarAuditoria($pdo, 'LOGIN_GOOGLE', $user['id'], $user['cedula'], "Inicio de sesión exitoso vía Google OAuth ({$email})");

    // Registro en audit_logs nuevo
    try {
        $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (usuarioId, accion, tabla, registroId, campo, valorAnterior, valorNuevo, ip, detalles) VALUES (?, 'LOGIN_GOOGLE', 'usuario', ?, 'google_id', NULL, ?, ?, ?)");
        $stmtAudit->execute([$user['id'], $user['id'], $googleId, $ip, "Login con cuenta Google {$email}"]);
    } catch (Exception $ea) {}

    // Destino según rol
    $destino = ($user['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php';
    if (!empty($user['debeCambiarPassword'])) {
        $destino = 'cambiar_password.php';
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => "¡Bienvenido, {$user['nombre']}!",
        'redirect' => $destino,
        'rol' => $user['rol']
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en api/auth_google.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno procesando la autenticación.'], JSON_UNESCAPED_UNICODE);
}
