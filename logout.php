<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: logout.php
 * Propósito: Cierre de sesión seguro y destrucción de cookies de autenticación.
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

cerrarSesion();

header('Location: login.php');
exit;
