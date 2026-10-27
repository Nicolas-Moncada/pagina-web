<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/csrf.php
 * Propósito: Protección estricta contra ataques de falsificación de peticiones en sitios cruzados.
 * =========================================================================================
 */

require_once __DIR__ . '/config.php';

/**
 * Genera o recupera el token criptográfico CSRF de la sesión.
 */
function generarCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna el elemento HTML oculto con el token CSRF activo.
 */
function campoCsrf(): string {
    $token = generarCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida un token CSRF con tiempo constante (hash_equals).
 */
function validarCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

