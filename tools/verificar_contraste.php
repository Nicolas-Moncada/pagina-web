<?php
/**
 * Herramienta de Verificación de Contraste WCAG 2.1 AAA / AA
 * Paleta Corporativa: Bosque Esmeralda (#0F4C3A) / Salvia (#F4F7F5) / Cobre Dorado (#D97706)
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

// Tokens Corporativos Bosque Esmeralda, Salvia & Cobre Dorado
$tokens = [
    'bg_canvas'      => '#F4F7F5', // Salvia muy sutil
    'bg_surface'     => '#FFFFFF', // Blanco puro
    'bg_subtle'      => '#ECF2EE', // Salvia suave
    'text_primary'   => '#0F1F1A', // Pizarra bosque oscuro
    'text_secondary' => '#475569', // Gris pizarra neutro
    'brand_primary'  => '#0F4C3A', // Bosque Esmeralda
    'brand_dark'     => '#082F24', // Abeto Oscuro
    'brand_secondary'=> '#15803D', // Esmeralda Vivo
    'on_primary'     => '#FFFFFF', // Blanco
    'accent_hover'   => '#B45309', // Cobre Dorado oscuro (para botones con texto blanco)
    'shift_am_text'  => '#92400E', // Ámbar oscuro sobre crema AM
    'shift_am_bg'    => '#FEF3C7',
    'shift_pm_text'  => '#1E40AF', // Cobalto sobre azul suave PM
    'shift_pm_bg'    => '#EFF6FF',
    'danger'         => '#B91C1C', // Rojo institucional sobrio
];

$tests = [
    ['Texto Principal sobre Blanco', $tokens['text_primary'], $tokens['bg_surface'], 7.0],
    ['Texto Principal sobre Canvas Salvia', $tokens['text_primary'], $tokens['bg_canvas'], 7.0],
    ['Texto Principal sobre Salvia Suave', $tokens['text_primary'], $tokens['bg_subtle'], 7.0],
    ['Texto Secundario sobre Blanco', $tokens['text_secondary'], $tokens['bg_surface'], 4.5],
    ['Texto Blanco sobre Bosque Esmeralda', $tokens['on_primary'], $tokens['brand_primary'], 7.0],
    ['Texto Blanco sobre Abeto Oscuro', $tokens['on_primary'], $tokens['brand_dark'], 7.0],
    ['Texto Blanco sobre Esmeralda Vivo', $tokens['on_primary'], $tokens['brand_secondary'], 4.5],
    ['Texto Blanco sobre Cobre Dorado (Botón CTA)', $tokens['on_primary'], $tokens['accent_hover'], 4.5],
    ['Etiqueta Turno AM sobre Fondo AM', $tokens['shift_am_text'], $tokens['shift_am_bg'], 4.5],
    ['Etiqueta Turno PM sobre Fondo PM', $tokens['shift_pm_text'], $tokens['shift_pm_bg'], 4.5],
    ['Alerta Peligro sobre Blanco', $tokens['danger'], $tokens['bg_surface'], 4.5],
];

echo "=================================================================\n";
echo "   VERIFICACIÓN DE CONTRASTE PALETA BOSQUE ESMERALDA & COBRE\n";
echo "   CRITERIO: Texto Normal >= 7:1 (AAA) / >= 4.5:1 (AA)\n";
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
    echo "[OK] EXCELENTE: 100% de las pruebas cumplen con el estándar WCAG AA/AAA de alta legibilidad.\n";
} else {
    echo "[ALERTA]: Algunos pares no cumplieron.\n";
}
