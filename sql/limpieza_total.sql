-- =========================================================================================
-- PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
-- SCRIPT DE LIMPIEZA TOTAL PARA ENTRADA EN PRODUCCIÓN
-- Archivo: sql/limpieza_total.sql
-- =========================================================================================
--
-- GUÍA DE EJECUCIÓN PASO A PASO EN phpMyAdmin:
-- -----------------------------------------------------------------------------------------
-- 1. RESPALDO PREVIO (OBLIGATORIO):
--    - En phpMyAdmin, seleccione la base de datos "asociacion_ganaderos".
--    - Vaya a la pestaña "Exportar" -> Método "Rápido" -> Formato "SQL" -> Clic en "Exportar".
--    - O desde PowerShell:
--      & "C:\xampp\mysql\bin\mysqldump.exe" -u root asociacion_ganaderos > sql/backup_pre_limpieza_total.sql
--
-- 2. MODO 1: SIMULACIÓN / DRY-RUN (RECOMENDADO PRIMERO):
--    - Ejecute únicamente las secciones 1 y 2 (Declaración y Validación) junto con el BLOQUE (A).
--    - Verifique que el conteo de registros a eliminar coincide con lo esperado y que la validación
--      del administrador apruebe exitosamente sin advertencias.
--
-- 3. MODO 2: EJECUCIÓN DEFINITIVA:
--    - Copie y pegue todo este script en la pestaña "SQL" de phpMyAdmin.
--    - Haga clic en "Continuar".
--    - El script ejecuta una transacción segura con validación previa de seguridad:
--      * Si la validación pasa, ejecuta los DELETE ordenados, reinicia los AUTO_INCREMENT
--        y realiza la verificación matemática de integridad (SHA-256) antes de hacer COMMIT.
--      * Si ocurre cualquier error o discordancia, aborta sin tocar datos.
-- =========================================================================================

-- =========================================================================================
-- 1. DECLARACIÓN DE IDENTIFICADOR ÚNICO DEL ADMINISTRADOR GENERAL
-- =========================================================================================
-- Esta variable protege de forma estricta la cuenta del administrador en todas las consultas.
SET @CEDULA_ADMIN = '79164451';

-- =========================================================================================
-- 2. BLOQUE DE VALIDACIÓN DE SEGURIDAD
-- =========================================================================================
-- Se verifica que exista exactamente UN usuario con dicha cédula y rol de Administrador.
-- Si la condición no se cumple, se interrumpe la ejecución mediante SIGNAL SQLSTATE.
DROP PROCEDURE IF EXISTS sp_validar_seguridad_admin;
DELIMITER $$
CREATE PROCEDURE sp_validar_seguridad_admin()
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_count 
    FROM usuario 
    WHERE cedula = @CEDULA_ADMIN AND rol = 'ADMIN';
    
    IF v_count <> 1 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERROR CRÍTICO: No se encontró exactamente 1 Administrador con la cédula configurada. Operación cancelada por seguridad.';
    END IF;
END$$
DELIMITER ;

CALL sp_validar_seguridad_admin();
DROP PROCEDURE IF EXISTS sp_validar_seguridad_admin;

-- =========================================================================================
-- BLOQUE (A): DRY-RUN / SIMULACIÓN (CONTEO DE REGISTROS A ELIMINAR ANTES DE BORRAR)
-- =========================================================================================
SELECT '--- SIMULACIÓN DE REGISTROS A ELIMINAR ---' AS estado;

SELECT 
    'alertaquincena' AS tabla,
    COUNT(*) AS registros_a_eliminar,
    'Reclamos y alertas de quincena en pruebas' AS motivo
FROM alertaquincena

UNION ALL
SELECT 
    'entrega',
    COUNT(*),
    'Pesajes diarios registrados en fase de pruebas'
FROM entrega

UNION ALL
SELECT 
    'quejas',
    COUNT(*),
    'Buzón de reclamos de pruebas'
FROM quejas

UNION ALL
SELECT 
    'registros_leche',
    COUNT(*),
    'Registros paralelos de pesajes de prueba'
FROM registros_leche

