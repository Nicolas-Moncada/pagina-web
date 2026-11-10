<?php
/**
 * Herramienta de Verificación de Contraste WCAG 2.1 AAA / AA
 * Paleta Premium Accesible para Adultos Mayores (Azul Marino / Crema / Dorado Ocre)
 */

function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}

function relativeLuminance($rgb) {
    $r = $rgb[0] / 255;
    $g = $rgb[1] / 255;
    $b = $rgb[2] / 255;

    $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
    $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
    $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

function contrastRatio($hex1, $hex2) {
    $l1 = relativeLuminance(hexToRgb($hex1));
    $l2 = relativeLuminance(hexToRgb($hex2));
    $lighter = max($l1, $l2);
    $darker = min($l1, $l2);
    return ($lighter + 0.05) / ($darker + 0.05);
}

// Tokens Premium Accesibles (Sin verdes)
$tokens = [
    'bg'           => '#f7f4ee', // Crema cálido
    'surface'      => '#ffffff', // Blanco puro
    'surface_2'    => '#ede7dc', // Crema / piedra suave
    'text'         => '#101c2b', // Azul marino noche / pizarra profundo
    'text_muted'   => '#3b4a5d', // Azul pizarra medio (alta legibilidad)
    'border'       => '#647890', // Borde pizarra nítido (>= 3.0:1)
    'primary'      => '#153250', // Azul marino corporativo
    'on_primary'   => '#ffffff', // Blanco
    'accent'       => '#b56f10', // Dorado ocre / ámbar tierra
    'on_accent'    => '#ffffff', // Blanco
    'input_bg'     => '#ffffff',
    'input_text'   => '#101c2b',
    'table_head_bg'=> '#153250',
    'table_head_txt'=>'#ffffff',
    'success'      => '#195b8c', // Azul marino acento
    'warning'      => '#9a5806', // Ámbar oscuro
    'danger'       => '#a82020', // Rojo ladrillo sobrio
];

$tests = [
    ['Texto sobre Superficie Blanca', $tokens['text'], $tokens['surface'], 7.0],
    ['Texto sobre Fondo Crema', $tokens['text'], $tokens['bg'], 7.0],
    ['Texto sobre Superficie 2', $tokens['text'], $tokens['surface_2'], 7.0],
    ['Texto Secundario sobre Superficie', $tokens['text_muted'], $tokens['surface'], 7.0],
    ['Texto Secundario sobre Fondo Crema', $tokens['text_muted'], $tokens['bg'], 7.0],
    ['Texto Botón Primario sobre Azul Marino', $tokens['on_primary'], $tokens['primary'], 3.0],
    ['Texto Input sobre Fondo Input', $tokens['input_text'], $tokens['input_bg'], 7.0],
    ['Texto Cabecera Tabla sobre Fondo Azul Marino', $tokens['table_head_txt'], $tokens['table_head_bg'], 7.0],
    ['Borde Input sobre Fondo Blanco', $tokens['border'], $tokens['surface'], 3.0],
    ['Texto Botón Acento Dorado sobre Fondo', $tokens['on_accent'], $tokens['accent'], 3.0],
    ['Alerta Peligro sobre Blanco', $tokens['danger'], $tokens['surface'], 7.0],
];

echo "=================================================================\n";
echo "    VERIFICACIÓN DE CONTRASTE PALETA PREMIUM (AZUL MARINO / CREMA)\n";
echo "    CRITERIO: Texto Normal >= 7:1 (AAA) | Botones/UI >= 3:1\n";
echo "=================================================================\n\n";

$allPassed = true;
foreach ($tests as $t) {
    $desc = $t[0];
    $c1 = $t[1];
    $c2 = $t[2];
    $minReq = $t[3];
    $ratio = contrastRatio($c1, $c2);
    $pass = $ratio >= $minReq;
    if (!$pass) $allPassed = false;
    $status = $pass ? "[PASS]" : "[FAIL]";
    printf(" %s %-45s : %5.2f:1 (Req: >= %.1f:1) [%s sobre %s]\n",
        $status, $desc, $ratio, $minReq, $c1, $c2);
}

echo "\n";
if ($allPassed) {
    echo "[OK] EXCELENTE: 100% de las pruebas cumplen con el estándar de alta legibilidad para adultos mayores (Texto >= 7:1, Botones >= 3:1).\n";
} else {
    echo "[ALERTA]: Algunos pares no cumplieron.\n";
}
