<?php
// CLI Database Reset Script - Asoagroalisal
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Acceso prohibido");
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = getDB();

$tables = [
    'registros_leche', 'entrega', 'quejas', 'alertaquincena', 'consulta',
    'intentos_login', 'solicitudes_recuperacion', 'intentos_recuperacion',
    'auditoria_seguridad', 'configuracion_quincena', 'asociados', 'usuario'
];

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
foreach ($tables as $t) {
    try {
        $pdo->exec("TRUNCATE TABLE `{$t}`;");
    } catch (Exception $e) {}
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

// Inicializar Administrador Único
$initialPassword = getEnvVar('ADMIN_INITIAL_PASSWORD', '123456789');
$adminHash = password_hash($initialPassword, PASSWORD_BCRYPT);
$adminId = 'usr_admin_luis_moncada';

$stmtA = $pdo->prepare("INSERT INTO asociados (id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, sessionVersion, creadoEn)
                        VALUES (?, '79164451', 'Luis Hernando Moncada', '3001234567', 'Sede Principal', ?, 'ADMIN', 1, 1, 1, NOW())");
$stmtA->execute([$adminId, $adminHash]);

$stmtU = $pdo->prepare("INSERT INTO usuario (id, cedula, nombre, telefono, passwordHash, rol, activo, debeCambiarPassword, sessionVersion, creadoEn)
                        VALUES (?, '79164451', 'Luis Hernando Moncada', '3001234567', ?, 'ADMIN', 1, 1, 1, NOW())");
$stmtU->execute([$adminId, $adminHash]);

// Inicializar Quincena Inicial 2027
$pdo->exec("INSERT INTO configuracion_quincena (id, fechaInicio, fechaFin, precioLitro, cerrada) VALUES 
            ('quin_20270101_20270115', '2027-01-01', '2027-01-15', 0, 0)");

echo "DB reset complete: Sole Admin (79164451) and 2027 fortnight initialized.\n";

