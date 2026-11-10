<?php
// ================================================================
// SCRIPT DE RECUPERACIÓN LOCAL DE ADMINISTRADOR (EXCLUSIVO CLI)
// Ejecución únicamente directa en el servidor por consola.
// Comando: php recuperar_admin.php
// ================================================================

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die("[ACCESO DENEGADO]: Por motivos de seguridad, este script solo puede ejecutarse localmente desde la terminal del servidor (CLI).\n");
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = getDB();

echo "=================================================================\n";
echo " ASOCIACIÓN DE GANADEROS - RECUPERACIÓN LOCAL DE ADMINISTRADOR\n";
echo "=================================================================\n\n";

$adminCedula = '79164451';

// Verificar existencia del admin en la base de datos
$stmt = $pdo->prepare("SELECT id, cedula, nombre, passwordHash FROM asociados WHERE cedula = ? AND rol = 'ADMIN' LIMIT 1");
$stmt->execute([$adminCedula]);
$admin = $stmt->fetch();

if (!$admin) {
    $stmt2 = $pdo->prepare("SELECT id, cedula, nombre, passwordHash FROM usuario WHERE cedula = ? AND rol = 'ADMIN' LIMIT 1");
    $stmt2->execute([$adminCedula]);
    $admin = $stmt2->fetch();
}

if (!$admin) {
    echo "[ERROR]: No se encontró al Administrador con cédula {$adminCedula}.\n";
    exit(1);
}

echo "Administrador detectado: {$admin['nombre']} (C.C. {$admin['cedula']})\n";
echo "Opciones:\n";
echo " [1] Generar automáticamente una contraseña temporal segura\n";
echo " [2] Escribir una nueva contraseña personalizada\n\n";

echo "Selecciona una opción (1 o 2) [1]: ";
$opcion = trim(fgets(STDIN));
if ($opcion === '' || $opcion === '1') {
    // Generar contraseña aleatoria de 14 caracteres con alta entropía
    $charsUpper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $charsLower = 'abcdefghijkmnopqrstuvwxyz';
    $charsNum   = '23456789';
    $charsSym   = '!@#$%&*';

    $passNueva = '';
    $passNueva .= $charsUpper[random_int(0, strlen($charsUpper) - 1)];
    $passNueva .= $charsLower[random_int(0, strlen($charsLower) - 1)];
    $passNueva .= $charsNum[random_int(0, strlen($charsNum) - 1)];
    $passNueva .= $charsSym[random_int(0, strlen($charsSym) - 1)];

    $todos = $charsUpper . $charsLower . $charsNum . $charsSym;
    for ($i = 0; $i < 10; $i++) {
        $passNueva .= $todos[random_int(0, strlen($todos) - 1)];
    }
    $passNueva = str_shuffle($passNueva);
    $esGenerada = true;
} else {
    echo "Ingresa la nueva contraseña (mínimo 10 caracteres, mayúscula, minúscula, número y símbolo): ";
    $passNueva = trim(fgets(STDIN));
    $esGenerada = false;
}

// Validar política de seguridad estricta
$check = validarPoliticaPassword($passNueva, 'ADMIN', $admin['passwordHash']);
if (!$check['valido']) {
    echo "\n[ERROR]: " . $check['error'] . "\n";
    exit(1);
}

$hash = password_hash($passNueva, PASSWORD_BCRYPT);

// Actualizar usuario
$pdo->prepare("UPDATE asociados SET passwordHash = ?, debeCambiarPassword = 1, sessionVersion = sessionVersion + 1 WHERE id = ?")
    ->execute([$hash, $admin['id']]);

$pdo->prepare("UPDATE usuario SET passwordHash = ?, debeCambiarPassword = 1, sessionVersion = sessionVersion + 1 WHERE id = ?")
    ->execute([$hash, $admin['id']]);

// Invalidar cualquier token pendiente
$pdo->prepare("UPDATE solicitudes_recuperacion SET usado = 1, estado = 'EXPIRADA' WHERE usuarioId = ? AND usado = 0")
    ->execute([$admin['id']]);

// Registrar auditoría
registrarAuditoria($pdo, 'RECUPERACION_CLI_ADMIN', $admin['id'], $admin['cedula'], 'Restablecida vía terminal');

echo "\n=================================================================\n";
echo "EXITO: Contraseña de Administrador restablecida correctamente.\n";
echo "=================================================================\n";
if ($esGenerada) {
    echo "Contraseña generada (cópiala ahora, se muestra una sola vez):\n";
    echo ">> {$passNueva} <<\n\n";
} else {
    echo "La contraseña ingresada ha sido establecida con éxito.\n\n";
}
echo "Nota de seguridad:\n";
echo " - Todas las sesiones abiertas anteriores han sido invalidadas.\n";
echo " - El sistema forzará el cambio de clave en el próximo inicio de sesión.\n";
echo " - URL de ingreso: http://localhost/asociacion/login.php\n";
echo "=================================================================\n";
exit(0);

