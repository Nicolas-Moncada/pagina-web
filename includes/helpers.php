<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/helpers.php
 * Propósito: Funciones utilitarias globales, CSRF, formateo monetario/litros, SweetAlert2.
 * =========================================================================================
 */

require_once __DIR__ . '/config.php';

/**
 * Escape seguro de cadenas contra XSS para renderizado en HTML.
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Genera o recupera el token CSRF activo de la sesión.
 */
function generarCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna el campo oculto HTML con el token CSRF.
 */
function campoCsrf(): string {
    $token = generarCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Valida un token CSRF recibido contra el almacenado en sesión.
 */
function validarCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Normaliza y valida una cédula (solo dígitos, longitud 6 a 12).
 */
if (!function_exists('normalizarCedula')) {
    function normalizarCedula($cedula): ?string {
        $limpia = preg_replace('/\D/', '', (string)$cedula);
        if (strlen($limpia) < 6 || strlen($limpia) > 12) {
            return null;
        }
        return $limpia;
    }
}

if (!function_exists('getClientIP')) {
    function getClientIP(): string {
        return $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '127.0.0.1';
    }
}

if (!function_exists('generarUUID')) {
    function generarUUID(string $prefix = 'id'): string {
        return $prefix . '_' . substr(bin2hex(random_bytes(8)), 0, 12);
    }
}

/**
 * Formatea un valor numérico a moneda colombiana (COP): $ 1.234.567
 */
function formatoMonedaCOP($valor): string {
    if ($valor === null || $valor === '' || !is_numeric($valor)) {
        return '$ 0';
    }
    return '$ ' . number_format((float)$valor, 0, ',', '.');
}

/**
 * Formatea una cantidad de litros con separador de miles: 1.234,5 L
 */
function formatoLitros($litros, int $decimales = 1): string {
    if ($litros === null || $litros === '' || !is_numeric($litros)) {
        return '0,0 L';
    }
    return number_format((float)$litros, $decimales, ',', '.') . ' L';
}

/**
 * Retorna la fecha en formato legible en español: "15 de Octubre de 2026"
 */
function formatoFechaEspanol(?string $dateStr): string {
    if (empty($dateStr)) return '';
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    $ts = strtotime($dateStr);
    if (!$ts) return $dateStr;
    $dia = date('j', $ts);
    $mesNum = (int)date('n', $ts);
    $ano = date('Y', $ts);
    return "{$dia} de " . ($meses[$mesNum] ?? '') . " de {$ano}";
}

/**
 * Calcula el periodo quincenal actual basado en la fecha de hoy.
 */
function calcularQuincenaActual(): array {
    $diaHoy = (int)date('d');
    $anoHoy = (int)date('Y');
    $mesHoy = (int)date('m');
    $padM = str_pad($mesHoy, 2, '0', STR_PAD_LEFT);

    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    $nombreMes = $meses[$mesHoy];

    if ($diaHoy <= 15) {
        $fIni = "{$anoHoy}-{$padM}-01";
        $fFin = "{$anoHoy}-{$padM}-15";
        $numQ = 1;
        $nombre = "1ra Quincena de {$nombreMes} {$anoHoy}";
    } else {
        $ultimoDia = date('t');
        $fIni = "{$anoHoy}-{$padM}-16";
        $fFin = "{$anoHoy}-{$padM}-{$ultimoDia}";
        $numQ = 2;
        $nombre = "2da Quincena de {$nombreMes} {$anoHoy}";
    }

    return [
        'fechaInicio'   => $fIni,
        'fechaFin'      => $fFin,
        'numeroQuincena'=> $numQ,
        'nombre'        => $nombre,
        'ano'           => $anoHoy,
        'mes'           => $mesHoy,
        'mesNombre'     => $nombreMes
    ];
}

/**
 * Genera la lista de días entre fechaInicio y fechaFin para la planilla de control lechero.
 */
function generarDiasQuincena(string $fechaInicio, string $fechaFin): array {
    $dias = [];
    $diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    
    $start = new DateTime($fechaInicio);
    $end = new DateTime($fechaFin);
    $end->modify('+1 day');

    $period = new DatePeriod($start, new DateInterval('P1D'), $end);

    foreach ($period as $dt) {
        $fechaStr = $dt->format('Y-m-d');
        $w = (int)$dt->format('w');
        $dias[] = [
            'fecha'         => $fechaStr,
            'dia'           => (int)$dt->format('d'),
            'diaSemana'     => $diasSemana[$w],
            'etiqueta'      => $diasSemana[$w] . ' ' . $dt->format('d'),
            'etiquetaCorta' => $dt->format('d')
        ];
    }

    return $dias;
}

/**
 * Establece una alerta flash para mostrarse con SweetAlert2 en la siguiente carga.
 */
function setFlashAlert(string $icon, string $title, string $text = ''): void {
    $_SESSION['swal_flash'] = [
        'icon'  => $icon,
        'title' => $title,
        'text'  => $text
    ];
}

/**
 * Renderiza el bloque de script de SweetAlert2 si existe alerta en flash.
 */
function renderSweetAlert(): string {
    if (empty($_SESSION['swal_flash'])) {
        return '';
    }
    $swal = $_SESSION['swal_flash'];
    unset($_SESSION['swal_flash']);

    $icon = e($swal['icon']);
    $title = e($swal['title']);
    $text = e($swal['text']);

    return "<script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '{$icon}',
                    title: '{$title}',
                    text: '{$text}',
                    confirmButtonColor: '#144722',
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        popup: 'rounded-4 shadow-lg'
                    }
                });
            }
        });
    </script>";
}

