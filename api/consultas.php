<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: api/consultas.php
 * Propósito: API para recepción segura de PQRS y consultas web (JSON y POST estándar).
 * =========================================================================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 1. Asegurar existencia de la tabla 'consulta'
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `consulta` (
        `id` VARCHAR(50) NOT NULL PRIMARY KEY,
        `nombre` VARCHAR(100) NOT NULL,
        `contacto` VARCHAR(100) NOT NULL,
        `mensaje` TEXT NOT NULL,
        `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `atendida` TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    error_log("Error asegurando tabla consulta: " . $e->getMessage());
}

// 2. GET: Listado para administración autorizada
if ($method === 'GET') {
    require_once __DIR__ . '/../includes/auth.php';
    $user = getCurrentUser();
    if (!$user || $user['rol'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
        exit;
    }

    try {
        $stmt = $pdo->query("SELECT id, nombre, contacto, mensaje, fecha, atendida FROM consulta ORDER BY fecha DESC LIMIT 100");
        $consultas = $stmt->fetchAll();
        echo json_encode(['success' => true, 'consultas' => $consultas]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error consultando registros']);
        exit;
    }
}

// 3. POST: Recepción de mensaje de contacto
if ($method === 'POST') {
    // Detectar si la petición es JSON o FormData
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $datos = [];

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $datos = json_decode($raw, true) ?: [];
    } else {
        $datos = $_POST;
    }

    // 4. Verificación de Honeypot Anti-Spam
    $honeypot = trim($datos['website_hp'] ?? ($datos['website'] ?? ''));
    if (!empty($honeypot)) {
        // Responder éxito de forma silenciosa para neutralizar bots
        echo json_encode([
            'success' => true,
            'ok' => true,
            'message' => 'Tu mensaje ha sido registrado exitosamente.'
        ]);
        exit;
    }

    // 5. Rate Limiting por IP (Máximo 1 envío cada 60 segundos)
    $ip = getClientIP();
    $ahora = time();
    $sessionKey = 'last_consulta_time_' . md5($ip);

    if (isset($_SESSION[$sessionKey]) && ($ahora - $_SESSION[$sessionKey] < CONSULTAS_RATE_LIMIT_SEGUNDOS)) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'ok' => false,
            'message' => 'Has enviado un mensaje recientemente. Por favor espera un minuto antes de enviar otro.'
        ]);
        exit;
    }

    // 6. Validación de CSRF Token si se envía
    $token = $datos['csrf_token'] ?? '';
    if (!empty($token) && !validarCsrfToken($token)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'ok' => false,
            'message' => 'La sesión de seguridad ha expirado. Por favor recarga la página.'
        ]);
        exit;
    }

    // 7. Sanitización y validaciones de contenido
    $nombre   = htmlspecialchars(strip_tags(trim($datos['nombre'] ?? '')), ENT_QUOTES, 'UTF-8');
    $contacto = htmlspecialchars(strip_tags(trim($datos['contacto'] ?? '')), ENT_QUOTES, 'UTF-8');
    $mensaje  = htmlspecialchars(strip_tags(trim($datos['mensaje'] ?? '')), ENT_QUOTES, 'UTF-8');

    if (empty($nombre) || empty($contacto) || empty($mensaje)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'ok' => false,
            'message' => 'Por favor completa todos los campos requeridos.'
        ]);
        exit;
    }

    if (strlen($nombre) < 3) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'ok' => false,
            'message' => 'El nombre debe contener al menos 3 caracteres.'
        ]);
        exit;
    }

    if (strlen($mensaje) < 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'ok' => false,
            'message' => 'El mensaje debe ser claro y contener al menos 10 caracteres.'
        ]);
        exit;
    }

    // 8. Inserción segura con PDO Prepared Statements
    try {
        $conId = generarUUID('con');
        $stmt = $pdo->prepare("INSERT INTO consulta (id, nombre, contacto, mensaje, fecha, atendida) VALUES (?, ?, ?, ?, NOW(), 0)");
        $stmt->execute([$conId, $nombre, $contacto, $mensaje]);

        $_SESSION[$sessionKey] = $ahora;

        echo json_encode([
            'success' => true,
            'ok' => true,
            'message' => '¡Consulta enviada exitosamente! La administración de ' . EMPRESA_SIGLA . ' se comunicará contigo pronto.'
        ]);
        exit;
    } catch (Exception $e) {
        error_log("Error insertando consulta: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'ok' => false,
            'message' => 'Ocurrió un error al procesar tu mensaje. Por favor intenta más tarde.'
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Método no permitido']);
exit;
