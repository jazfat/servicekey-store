<?php
// color_palette_tester.php

// Define una constante para la ruta de FPDF.
define('FPDF_PATH', __DIR__ . '/includes/fpdf/fpdf.php');

// Incluir FPDF si existe
if (file_exists(FPDF_PATH)) {
    require_once FPDF_PATH;
} else {
    error_log("Error: fpdf.php no encontrado en " . FPDF_PATH);
}

// Clase extendida para generar Recibos en PDF para PRUEBAS.
class PDF_Invoice_Test extends FPDF {
    public $primary_rgb = [106, 13, 173];
    public $secondary_rgb = [44, 62, 80];
    public $accent_rgb = [0, 255, 255];
    public $bg_rgb = [26, 26, 46];
    public $text_rgb = [224, 224, 224];
    public $card_bg_rgb = [31, 31, 58];

    function Header() {
        // Título del Recibo
        $this->SetFont('Arial', 'B', 28);
        $this->SetTextColor($this->primary_rgb[0], $this->primary_rgb[1], $this->primary_rgb[2]);
        $this->Cell(0, 15, 'RECIBO DE PRUEBA', 0, 1, 'R');
        
        $this->SetLineWidth(1);
        $this->SetDrawColor($this->accent_rgb[0], $this->accent_rgb[1], $this->accent_rgb[2]); 
        $this->Line(150, 30, 200, 30);
        
        $this->Ln(15);
    }

    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor($this->text_rgb[0], $this->text_rgb[1], $this->text_rgb[2]);

        $clarification = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Este es un recibo de prueba generado para verificar la paleta de colores.');
        $this->MultiCell(0, 5, $clarification, 0, 'C');

