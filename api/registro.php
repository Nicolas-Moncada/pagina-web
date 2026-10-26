<?php
// ================================================================
// REGISTRO SEGURO DE GANADEROS ASOCIADOS
// Validación de Cédula Única, Complejidad y CAPTCHA en Servidor
// ================================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/captcha.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$data = getRequestData();
$cedula = trim($data['cedula'] ?? '');
$nombre = trim($data['nombre'] ?? '');
$telefono = trim($data['telefono'] ?? '');
$password = trim($data['password'] ?? '');
$captcha = trim($data['captcha'] ?? '');
$captchaToken = $data['captchaToken'] ?? null;

// 1. Validar CAPTCHA
if (empty($captcha) && empty($captchaToken)) {
    jsonResponse(['error' => 'Por favor completa el código de verificación de seguridad (CAPTCHA).'], 400);
}

if (!validarCaptchaServidor($captcha, $captchaToken)) {
    jsonResponse(['error' => 'Código de seguridad (CAPTCHA) incorrecto o vencido. Por favor intenta de nuevo.'], 400);
}

// 2. Normalización y validación estricta de cédula (6 a 12 dígitos numéricos)
$cedulaNormalizada = normalizarCedula($cedula);
if (!$cedulaNormalizada) {
    jsonResponse(['error' => 'El número de cédula debe contener únicamente entre 6 y 12 dígitos numéricos.'], 400);
}

// 3. Validación de nombre
if (strlen($nombre) < 3 || strlen($nombre) > 100) {
    jsonResponse(['error' => 'El nombre completo debe tener entre 3 y 100 caracteres.'], 400);
}

// 4. Validación de contraseña (mínimo 8 caracteres y no contraseñas comunes)
if (strlen($password) < 8) {
    jsonResponse(['error' => 'La contraseña debe tener al menos 8 caracteres.'], 400);
}

$clavesComunes = ['12345678', '123456789', '1234567890', 'password', 'asociacion', 'clave123', 'admin123', 'ganadero123'];
if (in_array(strtolower($password), $clavesComunes)) {
    jsonResponse(['error' => 'La contraseña ingresada es demasiado común y predecible. Por favor elige una contraseña más segura.'], 400);
}

try {
    $pdo = getDB();

    // 5. Verificación de Cédula Única en el Servidor
    $check = $pdo->prepare("SELECT id FROM asociados WHERE cedula = ? LIMIT 1");
    $check->execute([$cedulaNormalizada]);
    if ($check->fetch()) {
        jsonResponse(['error' => 'Ya existe un usuario registrado con esta cédula.'], 400);
    }

    $checkU = $pdo->prepare("SELECT id FROM usuario WHERE cedula = ? LIMIT 1");
    $checkU->execute([$cedulaNormalizada]);
    if ($checkU->fetch()) {
        jsonResponse(['error' => 'Ya existe un usuario registrado con esta cédula.'], 400);
    }

    $id = generarUUID('usr');
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // 6. Inserción protegida
    $stmtA = $pdo->prepare("INSERT INTO asociados (id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, creadoEn)
                            VALUES (?, ?, ?, ?, 'Hacienda Asociada', ?, 'GANADERO', 1, 0, NOW())");
    $stmtA->execute([$id, $cedulaNormalizada, $nombre, $telefono, $hash]);

    $stmtU = $pdo->prepare("INSERT INTO usuario (id, cedula, nombre, telefono, passwordHash, rol, activo, debeCambiarPassword, creadoEn)
                            VALUES (?, ?, ?, ?, ?, 'GANADERO', 1, 0, NOW())");
    $stmtU->execute([$id, $cedulaNormalizada, $nombre, $telefono, $hash]);

    jsonResponse([
        'ok' => true,
        'mensaje' => '¡Registro completado exitosamente! Ya puedes ingresar con tu cédula y contraseña.',
        'cedula' => $cedulaNormalizada,
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error al procesar el registro.'], 500);
}
