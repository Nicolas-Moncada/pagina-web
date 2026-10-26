<?php
// ================================================================
// CONEXIÓN Y SEGURIDAD CENTRALIZADA - ASOCIACIÓN DE GANADEROS
// Native PHP + MySQL para XAMPP
// ================================================================

// 1. LECTURA DE VARIABLES DE ENTORNO (.env)
function getEnvVar($key, $default = '') {
    static $env = null;
    if ($env === null) {
        $env = [];
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $k = trim($parts[0]);
                    $v = trim($parts[1], " \t\n\r\0\x0B\"'");
                    $env[$k] = $v;
                }
            }
        }
    }
    return getenv($key) ?: ($env[$key] ?? $default);
}

// 2. CONFIGURACIÓN DE SESIONES SEGURAS
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Control de expiración por inactividad (30 minutos = 1800 segundos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

// 3. CABECERAS DE SEGURIDAD HTTP
function aplicarCabecerasSeguridad() {
    if (!headers_sent()) {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("X-XSS-Protection: 1; mode=block");
    }
}
aplicarCabecerasSeguridad();

// 4. CONEXIÓN A BASE DE DATOS
function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = '127.0.0.1';
    $dbname = 'asociacion_ganaderos';
    $user = 'root';
    $pass = '';

    try {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        try {
            $rootDsn = "mysql:host={$host};charset=utf8mb4";
            $tmpPdo = new PDO($rootDsn, $user, $pass);
            $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Exception $ex) {
            if (isApiRequest()) {
                jsonResponse([
                    'error' => 'No se pudo conectar a MySQL en XAMPP. Asegúrate de encender el módulo MySQL en el panel de XAMPP.',
                ], 500);
            } else {
                die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;color:#991b1b;border:2px solid #f87171;border-radius:12px;max-width:600px;margin:50px auto;text-align:center;'>
                    <h2>⚠️ MySQL no está encendido en XAMPP</h2>
                    <p>Por favor abre el Panel de Control de XAMPP y enciende el servicio MySQL.</p>
                </div>");
            }
        }
    }

    asegurarTablasYDatos($pdo);
    return $pdo;
}

