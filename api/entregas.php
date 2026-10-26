<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $currentUser = requireAuth();
    $fechaInicio = $_GET['fechaInicio'] ?? null;
    $fechaFin = $_GET['fechaFin'] ?? null;
    $fecha = $_GET['fecha'] ?? null;
    $turno = $_GET['turno'] ?? null;

    // IDOR Protection: Ganaderos solo pueden ver sus propios registros
    if ($currentUser['rol'] === 'GANADERO') {
        $usuarioId = $currentUser['id'];
    } else {
        $usuarioId = $_GET['usuarioId'] ?? null;
    }

    // Intentar consultar registros_leche primero
    $sql = "SELECT r.id, r.asociadoId AS usuarioId, r.fecha, r.turno, r.litros, r.creadoEn, 
                   a.nombre, a.cedula 
            FROM registros_leche r 
            JOIN asociados a ON r.asociadoId = a.id 
            WHERE 1=1";
    $params = [];

    if ($fechaInicio && $fechaFin) {
        $sql .= " AND r.fecha >= ? AND r.fecha <= ?";
        $params[] = $fechaInicio;
        $params[] = $fechaFin;
    } elseif ($fecha) {
        $sql .= " AND r.fecha = ?";
        $params[] = $fecha;
    }

    if ($usuarioId) {
        $sql .= " AND r.asociadoId = ?";
        $params[] = $usuarioId;
    }

    if ($turno && in_array($turno, ['MANANA', 'TARDE'])) {
        $sql .= " AND r.turno = ?";
        $params[] = $turno;
    }

    $sql .= " ORDER BY r.fecha ASC, r.creadoEn ASC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Si registros_leche estuviera vacío, consultar de entrega
        if (empty($rows)) {
            $sqlOld = "SELECT e.id, e.usuarioId, e.fecha, e.turno, e.litros, e.creadoEn, u.nombre, u.cedula 
                       FROM entrega e JOIN usuario u ON e.usuarioId = u.id WHERE 1=1";
            $paramsOld = [];
            if ($fechaInicio && $fechaFin) {
                $sqlOld .= " AND e.fecha >= ? AND e.fecha <= ?";
                $paramsOld[] = $fechaInicio;
                $paramsOld[] = $fechaFin;
            } elseif ($fecha) {
                $sqlOld .= " AND e.fecha = ?";
                $paramsOld[] = $fecha;
            }
            if ($usuarioId) {
                $sqlOld .= " AND e.usuarioId = ?";
                $paramsOld[] = $usuarioId;
            }
            if ($turno && in_array($turno, ['MANANA', 'TARDE'])) {
                $sqlOld .= " AND e.turno = ?";
                $paramsOld[] = $turno;
            }
            $stmtOld = $pdo->prepare($sqlOld);
            $stmtOld->execute($paramsOld);
            $rows = $stmtOld->fetchAll();
        }

        $entregas = array_map(function($r) {
            return [
                'id' => $r['id'],
                'usuarioId' => $r['usuarioId'],
                'fecha' => $r['fecha'],
                'turno' => $r['turno'],
                'litros' => (float)$r['litros'],
                'creadoEn' => $r['creadoEn'],
                'usuario' => [
                    'id' => $r['usuarioId'],
                    'cedula' => $r['cedula'],
                    'nombre' => $r['nombre']
                ]
            ];
        }, $rows);

        jsonResponse(['entregas' => $entregas]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al consultar entregas: ' . $e->getMessage()], 500);
    }
}

if ($method === 'POST') {
    requireAuth('ADMIN');
    $data = getRequestData();
    $usuarioId = trim($data['usuarioId'] ?? '');
    $fecha = trim($data['fecha'] ?? '');
    $turno = trim($data['turno'] ?? '');
    $litros = (float)str_replace(',', '.', $data['litros'] ?? '0');

    if (empty($usuarioId) || empty($fecha) || empty($turno) || $litros <= 0) {
        jsonResponse(['error' => 'Datos incompletos o cantidad de litros inválida.'], 400);
    }

    if (!in_array($turno, ['MANANA', 'TARDE'])) {
        jsonResponse(['error' => 'Turno inválido (debe ser MANANA o TARDE).'], 400);
    }

    try {
        // Verificar existencia del ganadero
        $chk = $pdo->prepare("SELECT nombre, cedula FROM asociados WHERE id = ? LIMIT 1");
        $chk->execute([$usuarioId]);
        $ganadero = $chk->fetch();
        if (!$ganadero) {
            $chkU = $pdo->prepare("SELECT nombre, cedula FROM usuario WHERE id = ? LIMIT 1");
            $chkU->execute([$usuarioId]);
            $ganadero = $chkU->fetch();
        }

        $nombreGanadero = $ganadero ? $ganadero['nombre'] : 'Ganadero';
        $id = generarUUID('reg');

        // Guardar en registros_leche
        $stmt = $pdo->prepare("INSERT INTO registros_leche (id, asociadoId, fecha, turno, litros, creadoEn, actualizadoEn) 
                               VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                               ON DUPLICATE KEY UPDATE litros = VALUES(litros), actualizadoEn = NOW()");
        $stmt->execute([$id, $usuarioId, $fecha, $turno, $litros]);

        // Guardar también en entrega
        $stmtE = $pdo->prepare("INSERT INTO entrega (id, usuarioId, fecha, turno, litros, creadoEn) 
                                VALUES (?, ?, ?, ?, ?, NOW())
                                ON DUPLICATE KEY UPDATE litros = VALUES(litros)");
        $stmtE->execute([$id, $usuarioId, $fecha, $turno, $litros]);

        $turnoNombre = ($turno === 'MANANA') ? 'Mañana' : 'Tarde';
        jsonResponse([
            'ok' => true,
            'mensaje' => "✅ Litros guardados: " . number_format($litros, 1) . " L para {$nombreGanadero} ({$turnoNombre})",
            'entrega' => [
                'id' => $id,
                'usuarioId' => $usuarioId,
                'fecha' => $fecha,
                'turno' => $turno,
                'litros' => $litros,
            ]
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al guardar entrega: ' . $e->getMessage()], 500);
    }
}

if ($method === 'DELETE') {
    requireAuth('ADMIN');
    $data = getRequestData();
    $id = $_GET['id'] ?? ($data['id'] ?? null);
    $usuarioId = $_GET['usuarioId'] ?? ($data['usuarioId'] ?? null);
    $fecha = $_GET['fecha'] ?? ($data['fecha'] ?? null);
    $turno = $_GET['turno'] ?? ($data['turno'] ?? null);

    try {
        if ($id) {
            $pdo->prepare("DELETE FROM registros_leche WHERE id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM entrega WHERE id = ?")->execute([$id]);
            jsonResponse(['ok' => true, 'mensaje' => 'Registro eliminado con éxito']);
        } elseif ($usuarioId && $fecha && $turno) {
            $pdo->prepare("DELETE FROM registros_leche WHERE asociadoId = ? AND fecha = ? AND turno = ?")->execute([$usuarioId, $fecha, $turno]);
            $pdo->prepare("DELETE FROM entrega WHERE usuarioId = ? AND fecha = ? AND turno = ?")->execute([$usuarioId, $fecha, $turno]);
            jsonResponse(['ok' => true, 'mensaje' => 'Registro eliminado con éxito']);
        } else {
            jsonResponse(['error' => 'Parámetros insuficientes para eliminar el registro.'], 400);
        }
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al eliminar: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Método no soportado'], 405);
