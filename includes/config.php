<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: includes/config.php
 * Propósito: Constantes globales, configuración de entorno, credenciales y banderas.
 * Fuente de Datos Única: docs/FICHA_OFICIAL_ASOAGROALIZAL.md
 * =========================================================================================
 */

if (defined('ASOAGRO_CONFIG_LOADED')) {
    return;
}
define('ASOAGRO_CONFIG_LOADED', true);

// Zona Horaria Oficial
date_default_timezone_set('America/Bogota');

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

// 3. PARÁMETROS DE BASE DE DATOS PARA XAMPP (Sin credenciales hardcodeadas en vistas)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'asociacion_ganaderos');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// 4. PARÁMETROS DE SEGURIDAD
define('MAX_LOGIN_INTENTOS', 5);                  // Bloqueo tras 5 intentos fallidos
define('LOCKOUT_TIEMPO_MINUTOS', 15);             // 15 minutos de suspensión
define('CAPTCHA_EXPIRACION_SEGUNDOS', 120);       // Vigencia estricta de 2 minutos
define('SESSION_EXPIRACION_SEGUNDOS', 1800);      // 30 minutos de inactividad
define('CONSULTAS_RATE_LIMIT_SEGUNDOS', 60);      // 1 consulta por minuto por IP

// 5. INTERRUPTOR DE CAPTCHA (GD NATIVO / GOOGLE reCAPTCHA v2)
define('USE_RECAPTCHA_V2', false);
define('RECAPTCHA_SITE_KEY', '');
define('RECAPTCHA_SECRET_KEY', '');

// 6. GESTIÓN SEGURA DE SESIONES (Cookies HttpOnly, SameSite, Secure)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
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

// 7. CABECERAS DE SEGURIDAD HTTP
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("X-XSS-Protection: 1; mode=block");
}
