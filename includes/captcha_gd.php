<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: includes/captcha_gd.php
 * Propósito: CAPTCHA nativo PHP con extensión GD (imagen distorsionada, 120s de vigencia)
 *            con interruptor para Google reCAPTCHA v2.
 * =========================================================================================
 */

require_once __DIR__ . '/config.php';

class CaptchaService {

    /**
     * Genera una cadena alfanumérica legible sin caracteres confusos (0, O, 1, I, l).
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
     * Dibuja y emite la imagen CAPTCHA con ruido y distorsión usando PHP GD.
     */
    public static function renderizarImagen(): void {
        $ancho = 150;
        $alto = 46;

        $codigo = self::generarCodigo(5);
        $_SESSION['captcha_code'] = strtoupper($codigo);
        $_SESSION['captcha_time'] = time();

        // Crear lienzo en color verdadero
        $imagen = imagecreatetruecolor($ancho, $alto);

        // Colores de la marca AsoagroAlisal
        $bg = imagecolorallocate($imagen, 244, 248, 244);       // Crema verde tenue
        $borde = imagecolorallocate($imagen, 180, 215, 185);    // Borde verde suave
        $lineas = imagecolorallocate($imagen, 130, 180, 140);   // Ruido líneas
        $puntos = imagecolorallocate($imagen, 80, 140, 95);     // Ruido puntos
        $textoVerde = imagecolorallocate($imagen, 20, 71, 34);  // Verde bosque primario
        $textoAmbar = imagecolorallocate($imagen, 180, 83, 9);  // Ámbar acento

        // Rellenar fondo
        imagefilledrectangle($imagen, 0, 0, $ancho, $alto, $bg);
        imagerectangle($imagen, 0, 0, $ancho - 1, $alto - 1, $borde);

        // Líneas de ruido aleatorias (distorsión)
        for ($i = 0; $i < 6; $i++) {
            imageline(
                $imagen,
                random_int(0, $ancho),
                random_int(0, $alto),
                random_int(0, $ancho),
                random_int(0, $alto),
                $lineas
            );
        }

        // Puntos de ruido aleatorios
        for ($i = 0; $i < 120; $i++) {
            imagesetpixel($imagen, random_int(0, $ancho), random_int(0, $alto), $puntos);
        }

        // Dibujar cada letra con ligera ondulación y variación de color
        $longitud = strlen($codigo);
        $espacio = ($ancho - 20) / $longitud;

        for ($i = 0; $i < $longitud; $i++) {
            $char = $codigo[$i];
            $color = ($i % 2 === 0) ? $textoVerde : $textoAmbar;
            $x = 12 + ($i * $espacio) + random_int(-2, 3);
            $y = 10 + random_int(-3, 4);

            // Uso de fuente nativa GD (tamaño 5 = fuente más grande integrada)
            imagechar($imagen, 5, (int)$x, (int)$y, $char, $color);
        }

        // Curva de distorsión adicional
        for ($x = 0; $x < $ancho; $x += 2) {
            $y = (int)(sin($x / 10) * 4 + $alto / 2);
            imagesetpixel($imagen, $x, $y, $lineas);
        }

        // Encabezados HTTP anti-caché y envío de imagen PNG
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        imagepng($imagen);
        imagedestroy($imagen);
        exit;
    }

    /**
     * Valida la respuesta enviada por el usuario (Nativo GD o Google reCAPTCHA v2).
     */
    public static function validar(?string $respuestaUsuario, ?string $tokenRecaptcha = null): bool {
        // 1. Verificación por Google reCAPTCHA v2 (si está habilitado en config)
        if (USE_RECAPTCHA_V2 && !empty(RECAPTCHA_SECRET_KEY) && !empty($tokenRecaptcha)) {
            return self::validarGoogleRecaptcha($tokenRecaptcha);
        }

        // 2. Verificación Nativa PHP GD en Sesión
        if (empty($_SESSION['captcha_code']) || empty($_SESSION['captcha_time'])) {
            return false;
        }

        // Verificar expiración (2 minutos = 120 segundos)
        $tiempoTranscurrido = time() - (int)$_SESSION['captcha_time'];
        if ($tiempoTranscurrido > CAPTCHA_EXPIRACION_SEGUNDOS) {
            unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);
            return false;
        }

        $esperado = trim((string)$_SESSION['captcha_code']);
        $recibido = strtoupper(trim((string)$respuestaUsuario));

        // Un solo uso estricto: destruir desafío tras comprobarlo
        unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);

        return (!empty($recibido) && $esperado === $recibido);
    }

    /**
     * Validación remota con los servidores de Google reCAPTCHA v2.
     */
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

            $context = stream_context_create($options);
            $res = file_get_contents($url, false, $context);
            if ($res) {
                $json = json_decode($res, true);
                return !empty($json['success']);
            }
        } catch (Exception $e) {
            error_log("Error validando Google reCAPTCHA: " . $e->getMessage());
        }
        return false;
    }
}

// Si se accede a este archivo directamente para renderizar la imagen CAPTCHA
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'captcha_gd.php' || isset($_GET['render_captcha'])) {
    CaptchaService::renderizarImagen();
}

