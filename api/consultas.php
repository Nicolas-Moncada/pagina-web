<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $currentUser = requireAuth('ADMIN');
    try {
        $stmt = $pdo->query("SELECT id, nombre, contacto, mensaje, atendida, COALESCE(fecha, NOW()) AS creadoEn FROM consulta ORDER BY id DESC");
        $consultas = $stmt->fetchAll();

        // También traer alertas e inconformidades para el buzón del administrador
        try {
            $altStmt = $pdo->query("SELECT a.id, a.asociadoId AS usuarioId, a.fechaInicio, a.fechaFin, a.mensaje, a.litrosAlMomento, a.estado, a.creadoEn,
                                           u.nombre AS ganaderoNombre, u.cedula
                                    FROM quejas a
                                    JOIN asociados u ON a.asociadoId = u.id
                                    ORDER BY a.creadoEn DESC");
            $alertas = $altStmt->fetchAll();
        } catch (Exception $e1) {
            $alertas = [];
        }

        if (empty($alertas)) {
            try {
                $altStmtOld = $pdo->query("SELECT a.id, a.usuarioId, a.fechaInicio, a.fechaFin, a.mensaje, a.litrosAlMomento, a.estado, a.creadoEn,
                                               u.nombre AS ganaderoNombre, u.cedula
                                        FROM alertaquincena a
                                        JOIN usuario u ON a.usuarioId = u.id
                                        ORDER BY a.creadoEn DESC");
                $alertas = $altStmtOld->fetchAll();
            } catch (Exception $e2) {
                $alertas = [];
            }
        }

        // Solicitudes de restablecimiento de contraseña para el administrador (exclusivo ganaderos)
        try {
            $solStmt = $pdo->query("SELECT s.id, s.usuarioId, s.cedula, s.nombre, s.rol, s.metodo, s.estado, s.ipSolicitud, s.creadoEn,
                                           COALESCE(a.telefono, '') AS telefono
                                    FROM solicitudes_recuperacion s
                                    LEFT JOIN asociados a ON s.usuarioId = a.id
                                    WHERE s.rol = 'GANADERO'
                                    ORDER BY s.creadoEn DESC");
            $solicitudes = $solStmt->fetchAll();
        } catch (Exception $eS) {
            $solicitudes = [];
        }

        jsonResponse(['consultas' => $consultas, 'alertas' => $alertas, 'solicitudes' => $solicitudes]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al consultar buzón: ' . $e->getMessage()], 500);
    }
}

if ($method === 'POST') {
    $data = getRequestData();
    $action = $data['action'] ?? '';

    // Si es acción administrativa de marcar como leída / atendida
    if ($action === 'toggle_atendida') {
        requireAuth('ADMIN');
        $id = $data['id'] ?? '';
        $atendida = !empty($data['atendida']) ? 1 : 0;
        try {
            $pdo->prepare("UPDATE consulta SET atendida = ? WHERE id = ?")->execute([$atendida, $id]);
            jsonResponse(['ok' => true, 'mensaje' => 'Estado de consulta actualizado.']);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Error al actualizar consulta: ' . $e->getMessage()], 500);
        }
    }

    // Si es acción de actualizar estado de queja
    if ($action === 'cambiar_estado_queja') {
        requireAuth('ADMIN');
        $id = $data['id'] ?? '';
        $estado = $data['estado'] ?? 'REVISADO';
        try {
            $pdo->prepare("UPDATE quejas SET estado = ? WHERE id = ?")->execute([$estado, $id]);
            $pdo->prepare("UPDATE alertaquincena SET estado = ? WHERE id = ?")->execute([$estado, $id]);
            jsonResponse(['ok' => true, 'mensaje' => 'Estado de inconformidad actualizado.']);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Error al actualizar queja: ' . $e->getMessage()], 500);
        }
    }

    $nombre = htmlspecialchars(strip_tags(trim($data['nombre'] ?? '')), ENT_QUOTES, 'UTF-8');
    $contacto = htmlspecialchars(strip_tags(trim($data['contacto'] ?? '')), ENT_QUOTES, 'UTF-8');
    $mensaje = htmlspecialchars(strip_tags(trim($data['mensaje'] ?? '')), ENT_QUOTES, 'UTF-8');

    if (empty($nombre) || empty($contacto) || empty($mensaje)) {
        jsonResponse(['error' => 'Por favor completa todos los campos del formulario.'], 400);
    }

    try {
        $id = generarUUID('con');
        $stmt = $pdo->prepare("INSERT INTO consulta (id, nombre, contacto, mensaje, fecha, atendida) VALUES (?, ?, ?, ?, NOW(), 0)");
        $stmt->execute([$id, $nombre, $contacto, $mensaje]);

        jsonResponse([
            'ok' => true,
            'mensaje' => '¡Mensaje enviado con éxito! Nos comunicaremos contigo lo más pronto posible.'
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al guardar la consulta: ' . $e->getMessage()], 500);
    }
}

// DELETE: Eliminar consulta o queja
if ($method === 'DELETE') {
    requireAuth('ADMIN');
    $data = getRequestData();
    $id = $_GET['id'] ?? ($data['id'] ?? null);
    $tipo = $_GET['tipo'] ?? ($data['tipo'] ?? 'consulta');

    if (empty($id)) {
        jsonResponse(['error' => 'ID requerido.'], 400);
    }

    try {
        if ($tipo === 'queja') {
            $pdo->prepare("DELETE FROM quejas WHERE id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM alertaquincena WHERE id = ?")->execute([$id]);
        } else {
            $pdo->prepare("DELETE FROM consulta WHERE id = ?")->execute([$id]);
        }
        jsonResponse(['ok' => true, 'mensaje' => 'Registro eliminado con éxito.']);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error al eliminar: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Método no soportado'], 405);
