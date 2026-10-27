-- ================================================================
-- ASOAGROALIZAL - SCRIPT DE MIGRACIÓN Y NORMALIZACIÓN SEGURA
-- Entorno: MySQL 8.x / MariaDB (XAMPP Windows)
-- Garantía: NO borra tablas ni registros existentes.
-- ================================================================

CREATE DATABASE IF NOT EXISTS `asociacion_ganaderos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `asociacion_ganaderos`;

-- 1. Tabla canónica de usuarios (Administradores y Ganaderos)
CREATE TABLE IF NOT EXISTS `usuario` (
  `id` VARCHAR(191) NOT NULL,
  `cedula` VARCHAR(191) NOT NULL,
  `nombre` VARCHAR(191) NOT NULL,
  `telefono` VARCHAR(191) NULL,
  `finca` VARCHAR(191) NULL DEFAULT 'Vereda Alizal',
  `passwordHash` VARCHAR(191) NOT NULL,
  `rol` ENUM('ADMIN', 'GANADERO') NOT NULL DEFAULT 'GANADERO',
  `activo` BOOLEAN NOT NULL DEFAULT TRUE,
  `debeCambiarPassword` BOOLEAN NOT NULL DEFAULT FALSE,
  `sessionVersion` INT NOT NULL DEFAULT 1,
  `email` VARCHAR(191) NULL,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_cedula_unique` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incorporación no destructiva de columnas si usuario ya existía sin ellas
