<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: api/obtener_bitacora.php
 * Propósito: Consulta paginada y detallada de la bitácora de auditoría (audit_logs).
 * =========================================================================================
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once __DIR__ . '/auth_middleware.php';

header('Content-Type: application/json; charset=utf-8');

$usuarioAdmin = requireAdminAuth(false);
$pdo = getDB();

try {
    $limite = min(100, max(10, (int)($_GET['limit'] ?? 50)));

    $stmt = $pdo->prepare("SELECT a.id, a.usuarioId, a.accion, a.tabla, a.registroId, a.campo, a.valorAnterior, a.valorNuevo, a.ip, a.detalles, a.creadoEn, 
                                  COALESCE(u.nombre, 'Sistema/Anónimo') AS usuarioNombre,
                                  u.rol AS usuarioRol
                           FROM audit_logs a
                           LEFT JOIN usuario u ON u.id = a.usuarioId
                           ORDER BY a.creadoEn DESC, a.id DESC
                           LIMIT ?");
    $stmt->bindValue(1, $limite, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();

    $formateados = array_map(function($l) {
        return [
            'id'            => $l['id'],
            'accion'        => $l['accion'],
            'tabla'         => $l['tabla'],
            'campo'         => $l['campo'] ?: '-',
            'valorAnterior' => $l['valorAnterior'] ?: '-',
            'valorNuevo'    => $l['valorNuevo'] ?: '-',
            'usuarioNombre' => $l['usuarioNombre'],
            'ip'            => $l['ip'],
            'detalles'      => $l['detalles'] ?: '',
            'fecha'         => date('d/m/Y H:i:s', strtotime($l['creadoEn']))
        ];
    }, $logs);

    echo json_encode(['ok' => true, 'total' => count($formateados), 'logs' => $formateados], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en api/obtener_bitacora.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error consultando la bitácora.'], JSON_UNESCAPED_UNICODE);
}
