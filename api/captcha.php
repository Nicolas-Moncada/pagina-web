<?php
// ================================================================
// SERVICIO DE CAPTCHA SEGURO (VALIDADO EN SERVIDOR PHP)
// Desafío de un solo uso con expiración estricta de 2 minutos
// Compatible con Turnstile/hCaptcha si se configuran claves en .env
// ================================================================

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// GET: Generar nuevo desafío visual
if ($method === 'GET') {
    $num1 = rand(3, 9);
    $num2 = rand(2, 8);
    $operadores = ['+'];
    $operador = $operadores[0];
    $resultado = $num1 + $num2;

    $_SESSION['captcha_answer'] = (string)$resultado;
    $_SESSION['captcha_time'] = time();

    // Generar imagen SVG con ruido y distorsión
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="130" height="42" viewBox="0 0 130 42" class="rounded-lg shadow-inner select-none">';
    $svg .= '<rect width="100%" height="100%" fill="#26462e" rx="8"/>';
    // Ruido visual (líneas aleatorias)
    for ($i = 0; $i < 4; $i++) {
        $x1 = rand(0, 130); $y1 = rand(0, 42); $x2 = rand(0, 130); $y2 = rand(0, 42);
        $svg .= "<line x1='{$x1}' y1='{$y1}' x2='{$x2}' y2='{$y2}' stroke='#498b58' stroke-width='1.5' stroke-opacity='0.4'/>";
    }
    // Texto del desafío
    $textoDesafio = "{$num1} {$operador} {$num2} = ?";
    $svg .= "<text x='50%' y='58%' dominant-baseline='middle' text-anchor='middle' fill='#fef08a' font-family='monospace' font-weight='900' font-size='19' letter-spacing='2'>{$textoDesafio}</text>";
    $svg .= '</svg>';

    jsonResponse([
        'ok' => true,
        'svg' => $svg,
        'pregunta' => "Resuelve: {$num1} + {$num2}",
        'siteKey' => getEnvVar('CAPTCHA_SITEKEY', '')
    ]);
}

// Función auxiliar para validar en el servidor (llamada desde login y registro)
function validarCaptchaServidor($respuestaUsuario, $externalToken = null) {
    $secret = getEnvVar('CAPTCHA_SECRET', '');

    // 1. Si hay clave secreta de Turnstile / hCaptcha configurada en .env
    if (!empty($secret) && !empty($externalToken)) {
        try {
            $postData = http_build_query([
                'secret' => $secret,
                'response' => $externalToken,
                'remoteip' => getClientIP()
            ]);
            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => $postData,
                    'timeout' => 5
                ]
            ];
            $context = stream_context_create($opts);
            $verifyRes = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
            if ($verifyRes) {
                $resData = json_decode($verifyRes, true);
                return !empty($resData['success']);
            }
        } catch (Exception $e) {}
    }

    // 2. Validación de desafío interno del servidor (un solo uso, expira en 120s)
    if (empty($_SESSION['captcha_answer']) || empty($_SESSION['captcha_time'])) {
        return false;
    }

    if (time() - $_SESSION['captcha_time'] > 120) {
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_time']);
        return false;
    }

    $esperado = trim((string)$_SESSION['captcha_answer']);
    $recibido = trim((string)$respuestaUsuario);

    // Destruir desafío tras evaluar (un solo uso estricto)
    unset($_SESSION['captcha_answer']);
    unset($_SESSION['captcha_time']);

    return ($esperado === $recibido);
}