// 5. ASEGURAR ESTRUCTURA IDEMPOTENTE (ARRANQUE LIMPIO)
function asegurarTablasYDatos($pdo) {
    static $verificado = false;
    if ($verificado) return;

    try {
        // Tablas principales
        $pdo->exec("CREATE TABLE IF NOT EXISTS `asociados` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `cedula` VARCHAR(191) NOT NULL UNIQUE,
            `nombre` VARCHAR(191) NOT NULL,
            `telefono` VARCHAR(191) NULL,
            `finca` VARCHAR(191) NULL,
            `passwordHash` VARCHAR(191) NOT NULL,
            `rol` ENUM('ADMIN', 'GANADERO') NOT NULL DEFAULT 'GANADERO',
            `activo` BOOLEAN NOT NULL DEFAULT TRUE,
            `debeCambiarPassword` BOOLEAN NOT NULL DEFAULT FALSE,
            `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `usuario` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `cedula` VARCHAR(191) NOT NULL UNIQUE,
            `nombre` VARCHAR(191) NOT NULL,
            `telefono` VARCHAR(191) NULL,
            `finca` VARCHAR(191) NULL,
            `passwordHash` VARCHAR(191) NOT NULL,
            `rol` ENUM('ADMIN', 'GANADERO') NOT NULL DEFAULT 'GANADERO',
            `activo` BOOLEAN NOT NULL DEFAULT TRUE,
            `debeCambiarPassword` BOOLEAN NOT NULL DEFAULT FALSE,
            `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `registros_leche` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `asociadoId` VARCHAR(191) NOT NULL,
            `fecha` VARCHAR(10) NOT NULL,
            `turno` ENUM('MANANA', 'TARDE') NOT NULL,
            `litros` DOUBLE NOT NULL,
            `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_registro_leche` (`asociadoId`, `fecha`, `turno`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `entrega` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `usuarioId` VARCHAR(191) NOT NULL,
            `fecha` VARCHAR(10) NOT NULL,
            `turno` ENUM('MANANA', 'TARDE') NOT NULL,
            `litros` DOUBLE NOT NULL,
            `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_entrega_leche` (`usuarioId`, `fecha`, `turno`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `configuracion_quincena` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `fechaInicio` VARCHAR(10) NOT NULL,
            `fechaFin` VARCHAR(10) NOT NULL,
            `precioLitro` DOUBLE NOT NULL DEFAULT 0,
            `cerrada` BOOLEAN NOT NULL DEFAULT FALSE,
            `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_conf_quincena` (`fechaInicio`, `fechaFin`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `quincena` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `fechaInicio` VARCHAR(10) NOT NULL,
            `fechaFin` VARCHAR(10) NOT NULL,
            `precioLitro` DOUBLE NULL,
            `cerrada` BOOLEAN NOT NULL DEFAULT FALSE,
            `creadaEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_quincena_fechas` (`fechaInicio`, `fechaFin`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `quejas` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `asociadoId` VARCHAR(191) NOT NULL,
            `fechaInicio` VARCHAR(10) NOT NULL,
            `fechaFin` VARCHAR(10) NOT NULL,
            `mensaje` TEXT NOT NULL,
            `litrosAlMomento` DOUBLE NOT NULL DEFAULT 0,
            `estado` VARCHAR(50) NOT NULL DEFAULT 'PENDIENTE',
            `correoEnviado` BOOLEAN NOT NULL DEFAULT FALSE,
            `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_queja_asociado_quincena` (`asociadoId`, `fechaInicio`, `fechaFin`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `alertaquincena` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `usuarioId` VARCHAR(191) NOT NULL,
            `fechaInicio` VARCHAR(10) NOT NULL,
            `fechaFin` VARCHAR(10) NOT NULL,
            `mensaje` TEXT NOT NULL,
            `litrosAlMomento` DOUBLE NOT NULL DEFAULT 0,
            `estado` VARCHAR(50) NOT NULL DEFAULT 'PENDIENTE',
            `correoEnviado` BOOLEAN NOT NULL DEFAULT FALSE,
            `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_alerta_quincena_usr` (`usuarioId`, `fechaInicio`, `fechaFin`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `consulta` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `nombre` VARCHAR(191) NOT NULL,
            `contacto` VARCHAR(191) NOT NULL,
            `mensaje` TEXT NOT NULL,
            `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atendida` BOOLEAN NOT NULL DEFAULT FALSE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Tabla para rate limiting / bloqueo de fuerza bruta
        $pdo->exec("CREATE TABLE IF NOT EXISTS `intentos_login` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `cedula` VARCHAR(50) NOT NULL,
            `ip` VARCHAR(50) NOT NULL,
            `intentos` INT NOT NULL DEFAULT 1,
            `bloqueadoHasta` DATETIME NULL,
            `actualizadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_cedula_ip` (`cedula`, `ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Tabla de solicitudes de restablecimiento de contraseña
        $pdo->exec("CREATE TABLE IF NOT EXISTS `solicitudes_recuperacion` (
            `id` VARCHAR(191) NOT NULL PRIMARY KEY,
            `usuarioId` VARCHAR(191) NOT NULL,
            `cedula` VARCHAR(50) NOT NULL,
            `nombre` VARCHAR(191) NOT NULL,
            `rol` ENUM('ADMIN', 'GANADERO') NOT NULL,
            `metodo` ENUM('ADMIN_MANUAL', 'CORREO_TOKEN') NOT NULL,
            `tokenHash` VARCHAR(255) NULL,
            `tokenExpira` DATETIME NULL,
            `usado` BOOLEAN NOT NULL DEFAULT FALSE,
            `claveTemporalHash` VARCHAR(255) NULL,
            `estado` ENUM('PENDIENTE', 'ATENDIDA', 'COMPLETADA', 'EXPIRADA') NOT NULL DEFAULT 'PENDIENTE',
            `ipSolicitud` VARCHAR(50) NOT NULL,
            `atendidoPor` VARCHAR(191) NULL,
            `atendidoEn` DATETIME NULL,
            `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_recup_cedula` (`cedula`),
            INDEX `idx_recup_token` (`tokenHash`),
            INDEX `idx_recup_estado` (`estado`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Tabla para rate limiting de solicitudes de recuperación (máx 3 por hora)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `intentos_recuperacion` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `cedula` VARCHAR(50) NOT NULL,
            `ip` VARCHAR(50) NOT NULL,
            `solicitudes` INT NOT NULL DEFAULT 1,
            `primeraSolicitudEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `bloqueadoHasta` DATETIME NULL,
            `actualizadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_recup_cedula_ip` (`cedula`, `ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Tabla de auditoría de seguridad
        $pdo->exec("CREATE TABLE IF NOT EXISTS `auditoria_seguridad` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `usuarioId` VARCHAR(191) NULL,
            `cedula` VARCHAR(50) NULL,
            `evento` VARCHAR(100) NOT NULL,
            `ip` VARCHAR(50) NOT NULL,
            `detalles` TEXT NULL,
            `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_audit_evento` (`evento`),
            INDEX `idx_audit_cedula` (`cedula`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Columnas para control de sesiones concurrentes y correos
        try { $pdo->exec("ALTER TABLE `asociados` ADD COLUMN `sessionVersion` INT NOT NULL DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `usuario` ADD COLUMN `sessionVersion` INT NOT NULL DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `asociados` ADD COLUMN `email` VARCHAR(191) NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `usuario` ADD COLUMN `email` VARCHAR(191) NULL"); } catch (Exception $e) {}

        // Si la tabla asociados está vacía: inicializar ÚNICO administrador
        $cntAsoc = (int)$pdo->query("SELECT COUNT(*) FROM asociados")->fetchColumn();
        if ($cntAsoc === 0) {
            $initialPassword = getEnvVar('ADMIN_INITIAL_PASSWORD', '123456789');
            $adminHash = password_hash($initialPassword, PASSWORD_BCRYPT);
            $adminId = 'usr_admin_luis_moncada';

            $stmtA = $pdo->prepare("INSERT INTO asociados (id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, creadoEn)
                                    VALUES (?, '79164451', 'Luis Hernando Moncada', '3001234567', 'Sede Principal', ?, 'ADMIN', 1, 1, NOW())");
            $stmtA->execute([$adminId, $adminHash]);

            $stmtU = $pdo->prepare("INSERT INTO usuario (id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, creadoEn)
                                    VALUES (?, '79164451', 'Luis Hernando Moncada', '3001234567', 'Sede Principal', ?, 'ADMIN', 1, 1, NOW())");
            $stmtU->execute([$adminId, $adminHash]);
        }

        // Si configuracion_quincena está vacía: inicializar PRIMERA quincena (1 al 15 de enero de 2027, $0 COP)
        $cntQ = (int)$pdo->query("SELECT COUNT(*) FROM configuracion_quincena")->fetchColumn();
        if ($cntQ === 0) {
            $pdo->exec("INSERT INTO configuracion_quincena (id, fechaInicio, fechaFin, precioLitro, cerrada) VALUES 
                ('q_2027_01_1', '2027-01-01', '2027-01-15', 0.00, 0)");
            $pdo->exec("INSERT INTO quincena (id, fechaInicio, fechaFin, precioLitro, cerrada, creadaEn) VALUES 
                ('q_2027_01_1', '2027-01-01', '2027-01-15', 0.00, 0, NOW())
                ON DUPLICATE KEY UPDATE precioLitro = VALUES(precioLitro)");
        }

        $verificado = true;
    } catch (Exception $e) {
        // En caso de error estructural silencioso
    }
}

// 6. RATE LIMITING / BLINDAJE CONTRA FUERZA BRUTA (5 intentos -> bloqueo 15 min)
function getClientIP() {
    return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function verificarRateLimitLogin($pdo, $cedula, $ip) {
    try {
        $stmt = $pdo->prepare("SELECT intentos, bloqueadoHasta FROM intentos_login WHERE cedula = ? AND ip = ? LIMIT 1");
        $stmt->execute([$cedula, $ip]);
        $row = $stmt->fetch();

        if ($row && !empty($row['bloqueadoHasta'])) {
            $ahora = new DateTime();
            $bloqueo = new DateTime($row['bloqueadoHasta']);
            if ($ahora < $bloqueo) {
                $diff = $bloqueo->getTimestamp() - $ahora->getTimestamp();
                $minutos = ceil($diff / 60);
                return [
                    'bloqueado' => true,
                    'mensaje' => "Demasiados intentos fallidos. Tu acceso está temporalmente suspendido por {$minutos} minutos."
                ];
            }
        }
    } catch (Exception $e) {}

    return ['bloqueado' => false];
}

function registrarIntentoFallido($pdo, $cedula, $ip) {
    try {
        $stmt = $pdo->prepare("SELECT intentos FROM intentos_login WHERE cedula = ? AND ip = ? LIMIT 1");
        $stmt->execute([$cedula, $ip]);
        $row = $stmt->fetch();

        if ($row) {
            $nuevosIntentos = (int)$row['intentos'] + 1;
            if ($nuevosIntentos >= 5) {
                // Bloqueo temporal por 15 minutos
                $bloqueoHasta = date('Y-m-d H:i:s', time() + 900);
                $upd = $pdo->prepare("UPDATE intentos_login SET intentos = ?, bloqueadoHasta = ? WHERE cedula = ? AND ip = ?");
                $upd->execute([$nuevosIntentos, $bloqueoHasta, $cedula, $ip]);
            } else {
                $upd = $pdo->prepare("UPDATE intentos_login SET intentos = ? WHERE cedula = ? AND ip = ?");
                $upd->execute([$nuevosIntentos, $cedula, $ip]);
            }
        } else {
            $ins = $pdo->prepare("INSERT INTO intentos_login (cedula, ip, intentos, bloqueadoHasta) VALUES (?, ?, 1, NULL)");
            $ins->execute([$cedula, $ip]);
        }
    } catch (Exception $e) {}
}

function limpiarIntentosFallidos($pdo, $cedula, $ip) {
    try {
        $stmt = $pdo->prepare("DELETE FROM intentos_login WHERE cedula = ? AND ip = ?");
        $stmt->execute([$cedula, $ip]);
    } catch (Exception $e) {}
}

// RATE LIMITING DE RECUPERACIÓN DE CONTRASEÑA (Máx 3 por hora por cédula e IP)
function verificarRateLimitRecuperacion($pdo, $cedula, $ip) {
    try {
        $stmt = $pdo->prepare("SELECT solicitudes, TIMESTAMPDIFF(SECOND, primeraSolicitudEn, NOW()) AS segundosTranscurridos FROM intentos_recuperacion WHERE cedula = ? AND ip = ? LIMIT 1");
        $stmt->execute([$cedula, $ip]);
        $row = $stmt->fetch();

        if ($row) {
            $segundos = (int)$row['segundosTranscurridos'];
            
            // Si ya pasó más de una hora (3600s), reiniciar ventana
            if ($segundos > 3600) {
                $reset = $pdo->prepare("UPDATE intentos_recuperacion SET solicitudes = 0, primeraSolicitudEn = NOW(), bloqueadoHasta = NULL WHERE cedula = ? AND ip = ?");
                $reset->execute([$cedula, $ip]);
                return ['bloqueado' => false];
            }

            // Si ya alcanzó o superó las 3 solicitudes dentro de la hora
            if ((int)$row['solicitudes'] >= 3) {
                $minutosRestantes = max(1, ceil((3600 - $segundos) / 60));
                return [
                    'bloqueado' => true,
                    'mensaje' => "Has superado el límite de solicitudes de restablecimiento (máximo 3 por hora). Por favor intenta de nuevo en {$minutosRestantes} minutos."
                ];
            }
        }
    } catch (Exception $e) {}

    return ['bloqueado' => false];
}

function registrarIntentoRecuperacion($pdo, $cedula, $ip) {
    try {
        $stmt = $pdo->prepare("SELECT solicitudes, TIMESTAMPDIFF(SECOND, primeraSolicitudEn, NOW()) AS segundosTranscurridos FROM intentos_recuperacion WHERE cedula = ? AND ip = ? LIMIT 1");
        $stmt->execute([$cedula, $ip]);
        $row = $stmt->fetch();

        if ($row) {
            $segundos = (int)$row['segundosTranscurridos'];
            if ($segundos > 3600) {
                $upd = $pdo->prepare("UPDATE intentos_recuperacion SET solicitudes = 1, primeraSolicitudEn = NOW(), bloqueadoHasta = NULL WHERE cedula = ? AND ip = ?");
                $upd->execute([$cedula, $ip]);
            } else {
                $nuevas = (int)$row['solicitudes'] + 1;
                $upd = $pdo->prepare("UPDATE intentos_recuperacion SET solicitudes = ?, bloqueadoHasta = IF(? >= 3, DATE_ADD(NOW(), INTERVAL 1 HOUR), NULL) WHERE cedula = ? AND ip = ?");
                $upd->execute([$nuevas, $nuevas, $cedula, $ip]);
            }
        } else {
            $ins = $pdo->prepare("INSERT INTO intentos_recuperacion (cedula, ip, solicitudes, primeraSolicitudEn, bloqueadoHasta) VALUES (?, ?, 1, NOW(), NULL)");
            $ins->execute([$cedula, $ip]);
        }
    } catch (Exception $e) {}
}

function limpiarIntentosRecuperacion($pdo, $cedula, $ip) {
    try {
        $stmt = $pdo->prepare("DELETE FROM intentos_recuperacion WHERE cedula = ? AND ip = ?");
        $stmt->execute([$cedula, $ip]);
    } catch (Exception $e) {}
}

// REGISTRO DE AUDITORÍA DE SEGURIDAD (Sin contraseñas ni tokens)
function registrarAuditoria($pdo, $evento, $usuarioId = null, $cedula = null, $detalles = null) {
    try {
        $ip = getClientIP();
        $stmt = $pdo->prepare("INSERT INTO auditoria_seguridad (usuarioId, cedula, evento, ip, detalles, creadoEn) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$usuarioId, $cedula, $evento, $ip, $detalles]);
    } catch (Exception $e) {}
}

// VALIDACIÓN DE POLÍTICA DE CONTRASEÑAS SEGÚN ROL
function validarPoliticaPassword($password, $rol = 'GANADERO', $hashActual = null) {
    if (empty($password)) {
        return ['valido' => false, 'error' => 'La contraseña no puede estar vacía.'];
    }

    // Comprobar que sea distinta de la contraseña actual
    if (!empty($hashActual) && password_verify($password, $hashActual)) {
        return ['valido' => false, 'error' => 'La nueva contraseña debe ser distinta de la contraseña actual.'];
    }

    if ($rol === 'ADMIN') {
        if (strlen($password) < 10) {
            return ['valido' => false, 'error' => 'La contraseña del administrador debe tener al menos 10 caracteres.'];
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valido' => false, 'error' => 'La contraseña debe contener al menos una letra mayúscula.'];
        }
        if (!preg_match('/[a-z]/', $password)) {
            return ['valido' => false, 'error' => 'La contraseña debe contener al menos una letra minúscula.'];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return ['valido' => false, 'error' => 'La contraseña debe contener al menos un número.'];
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return ['valido' => false, 'error' => 'La contraseña debe contener al menos un carácter especial o símbolo (ej. !@#$%&*).'];
        }
    } else {
        if (strlen($password) < 8) {
            return ['valido' => false, 'error' => 'La contraseña del ganadero debe tener al menos 8 caracteres.'];
        }
        $clavesComunes = ['12345678', '123456789', '1234567890', 'password', 'asociacion', 'clave123', 'admin123', 'ganadero123', 'leche2026', 'leche2027'];
        if (in_array(strtolower($password), $clavesComunes)) {
            return ['valido' => false, 'error' => 'La contraseña es demasiado común y predecible. Por favor elige una contraseña más segura.'];
        }
    }

    return ['valido' => true];
}

// 7. NORMALIZACIÓN Y VALIDACIÓN DE CÉDULAS
function normalizarCedula($cedula) {
    $limpia = preg_replace('/\D/', '', (string)$cedula);
    if (strlen($limpia) < 6 || strlen($limpia) > 12) {
        return null;
    }
    return $limpia;
}

// 8. SABER SI LA PETICIÓN ES API JSON
function isApiRequest() {
    return (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false)
        || (!empty($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false)
        || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

// 9. RESPUESTAS JSON
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 10. OBTENER DATOS DE LA PETICIÓN
function getRequestData() {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }
    }
    return $_POST;
}

// 11. USUARIO ACTUAL
function getCurrentUser() {
    return $_SESSION['usuario'] ?? null;
}

// 12. CONTROL DE ACCESO ESTRICTO CON INVALIDACIÓN DE SESIONES
function requireAuth($rolEsperado = null) {
    $user = getCurrentUser();

    if (!$user) {
        if (isApiRequest()) {
            jsonResponse(['error' => 'No autenticado. Por favor inicia sesión.'], 401);
        } else {
            header('Location: login.php');
            exit;
        }
    }

    // Validar versión de sesión contra la base de datos (invalida sesiones concurrentes al cambiar clave)
    if (!empty($user['id']) && isset($user['sessionVersion'])) {
        try {
            $pdo = getDB();
            $stmtVer = $pdo->prepare("SELECT sessionVersion, activo FROM asociados WHERE id = ? LIMIT 1");
            $stmtVer->execute([$user['id']]);
            $dbData = $stmtVer->fetch();
            if (!$dbData) {
                $stmtVer2 = $pdo->prepare("SELECT sessionVersion, activo FROM usuario WHERE id = ? LIMIT 1");
                $stmtVer2->execute([$user['id']]);
                $dbData = $stmtVer2->fetch();
            }

            if ($dbData) {
                if (empty($dbData['activo']) || (int)$dbData['sessionVersion'] !== (int)$user['sessionVersion']) {
                    $_SESSION = [];
                    if (session_id()) session_destroy();
                    if (isApiRequest()) {
                        jsonResponse(['error' => 'Tu sesión ha expirado o fue cerrada debido a un cambio de contraseña.'], 401);
                    } else {
                        header('Location: login.php?msg=sesion_invalida');
                        exit;
                    }
                }
            }
        } catch (Exception $e) {}
    }

    if ($rolEsperado && $user['rol'] !== $rolEsperado && $user['rol'] !== 'ADMIN') {
        if (isApiRequest()) {
            jsonResponse(['error' => 'Acceso no autorizado para este rol.'], 403);
        } else {
            header('Location: login.php');
            exit;
        }
    }

    return $user;
}

// 13. GENERADOR DE UUID SEGURO
function generarUUID($prefix = 'id') {
    return $prefix . '_' . substr(bin2hex(random_bytes(8)), 0, 12);
}
