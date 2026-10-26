<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireAuth('ADMIN');
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// GET: Listar ganaderos asociados con sus litros en la quincena actual
if ($method === 'GET') {
    try {
        $fechaInicio = $_GET['fechaInicio'] ?? null;
        $fechaFin = $_GET['fechaFin'] ?? null;

        // Consultar asociados
        $stmt = $pdo->query("SELECT id, cedula, nombre, telefono, finca, activo, creadoEn FROM asociados WHERE rol = 'GANADERO' ORDER BY nombre ASC");
        $ganaderos = $stmt->fetchAll();

        // Si la tabla asociados estuviera vacía, consultar de usuario
        if (empty($ganaderos)) {
            $stmt2 = $pdo->query("SELECT id, cedula, nombre, telefono, '' AS finca, activo, creadoEn FROM usuario WHERE rol = 'GANADERO' ORDER BY nombre ASC");
            $ganaderos = $stmt2->fetchAll();
        }

        // Calcular litros acumulados en la quincena para cada ganadero
        $stmtLitros = $pdo->prepare("SELECT COALESCE(SUM(litros), 0) FROM registros_leche WHERE asociadoId = ? AND fecha >= ? AND fecha <= ?");
        $stmtLitrosOld = $pdo->prepare("SELECT COALESCE(SUM(litros), 0) FROM entrega WHERE usuarioId = ? AND fecha >= ? AND fecha <= ?");

        foreach ($ganaderos as &$g) {
            $stmtLitros->execute([$g['id'], $fechaInicio, $fechaFin]);
            $lts = (float)$stmtLitros->fetchColumn();
            if ($lts == 0) {
                $stmtLitrosOld->execute([$g['id'], $fechaInicio, $fechaFin]);
                $lts = (float)$stmtLitrosOld->fetchColumn();
            }
            $g['totalLitrosQuincena'] = $lts;
        }

        jsonResponse(['ganaderos' => $ganaderos]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al listar ganaderos: ' . $e->getMessage()], 500);
    }
}

// POST: Crear o editar asociado
if ($method === 'POST') {
    $data = getRequestData();
    $id = trim($data['id'] ?? '');
    $cedula = normalizarCedula($data['cedula'] ?? '');
    $nombre = trim($data['nombre'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $finca = trim($data['finca'] ?? '');
    $password = trim($data['password'] ?? '');
    $activo = isset($data['activo']) ? (int)(bool)$data['activo'] : 1;

    if (empty($cedula) || empty($nombre)) {
        jsonResponse(['error' => 'Cédula y nombre son obligatorios.'], 400);
    }

    if (strlen($cedula) < 6 || strlen($cedula) > 12) {
        jsonResponse(['error' => 'Número de cédula inválido (debe tener entre 6 y 12 dígitos numéricos).'], 400);
    }

    try {
        if (!empty($id)) {
            // Validar cédula única al editar
            $chk = $pdo->prepare("SELECT id FROM asociados WHERE cedula = ? AND id != ? LIMIT 1");
            $chk->execute([$cedula, $id]);
            if ($chk->fetch()) {
                jsonResponse(['error' => 'Ya existe otro asociado registrado con esta cédula.'], 400);
            }

            // Actualizar asociado
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    jsonResponse(['error' => 'La contraseña debe tener al menos 8 caracteres.'], 400);
                }
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE asociados SET cedula = ?, nombre = ?, telefono = ?, finca = ?, passwordHash = ?, activo = ? WHERE id = ?");
                $stmt->execute([$cedula, $nombre, $telefono, $finca, $hash, $activo, $id]);

                $stmtU = $pdo->prepare("UPDATE usuario SET cedula = ?, nombre = ?, telefono = ?, passwordHash = ?, activo = ? WHERE id = ?");
                $stmtU->execute([$cedula, $nombre, $telefono, $hash, $activo, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE asociados SET cedula = ?, nombre = ?, telefono = ?, finca = ?, activo = ? WHERE id = ?");
                $stmt->execute([$cedula, $nombre, $telefono, $finca, $activo, $id]);

                $stmtU = $pdo->prepare("UPDATE usuario SET cedula = ?, nombre = ?, telefono = ?, activo = ? WHERE id = ?");
                $stmtU->execute([$cedula, $nombre, $telefono, $activo, $id]);
            }
            jsonResponse(['ok' => true, 'mensaje' => 'Asociado actualizado con éxito.']);
        } else {
            // Crear nuevo asociado
            if (empty($password) || strlen($password) < 8) {
                jsonResponse(['error' => 'La contraseña es obligatoria y debe tener al menos 8 caracteres.'], 400);
            }

            $chk = $pdo->prepare("SELECT id FROM asociados WHERE cedula = ? LIMIT 1");
            $chk->execute([$cedula]);
            if ($chk->fetch()) {
                jsonResponse(['error' => 'Ya existe un ganadero con esta cédula.'], 400);
            }

            $newId = generarUUID('usr');
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("INSERT INTO asociados (id, cedula, nombre, telefono, finca, passwordHash, rol, activo, creadoEn) VALUES (?, ?, ?, ?, ?, ?, 'GANADERO', ?, NOW())");
            $stmt->execute([$newId, $cedula, $nombre, $telefono, $finca, $hash, $activo]);

            $stmtU = $pdo->prepare("INSERT INTO usuario (id, cedula, nombre, telefono, passwordHash, rol, activo, creadoEn) VALUES (?, ?, ?, ?, ?, 'GANADERO', ?, NOW())");
            $stmtU->execute([$newId, $cedula, $nombre, $telefono, $hash, $activo]);

            jsonResponse(['ok' => true, 'mensaje' => 'Nuevo asociado registrado con éxito.', 'id' => $newId]);
        }
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al guardar asociado: ' . $e->getMessage()], 500);
    }
}

// DELETE: Eliminar o desactivar asociado
if ($method === 'DELETE') {
    $data = getRequestData();
    $id = $_GET['id'] ?? ($data['id'] ?? null);

    if (empty($id)) {
        jsonResponse(['error' => 'ID de asociado requerido.'], 400);
    }

    try {
        $pdo->prepare("DELETE FROM registros_leche WHERE asociadoId = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM entrega WHERE usuarioId = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM quejas WHERE asociadoId = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM alertaquincena WHERE usuarioId = ?")->execute([$id]);

        $pdo->prepare("DELETE FROM asociados WHERE id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM usuario WHERE id = ?")->execute([$id]);

        jsonResponse(['ok' => true, 'mensaje' => 'Asociado eliminado correctamente.']);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al eliminar asociado: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Método no soportado'], 405);
