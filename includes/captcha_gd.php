<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/captcha_gd.php
 * Propósito: CAPTCHA nativo PHP GD con fallback SVG aritmético si GD no está presente,
 *            validación de un solo uso en servidor (120 s) e interruptor reCAPTCHA v2.
 * =========================================================================================
 */

require_once __DIR__ . '/config.php';

class CaptchaService {

    /**
     * Genera una cadena alfanumérica sin caracteres confusos.
     */
    public static function generarCodigo(int $longitud = 5): string {
        $caracteres = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $codigo = '';
        $max = strlen($caracteres) - 1;
        for ($i = 0; $i < $longitud; $i++) {
            $codigo .= $caracteres[random_int(0, $max)];
        }
        return $codigo;
    }

    /**
     * Dibuja y envía la imagen CAPTCHA. Si GD no está cargado, emite fallback SVG aritmético.
     */
    public static function renderizarImagen(): void {
        if (!extension_loaded('gd')) {
            self::renderizarFallbackSvg();
            return;
        }

        $ancho = 150;
        $alto = 46;

        $codigo = self::generarCodigo(5);
        $_SESSION['captcha_code'] = strtoupper($codigo);
        $_SESSION['captcha_time'] = time();

        $imagen = imagecreatetruecolor($ancho, $alto);

        $bg = imagecolorallocate($imagen, 244, 248, 244);
        $borde = imagecolorallocate($imagen, 180, 215, 185);
        $lineas = imagecolorallocate($imagen, 130, 180, 140);
        $puntos = imagecolorallocate($imagen, 80, 140, 95);
        $textoVerde = imagecolorallocate($imagen, 20, 71, 34);
        $textoAmbar = imagecolorallocate($imagen, 180, 83, 9);

        imagefilledrectangle($imagen, 0, 0, $ancho, $alto, $bg);
        imagerectangle($imagen, 0, 0, $ancho - 1, $alto - 1, $borde);

        // Ruido de líneas
        for ($i = 0; $i < 6; $i++) {
            imageline($imagen, random_int(0, $ancho), random_int(0, $alto), random_int(0, $ancho), random_int(0, $alto), $lineas);
        }

        // Ruido de puntos
        for ($i = 0; $i < 120; $i++) {
            imagesetpixel($imagen, random_int(0, $ancho), random_int(0, $alto), $puntos);
        }

        // Texto con ligera ondulación
        $longitud = strlen($codigo);
        $espacio = ($ancho - 20) / $longitud;

        for ($i = 0; $i < $longitud; $i++) {
            $char = $codigo[$i];
            $color = ($i % 2 === 0) ? $textoVerde : $textoAmbar;
            $x = 12 + ($i * $espacio) + random_int(-2, 3);
            $y = 12 + random_int(-3, 3);
            imagechar($imagen, 5, (int)$x, (int)$y, $char, $color);
        }

        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        imagepng($imagen);
        imagedestroy($imagen);
        exit;
    }

    /**
     * Fallback SVG aritmético si la extensión PHP GD no estuviera disponible.
     */
    private static function renderizarFallbackSvg(): void {
        $n1 = random_int(3, 9);
        $n2 = random_int(2, 8);
        $res = $n1 + $n2;

        $_SESSION['captcha_code'] = (string)$res;
        $_SESSION['captcha_time'] = time();

        header('Content-Type: image/svg+xml');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        echo '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="46" viewBox="0 0 150 46">';
        echo '<rect width="100%" height="100%" fill="#144722" rx="8" stroke="#f59e0b" stroke-width="2"/>';
        echo "<text x='50%' y='60%' dominant-baseline='middle' text-anchor='middle' fill='#fef08a' font-family='monospace' font-weight='bold' font-size='20'>{$n1} + {$n2} = ?</text>";
        echo '</svg>';
        exit;
    }

    /**
     * Valida la respuesta enviada por el usuario (Nativo GD/SVG o Google reCAPTCHA v2).
     */
    public static function validar(?string $respuestaUsuario, ?string $tokenRecaptcha = null): bool {
        if (USE_RECAPTCHA_V2 && !empty(RECAPTCHA_SECRET_KEY) && !empty($tokenRecaptcha)) {
            return self::validarGoogleRecaptcha($tokenRecaptcha);
        }

        if (empty($_SESSION['captcha_code']) || empty($_SESSION['captcha_time'])) {
            return false;
        }

        if (time() - (int)$_SESSION['captcha_time'] > CAPTCHA_EXPIRACION_SEGUNDOS) {
            unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);
            return false;
        }

        $esperado = strtoupper(trim((string)$_SESSION['captcha_code']));
        $recibido = strtoupper(trim((string)$respuestaUsuario));

        unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);

        return (!empty($recibido) && $esperado === $recibido);
    }

    private static function validarGoogleRecaptcha(string $token): bool {
        try {
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = [
                'secret'   => RECAPTCHA_SECRET_KEY,
                'response' => $token,
                'remoteip' => getClientIP()
            ];

            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                    'timeout' => 5
                ]
            ];

            $res = file_get_contents($url, false, stream_context_create($options));
            if ($res) {
                $json = json_decode($res, true);
                return !empty($json['success']);
            }
        } catch (Exception $e) {
            error_log("Error validando reCAPTCHA: " . $e->getMessage());
        }
        return false;
    }
}

// Invocación directa como endpoint
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'captcha_gd.php' || isset($_GET['render_captcha'])) {
    CaptchaService::renderizarImagen();
}
