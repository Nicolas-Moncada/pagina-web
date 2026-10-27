-- ================================================================
-- ASOAGROALIZAL - SCRIPT DE MIGRACIÓN 001
-- Archivo: sql/migracion_001.sql
-- Naturaleza: Idempotente (IF NOT EXISTS), aditiva, no destructiva.
-- ================================================================

CREATE DATABASE IF NOT EXISTS `asociacion_ganaderos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `asociacion_ganaderos`;

-- 1. Tabla de Usuarios (Administrador y Ganaderos Asociados)
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

-- Adición no destructiva de columnas
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

-- 3. Tabla de Entregas / Pesajes de Leche
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

-- 4. Tabla de Consultas Públicas
CREATE TABLE IF NOT EXISTS `consulta` (
  `id` VARCHAR(191) NOT NULL,
  `nombre` VARCHAR(191) NOT NULL,
  `contacto` VARCHAR(191) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `ip` VARCHAR(50) NULL,
  `atendida` BOOLEAN NOT NULL DEFAULT FALSE,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columna ip en consulta
SET @tablename = "consulta";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'ip') > 0,
  "SELECT 1",
  "ALTER TABLE `consulta` ADD COLUMN `ip` VARCHAR(50) NULL AFTER `mensaje`;"
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

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

-- 6. Tabla de Intentos de Login (Anti-Fuerza Bruta)
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