        $this->Ln(3);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function OrderDetailsTable($header, $data, $order_info) {
        $this->SetFillColor($this->secondary_rgb[0], $this->secondary_rgb[1], $this->secondary_rgb[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor($this->card_bg_rgb[0], $this->card_bg_rgb[1], $this->card_bg_rgb[2]);
        $this->SetFont('Arial', 'B', 12);

        $w = [90, 25, 35, 40];

        for($i=0; $i<count($header); $i++)
            $this->Cell($w[$i], 9, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $header[$i]), 1, 0, 'C', true);
        $this->Ln();

        $this->SetFillColor($this->card_bg_rgb[0], $this->card_bg_rgb[1], $this->card_bg_rgb[2]);
        $this->SetTextColor($this->text_rgb[0], $this->text_rgb[1], $this->text_rgb[2]);
        $this->SetFont('Arial', '', 10);
        $fill = false;

        foreach($data as $row) {
            $product_name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row['name']);
            if ($this->GetStringWidth($product_name) > ($w[0] - 4)) {
                $product_name = substr($product_name, 0, floor(($w[0] - 4) / $this->GetStringWidth('A'))) . '...';
            }

            $this->Cell($w[0], 8, $product_name, 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 8, $row['quantity'], 'LR', 0, 'C', $fill);
            $this->Cell($w[2], 8, '$' . number_format($row['price_usd'], 2, '.', ','), 'LR', 0, 'R', $fill);
            $this->Cell($w[3], 8, '$' . number_format($row['subtotal_usd'], 2, '.', ','), 'LR', 0, 'R', $fill);
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(7);

        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor($this->text_rgb[0], $this->text_rgb[1], $this->text_rgb[2]);

        $this->Cell(array_sum($w) - ($w[2] + $w[3]), 7, '', 0, 0);
        $this->Cell($w[2] + $w[3], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Subtotal: $') . number_format($order_info['raw_subtotal_usd'], 2, '.', ','), 0, 1, 'R');

        if ($order_info['discount_amount'] > 0) {
            $this->Cell(array_sum($w) - ($w[2] + $w[3]), 7, '', 0, 0);
            $this->Cell($w[2] + $w[3], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Descuento (' . $order_info['coupon_code'] . '): -$') . number_format($order_info['discount_amount'], 2, '.', ','), 0, 1, 'R');
        }

        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor($this->accent_rgb[0], $this->accent_rgb[1], $this->accent_rgb[2]);
        $this->Cell(array_sum($w) - ($w[2] + $w[3]), 12, '', 0, 0);
        $this->Cell($w[2] + $w[3], 12, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'TOTAL: $' ) . number_format($order_info['final_total_usd'], 2, '.', ','), 'TB', 1, 'R');
        
        $this->SetDrawColor($this->accent_rgb[0], $this->accent_rgb[1], $this->accent_rgb[2]);
        $this->SetLineWidth(0.8);
        $this->Line($this->GetX() + array_sum($w) - ($w[2] + $w[3]) + 5, $this->GetY() - 0.5, 200, $this->GetY() - 0.5);
        $this->SetLineWidth(0.3);
        $this->Line($this->GetX() + array_sum($w) - ($w[2] + $w[3]) + 5, $this->GetY() + 0.5, 200, $this->GetY() + 0.5);
    }
}

/**
 * Función para generar un PDF de recibo de prueba.
 * @param array $colorPalette Array asociativo de colores RGB para aplicar al PDF.
 * @return void El PDF se envía directamente al navegador o se descarga.
 */
function generate_test_invoice_pdf($colorPalette) {
    if (!class_exists('FPDF')) {
        die("Error: La librería FPDF no está disponible. Asegúrate de que " . FPDF_PATH . " sea correcto.");
    }

    $pdf = new PDF_Invoice_Test();
    
    // Asignar los colores a la instancia del PDF
    $pdf->primary_rgb = $colorPalette['primary_rgb'] ?? [106, 13, 173];
    $pdf->secondary_rgb = $colorPalette['secondary_rgb'] ?? [44, 62, 80];
    $pdf->accent_rgb = $colorPalette['accent_rgb'] ?? [0, 255, 255];
    $pdf->bg_rgb = $colorPalette['bg_rgb'] ?? [26, 26, 46];
    $pdf->text_rgb = $colorPalette['text_rgb'] ?? [224, 224, 224];
    $pdf->card_bg_rgb = $colorPalette['card_bg_rgb'] ?? [31, 31, 58];

    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Datos de ejemplo para el recibo
    $order_id_test = 'TEST-12345';
    $customer_email_test = 'cliente@ejemplo.com';
    $order_date_test = date('Y-m-d H:i:s');
    $raw_subtotal_usd_test = 150.00;
    $discount_amount_test = 15.00;
    $coupon_code_test = 'DESC10';
    $final_total_usd_test = 135.00;

    $items_test = [
        ['name' => 'Licencia de Software Pro', 'quantity' => 1, 'price_usd' => 100.00, 'subtotal_usd' => 100.00],
        ['name' => 'Paquete de Iconos Premium', 'quantity' => 1, 'price_usd' => 50.00, 'subtotal_usd' => 50.00],
        ['name' => 'Soporte Técnico Anual (Ejemplo de nombre de producto muy largo para probar el truncado del nombre en la tabla)', 'quantity' => 1, 'price_usd' => 0.00, 'subtotal_usd' => 0.00],
    ];

    // Información de la Orden y Cliente
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor($pdf->text_rgb[0], $pdf->text_rgb[1], $pdf->text_rgb[2]);
    
    $pdf->Cell(40, 8, 'Recibo Nro:', 0, 0); 
    $pdf->SetFont('Arial', 'B', 12); 
    $pdf->Cell(0, 8, 'ORD-' . $order_id_test, 0, 1);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(40, 8, 'Fecha:', 0, 0); 
    $pdf->SetFont('Arial', 'B', 12); 
    $pdf->Cell(0, 8, date('d/m/Y H:i', strtotime($order_date_test)), 0, 1);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(40, 8, 'Cliente:', 0, 0); 
    $pdf->SetFont('Arial', 'B', 12); 
    $pdf->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $customer_email_test), 0, 1);
    