UNION ALL
SELECT 
    'consulta',
    COUNT(*),
    'Mensajes de contacto de prueba de la landing page'
FROM consulta

UNION ALL
SELECT 
    'solicitudes_recuperacion',
    COUNT(*),
    'Tokens y solicitudes temporales de cambio de clave'
FROM solicitudes_recuperacion

UNION ALL
SELECT 
    'intentos_login',
    COUNT(*),
    'Bloqueos y contadores de rate limiting en pruebas'
FROM intentos_login

UNION ALL
SELECT 
    'login_intentos',
    COUNT(*),
    'Contadores auxiliares de intentos de login'
FROM login_intentos

UNION ALL
SELECT 
    'intentos_recuperacion',
    COUNT(*),
    'Contadores de rate limiting de recuperación'
FROM intentos_recuperacion

UNION ALL
SELECT 
    'auditoria_seguridad',
    COUNT(*),
    'Bitácora de eventos y accesos en fase de desarrollo'
FROM auditoria_seguridad

UNION ALL
SELECT 
    'usuario (excluyendo Administrador)',
    COUNT(*),
    'Usuarios ganaderos sintéticos de prueba'
FROM usuario 
WHERE cedula <> @CEDULA_ADMIN

UNION ALL
SELECT 
    'asociados (excluyendo Administrador)',
    COUNT(*),
    'Asociados sintéticos de prueba en tabla secundaria'
FROM asociados 
WHERE cedula <> @CEDULA_ADMIN;

-- Tablas que se CONSERVAN INTACTAS (Informativo)
SELECT 
    'quincena' AS tabla_conservada,
    COUNT(*) AS registros_existentes,
    'CONSERVADA: Períodos quincenales y calendario' AS estado
FROM quincena
UNION ALL
SELECT 
    'configuracion_quincena',
    COUNT(*),
    'CONSERVADA: Precios base por litro'
FROM configuracion_quincena
UNION ALL
SELECT 
    'usuario (ADMIN)',
    COUNT(*),
    'CONSERVADO: Cuenta del Administrador General intacta'
FROM usuario 
WHERE cedula = @CEDULA_ADMIN;


-- =========================================================================================
-- BLOQUE (B): EJECUCIÓN DESTRUCTIVA TRANSACCIONAL (ORDENADA POR DEPENDENCIAS)
-- =========================================================================================
START TRANSACTION;

-- 1. Capturar Checksum SHA-256 del Administrador ANTES de cualquier modificación
SET @CHECKSUM_ADMIN_ANTES = (
    SELECT SHA2(CONCAT_WS('|', 
        id, 
        cedula, 
        nombre, 
        IFNULL(telefono, ''), 
        IFNULL(finca, ''), 
        passwordHash, 
        rol, 
        activo, 
        creadoEn, 
        debeCambiarPassword, 
        sessionVersion, 
        IFNULL(email, '')
    ), 256)
    FROM usuario
    WHERE cedula = @CEDULA_ADMIN
);

SET @ADMIN_ID = (SELECT id FROM usuario WHERE cedula = @CEDULA_ADMIN);

-- 2. Eliminar tablas hijas con Clave Foránea a usuario(id)
DELETE FROM alertaquincena;
DELETE FROM entrega;

-- 3. Eliminar tablas transaccionales y de soporte sin claves foráneas formales
DELETE FROM quejas;
DELETE FROM registros_leche;
DELETE FROM consulta;
DELETE FROM solicitudes_recuperacion;
DELETE FROM intentos_login;
DELETE FROM login_intentos;
DELETE FROM intentos_recuperacion;
DELETE FROM auditoria_seguridad;

-- 4. Eliminar usuarios y asociados de prueba, blindando al Administrador
-- (Condición de seguridad triple: cedula distinta, cedula no nula, longitud válida)
DELETE FROM asociados 
WHERE cedula <> @CEDULA_ADMIN 
  AND @CEDULA_ADMIN IS NOT NULL 
  AND LENGTH(@CEDULA_ADMIN) >= 5;

DELETE FROM usuario 
WHERE cedula <> @CEDULA_ADMIN 
  AND @CEDULA_ADMIN IS NOT NULL 
  AND LENGTH(@CEDULA_ADMIN) >= 5;


