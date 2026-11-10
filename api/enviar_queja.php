<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireAuth();
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// GET: Consultar si ya existe una alerta para esta quincena
if ($method === 'GET') {
    $fechaInicio = $_GET['fechaInicio'] ?? '';
    $fechaFin = $_GET['fechaFin'] ?? '';
    $usuarioId = ($currentUser['rol'] === 'ADMIN' && !empty($_GET['usuarioId'])) ? $_GET['usuarioId'] : $currentUser['id'];

    if (empty($fechaInicio) || empty($fechaFin)) {
        jsonResponse(['error' => 'Rango de fechas requerido.'], 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT id, asociadoId AS usuarioId, fechaInicio, fechaFin, mensaje, litrosAlMomento, correoEnviado, creadoEn 
                               FROM quejas 
                               WHERE asociadoId = ? AND fechaInicio = ? AND fechaFin = ? 
                               LIMIT 1");
        $stmt->execute([$usuarioId, $fechaInicio, $fechaFin]);
        $alerta = $stmt->fetch();

        if (!$alerta) {
            $stmtOld = $pdo->prepare("SELECT id, usuarioId, fechaInicio, fechaFin, mensaje, litrosAlMomento, correoEnviado, creadoEn 
                                      FROM alertaquincena 
                                      WHERE usuarioId = ? AND fechaInicio = ? AND fechaFin = ? 
                                      LIMIT 1");
            $stmtOld->execute([$usuarioId, $fechaInicio, $fechaFin]);
            $alerta = $stmtOld->fetch();
        }

        if ($alerta) {
            jsonResponse([
                'yaEnviada' => true,
                'alerta' => [
                    'id' => $alerta['id'],
                    'mensaje' => $alerta['mensaje'],
                    'litrosAlMomento' => (float)$alerta['litrosAlMomento'],
                    'creadoEn' => $alerta['creadoEn'],
                ]
            ]);
        } else {
            jsonResponse(['yaEnviada' => false, 'alerta' => null]);
        }
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al verificar alerta: ' . $e->getMessage()], 500);
    }
}

// POST: Registrar nueva inconformidad (Límite estricto de 1 por quincena)
if ($method === 'POST') {
    $data = getRequestData();
    $fechaInicio = trim($data['fechaInicio'] ?? '');
    $fechaFin = trim($data['fechaFin'] ?? '');
    $mensaje = htmlspecialchars(strip_tags(trim($data['mensaje'] ?? '')), ENT_QUOTES, 'UTF-8');
    $usuarioId = $currentUser['id'];

    if (empty($fechaInicio) || empty($fechaFin) || empty($mensaje)) {
        jsonResponse(['error' => 'Por favor escribe el detalle de tu inconformidad.'], 400);
    }

    if (strlen($mensaje) < 10) {
        jsonResponse(['error' => 'El mensaje debe contener al menos 10 caracteres con el detalle de tu reclamo.'], 400);
    }

    try {
        // 1. RESTRICCIÓN DE 1 SOLA ALERTA: Verificar si ya existe en la base de datos
        $check = $pdo->prepare("SELECT id, creadoEn, mensaje FROM quejas WHERE asociadoId = ? AND fechaInicio = ? AND fechaFin = ? LIMIT 1");
        $check->execute([$usuarioId, $fechaInicio, $fechaFin]);
        $existente = $check->fetch();

        if (!$existente) {
            $chkOld = $pdo->prepare("SELECT id, creadoEn, mensaje FROM alertaquincena WHERE usuarioId = ? AND fechaInicio = ? AND fechaFin = ? LIMIT 1");
            $chkOld->execute([$usuarioId, $fechaInicio, $fechaFin]);
            $existente = $chkOld->fetch();
        }

        if ($existente) {
            jsonResponse([
                'error' => 'Ya has enviado una inquietud para esta quincena. El administrador la está revisando.',
                'yaEnviada' => true,
                'alerta' => $existente
            ], 400);
        }

        // 2. Calcular litros acumulados hasta el momento
        $sumStmt = $pdo->prepare("SELECT COALESCE(SUM(litros), 0) AS totalLitros FROM registros_leche WHERE asociadoId = ? AND fecha >= ? AND fecha <= ?");
        $sumStmt->execute([$usuarioId, $fechaInicio, $fechaFin]);
        $rowLitros = $sumStmt->fetch();
        $litrosAlMomento = (float)($rowLitros['totalLitros'] ?? 0);
        if ($litrosAlMomento == 0) {
            $sumOld = $pdo->prepare("SELECT COALESCE(SUM(litros), 0) AS totalLitros FROM entrega WHERE usuarioId = ? AND fecha >= ? AND fecha <= ?");
            $sumOld->execute([$usuarioId, $fechaInicio, $fechaFin]);
            $litrosAlMomento = (float)($sumOld->fetch()['totalLitros'] ?? 0);
        }

        // 3. Insertar alerta en ambas tablas
        $alertaId = generarUUID('alt');
        $pdo->prepare("INSERT INTO quejas (id, asociadoId, fechaInicio, fechaFin, mensaje, litrosAlMomento, estado, correoEnviado, creadoEn)
                       VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE', 0, NOW())")
            ->execute([$alertaId, $usuarioId, $fechaInicio, $fechaFin, $mensaje, $litrosAlMomento]);

        $pdo->prepare("INSERT INTO alertaquincena (id, usuarioId, fechaInicio, fechaFin, mensaje, litrosAlMomento, estado, correoEnviado, creadoEn)
                       VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE', 0, NOW())")
            ->execute([$alertaId, $usuarioId, $fechaInicio, $fechaFin, $mensaje, $litrosAlMomento]);

        // 4. ENVÍO DE CORREO ELECTRÓNICO AL ADMINISTRADOR (asoagroalisal@gmail.com)
        $adminEmail = 'asoagroalisal@gmail.com';
        $asunto = "[Reclamo de Litros] {$currentUser['nombre']} - Quincena {$fechaInicio} al {$fechaFin}";

        $cuerpoHTML = "
        <html>
        <head><meta charset='utf-8'></head>
        <body style='font-family: Arial, sans-serif; background-color: #f7f5ed; padding: 20px; color: #203a27;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; border: 2px solid #99c6a4; padding: 25px;'>
                <div style='background-color: #26462e; color: white; padding: 15px; border-radius: 10px; text-align: center;'>
                    <h2 style='margin: 0;'>Alerta de Inconformidad en Pesaje de Leche</h2>
                    <p style='margin: 5px 0 0 0; font-size: 14px; color: #c4dfcb;'>Asociación de Ganaderos</p>
                </div>
                <div style='padding: 20px 0;'>
                    <p>Se ha registrado un reclamo para la quincena actual:</p>
                    <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                        <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; font-weight: bold;'>Ganadero:</td><td>{$currentUser['nombre']}</td></tr>
                        <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; font-weight: bold;'>Cédula:</td><td>{$currentUser['cedula']}</td></tr>
                        <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; font-weight: bold;'>Periodo Quincena:</td><td>{$fechaInicio} al {$fechaFin}</td></tr>
                        <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; font-weight: bold;'>Litros al Momento:</td><td style='font-weight: bold; color: #26462e;'>" . number_format($litrosAlMomento, 1) . " Litros</td></tr>
                        <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; font-weight: bold;'>Fecha y Hora:</td><td>" . date('Y-m-d H:i:s') . "</td></tr>
                    </table>
                    <div style='margin-top: 20px; background: #faf6f1; border-left: 4px solid #d97706; padding: 15px; border-radius: 4px;'>
                        <p style='margin: 0; font-weight: bold; color: #664234;'>Mensaje del Ganadero:</p>
                        <p style='margin: 8px 0 0 0; white-space: pre-wrap; font-style: italic;'>" . htmlspecialchars($mensaje) . "</p>
                    </div>
                </div>
                <div style='text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 15px;'>
                    Este mensaje fue generado automáticamente por la Plataforma de Control Lechero de la Asociación de Ganaderos.
                </div>
            </div>
        </body>
        </html>
        ";

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Control Lechero <no-reply@asociacionganaderos.local>\r\n";
        $headers .= "Reply-To: asoagroalisal@gmail.com\r\n";

        $correoEnviado = false;
        try {
            $correoEnviado = @mail($adminEmail, $asunto, $cuerpoHTML, $headers);
            if ($correoEnviado) {
                $pdo->prepare("UPDATE quejas SET correoEnviado = 1 WHERE id = ?")->execute([$alertaId]);
                $pdo->prepare("UPDATE alertaquincena SET correoEnviado = 1 WHERE id = ?")->execute([$alertaId]);
            }
        } catch (Exception $mailErr) {
            $correoEnviado = false;
        }

        jsonResponse([
            'ok' => true,
            'mensaje' => 'Tu inquietud ha sido registrada y notificada a la administración. Solo se permite 1 alerta por quincena.',
            'alertaId' => $alertaId,
            'correoEnviado' => $correoEnviado
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al procesar la inquietud: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Método no soportado'], 405);