    $pdf->Ln(15);

    // Preparar datos para la tabla
    $table_header = ['Producto', 'Cant.', 'Precio Uni.', 'Subtotal'];
    $order_info_for_table = [
        'raw_subtotal_usd' => $raw_subtotal_usd_test,
        'discount_amount' => $discount_amount_test,
        'coupon_code' => $coupon_code_test,
        'final_total_usd' => $final_total_usd_test
    ];

    $pdf->OrderDetailsTable($table_header, $items_test, $order_info_for_table);
    
    $pdf->Ln(20);

    // Pie de Recibo de prueba
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor($pdf->text_rgb[0], $pdf->text_rgb[1], $pdf->text_rgb[2]);
    $pdf->MultiCell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Este es un recibo de prueba generado automáticamente. No es una transacción real. Se utiliza para visualizar los colores del tema.'), 0, 'C');

    $pdf->Output('I', 'recibo_prueba_colores.pdf');
    exit;
}

// Función auxiliar para convertir HEX a RGB
function hexToRgb($hex) {
    $hex = str_replace("#", "", $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
}

// --- Lógica principal para extraer colores y procesar solicitud de PDF ---
$cssFilePath = __DIR__ . '/css/style.css'; 
$colors = [];
$cssContent = '';

if (file_exists($cssFilePath)) {
    $cssContent = file_get_contents($cssFilePath);

    preg_match_all('/--([a-zA-Z0-9-]+):\s*#([0-9a-fA-F]{3,6});/', $cssContent, $matches_hex, PREG_SET_ORDER);
    foreach ($matches_hex as $match) {
        $varName = $match[1];
        $hexValue = '#' . $match[2];
        $colors[$varName]['hex'] = $hexValue;
    }

    preg_match_all('/--([a-zA-Z0-9-]+)-rgb:\s*(\d{1,3},\s*\d{1,3},\s*\d{1,3});/', $cssContent, $matches_rgb, PREG_SET_ORDER);
    foreach ($matches_rgb as $match) {
        $varName = str_replace('-rgb', '', $match[1]);
        $rgbValue = $match[2];
        $colors[$varName]['rgb'] = $rgbValue;
    }
} else {
    $colors['error'] = ['message' => 'El archivo style.css no se encontró en la ruta especificada: ' . htmlspecialchars($cssFilePath)];
}

// Inicializar los colores para el formulario y el PDF con los valores por defecto o los del CSS
$pdfTestColors = [
    'primary_rgb' => [106, 13, 173],
    'secondary_rgb' => [44, 62, 80],
    'accent_rgb' => [0, 255, 255],
    'bg_rgb' => [26, 26, 46],
    'text_rgb' => [224, 224, 224],
    'card_bg_rgb' => [31, 31, 58],
];

// Asignar los colores extraídos del CSS a las variables de prueba si existen
if (isset($colors['primary-color']['rgb'])) {
    $pdfTestColors['primary_rgb'] = array_map('intval', explode(',', $colors['primary-color']['rgb']));
} elseif (isset($colors['primary-color']['hex'])) {
    $pdfTestColors['primary_rgb'] = array_map('intval', explode(',', hexToRgb($colors['primary-color']['hex'])));
}

if (isset($colors['secondary-color']['rgb'])) {
    $pdfTestColors['secondary_rgb'] = array_map('intval', explode(',', $colors['secondary-color']['rgb']));
} elseif (isset($colors['secondary-color']['hex'])) {
    $pdfTestColors['secondary_rgb'] = array_map('intval', explode(',', hexToRgb($colors['secondary-color']['hex'])));
}

if (isset($colors['accent-color']['rgb'])) {
    $pdfTestColors['accent_rgb'] = array_map('intval', explode(',', $colors['accent-color']['rgb']));
} elseif (isset($colors['accent-color']['hex'])) {
    $pdfTestColors['accent_rgb'] = array_map('intval', explode(',', hexToRgb($colors['accent-color']['hex'])));
}

if (isset($colors['bg-color']['rgb'])) {
    $pdfTestColors['bg_rgb'] = array_map('intval', explode(',', $colors['bg-color']['rgb']));
} elseif (isset($colors['bg-color']['hex'])) {
    $pdfTestColors['bg_rgb'] = array_map('intval', explode(',', hexToRgb($colors['bg-color']['hex'])));
}

if (isset($colors['text-color']['rgb'])) {
    $pdfTestColors['text_rgb'] = array_map('intval', explode(',', $colors['text-color']['rgb']));
} elseif (isset($colors['text-color']['hex'])) {
    $pdfTestColors['text_rgb'] = array_map('intval', explode(',', hexToRgb($colors['text-color']['hex'])));
}

if (isset($colors['card-bg']['rgb'])) {
    $pdfTestColors['card_bg_rgb'] = array_map('intval', explode(',', $colors['card-bg']['rgb']));
} elseif (isset($colors['card-bg']['hex'])) {
    $pdfTestColors['card_bg_rgb'] = array_map('intval', explode(',', hexToRgb($colors['card-bg']['hex'])));
}


// Si se envió el formulario para generar el PDF con colores personalizados
if (isset($_POST['generate_pdf_custom'])) {
    $customColors = [];
    $colorNames = ['primary', 'secondary', 'accent', 'bg', 'text', 'card_bg']; // Nombres de los colores en el formulario

    foreach ($colorNames as $colorName) {
        $hexInputName = $colorName . '_hex_input';
        if (isset($_POST[$hexInputName]) && !empty($_POST[$hexInputName])) {
            $hexValue = $_POST[$hexInputName];
            $customColors[$colorName . '_rgb'] = array_map('intval', explode(',', hexToRgb($hexValue)));
        } else {
            // Si el campo HEX está vacío, usa el valor predeterminado del CSS o el fallback
            $customColors[$colorName . '_rgb'] = $pdfTestColors[$colorName . '_rgb'];
        }
    }
    
    generate_test_invoice_pdf($customColors);
}

// Función auxiliar para convertir RGB (ej. "255,0,0") a HEX (ej. "#FF0000")
function rgbToHex($rgb_str) {
    list($r, $g, $b) = array_map('intval', explode(',', $rgb_str));
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Probador de Paleta de Colores CSS y PDF</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        <?php
        if (!empty($cssContent)) {
            preg_match('/:root\s*{[^}]+}/s', $cssContent, $root_vars);
            if (isset($root_vars[0])) {
                echo $root_vars[0];
            }
            echo "
            body {
                font-family: 'Roboto', sans-serif;
                background-color: var(--bg-color, #1a1a2e);
                color: var(--text-color, #e0e0e0);
                line-height: 1.6;
                padding: 20px;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
            h1 {
                font-size: clamp(2.5rem, 5vw, 3.5rem);
                margin-bottom: 2rem;
                background: linear-gradient(90deg, var(--primary-color, #6a0dad), var(--accent-color, #00ffff));
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
                text-align: center;
                font-weight: 700;
            }
            .button-group, .color-form-container {
                text-align: center;
                margin-top: 30px;
                background-color: var(--card-bg, #1f1f3a);
                padding: 25px;
                border-radius: 12px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                max-width: 800px;
                margin-left: auto;
                margin-right: auto;
                border: 1px solid var(--secondary-color, #2c3e50);
            }
            .color-form-container h2 {
                color: var(--accent-color, #00ffff);
                margin-top: 0;
                margin-bottom: 20px;
                font-size: 1.8rem;
                border-bottom: 1px solid rgba(var(--secondary-color-rgb, 44, 62, 80), 0.5);
                padding-bottom: 10px;
            }
            .color-input-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .form-group-color {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }
            .form-group-color label {
                font-weight: bold;
                color: var(--text-color-light, #bdc3c7);
                font-size: 0.95rem;
            }
            .form-group-color input[type='color'] {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                width: 60px;
                height: 40px;
                border: none;
                background: none;
                cursor: pointer;
                border-radius: 5px;
            }
            .form-group-color input[type='color']::-webkit-color-swatch-wrapper {
                padding: 0;
            }
            .form-group-color input[type='color']::-webkit-color-swatch {
                border: 2px solid var(--secondary-color, #2c3e50);
                border-radius: 5px;
            }
            .form-group-color input[type='color']::-moz-color-swatch-wrapper {
                padding: 0;
            }
            .form-group-color input[type='color']::-moz-color-swatch {
                border: 2px solid var(--secondary-color, #2c3e50);
                border-radius: 5px;
            }
            .form-group-color input[type='text'] {
                width: 100px;
                padding: 8px;
                border: 1px solid var(--secondary-color, #2c3e50);
                background-color: var(--bg-color, #1a1a2e);
                color: var(--text-color, #e0e0e0);
                border-radius: 5px;
                text-align: center;
                font-family: monospace;
            }
            .btn {
                padding: 12px 25px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 700;
                border: none;
                cursor: pointer;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-size: 1rem;
            }
            .btn-primary {
                background-color: var(--primary-color, #6a0dad);
                color: white;
                box-shadow: 0 4px 10px rgba(var(--primary-color-rgb, 106, 13, 173), 0.4);
            }
            .btn-primary:hover {
                background-color: #8a2be2;
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(var(--primary-color-rgb, 106, 13, 173), 0.6);
            }
            .btn-secondary {
                background-color: transparent;
                border: 2px solid var(--accent-color, #00ffff);
                color: var(--accent-color, #00ffff);
                margin-left: 15px; /* Espacio entre botones */
                box-shadow: none;
            }
            .btn-secondary:hover {
                background-color: var(--accent-color, #00ffff);
                color: var(--bg-color, #1a1a2e);
                transform: translateY(-2px);
            }
            ";
        } else {
            echo "
            body { font-family: sans-serif; background-color: #333; color: #eee; padding: 20px; }
            h1 { color: #fff; text-align: center; margin-bottom: 2rem; }
            .button-group { text-align: center; margin-top: 30px; }
            .btn { padding: 10px 20px; border-radius: 5px; background-color: #007bff; color: white; border: none; cursor: pointer; }
            .color-form-container { background-color: #444; padding: 20px; border-radius: 8px; margin: 30px auto; max-width: 600px; }
            .color-form-container h2 { color: #fff; margin-bottom: 20px; }
            .color-input-grid { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; }
            .form-group-color { display: flex; flex-direction: column; align-items: center; gap: 5px; }
            .form-group-color label { color: #ccc; font-weight: bold; }
            .form-group-color input[type='color'], .form-group-color input[type='text'] { width: 80px; height: 35px; border: 1px solid #555; border-radius: 5px; }
            .form-group-color input[type='text'] { background-color: #333; color: #eee; text-align: center; }
            .btn-secondary { background-color: #6c757d; color: white; border: none; }
            ";
        }
        ?>

        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .color-card {
            background-color: var(--card-bg, #1f1f3a);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--secondary-color, #2c3e50);
        }

        .color-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
        }

        .color-box {
            width: 100%;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .color-info {
            padding: 15px;
            text-align: center;
            line-height: 1.5;
        }

        .color-info h3 {
            margin: 0 0 10px;
            color: var(--accent-color, #00ffff);
            font-size: 1.2rem;
        }

        .color-info p {
            margin: 5px 0;
            font-size: 0.95rem;
            color: var(--text-color-light, #bdc3c7);
        }

        .error-message {
            background-color: rgba(var(--error-color-rgb, 231, 76, 60), 0.2);
            color: var(--error-color, #e74c3c);
            border: 1px solid var(--error-color, #e74c3c);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            max-width: 800px;
            margin: 50px auto;
        }
    </style>
</head>
<body>
    <h1>Paleta de Colores de tu Tema</h1>

    <div class="color-form-container">
        <h2>Personalizar Colores del PDF</h2>
        <form action="" method="POST">
            <input type="hidden" name="generate_pdf_custom" value="1">
            <div class="color-input-grid">
                <?php
                // Array de mapeo para los nombres de las variables de color en el formulario
                $color_map = [
                    'primary_rgb' => 'Principal',
                    'secondary_rgb' => 'Secundario',
                    'accent_rgb' => 'Acento',
                    'bg_rgb' => 'Fondo',
                    'text_rgb' => 'Texto',
                    'card_bg_rgb' => 'Fondo Tarjeta',
                ];

                foreach ($pdfTestColors as $varName => $rgbArray) {
                    $hexValue = rgbToHex(implode(',', $rgbArray));
                    $labelName = $color_map[$varName] ?? str_replace('_rgb', '', $varName); // Nombre amigable
                    $inputName = str_replace('_rgb', '', $varName) . '_hex_input'; // Nombre del input

                    echo '<div class="form-group-color">';
                    echo '<label for="' . $inputName . '">' . htmlspecialchars($labelName) . '</label>';
                    echo '<input type="color" id="' . $inputName . '_picker" value="' . htmlspecialchars($hexValue) . '" onchange="document.getElementById(\'' . $inputName . '\').value = this.value;">';
                    echo '<input type="text" id="' . $inputName . '" name="' . $inputName . '" value="' . htmlspecialchars($hexValue) . '">';
                    echo '</div>';
                }
                ?>
            </div>
            <button type="submit" class="btn btn-primary">Generar PDF con estos colores</button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= $_SERVER['PHP_SELF'] ?>'">Restablecer (Valores CSS)</button>
        </form>
    </div>

    <div class="color-grid">
        <?php if (isset($colors['error'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($colors['error']['message']) ?>
                <p>Por favor, verifica la ruta del archivo `style.css` en este script.</p>
            </div>
        <?php elseif (empty($colors)): ?>
            <div class="error-message">
                No se encontraron variables de color en `style.css` con el formato `--nombre-color: #HEX;` o `--nombre-color-rgb: R, G, B;`.
                <p>Asegúrate de que tus colores estén definidos en la sección `:root`.</p>
            </div>
        <?php else: ?>
            <?php foreach ($colors as $name => $data): 
                $hex = $data['hex'] ?? null;
                $rgb = $data['rgb'] ?? null;

                if ($hex && !$rgb) {
                    $rgb = hexToRgb($hex);
                }
                
                $bgColor = $hex ?: (str_contains($name, '-rgb') ? "var(--{$name})" : "rgb($rgb)"); 
                if ($rgb && !$hex && str_contains($name, '-rgb')) {
                    $bgColor = "var(--" . htmlspecialchars($name) . ")";
                } elseif ($rgb && !$hex) {
                     $bgColor = "rgb($rgb)";
                }
            ?>
                <div class="color-card">
                    <div class="color-box" style="background-color: <?= $bgColor ?>;">
                        <?php 
                        $r = $g = $b = 0;
                        if ($rgb) {
                            list($r, $g, $b) = array_map('intval', explode(',', $rgb));
                        } elseif ($hex) {
                            list($r, $g, $b) = array_map('hexdec', str_split(str_replace('#', '', $hex), 2));
                        }
                        
                        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                        echo ($brightness > 180) ? '<span style="color: #333;">Aa</span>' : '<span style="color: #EEE;">Aa</span>';
                        ?>
                    </div>
                    <div class="color-info">
                        <h3>--<?= htmlspecialchars($name) ?></h3>
                        <?php if ($hex): ?>
                            <p>HEX: <code><?= htmlspecialchars($hex) ?></code></p>
                        <?php endif; ?>
                        <?php if ($rgb): ?>
                            <p>RGB: <code><?= htmlspecialchars($rgb) ?></code></p>
                        <?php endif; ?>
                        <?php if (!$hex && !$rgb): ?>
                            <p>Valor no encontrado o formato incorrecto.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>