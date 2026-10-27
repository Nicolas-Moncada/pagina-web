<?php
/**
 * Herramienta de Verificación de Contraste WCAG 2.1 AA
 * Calcula y valida matemáticamente la relación de contraste de todos los tokens
 * en modo Claro y modo Oscuro según el estándar W3C.
 */

function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return [$r, $g, $b];
}

function relativeLuminance($r, $g, $b) {
    $channels = [$r / 255.0, $g / 255.0, $b / 255.0];
    foreach ($channels as &$c) {
        $c = ($c <= 0.04045) ? ($c / 12.92) : pow(($c + 0.055) / 1.055, 2.4);
    }
    return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

function contrastRatio($hex1, $hex2) {
    [$r1, $g1, $b1] = hexToRgb($hex1);
    [$r2, $g2, $b2] = hexToRgb($hex2);
    $l1 = relativeLuminance($r1, $g1, $b1);
    $l2 = relativeLuminance($r2, $g2, $b2);
    $lighter = max($l1, $l2);
    $darker = min($l1, $l2);
    return ($lighter + 0.05) / ($darker + 0.05);
}

// Tokens definidos para ambos modos
$tokens = [
    'light' => [
        'bg'               => '#f4f7f4',
        'surface'          => '#ffffff',
        'surface_2'        => '#eaf1ec',
        'text'             => '#122216',
        'text_muted'       => '#435848',
        'border'           => '#8fa993', // Divisor contrast >= 2.0:1
        'primary'          => '#11501e',
        'primary_hover'    => '#0c3d16',
        'on_primary'       => '#ffffff',
        'input_bg'         => '#ffffff',
        'input_text'       => '#122216',
        'input_border'     => '#65826b', // UI border contrast >= 3.0:1
        'placeholder'      => '#4f6655', // Normal text contrast >= 4.5:1
        'table_head_bg'    => '#0a4217',
        'table_head_text'  => '#ffffff',
        'row_hover'        => '#ebf4ed',
        'cell_edit_bg'     => '#e2f3e5',
        'cell_readonly_bg' => '#f2f6f3',
        'success'          => '#116124',
        'warning'          => '#874f00',
        'danger'           => '#b91c1c'
    ],
    'dark' => [
        'bg'               => '#111813',
        'surface'          => '#17221a',
        'surface_2'        => '#202e24',
        'text'             => '#e5eee7',
        'text_muted'       => '#9cb3a0',
        'border'           => '#3c5242', // Divisor contrast >= 2.0:1
        'primary'          => '#3dd676',
        'primary_hover'    => '#53df87',
        'on_primary'       => '#08230f',
        'input_bg'         => '#131d15',
        'input_text'       => '#e5eee7',
        'input_border'     => '#5c8066', // UI border contrast >= 3.0:1
        'placeholder'      => '#95ad9a', // Normal text contrast >= 4.5:1
        'table_head_bg'    => '#0b3915',
        'table_head_text'  => '#ffffff',
        'row_hover'        => '#1e2d22',
        'cell_edit_bg'     => '#1f3825',
        'cell_readonly_bg' => '#121a14',
        'success'          => '#4ade80',
        'warning'          => '#fbbf24',
        'danger'           => '#f87171'
    ]
];

// Pares a verificar en cada tema
$pares = [
    // Texto sobre superficie y fondo
    ['text', 'surface', 4.5, 'Texto sobre Superficie'],
    ['text', 'bg', 4.5, 'Texto sobre Fondo'],
    ['text', 'surface_2', 4.5, 'Texto sobre Superficie 2'],
    ['text_muted', 'surface', 4.5, 'Texto Secundario sobre Superficie'],
    ['text_muted', 'bg', 4.5, 'Texto Secundario sobre Fondo'],
    ['on_primary', 'primary', 4.5, 'Texto de Botón Primario sobre Fondo Primario'],
    ['input_text', 'input_bg', 4.5, 'Texto de Input sobre Fondo de Input'],
    ['placeholder', 'input_bg', 4.5, 'Placeholder sobre Fondo de Input'],
    ['table_head_text', 'table_head_bg', 4.5, 'Texto Cabecera Tabla sobre Fondo Cabecera'],
    
    // Alertas y estados semánticos sobre superficies
    ['success', 'surface', 4.5, 'Color Éxito sobre Superficie'],
    ['warning', 'surface', 4.5, 'Color Advertencia sobre Superficie'],
    ['danger', 'surface', 4.5, 'Color Peligro sobre Superficie'],
    
    // Elementos gráficos e inputs UI (mínimo 3.0:1)
    ['input_border', 'surface', 3.0, 'Borde de Input sobre Superficie'],
    ['border', 'bg', 2.0, 'Borde contenedor sobre Fondo (Divisores)']
];

$errores = 0;
echo "=================================================================\n";
echo "    VERIFICACIÓN DE CONTRASTE WCAG 2.1 AA (TEMA CLARO Y OSCURO)  \n";
echo "=================================================================\n\n";

foreach (['light' => 'MODO CLARO', 'dark' => 'MODO OSCURO'] as $tema => $nombreTema) {
    echo "--- $nombreTema ---\n";
    $p = $tokens[$tema];
    foreach ($pares as [$c1, $c2, $minimo, $desc]) {
        $hex1 = $p[$c1];
        $hex2 = $p[$c2];
        $ratio = contrastRatio($hex1, $hex2);
        $ok = ($ratio >= $minimo);
        $status = $ok ? "[PASS]" : "[FAIL]";
        if (!$ok) $errores++;
        printf(" %s %-45s: %5.2f:1 (Req: >= %.1f:1) [%s sobre %s]\n", $status, $desc, $ratio, $minimo, $hex1, $hex2);
    }
    echo "\n";
}

if ($errores > 0) {
    echo "❌ ERROR: $errores combinaciones no cumplieron con el estándar WCAG AA.\n";
    exit(1);
} else {
    echo "✅ EXCELENTE: 100% de los pares cumplen con WCAG 2.1 AA en ambos temas.\n";
    exit(0);
}
