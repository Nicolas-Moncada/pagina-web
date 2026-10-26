<?php
// ================================================================
// ENDPOINT DE SOLICITUD DE RECUPERACIÓN DE CONTRASEÑA
// Anti-enumeración de usuarios, CAPTCHA y Rate-Limiting estricto
// ================================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/captcha.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$pdo = getDB();
$data = getRequestData();
$ip = getClientIP();

$cedulaRaw = $data['cedula'] ?? '';
$captcha = trim($data['captcha'] ?? '');
$captchaToken = $data['captchaToken'] ?? null;

// 1. Validar CAPTCHA obligatorio
if (empty($captcha) && empty($captchaToken)) {
    jsonResponse(['error' => 'Por favor completa el código de seguridad (CAPTCHA).'], 400);
}

if (!validarCaptchaServidor($captcha, $captchaToken)) {
    jsonResponse(['error' => 'Código de seguridad (CAPTCHA) incorrecto o vencido.'], 400);
}

// 2. Normalizar cédula
$cedulaNormalizada = normalizarCedula($cedulaRaw);
if (!$cedulaNormalizada) {
    // Respuesta genérica incluso ante formato inválido para evitar oráculos de entrada
    jsonResponse([
        'ok' => true,
        'mensaje' => 'Si la cédula existe en el sistema, se procesará la solicitud de restablecimiento.'
    ]);
}

// 3. Verificar Rate-Limiting de recuperación (máximo 3 por hora por cédula e IP)
$estadoRate = verificarRateLimitRecuperacion($pdo, $cedulaNormalizada, $ip);
if ($estadoRate['bloqueado']) {
    registrarAuditoria($pdo, 'BLOQUEO_RATE_LIMIT_RECUPERACION', null, $cedulaNormalizada, $estadoRate['mensaje']);
    jsonResponse(['error' => $estadoRate['mensaje']], 429);
}

// Registrar este intento para el contador de rate-limiting
registrarIntentoRecuperacion($pdo, $cedulaNormalizada, $ip);

// 4. Buscar usuario (sin filtrar ni dar pistas)
try {
    $stmt = $pdo->prepare("SELECT id, cedula, nombre, telefono, rol, email FROM asociados WHERE cedula = ? LIMIT 1");
    $stmt->execute([$cedulaNormalizada]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt2 = $pdo->prepare("SELECT id, cedula, nombre, telefono, rol, email FROM usuario WHERE cedula = ? LIMIT 1");
        $stmt2->execute([$cedulaNormalizada]);
        $user = $stmt2->fetch();
    }

    if ($user) {
        $usuarioId = $user['id'];
        $rol = $user['rol'];
        $nombre = $user['nombre'];

        if ($rol === 'GANADERO') {
            // Flujo A: Solicitud registrada para el panel de administración
            $solicitudId = generarUUID('rec');
            $ins = $pdo->prepare("INSERT INTO solicitudes_recuperacion 
                (id, usuarioId, cedula, nombre, rol, metodo, estado, ipSolicitud, creadoEn)
                VALUES (?, ?, ?, ?, 'GANADERO', 'ADMIN_MANUAL', 'PENDIENTE', ?, NOW())");
            $ins->execute([$solicitudId, $usuarioId, $cedulaNormalizada, $nombre, $ip]);

            registrarAuditoria($pdo, 'SOLICITUD_RECUPERACION_GANADERO', $usuarioId, $cedulaNormalizada, 'Bandeja admin');
        } elseif ($rol === 'ADMIN') {
            // Flujo Admin: Token criptográfico de 32 bytes enviado por correo o recuperable por CLI
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expira = date('Y-m-d H:i:s', time() + 900); // 15 minutos

            // Invalidar tokens previos no consumidos
            $pdo->prepare("UPDATE solicitudes_recuperacion SET usado = 1, estado = 'EXPIRADA' WHERE usuarioId = ? AND usado = 0")
                ->execute([$usuarioId]);

            $solicitudId = generarUUID('rec');
            $ins = $pdo->prepare("INSERT INTO solicitudes_recuperacion 
                (id, usuarioId, cedula, nombre, rol, metodo, tokenHash, tokenExpira, estado, ipSolicitud, creadoEn)
                VALUES (?, ?, ?, ?, 'ADMIN', 'CORREO_TOKEN', ?, ?, 'PENDIENTE', ?, NOW())");
            $ins->execute([$solicitudId, $usuarioId, $cedulaNormalizada, $nombre, $tokenHash, $expira, $ip]);

            registrarAuditoria($pdo, 'SOLICITUD_RECUPERACION_ADMIN', $usuarioId, $cedulaNormalizada, 'Token 15m generado');

            // Intento de envío de correo electrónico al correo oficial de la asociación
            $adminEmail = 'asoagroalisal@gmail.com';
            $resetLink = "http://localhost/asociacion/restablecer_password.php?token={$rawToken}";
            $asunto = "🔐 Restablecimiento de Contraseña de Administrador - Asoagroalisal";
            $cuerpo = "Hola {$nombre},\n\nSe ha solicitado el restablecimiento de tu contraseña de Administrador.\n\n"
                    . "Para crear una nueva contraseña segura, haz clic en el siguiente enlace:\n"
                    . "{$resetLink}\n\n"
                    . "Este enlace es de UN SOLO USO y expira exactamente en 15 minutos.\n"
                    . "Si tú no realizaste esta solicitud, puedes ignorar este correo.\n";

            $headers = "From: Seguridad Asoagroalisal <no-reply@asociacionganaderos.local>\r\n"
                     . "Reply-To: asoagroalisal@gmail.com\r\n"
                     . "X-Mailer: PHP/" . phpversion();

            @mail($adminEmail, $asunto, $cuerpo, $headers);
        }
    } else {
        // Usuario inexistente: registrar auditoría silenciosa
        registrarAuditoria($pdo, 'SOLICITUD_RECUPERACION_CEDULA_INEXISTENTE', null, $cedulaNormalizada);
    }
} catch (Exception $e) {
    // No revelar errores de base de datos
}

// 5. RESPUESTA SIEMPRE GENÉRICA (REGLA DE SEGURIDAD ESTRICTA)
jsonResponse([
    'ok' => true,
    'mensaje' => 'Si la cédula existe, se procesará tu solicitud.'
]);