/**
 * Verifica si una cédula/IP está bloqueada por intentos fallidos de login.
 */
function verificarRateLimitLogin(PDO $pdo, string $cedula, string $ip): array {
    try {
        $stmt = $pdo->prepare("SELECT intentos, bloqueadoHasta FROM login_intentos WHERE cedula = ? AND ip = ? LIMIT 1");
        $stmt->execute([$cedula, $ip]);
        $row = $stmt->fetch();

        if ($row && !empty($row['bloqueadoHasta'])) {
            $ahora = new DateTime();
            $bloqueo = new DateTime($row['bloqueadoHasta']);
            if ($ahora < $bloqueo) {
                $diff = $bloqueo->getTimestamp() - $ahora->getTimestamp();
                $minutos = max(1, ceil($diff / 60));
                return [
                    'bloqueado' => true,
                    'mensaje'   => "Acceso suspendido temporalmente por reiterados intentos fallidos. Por favor espera {$minutos} minutos."
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error en verificarRateLimitLogin: " . $e->getMessage());
    }

    return ['bloqueado' => false];
}

/**
 * Registra un intento fallido y bloquea si alcanza MAX_LOGIN_INTENTOS.
 */
function registrarIntentoFallido(PDO $pdo, string $cedula, string $ip): void {
    try {
        $stmt = $pdo->prepare("SELECT intentos FROM login_intentos WHERE cedula = ? AND ip = ? LIMIT 1");
        $stmt->execute([$cedula, $ip]);
        $row = $stmt->fetch();

        if ($row) {
            $nuevos = (int)$row['intentos'] + 1;
            $bloqueoHasta = ($nuevos >= MAX_LOGIN_INTENTOS) ? date('Y-m-d H:i:s', time() + (LOCKOUT_TIEMPO_MINUTOS * 60)) : null;

            $upd = $pdo->prepare("UPDATE login_intentos SET intentos = ?, bloqueadoHasta = ? WHERE cedula = ? AND ip = ?");
            $upd->execute([$nuevos, $bloqueoHasta, $cedula, $ip]);
        } else {
            $ins = $pdo->prepare("INSERT INTO login_intentos (cedula, ip, intentos, bloqueadoHasta) VALUES (?, ?, 1, NULL)");
            $ins->execute([$cedula, $ip]);
        }
    } catch (Exception $e) {
        error_log("Error en registrarIntentoFallido: " . $e->getMessage());
    }
}

/**
 * Limpia los intentos fallidos al iniciar sesión con éxito.
 */
function limpiarIntentosFallidos(PDO $pdo, string $cedula, string $ip): void {
    try {
        $stmt = $pdo->prepare("DELETE FROM login_intentos WHERE cedula = ? AND ip = ?");
        $stmt->execute([$cedula, $ip]);
    } catch (Exception $e) {
        error_log("Error en limpiarIntentosFallidos: " . $e->getMessage());
    }
}

/**
 * Registra eventos de seguridad sin almacenar contraseñas ni datos sensibles.
 */
function registrarAuditoria(PDO $pdo, string $evento, ?string $usuarioId = null, ?string $cedula = null, ?string $detalles = null): void {
    try {
        $ip = getClientIP();
        $stmt = $pdo->prepare("INSERT INTO auditoria_seguridad (usuarioId, cedula, evento, ip, detalles, creadoEn) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$usuarioId, $cedula, $evento, $ip, $detalles]);
    } catch (Exception $e) {
        error_log("Error en registrarAuditoria: " . $e->getMessage());
    }
}
