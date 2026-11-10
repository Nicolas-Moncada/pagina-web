<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: api/auth_middleware.php
 * Propósito: Middleware centralizado de seguridad y verificación estricta de Administrador.
 * =========================================================================================
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

/**
 * Verifica si la petición actual espera una respuesta JSON.
 */
function isJsonResponseExpected(): bool {
    return (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false)
        || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
}

/**
 * Valida de forma estricta que exista una sesión activa con rol Administrador verificado en BD.
 *
 * @param bool $validarCsrfEnPost Si es true, valida automáticamente el token CSRF en POST/PUT/DELETE
 * @return array Datos del administrador autenticado
 */
function requireAdminAuth(bool $validarCsrfEnPost = false): array {
    $user = $_SESSION['usuario'] ?? null;

    if (!$user || empty($user['id'])) {
        if (isJsonResponseExpected()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => 'Sesión no iniciada o expirada. Por favor ingresa nuevamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Location: login.php?msg=auth_requerida');
        exit;
    }

    // Consulta en caliente en la base de datos para evitar suplantación o cambios de estado
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, cedula, nombre, email, rol, activo, debeCambiarPassword, sessionVersion FROM usuario WHERE id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $dbUser = $stmt->fetch();

        if (!$dbUser || empty($dbUser['activo'])) {
            // Usuario inexistente o inactivo
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();

            if (isJsonResponseExpected()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'error' => 'Tu cuenta ha sido desactivada o no existe.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: login.php?msg=cuenta_desactivada');
            exit;
        }

        // Comprobar rol de administrador
        if ($dbUser['rol'] !== 'ADMIN') {
            if (isJsonResponseExpected()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'error' => 'Acceso denegado. Se requieren privilegios de Administrador.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: panel.php?msg=sin_permiso_admin');
            exit;
        }

        // Validar versión de sesión contra cierres remotos o cambios de clave
        if (isset($user['sessionVersion']) && (int)$dbUser['sessionVersion'] !== (int)$user['sessionVersion']) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();

            if (isJsonResponseExpected()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'error' => 'Tu sesión fue cerrada debido a un cambio de clave o inicio concurrente.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: login.php?msg=sesion_expirada');
            exit;
        }

        // Cambio de contraseña obligatorio en primer ingreso
        $scriptActual = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if (!empty($dbUser['debeCambiarPassword']) && $scriptActual !== 'cambiar_password.php' && $scriptActual !== 'logout.php') {
            if (isJsonResponseExpected()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'error' => 'Debes actualizar tu contraseña inicial antes de continuar.',
                    'redirect' => 'cambiar_password.php'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: cambiar_password.php');
            exit;
        }

        // Sincronizar datos de sesión actualizados
        $_SESSION['usuario']['nombre'] = $dbUser['nombre'];
        $_SESSION['usuario']['rol'] = $dbUser['rol'];
        $_SESSION['usuario']['debeCambiarPassword'] = (int)$dbUser['debeCambiarPassword'];
        $_SESSION['usuario']['sessionVersion'] = (int)$dbUser['sessionVersion'];

    } catch (Exception $e) {
        error_log("Error crítico en requireAdminAuth: " . $e->getMessage());
        if (isJsonResponseExpected()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Error de autenticación en servidor.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Validación CSRF para peticiones POST/PUT/DELETE si se solicita
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($validarCsrfEnPost && in_array($metodo, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        $token = $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? $_SERVER['HTTP_CSRF_TOKEN']
            ?? null;

        // Intentar leer de payload JSON si no está en $_POST
        if (!$token && isJsonResponseExpected()) {
            $input = json_decode(file_contents_clean(), true);
            $token = $input['csrf_token'] ?? null;
        }

        if (!validarCsrfToken($token)) {
            if (isJsonResponseExpected()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido o ausente.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;color:#991b1b;border:2px solid #f87171;border-radius:12px;max-width:500px;margin:50px auto;text-align:center;'>
                <h3>⚠️ Error de Validación CSRF</h3>
                <p>La solicitud no superó la verificación de seguridad. Por favor recarga la página y vuelve a intentar.</p>
                <a href='admin.php' style='display:inline-block;padding:8px 16px;background:#144722;color:white;border-radius:6px;text-decoration:none;'>Regresar</a>
            </div>");
        }
    }

    return $_SESSION['usuario'];
}

/**
 * Lee de forma segura el input stream php://input
 */
function file_contents_clean(): string {
    static $input = null;
    if ($input === null) {
        $input = file_get_contents('php://input') ?: '';
    }
    return $input;
}
