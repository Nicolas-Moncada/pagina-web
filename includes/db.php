<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/db.php
 * Propósito: Conexión PDO Singleton segura (UTF-8, ERRMODE_EXCEPTION, EmulatePrepares OFF).
 * =========================================================================================
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Retorna la instancia única de conexión PDO.
     */
    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = DB_HOST;
        $port = DB_PORT;
        $dbname = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        $charset = DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            self::$instance = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Intento de fallback o creación de la base de datos si no existe
            try {
                $rootDsn = "mysql:host={$host};port={$port};charset={$charset}";
                $tmpPdo = new PDO($rootDsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET {$charset} COLLATE utf8mb4_unicode_ci");
                unset($tmpPdo);

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (Exception $ex) {
                // Fallback a asociacion_ganaderos si existe
                try {
                    $dsnFallback = "mysql:host={$host};port={$port};dbname=asociacion_ganaderos;charset={$charset}";
                    self::$instance = new PDO($dsnFallback, $user, $pass, $options);
                } catch (Exception $ex2) {
                    error_log("Fallo crítico de conexión a MySQL [ASOAGROALIZAL]: " . $ex2->getMessage());

                    if (self::isJsonRequest()) {
                        http_response_code(500);
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'ok' => false,
                            'error' => 'No fue posible conectar con el servidor de datos en XAMPP. Verifica que MySQL esté activo en XAMPP.'
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    } else {
                        http_response_code(500);
                        die("
                        <!DOCTYPE html>
                        <html lang='es'>
                        <head>
                            <meta charset='UTF-8'>
                            <title>Error de Conexión | " . htmlspecialchars(EMPRESA_SIGLA) . "</title>
                            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
                            <style>body { background:#f7faf7; font-family:sans-serif; padding-top:60px; }</style>
                        </head>
                        <body>
                            <div class='container' style='max-width: 620px;'>
                                <div class='card shadow-lg border-0 rounded-4 overflow-hidden'>
                                    <div class='card-header text-white p-4 text-center' style='background: #144722;'>
                                        <h3 class='fw-bold mb-1'>🥛 " . htmlspecialchars(EMPRESA_SIGLA) . "</h3>
                                        <small class='opacity-75'>Control Lechero &bull; Carmen de Carupa</small>
                                    </div>
                                    <div class='card-body p-4 text-center'>
                                        <div class='display-4 mb-3 text-warning'>⚠️</div>
                                        <h4 class='fw-bold text-dark mb-2'>Base de Datos no disponible</h4>
                                        <p class='text-muted mb-4'>No se pudo conectar con MySQL en XAMPP. Abre el Panel de Control de XAMPP y presiona <strong>Start</strong> en el módulo <strong>MySQL</strong>.</p>
                                        <a href='javascript:location.reload()' class='btn btn-success fw-bold px-4 py-2 rounded-3'>Reintentar Conexión</a>
                                    </div>
                                </div>
                            </div>
                        </body>
                        </html>
                        ");
                    }
                }
            }
        }

        self::asegurarEstructuraIdempotente(self::$instance);
        return self::$instance;
    }

    /**
     * Detección si la petición actual espera respuesta JSON
     */
    private static function isJsonRequest(): bool {
        return (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false);
    }

    /**
     * Garantiza de forma idempotente que las tablas y columnas necesarias existan
     */
    private static function asegurarEstructuraIdempotente(PDO $pdo): void {
        static $verificado = false;
        if ($verificado) return;

        try {
            // 1. Tabla Usuario
            $pdo->exec("CREATE TABLE IF NOT EXISTS `usuario` (
                `id` VARCHAR(191) NOT NULL PRIMARY KEY,
                `cedula` VARCHAR(191) NOT NULL UNIQUE,
                `nombre` VARCHAR(191) NOT NULL,
                `email` VARCHAR(191) NULL UNIQUE,
                `google_id` VARCHAR(191) NULL UNIQUE,
                `telefono` VARCHAR(191) NULL,
                `finca` VARCHAR(191) NULL DEFAULT 'Vereda Alizal',
                `passwordHash` VARCHAR(191) NOT NULL,
                `rol` ENUM('ADMIN', 'GANADERO') NOT NULL DEFAULT 'GANADERO',
                `activo` BOOLEAN NOT NULL DEFAULT TRUE,
                `debeCambiarPassword` BOOLEAN NOT NULL DEFAULT FALSE,
                `sessionVersion` INT NOT NULL DEFAULT 1,
                `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // 2. Tabla Quincena
            $pdo->exec("CREATE TABLE IF NOT EXISTS `quincena` (
                `id` VARCHAR(191) NOT NULL PRIMARY KEY,
                `fechaInicio` VARCHAR(10) NOT NULL,
                `fechaFin` VARCHAR(10) NOT NULL,
                `precioLitro` DOUBLE NULL DEFAULT 0,
                `cerrada` BOOLEAN NOT NULL DEFAULT FALSE,
                `cerradaPor` VARCHAR(191) NULL,
                `cerradaEn` DATETIME NULL,
                `creadaEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_quincena_fechas` (`fechaInicio`, `fechaFin`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // 3. Tabla Entrega de Leche
            $pdo->exec("CREATE TABLE IF NOT EXISTS `entrega` (
                `id` VARCHAR(191) NOT NULL PRIMARY KEY,
                `usuarioId` VARCHAR(191) NOT NULL,
                `fecha` VARCHAR(10) NOT NULL,
                `turno` ENUM('MANANA', 'TARDE') NOT NULL,
                `litros` DOUBLE NOT NULL,
                `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_entrega_pesaje` (`usuarioId`, `fecha`, `turno`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // 4. Tabla Consulta Pública (Contacto Web)
            $pdo->exec("CREATE TABLE IF NOT EXISTS `consulta` (
                `id` VARCHAR(191) NOT NULL PRIMARY KEY,
                `nombre` VARCHAR(191) NOT NULL,
                `contacto` VARCHAR(191) NOT NULL,
                `mensaje` TEXT NOT NULL,
                `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `atendida` BOOLEAN NOT NULL DEFAULT FALSE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // 5. Tabla Alerta Quincena (Inconformidad con respuesta oficial)
            $pdo->exec("CREATE TABLE IF NOT EXISTS `alertaquincena` (
                `id` VARCHAR(191) NOT NULL PRIMARY KEY,
                `usuarioId` VARCHAR(191) NOT NULL,
                `fechaInicio` VARCHAR(10) NOT NULL,
                `fechaFin` VARCHAR(10) NOT NULL,
                `mensaje` TEXT NOT NULL,
                `litrosAlMomento` DOUBLE NOT NULL DEFAULT 0,
                `estado` ENUM('PENDIENTE', 'EN_REVISION', 'RESUELTO') NOT NULL DEFAULT 'PENDIENTE',
                `respuestaAdmin` TEXT NULL,
                `respondidoEn` DATETIME NULL,
                `respondidoPor` VARCHAR(191) NULL,
                `correoEnviado` BOOLEAN NOT NULL DEFAULT FALSE,
                `atendida` BOOLEAN NOT NULL DEFAULT FALSE,
                `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_alerta_quincena_usr` (`usuarioId`, `fechaInicio`, `fechaFin`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // 6. Tabla Bitácora de Auditoría
            $pdo->exec("CREATE TABLE IF NOT EXISTS `audit_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `usuarioId` VARCHAR(191) NULL,
                `accion` VARCHAR(100) NOT NULL,
                `tabla` VARCHAR(50) NOT NULL,
                `registroId` VARCHAR(191) NULL,
                `campo` VARCHAR(50) NULL,
                `valorAnterior` TEXT NULL,
                `valorNuevo` TEXT NULL,
                `ip` VARCHAR(50) NOT NULL,
                `detalles` TEXT NULL,
                `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // 7. Tabla Control de Intentos de Login (Rate Limiting)
            $pdo->exec("CREATE TABLE IF NOT EXISTS `login_intentos` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `cedula` VARCHAR(50) NOT NULL,
                `ip` VARCHAR(50) NOT NULL,
                `intentos` INT NOT NULL DEFAULT 1,
                `bloqueadoHasta` DATETIME NULL,
                `actualizadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_login_ip` (`cedula`, `ip`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Alias login_attempts
            $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `cedula` VARCHAR(50) NOT NULL,
                `ip` VARCHAR(50) NOT NULL,
                `intentos` INT NOT NULL DEFAULT 1,
                `bloqueadoHasta` DATETIME NULL,
                `actualizadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_login_att_ip` (`cedula`, `ip`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Asegurar columnas no destructivas
            try { $pdo->exec("ALTER TABLE `usuario` ADD COLUMN `email` VARCHAR(191) NULL UNIQUE AFTER `nombre`"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `usuario` ADD COLUMN `google_id` VARCHAR(191) NULL UNIQUE AFTER `email`"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `usuario` ADD COLUMN `finca` VARCHAR(191) NULL DEFAULT 'Vereda Alizal'"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `usuario` ADD COLUMN `debeCambiarPassword` BOOLEAN NOT NULL DEFAULT FALSE"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `usuario` ADD COLUMN `sessionVersion` INT NOT NULL DEFAULT 1"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `alertaquincena` ADD COLUMN `estado` ENUM('PENDIENTE', 'EN_REVISION', 'RESUELTO') NOT NULL DEFAULT 'PENDIENTE'"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `alertaquincena` ADD COLUMN `respuestaAdmin` TEXT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `alertaquincena` ADD COLUMN `respondidoEn` DATETIME NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `alertaquincena` ADD COLUMN `respondidoPor` VARCHAR(191) NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `quincena` ADD COLUMN `cerradaPor` VARCHAR(191) NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `quincena` ADD COLUMN `cerradaEn` DATETIME NULL"); } catch (Exception $e) {}

            $verificado = true;
        } catch (Exception $e) {
            error_log("Aviso estructural en asegurarTablas: " . $e->getMessage());
        }
    }
}

/**
 * Función helper global para compatibilidad de acceso rápido a PDO
 */
if (!function_exists('getDB')) {
    function getDB(): PDO {
        return Database::getConnection();
    }
}
