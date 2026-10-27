<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA, CUNDINAMARCA)
 * Archivo: includes/config.php
 * Propósito: Constantes globales, configuración de entorno, seguridad y credenciales.
 * =========================================================================================
 */

// Evitar doble inclusión
if (defined('ASOAGRO_CONFIG_LOADED')) {
    return;
}
define('ASOAGRO_CONFIG_LOADED', true);

// Zona Horaria Oficial de Colombia
date_default_timezone_set('America/Bogota');

// 1. DATOS CORPORATIVOS OFICIALES Y LEGALES
define('EMPRESA_NOMBRE', 'ASOCIACION CAMPESINA NO NACIONAL DE TRABAJADORES DE LA VEREDA ALIZAL MUNICIPIO DE CARMEN DE CARUPA DEPARTAMENTO DE CUNDINAMARCA');
define('EMPRESA_SIGLA', 'ASOAGROALIZAL');
define('EMPRESA_NIT', '832006127-2');
define('EMPRESA_UBICACION', 'Vereda Alizal Finca El Recuerdo, Carmen De Carupa, Cundinamarca');
define('EMPRESA_TELEFONO', '3143820926');
define('EMPRESA_CORREO', 'asoagroalizal@gmail.com');
define('EMPRESA_ASOCIADOS_TOTAL', 32);

// 2. PARÁMETROS DE CONEXIÓN A BASE DE DATOS (XAMPP WINDOWS POR DEFECTO)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'asociacion_ganaderos');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// 3. SEGURIDAD DE SESIONES Y RATE LIMITING
define('MAX_LOGIN_INTENTOS', 5);                  // Intentos fallidos antes de bloqueo
define('LOCKOUT_TIEMPO_MINUTOS', 15);             // Minutos de suspensión
define('CAPTCHA_EXPIRACION_SEGUNDOS', 120);       // Vigencia estricta de 2 minutos
define('SESSION_EXPIRACION_SEGUNDOS', 1800);      // 30 minutos de inactividad

// 4. CONFIGURACIÓN DEL CAPTCHA (INTERRUPTOR GOOGLE reCAPTCHA v2 / GD NATIVO)
// Cambiar USE_RECAPTCHA_V2 a true e ingresar las claves para activar Google reCAPTCHA
define('USE_RECAPTCHA_V2', false);
define('RECAPTCHA_SITE_KEY', '');
define('RECAPTCHA_SECRET_KEY', '');

// 5. INICIALIZACIÓN DE SESIÓN SEGURA CON COOKIES HTTPONLY / SAMESITE
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Control estricto de inactividad de sesión
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_EXPIRACION_SEGUNDOS)) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

// 6. CABECERAS DE SEGURIDAD HTTP
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("X-XSS-Protection: 1; mode=block");
}

