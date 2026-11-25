<?php
/**
 * Endpoint público seguro de imagen CAPTCHA (envuelve includes/captcha_gd.php
 * para evitar el bloqueo .htaccess 403 sobre la carpeta interna /includes).
 */
require_once dirname(__DIR__) . '/includes/captcha_gd.php';
CaptchaService::renderizarImagen();