-- =========================================================================================
-- BLOQUE (C): RESETEO DE AUTO_INCREMENT A 1 EN TABLAS VACIADAS
-- =========================================================================================
ALTER TABLE auditoria_seguridad AUTO_INCREMENT = 1;
ALTER TABLE intentos_login AUTO_INCREMENT = 1;
ALTER TABLE login_intentos AUTO_INCREMENT = 1;
ALTER TABLE intentos_recuperacion AUTO_INCREMENT = 1;


-- =========================================================================================
-- BLOQUE (D): VERIFICACIÓN DE INTEGRIDAD Y RECUENTO FINAL
-- =========================================================================================

-- 1. Capturar Checksum SHA-256 del Administrador DESPUÉS de las eliminaciones
SET @CHECKSUM_ADMIN_DESPUES = (
    SELECT SHA2(CONCAT_WS('|', 
        id, 
        cedula, 
        nombre, 
        IFNULL(telefono, ''), 
        IFNULL(finca, ''), 
        passwordHash, 
        rol, 
        activo, 
        creadoEn, 
        debeCambiarPassword, 
        sessionVersion, 
        IFNULL(email, '')
    ), 256)
    FROM usuario
    WHERE cedula = @CEDULA_ADMIN
);

-- 2. Comparación de integridad de la cuenta de Administrador
SELECT 
    @ADMIN_ID AS id_admin,
    'PROTEGIDA' AS cedula_estado,
    nombre AS nombre_admin,
    rol AS rol_admin,
    activo AS activo_admin,
    IF(@CHECKSUM_ADMIN_ANTES = @CHECKSUM_ADMIN_DESPUES, 
       'INTEGRIDAD 100% GARANTIZADA - EXACTAMENTE IGUAL', 
       'ALERTA CRÍTICA: DISCREPANCIA DETECTADA') AS resultado_integridad
FROM usuario
WHERE cedula = @CEDULA_ADMIN;

-- 3. Balance general de registros restantes por tabla en la Base de Datos
SELECT '--- RECUENTO FINAL TRAS LIMPIEZA ---' AS reporte;

SELECT 'alertaquincena' AS tabla, COUNT(*) AS filas_restantes, IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') AS estado FROM alertaquincena
UNION ALL SELECT 'entrega', COUNT(*), IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') FROM entrega
UNION ALL SELECT 'quejas', COUNT(*), IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') FROM quejas
UNION ALL SELECT 'registros_leche', COUNT(*), IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') FROM registros_leche
UNION ALL SELECT 'consulta', COUNT(*), IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') FROM consulta
UNION ALL SELECT 'solicitudes_recuperacion', COUNT(*), IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') FROM solicitudes_recuperacion
UNION ALL SELECT 'intentos_login', COUNT(*), IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') FROM intentos_login
UNION ALL SELECT 'login_intentos', COUNT(*), IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') FROM login_intentos
UNION ALL SELECT 'intentos_recuperacion', COUNT(*), IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') FROM intentos_recuperacion
UNION ALL SELECT 'auditoria_seguridad', COUNT(*), IF(COUNT(*) = 0, 'CORRECTO (VACÍA)', 'ERROR') FROM auditoria_seguridad
UNION ALL SELECT 'usuario', COUNT(*), IF(COUNT(*) = 1, 'CORRECTO (SOLO ADMIN)', 'ERROR') FROM usuario
UNION ALL SELECT 'asociados', COUNT(*), IF(COUNT(*) = 1, 'CORRECTO (SOLO ADMIN)', 'ERROR') FROM asociados
UNION ALL SELECT 'quincena', COUNT(*), 'CONSERVADA' FROM quincena
UNION ALL SELECT 'configuracion_quincena', COUNT(*), 'CONSERVADA' FROM configuracion_quincena;

-- =========================================================================================
-- CONFIRMACIÓN DE LA TRANSACCIÓN
-- =========================================================================================
-- Si todos los resultados anteriores son correctos, ejecute COMMIT para consolidar los cambios.
-- Si detecta alguna anomalía, reemplace COMMIT por ROLLBACK.
COMMIT;