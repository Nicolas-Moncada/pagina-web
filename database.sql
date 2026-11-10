-- ================================================================
-- SISTEMA DE CONTROL LECHERO - ASOCIACIÓN DE GANADEROS (ASOAGROALIZAL)
-- Base de Datos MySQL Nativa para XAMPP
-- Hardening y Arquitectura Segura 2026 (27 Controles de Seguridad)
-- ================================================================

CREATE DATABASE IF NOT EXISTS `asociacion_lechera` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `asociacion_lechera`;

-- 1. Tabla de Usuarios (Administradores y Ganaderos) con soporte Google OAuth 2.0 y Argon2id
CREATE TABLE IF NOT EXISTS `usuario` (
  `id` VARCHAR(191) NOT NULL,
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
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Quincenas con soporte de Cierre y Bloqueo Seguro
CREATE TABLE IF NOT EXISTS `quincena` (
  `id` VARCHAR(191) NOT NULL,
  `fechaInicio` VARCHAR(191) NOT NULL,
  `fechaFin` VARCHAR(191) NOT NULL,
  `precioLitro` DOUBLE NULL DEFAULT 0,
  `cerrada` BOOLEAN NOT NULL DEFAULT FALSE,
  `cerradaPor` VARCHAR(191) NULL,
  `cerradaEn` DATETIME NULL,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quincena_fechaInicio_fechaFin_key` (`fechaInicio`, `fechaFin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Entregas / Pesajes de Leche (Turnos Mañana y Tarde)
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
  `mensaje` TEXT NOT NULL,
  `atendida` BOOLEAN NOT NULL DEFAULT FALSE,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla de Alertas / Inconformidades Quincenales con Gestión Interactiva y Respuesta Oficial
CREATE TABLE IF NOT EXISTS `alertaquincena` (
  `id` VARCHAR(191) NOT NULL,
  `usuarioId` VARCHAR(191) NOT NULL,
  `fechaInicio` VARCHAR(191) NOT NULL,
  `fechaFin` VARCHAR(191) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `litrosAlMomento` DOUBLE NOT NULL DEFAULT 0,
  `estado` ENUM('PENDIENTE', 'EN_REVISION', 'RESUELTO') NOT NULL DEFAULT 'PENDIENTE',
  `respuestaAdmin` TEXT NULL,
  `respondidoEn` DATETIME NULL,
  `respondidoPor` VARCHAR(191) NULL,
  `correoEnviado` BOOLEAN NOT NULL DEFAULT FALSE,
  `atendida` BOOLEAN NOT NULL DEFAULT FALSE,
  `creadoEn` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `alertaquincena_usuarioId_fechas_key` (`usuarioId`, `fechaInicio`, `fechaFin`),
  CONSTRAINT `alerta_usuarioId_fkey` FOREIGN KEY (`usuarioId`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla de Auditoría (Bitácora de Cambios / Historial Operativo)
CREATE TABLE IF NOT EXISTS `audit_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla de Control de Intentos de Login (Rate Limiting y Anti-Fuerza Bruta)
CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cedula` VARCHAR(50) NOT NULL,
  `ip` VARCHAR(50) NOT NULL,
  `intentos` INT NOT NULL DEFAULT 1,
  `bloqueadoHasta` DATETIME NULL,
  `actualizadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_login_ip` (`cedula`, `ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- INSERCIÓN DE DATOS INICIALES (ADMINISTRADOR Y GANADEROS)
-- ================================================================

-- Administrador: Cédula 79164451
INSERT INTO `usuario` (`id`, `cedula`, `nombre`, `email`, `telefono`, `passwordHash`, `rol`, `activo`, `debeCambiarPassword`)
VALUES ('usr_admin_01', '79164451', 'Administrador General', 'asoagroalizal@gmail.com', '3185235690', '$argon2id$v=19$m=65536,t=4,p=1$Z3V4Z3V4Z3V4Z3V4$8c7N9Z8vB7bYkF1Vb1iW3r6f8h9j2k4l', 'ADMIN', 1, 0)
ON DUPLICATE KEY UPDATE `rol` = 'ADMIN', `activo` = 1;

-- Ganadero: Hilda Inés Montaño - Cédula 20444983
INSERT INTO `usuario` (`id`, `cedula`, `nombre`, `telefono`, `passwordHash`, `rol`, `activo`)
VALUES ('usr_ganadero_hilda', '20444983', 'Hilda Inés Montaño', '3104449830', '$2a$10$1RCHmhkqe5m8IKMMBzyVoOCQ910BzGGs2YBjWT4e4Xyfdjx2W953e', 'GANADERO', 1)
ON DUPLICATE KEY UPDATE `rol` = 'GANADERO', `activo` = 1;

-- Quincenas de muestra
INSERT INTO `quincena` (`id`, `fechaInicio`, `fechaFin`, `precioLitro`, `cerrada`) VALUES
('q_2026_10_1', '2026-10-01', '2026-10-15', 2150.00, 1),
('q_2026_10_2', '2026-10-16', '2026-10-31', 2250.00, 0)
ON DUPLICATE KEY UPDATE `precioLitro` = VALUES(`precioLitro`);
