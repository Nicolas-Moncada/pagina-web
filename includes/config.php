<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: includes/config.php
 * Propósito: Constantes globales, configuración de entorno, credenciales y hardening 2026.
 * Fuente de Datos Única: docs/FICHA_OFICIAL_ASOAGROALIZAL.md
 * =========================================================================================
 */

if (defined('ASOAGRO_CONFIG_LOADED')) {
    return;
}
define('ASOAGRO_CONFIG_LOADED', true);

// Zona Horaria Oficial
date_default_timezone_set('America/Bogota');

// 0. CARGADOR DE VARIABLES DE ENTORNO (.env)
(function() {
    $envPath = dirname(__DIR__) . '/.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $k = trim($parts[0]);
                $v = trim($parts[1], " \t\n\r\0\x0B\"'");
                if (getenv($k) === false) {
                    putenv("{$k}={$v}");
                    $_ENV[$k] = $v;
                    $_SERVER[$k] = $v;
                }
            }
        }
    }
})();

// 1. DATOS CORPORATIVOS OFICIALES (FICHA OFICIAL)
define('EMPRESA_NOMBRE', 'ASOCIACION CAMPESINA NO NACIONAL DE TRABAJADORES DE LA VEREDA ALIZAL MUNICIPIO DE CARMEN DE CARUPA DEPARTAMENTO DE CUNDINAMARCA');
define('EMPRESA_SIGLA', 'ASOAGROALIZAL');
define('EMPRESA_NIT', '832006127-2');
define('EMPRESA_UBICACION', 'Vereda Alizal, Finca El Recuerdo, Carmen De Carupa, Cundinamarca');
define('EMPRESA_TELEFONO', '3185235690');          // Teléfono público único oficial
define('EMPRESA_CORREO', 'asoagroalizal@gmail.com');
define('EMPRESA_ASOCIADOS_TOTAL', 32);             // 32 asociados oficiales
define('EMPRESA_ANO_CONSTITUCION', 2000);          // Más de 25 años de trayectoria

// 2. BANDERAS DE INTEGRACIÓN Y CANALES
define('WHATSAPP_ACTIVO', false);                  // Bandera obligatoria: Desactivado por defecto
define('APP_ENV', getenv('APP_ENV') ?: 'local');

// 3. PARÁMETROS DE BASE DE DATOS PARA XAMPP (Prioridad .env)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'asociacion_lechera');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// 4. PARÁMETROS DE SEGURIDAD Y RATE LIMITING
define('MAX_LOGIN_INTENTOS', 5);                  // Bloqueo tras 5 intentos fallidos
define('LOCKOUT_TIEMPO_MINUTOS', 15);             // 15 minutos de suspensión
define('CAPTCHA_EXPIRACION_SEGUNDOS', 120);       // Vigencia estricta de 2 minutos
define('SESSION_EXPIRACION_SEGUNDOS', 1800);      // 30 minutos de inactividad
define('CONSULTAS_RATE_LIMIT_SEGUNDOS', 60);      // 1 consulta por minuto por IP

// 5. GOOGLE IDENTITY SERVICES (OAuth 2.0)
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'tu_client_id_de_google.apps.googleusercontent.com');

// 6. INTERRUPTOR DE CAPTCHA (GD NATIVO / GOOGLE reCAPTCHA v2)
define('USE_RECAPTCHA_V2', false);
define('RECAPTCHA_SITE_KEY', getenv('CAPTCHA_SITEKEY') ?: '');
define('RECAPTCHA_SECRET_KEY', getenv('CAPTCHA_SECRET') ?: '');

// 7. GESTIÓN DE ERRORES Y LOGS
$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', $logDir . '/error.log');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', $logDir . '/error.log');
}

// 8. GESTIÓN SEGURA DE SESIONES (Cookies HttpOnly, Strict, Secure)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Control de expiración por inactividad
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_EXPIRACION_SEGUNDOS)) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

// 9. CABECERAS DE SEGURIDAD HTTP Y CSP
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("X-XSS-Protection: 1; mode=block");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://unpkg.com https://code.jquery.com https://cdn.datatables.net https://accounts.google.com https://apis.google.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://fonts.googleapis.com https://accounts.google.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com data:; img-src 'self' data: https: blob:; connect-src 'self' https://accounts.google.com https://*.google.com https://cdn.datatables.net https://cdn.jsdelivr.net; frame-src 'self' https://accounts.google.com;");
}
