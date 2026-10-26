-- ================================================================
-- SISTEMA DE CONTROL LECHERO - ASOCIACIÓN DE GANADEROS
-- Base de Datos MySQL Nativa para XAMPP
-- ================================================================

CREATE DATABASE IF NOT EXISTS `asociacion_ganaderos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `asociacion_ganaderos`;

-- 1. Tabla de Usuarios (Administradores y Ganaderos)
CREATE TABLE IF NOT EXISTS `usuario` (
  `id` VARCHAR(191) NOT NULL,
  `cedula` VARCHAR(191) NOT NULL UNIQUE,
  `nombre` VARCHAR(191) NOT NULL,
  `telefono` VARCHAR(191) NULL,
  `passwordHash` VARCHAR(191) NOT NULL,
  `rol` ENUM('ADMIN', 'GANADERO') NOT NULL DEFAULT 'GANADERO',
  `activo` BOOLEAN NOT NULL DEFAULT TRUE,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Quincenas
CREATE TABLE IF NOT EXISTS `quincena` (
  `id` VARCHAR(191) NOT NULL,
  `fechaInicio` VARCHAR(191) NOT NULL,
  `fechaFin` VARCHAR(191) NOT NULL,
  `precioLitro` DOUBLE NULL,
  `cerrada` BOOLEAN NOT NULL DEFAULT FALSE,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quincena_fechaInicio_fechaFin_key` (`fechaInicio`, `fechaFin`)
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
  UNIQUE KEY `entrega_usuarioId_fecha_turno_key` (`usuarioId`, `fecha`, `turno`),
  CONSTRAINT `entrega_usuarioId_fkey` FOREIGN KEY (`usuarioId`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Consultas Públicas (Formulario de Contacto Web)
CREATE TABLE IF NOT EXISTS `consulta` (
  `id` VARCHAR(191) NOT NULL,
  `nombre` VARCHAR(191) NOT NULL,
  `contacto` VARCHAR(191) NOT NULL,
  `mensaje TEXT` NOT NULL,
  `atendida` BOOLEAN NOT NULL DEFAULT FALSE,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla de Alertas / Inconformidades Quincenales (1 por quincena)
CREATE TABLE IF NOT EXISTS `alertaquincena` (
  `id` VARCHAR(191) NOT NULL,
  `usuarioId` VARCHAR(191) NOT NULL,
  `fechaInicio` VARCHAR(191) NOT NULL,
  `fechaFin` VARCHAR(191) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `litrosAlMomento` DOUBLE NOT NULL DEFAULT 0,
  `correoEnviado` BOOLEAN NOT NULL DEFAULT FALSE,
  `atendida` BOOLEAN NOT NULL DEFAULT FALSE,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `alertaquincena_usuarioId_fechas_key` (`usuarioId`, `fechaInicio`, `fechaFin`),
  CONSTRAINT `alerta_usuarioId_fkey` FOREIGN KEY (`usuarioId`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- INSERCIÓN DE DATOS INICIALES (ADMINISTRADOR Y GANADEROS)
-- ================================================================

-- Administrador: Cédula 79164451 / Contraseña: 123456
INSERT INTO `usuario` (`id`, `cedula`, `nombre`, `telefono`, `passwordHash`, `rol`, `activo`)
VALUES ('usr_admin_01', '79164451', 'Administrador General', '3001234567', '$2a$10$1RCHmhkqe5m8IKMMBzyVoOCQ910BzGGs2YBjWT4e4Xyfdjx2W953e', 'ADMIN', 1)
ON DUPLICATE KEY UPDATE `passwordHash` = VALUES(`passwordHash`), `rol` = 'ADMIN', `activo` = 1;

-- Ganadero: Hilda Inés Montaño - Cédula 20444983 / Contraseña: 123456
INSERT INTO `usuario` (`id`, `cedula`, `nombre`, `telefono`, `passwordHash`, `rol`, `activo`)
VALUES ('usr_ganadero_hilda', '20444983', 'Hilda Inés Montaño', '3104449830', '$2a$10$1RCHmhkqe5m8IKMMBzyVoOCQ910BzGGs2YBjWT4e4Xyfdjx2W953e', 'GANADERO', 1)
ON DUPLICATE KEY UPDATE `passwordHash` = VALUES(`passwordHash`), `rol` = 'GANADERO', `activo` = 1;

-- Ganaderos de ejemplo adicionales (Clave 1234)
INSERT INTO `usuario` (`id`, `cedula`, `nombre`, `telefono`, `passwordHash`, `rol`, `activo`) VALUES
('usr_ganadero_1001', '1001', 'Don Carlos Alberto Mendoza', '3105551001', '$2a$10$qR6Q11u9Z46pSgG1i.1E0.U2F4G3aD0eZ3pG9A.N0E7D2F0aB.C1e', 'GANADERO', 1),
('usr_ganadero_1002', '1002', 'Doña María Elena Restrepo', '3125551002', '$2a$10$qR6Q11u9Z46pSgG1i.1E0.U2F4G3aD0eZ3pG9A.N0E7D2F0aB.C1e', 'GANADERO', 1),
('usr_ganadero_1003', '1003', 'Don José Vicente Morales', '3155551003', '$2a$10$qR6Q11u9Z46pSgG1i.1E0.U2F4G3aD0eZ3pG9A.N0E7D2F0aB.C1e', 'GANADERO', 1),
('usr_ganadero_1004', '1004', 'Doña Rosa Inés Beltrán', '3185551004', '$2a$10$qR6Q11u9Z46pSgG1i.1E0.U2F4G3aD0eZ3pG9A.N0E7D2F0aB.C1e', 'GANADERO', 1)
ON DUPLICATE KEY UPDATE `activo` = 1;

-- Quincenas de muestra
INSERT INTO `quincena` (`id`, `fechaInicio`, `fechaFin`, `precioLitro`, `cerrada`) VALUES
('q_2026_10_1', '2026-10-01', '2026-10-15', 2150.00, 1),
('q_2026_10_2', '2026-10-16', '2026-10-31', 2250.00, 0)
ON DUPLICATE KEY UPDATE `precioLitro` = VALUES(`precioLitro`);

