<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/auth.php
 * Propósito: Control de acceso, sesiones concurrentes, políticas de contraseñas y roles.
 * =========================================================================================
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Obtiene los datos del usuario actualmente autenticado en sesión.
 */
if (!function_exists('getCurrentUser')) {
    function getCurrentUser(): ?array {
        return $_SESSION['usuario'] ?? null;
    }
}

if (!function_exists('requireAuth')) {
    function requireAuth(?string $rolEsperado = null): array {
        $user = getCurrentUser();

        if (!$user) {
            if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Sesión expirada o no iniciada.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: login.php');
            exit;
        }

        // Comprobación de versión de sesión contra la base de datos (invalidación de sesiones concurrentes)
        if (!empty($user['id']) && isset($user['sessionVersion'])) {
            try {
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT sessionVersion, activo, debeCambiarPassword, rol, nombre FROM usuario WHERE id = ? LIMIT 1");
                $stmt->execute([$user['id']]);
                $dbData = $stmt->fetch();

                if (!$dbData || empty($dbData['activo']) || (int)$dbData['sessionVersion'] !== (int)$user['sessionVersion']) {
                    // Sesión invalidada
                    $_SESSION = [];
                    if (ini_get('session.use_cookies')) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                    }
                    session_destroy();

                    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                        http_response_code(401);
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => false, 'error' => 'Tu sesión fue cerrada debido a un cambio de contraseña o desactivación de cuenta.'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    header('Location: login.php?msg=sesion_expirada');
                    exit;
                }

                // Sincronizar cambios de rol o banderas
                $_SESSION['usuario']['debeCambiarPassword'] = (int)$dbData['debeCambiarPassword'];
                $_SESSION['usuario']['nombre'] = $dbData['nombre'];
                $_SESSION['usuario']['rol'] = $dbData['rol'];
                $user = $_SESSION['usuario'];

            } catch (Exception $e) {
                error_log("Error validando sessionVersion: " . $e->getMessage());
            }
        }

        // Redirección obligatoria si debe cambiar la contraseña y no está en la página de cambio
        $scriptActual = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if (!empty($user['debeCambiarPassword']) && $scriptActual !== 'cambiar_password.php' && $scriptActual !== 'logout.php') {
            header('Location: cambiar_password.php');
            exit;
        }

        // Validación de rol solicitado
        if ($rolEsperado !== null) {
            if ($user['rol'] !== $rolEsperado && $user['rol'] !== 'ADMIN') {
                if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => false, 'error' => 'Acceso denegado. No tienes permisos para este módulo.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                header('Location: panel.php');
                exit;
            }
        }

        return $user;
    }
}

/**
 * Valida la robustez de la contraseña según el rol del usuario.
 */
function validarPoliticaPassword(string $password, string $rol = 'GANADERO', ?string $hashActual = null): array {
    if (empty($password)) {
        return ['valido' => false, 'error' => 'La contraseña no puede estar vacía.'];
    }

    // Comprobar que sea distinta a la actual
    if (!empty($hashActual) && password_verify($password, $hashActual)) {
        return ['valido' => false, 'error' => 'La nueva contraseña debe ser diferente a la contraseña actual.'];
    }

    if ($rol === 'ADMIN') {
        if (strlen($password) < 10) {
            return ['valido' => false, 'error' => 'La contraseña de Administrador debe tener al menos 10 caracteres.'];
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valido' => false, 'error' => 'La contraseña de Administrador debe contener al menos una letra mayúscula.'];
        }
        if (!preg_match('/[a-z]/', $password)) {
            return ['valido' => false, 'error' => 'La contraseña de Administrador debe contener al menos una letra minúscula.'];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return ['valido' => false, 'error' => 'La contraseña de Administrador debe contener al menos un número.'];
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return ['valido' => false, 'error' => 'La contraseña de Administrador debe incluir al menos un símbolo o carácter especial (!@#$%&*).'];
        }
    } else {
        // Rol GANADERO: Accesible pero segura (mínimo 8 caracteres)
        if (strlen($password) < 8) {
            return ['valido' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.'];
        }
        $comunes = ['12345678', '123456789', '1234567890', 'password', 'asociacion', 'clave123', 'admin123', 'ganadero123', 'leche2026', 'leche2027'];
        if (in_array(strtolower($password), $comunes)) {
            return ['valido' => false, 'error' => 'La contraseña es demasiado fácil de adivinar. Elige una combinación más segura.'];
        }
    }

    return ['valido' => true];
}

/**
 * Cierre de sesión seguro con limpieza de cookies e historial.
 */
function cerrarSesion(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