SET @dbname = DATABASE();
SET @tablename = "usuario";

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'finca') > 0,
  "SELECT 1",
  "ALTER TABLE `usuario` ADD COLUMN `finca` VARCHAR(191) NULL DEFAULT 'Vereda Alizal' AFTER `telefono`;"
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'debeCambiarPassword') > 0,
  "SELECT 1",
  "ALTER TABLE `usuario` ADD COLUMN `debeCambiarPassword` BOOLEAN NOT NULL DEFAULT FALSE AFTER `activo`;"
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'sessionVersion') > 0,
  "SELECT 1",
  "ALTER TABLE `usuario` ADD COLUMN `sessionVersion` INT NOT NULL DEFAULT 1 AFTER `debeCambiarPassword`;"
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'email') > 0,
  "SELECT 1",
  "ALTER TABLE `usuario` ADD COLUMN `email` VARCHAR(191) NULL AFTER `sessionVersion`;"
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Tabla de Quincenas
CREATE TABLE IF NOT EXISTS `quincena` (
  `id` VARCHAR(191) NOT NULL,
  `fechaInicio` VARCHAR(191) NOT NULL,
  `fechaFin` VARCHAR(191) NOT NULL,
  `precioLitro` DOUBLE NULL DEFAULT 0,
  `cerrada` BOOLEAN NOT NULL DEFAULT FALSE,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quincena_fechas_key` (`fechaInicio`, `fechaFin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Entregas / Pesajes
CREATE TABLE IF NOT EXISTS `entrega` (
  `id` VARCHAR(191) NOT NULL,
  `usuarioId` VARCHAR(191) NOT NULL,
  `fecha` VARCHAR(191) NOT NULL,
  `turno` ENUM('MANANA', 'TARDE') NOT NULL,
  `litros` DOUBLE NOT NULL,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `actualizadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `entrega_usuario_fecha_turno_key` (`usuarioId`, `fecha`, `turno`),
  CONSTRAINT `entrega_usuario_fk` FOREIGN KEY (`usuarioId`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Consultas Públicas (formulario de contacto web)
CREATE TABLE IF NOT EXISTS `consulta` (
  `id` VARCHAR(191) NOT NULL,
  `nombre` VARCHAR(191) NOT NULL,
  `contacto` VARCHAR(191) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `atendida` BOOLEAN NOT NULL DEFAULT FALSE,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla de Alertas e Inconformidades Quincenales
CREATE TABLE IF NOT EXISTS `alertaquincena` (
  `id` VARCHAR(191) NOT NULL,
  `usuarioId` VARCHAR(191) NOT NULL,
  `fechaInicio` VARCHAR(191) NOT NULL,
  `fechaFin` VARCHAR(191) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `litrosAlMomento` DOUBLE NOT NULL DEFAULT 0,
  `estado` VARCHAR(50) NOT NULL DEFAULT 'PENDIENTE',
  `correoEnviado` BOOLEAN NOT NULL DEFAULT FALSE,
  `atendida` BOOLEAN NOT NULL DEFAULT FALSE,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `alerta_usuario_quincena_key` (`usuarioId`, `fechaInicio`, `fechaFin`),
  CONSTRAINT `alerta_usuario_fk` FOREIGN KEY (`usuarioId`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columna estado en alertaquincena si ya existía sin ella
SET @tablename = "alertaquincena";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'estado') > 0,
  "SELECT 1",
  "ALTER TABLE `alertaquincena` ADD COLUMN `estado` VARCHAR(50) NOT NULL DEFAULT 'PENDIENTE' AFTER `litrosAlMomento`;"
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. Tabla de Intentos de Login (Blindaje contra Fuerza Bruta)
CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cedula` VARCHAR(50) NOT NULL,
  `ip` VARCHAR(50) NOT NULL,
  `intentos` INT NOT NULL DEFAULT 1,
  `bloqueadoHasta` DATETIME NULL,
  `actualizadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_login_cedula_ip` (`cedula`, `ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla de Auditoría de Seguridad
CREATE TABLE IF NOT EXISTS `auditoria_seguridad` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuarioId` VARCHAR(191) NULL,
  `cedula` VARCHAR(50) NULL,
  `evento` VARCHAR(100) NOT NULL,
  `ip` VARCHAR(50) NOT NULL,
  `detalles` TEXT NULL,
  `creadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_evento` (`evento`),
  INDEX `idx_audit_cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Migración preventiva si existen tablas paralelas del desarrollo previo
SET @tableAsociadosExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'asociados');
SET @sqlMigAsoc = IF(@tableAsociadosExists > 0, 
  "INSERT IGNORE INTO `usuario` (`id`, `cedula`, `nombre`, `telefono`, `finca`, `passwordHash`, `rol`, `activo`, `debeCambiarPassword`, `creadoEn`)
   SELECT `id`, `cedula`, `nombre`, `telefono`, `finca`, `passwordHash`, `rol`, `activo`, `debeCambiarPassword`, `creadoEn` FROM `asociados`;", 
  "SELECT 1;");
PREPARE stmtMigA FROM @sqlMigAsoc; EXECUTE stmtMigA; DEALLOCATE PREPARE stmtMigA;

SET @tableRegLecheExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'registros_leche');
SET @sqlMigReg = IF(@tableRegLecheExists > 0,
  "INSERT IGNORE INTO `entrega` (`id`, `usuarioId`, `fecha`, `turno`, `litros`, `creadoEn`)
   SELECT `id`, `asociadoId`, `fecha`, `turno`, `litros`, `creadoEn` FROM `registros_leche`;",
  "SELECT 1;");
PREPARE stmtMigR FROM @sqlMigReg; EXECUTE stmtMigR; DEALLOCATE PREPARE stmtMigR;

SET @tableConfQExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'configuracion_quincena');
SET @sqlMigConfQ = IF(@tableConfQExists > 0,
  "INSERT IGNORE INTO `quincena` (`id`, `fechaInicio`, `fechaFin`, `precioLitro`, `cerrada`, `creadoEn`)
   SELECT `id`, `fechaInicio`, `fechaFin`, `precioLitro`, `cerrada`, `creadoEn` FROM `configuracion_quincena`;",
  "SELECT 1;");
PREPARE stmtMigQ FROM @sqlMigConfQ; EXECUTE stmtMigQ; DEALLOCATE PREPARE stmtMigQ;

SET @tableQuejasExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'quejas');
SET @sqlMigQuejas = IF(@tableQuejasExists > 0,
  "INSERT IGNORE INTO `alertaquincena` (`id`, `usuarioId`, `fechaInicio`, `fechaFin`, `mensaje`, `litrosAlMomento`, `estado`, `correoEnviado`, `creadoEn`)
   SELECT `id`, `asociadoId`, `fechaInicio`, `fechaFin`, `mensaje`, `litrosAlMomento`, `estado`, `correoEnviado`, `creadoEn` FROM `quejas`;",
  "SELECT 1;");
PREPARE stmtMigQuejas FROM @sqlMigQuejas; EXECUTE stmtMigQuejas; DEALLOCATE PREPARE stmtMigQuejas;

-- 9. Datos de Administrador y Ganaderos base si no existen aún
INSERT INTO `usuario` (`id`, `cedula`, `nombre`, `telefono`, `finca`, `passwordHash`, `rol`, `activo`)
VALUES ('usr_admin_01', '79164451', 'Administrador General', '3143820926', 'Sede Principal Vereda Alizal', '$2y$10$wTf20i42hQ85L0LqK7z3AOcjT40tO1jZ3QO9G4m9bVzHq1Jc4G.8i', 'ADMIN', 1)
ON DUPLICATE KEY UPDATE `activo` = 1;

-- Quincena por defecto activa para el año corriente
INSERT INTO `quincena` (`id`, `fechaInicio`, `fechaFin`, `precioLitro`, `cerrada`)
VALUES ('q_activa', DATE_FORMAT(NOW(), '%Y-%m-01'), DATE_FORMAT(NOW(), '%Y-%m-15'), 2250.00, 0)
ON DUPLICATE KEY UPDATE `precioLitro` = VALUES(`precioLitro`);

