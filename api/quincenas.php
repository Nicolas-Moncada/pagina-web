<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, fechaInicio, fechaFin, precioLitro, cerrada, creadoEn FROM configuracion_quincena ORDER BY fechaInicio DESC");
        $quincenas = $stmt->fetchAll();

        if (empty($quincenas)) {
            $stmtOld = $pdo->query("SELECT id, fechaInicio, fechaFin, precioLitro, cerrada, creadoEn FROM quincena ORDER BY fechaInicio DESC");
            $quincenas = $stmtOld->fetchAll();
        }

        jsonResponse(['quincenas' => $quincenas]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al consultar quincenas: ' . $e->getMessage()], 500);
    }
}

if ($method === 'POST') {
    requireAuth('ADMIN');
    $data = getRequestData();
    $fechaInicio = trim($data['fechaInicio'] ?? '');
    $fechaFin = trim($data['fechaFin'] ?? '');
    $precioLitro = isset($data['precioLitro']) && $data['precioLitro'] !== '' ? (float)$data['precioLitro'] : 0.0;
    $cerrada = isset($data['cerrada']) ? (int)(bool)$data['cerrada'] : 0;

    if (empty($fechaInicio) || empty($fechaFin)) {
        jsonResponse(['error' => 'Rango de fechas de quincena requerido.'], 400);
    }

    try {
        $id = generarUUID('q');
        $stmt = $pdo->prepare("INSERT INTO configuracion_quincena (id, fechaInicio, fechaFin, precioLitro, cerrada, creadoEn)
                               VALUES (?, ?, ?, ?, ?, NOW())
                               ON DUPLICATE KEY UPDATE precioLitro = VALUES(precioLitro), cerrada = VALUES(cerrada)");
        $stmt->execute([$id, $fechaInicio, $fechaFin, $precioLitro, $cerrada]);

        $stmtOld = $pdo->prepare("INSERT INTO quincena (id, fechaInicio, fechaFin, precioLitro, cerrada, creadaEn)
                                  VALUES (?, ?, ?, ?, ?, NOW())
                                  ON DUPLICATE KEY UPDATE precioLitro = VALUES(precioLitro), cerrada = VALUES(cerrada)");
        $stmtOld->execute([$id, $fechaInicio, $fechaFin, $precioLitro, $cerrada]);

        jsonResponse([
            'ok' => true,
            'mensaje' => 'Precio de la quincena actualizado correctamente.',
            'quincena' => [
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'precioLitro' => $precioLitro,
                'cerrada' => (bool)$cerrada
            ]
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al guardar quincena: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Método no soportado'], 405);
