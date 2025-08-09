<?php
// includes/functions.php (Versión Maestra Final y Completa)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Incluir librerías de terceros (PHPMailer, FPDF)
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/fpdf/fpdf.php';
require_once __DIR__ . '/CurrencyConverter.php';

/**
 * Clase extendida para generar Recibos en PDF.
 * Usa colores de la paleta de diseño.
 */
class PDF_Invoice extends FPDF {
    // Definir colores basados en las variables CSS (RGB)
    public $primary_rgb = [106, 13, 173];    // --primary-color #6a0dad
    public $secondary_rgb = [31, 31, 56];    // --secondary-color #1f1f38
    public $accent_rgb = [106, 13, 173];      // --accent-color #6a0dad
    public $bg_rgb = [31, 31, 56];           // --bg-color #1f1f38
    public $text_rgb = [31, 31, 56];         // --text-color #1f1f38
    public $card_bg_rgb = [0, 255, 255];      // --card-bg #00ffff

    function Header() {
        global $site_settings; // Para acceder a la URL del logo
        
        // Logo
        $logoPath = __DIR__ . '/../' . ($site_settings['site_logo_url'] ?? 'assets/images/default_logo.png');
        if (!empty($site_settings['site_logo_url']) && file_exists($logoPath)) {
            $this->Image($logoPath, 10, 6, 40); // Ajustado tamaño del logo
        }

        // Título del Recibo
        $this->SetFont('Arial', 'B', 24); // Fuente más grande para el título
        $this->SetTextColor($this->primary_rgb[0], $this->primary_rgb[1], $this->primary_rgb[2]); // Color primario
        $this->Cell(0, 15, 'RECIBO DE COMPRA', 0, 1, 'R'); // Título a la derecha
        $this->SetLineWidth(0.5);
        $this->SetDrawColor($this->accent_rgb[0], $this->accent_rgb[1], $this->accent_rgb[2]); // Línea del color accent
        $this->Line(150, 28, 200, 28); // Línea debajo del título
        $this->Ln(15); // Espacio después del título
    }

    function Footer() {
        $this->SetY(-20); // Posición desde el final de la página
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor($this->text_rgb[0], $this->text_rgb[1], $this->text_rgb[2]); // Color de texto
        $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Este es un recibo de compra. No es una factura con validez fiscal.'), 0, 0, 'C'); // Aclaración
        $this->Ln(5);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C'); // Numeración de página
    }

    // Método para dibujar la tabla de detalles de la orden
    function OrderDetailsTable($header, $data, $order_info) {
        // Colores y fuentes para encabezados de tabla
        $this->SetFillColor($this->secondary_rgb[0], $this->secondary_rgb[1], $this->secondary_rgb[2]); // Fondo de encabezado
        $this->SetTextColor(255, 255, 255); // Texto blanco
        $this->SetDrawColor($this->accent_rgb[0], $this->accent_rgb[1], $this->accent_rgb[2]); // Borde de tabla
        $this->SetFont('Arial', 'B', 11);

        // Anchos de columna
        $w = [100, 20, 35, 35]; // Nombre, Cant., Precio Uni., Subtotal

        // Cabecera
        for($i=0; $i<count($header); $i++)
            $this->Cell($w[$i], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $header[$i]), 1, 0, 'C', true);
        $this->Ln();

        // Colores y fuentes para filas de datos
        $this->SetFillColor($this->card_bg_rgb[0], $this->card_bg_rgb[1], $this->card_bg_rgb[2]); // Fondo de fila
        $this->SetTextColor($this->text_rgb[0], $this->text_rgb[1], $this->text_rgb[2]); // Texto claro
        $this->SetFont('Arial', '', 10);
        $fill = false; // Alternar color de fila

        foreach($data as $row) {
            $this->Cell($w[0], 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row['name']), 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 6, $row['quantity'], 'LR', 0, 'C', $fill);
            $this->Cell($w[2], 6, '$' . number_format($row['price_usd'], 2, '.', ','), 'LR', 0, 'R', $fill);
            $this->Cell($w[3], 6, '$' . number_format($row['subtotal_usd'], 2, '.', ','), 'LR', 0, 'R', $fill);
            $this->Ln();
            $fill = !$fill; // Alternar color
        }
        // Línea de cierre de tabla
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(5);

        // Resumen de totales
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor($this->text_rgb[0], $this->text_rgb[1], $this->text_rgb[2]);

        // Subtotal
        $this->Cell(array_sum($w) - $w[3] - $w[2], 7, '', 0, 0); // Celda vacía para alinear a la derecha
        $this->Cell($w[2] + $w[3], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Subtotal: $') . number_format($order_info['raw_subtotal_usd'], 2, '.', ','), 0, 1, 'R');

        // Descuento
        if ($order_info['discount_amount'] > 0) {
            $this->Cell(array_sum($w) - $w[3] - $w[2], 7, '', 0, 0);
            $this->Cell($w[2] + $w[3], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Descuento (' . $order_info['coupon_code'] . '): -$') . number_format($order_info['discount_amount'], 2, '.', ','), 0, 1, 'R');
        }

        // Total Final
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor($this->accent_rgb[0], $this->accent_rgb[1], $this->accent_rgb[2]); // Color accent para el total
        $this->Cell(array_sum($w) - $w[3] - $w[2], 10, '', 0, 0);
        $this->Cell($w[2] + $w[3], 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'TOTAL: $' ) . number_format($order_info['final_total_usd'], 2, '.', ','), 'TB', 1, 'R'); // Borde superior e inferior
        $this->SetDrawColor($this->accent_rgb[0], $this->accent_rgb[1], $this->accent_rgb[2]);
        $this->SetLineWidth(0.7);
        $this->Line(170, $this->GetY() - 1, 200, $this->GetY() - 1); // Línea doble debajo del total
        $this->SetLineWidth(0.3);
    }
}

// --- FUNCIONES DE SEGURIDAD Y CIFRADO ---

/**
 * Encripta una clave usando el método y clave definidos en config.php.
 * Genera un IV único para cada encriptación para mayor seguridad.
 * @param string $key La clave (texto plano) a encriptar.
 * @return string La clave encriptada y codificada en base64 con el IV.
 */
function encrypt_key($key) {
    $encryption_key_bytes = base64_decode(ENCRYPTION_KEY); // Decodificar la clave de Base64 a binario
    if (strlen($encryption_key_bytes) !== 32) { // AES-256 requiere una clave de 32 bytes (256 bits)
        error_log("CRITICAL ERROR: ENCRYPTION_KEY en config.php no es una clave binaria de 32 bytes después de decodificación Base64. Su longitud es: " . strlen($encryption_key_bytes));
        return '';
    }

    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    if ($iv_length === false) {
         error_log("CRITICAL ERROR: Método de encriptación '" . ENCRYPTION_METHOD . "' no válido o no soportado.");
         return '';
    }
    $iv = openssl_random_pseudo_bytes($iv_length);

    $encrypted_key = openssl_encrypt($key, ENCRYPTION_METHOD, $encryption_key_bytes, OPENSSL_RAW_DATA, $iv); // Usar OPENSSL_RAW_DATA

    if ($encrypted_key === false) {
        error_log("Error de encriptación: Fallo openssl_encrypt para clave: " . $key);
        return '';
    }

    $result = base64_encode($encrypted_key . '::' . base64_encode($iv)); // Codificar IV en base64 también
    custom_log("DEBUG ENCRYPT: Clave encriptada (parcial) y IV codificado: " . substr($result, 0, 50) . "...", 'encrypt_debug.log');
    return $result;
}

/**
 * Desencripta una clave previamente encriptada con encrypt_key().
 * Espera el formato 'clave_encriptada::IV_codificado_en_base64'.
 * @param string $encrypted_data La cadena encriptada (base64) con el IV.
 * @return string La clave desencriptada o una cadena vacía si falla.
 */
function decrypt_key($encrypted_data) {
    if (empty($encrypted_data)) {
        return '';
    }

    $encryption_key_bytes = base64_decode(ENCRYPTION_KEY); // Decodificar la clave de Base64 a binario
    if (strlen($encryption_key_bytes) !== 32) {
        error_log("CRITICAL ERROR: ENCRYPTION_KEY en config.php no es una clave binaria de 32 bytes después de decodificación Base64 al desencriptar. Su longitud es: " . strlen($encryption_key_bytes));
        return '';
    }

    $decoded_data = base64_decode($encrypted_data, true);
    if ($decoded_data === false || !str_contains($decoded_data, '::')) {
        error_log("Error de desencriptación: el formato del dato es inválido o base64 corrupto. Dato: " . substr($encrypted_data, 0, 50) . "...");
        return '';
    }

    list($encrypted_key, $encoded_iv) = explode('::', $decoded_data, 2);
    $iv = base64_decode($encoded_iv, true); // Decodificar el IV de base64

    $expected_iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    if ($iv === false || strlen($iv) !== $expected_iv_length) {
        error_log("Error de desencriptación: longitud de IV incorrecta o IV corrupto. Esperado: {$expected_iv_length} bytes, Obtenido: " . (is_string($iv) ? strlen($iv) : 'N/A') . " bytes. Encoded IV: " . substr($encoded_iv, 0, 50) . "...");
        return '';
    }

    $decrypted_key = openssl_decrypt($encrypted_key, ENCRYPTION_METHOD, $encryption_key_bytes, OPENSSL_RAW_DATA, $iv); // Usar OPENSSL_RAW_DATA

    if ($decrypted_key === false) {
        error_log("Error de desencriptación: la clave de cifrado o el IV son incorrectos, o datos corruptos. Posiblemente clave encriptada con un ENCRYPTION_KEY diferente o datos alterados.");
        return '';
    }

    custom_log("DEBUG DECRYPT: Clave desencriptada con éxito (parcial): " . substr($decrypted_key, -8), 'encrypt_debug.log');
    return $decrypted_key;
}

/**
 * Enmascara una clave de licencia desencriptada para mostrar solo los últimos 4 caracteres.
 * @param string $decrypted_key La clave de licencia en texto plano.
 * @return string La clave enmascarada.
 */
function mask_license_key($decrypted_key) {
    if (strlen($decrypted_key) > 4) {
        return '****-****-****-' . substr($decrypted_key, -4);
    }
    return '****'; // Por si la clave es muy corta
}

/**
 * Genera un token CSRF y lo almacena en la sesión.
 * @return string El token CSRF.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF recibido de una solicitud POST.
 * Termina la ejecución si el token es inválido.
 */
function validate_csrf_token() {
    // Si no hay token en sesión, es un posible ataque o sesión expirada.
    if (empty($_SESSION['csrf_token'])) {
        die('Error de seguridad (CSRF): Token de sesión no encontrado. Por favor, intenta de nuevo.');
    }

    $token_from_request = $_POST['csrf_token'] ?? '';

    // Si no está en $_POST, buscar en el cuerpo JSON (para requests tipo fetch/API)
    if (empty($token_from_request)) {
        $data = json_decode(file_get_contents('php://input'), true);
        $token_from_request = $data['csrf_token'] ?? '';
    }

    if (!hash_equals($_SESSION['csrf_token'], $token_from_request)) {
        // Registra el intento fallido
        error_log("Intento de ataque CSRF detectado. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . " - Expected: " . ($_SESSION['csrf_token'] ?? 'N/A') . " - Received: " . ($token_from_request ?? 'N/A'));
        die('Error de validación de seguridad (CSRF). Por favor, intenta de nuevo. Si el problema persiste, limpia la caché de tu navegador.');
    }
}

/**
 * Verifica si un producto es válido para añadir al carrito (tiene stock o permite pre-compra).
 * @param int $product_id ID del producto.
 * @return bool True si es válido, false en caso contrario.
 */
function is_product_valid($product_id) {
    global $pdo;
    if ($product_id <= 0) return false;
    $stmt = $pdo->prepare("SELECT allow_preorder, (SELECT COUNT(*) FROM licenses WHERE product_id = ? AND status = 'disponible') as stock FROM products WHERE id = ?");
    $stmt->execute([$product_id, $product_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) return false;
    return ($data['stock'] > 0 || $data['allow_preorder']);
}

/**
 * Obtiene un token de acceso para la API de PayPal.
 * @return string|null El token de acceso o null si falla.
 */
function get_paypal_access_token() {
    $url = PAYPAL_API_URL . '/v1/oauth2/token';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET, // Credenciales de autenticación básica
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: en_US'],
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_SSL_VERIFYPEER => PAYPAL_SANDBOX ? false : true // Deshabilitar para sandbox si hay problemas de certificado
    ]);
    $result = curl_exec($ch);
    if(curl_errno($ch)){
        error_log('cURL error en get_paypal_access_token: ' . curl_error($ch));
        return null;
    }
    curl_close($ch);
    $data = json_decode($result);
    return $data->access_token ?? null;
}

/**
 * Configura una instancia de PHPMailer con los ajustes SMTP del sitio.
 * @return PHPMailer La instancia de PHPMailer configurada.
 */
function setup_mailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Usar SSL/TLS (SMTPS para puerto 465)
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    return $mail;
}

/**
 * Envía un correo electrónico al cliente con las licencias de una orden completada.
 * Incluye el recibo en PDF como adjunto.
 * @param int $order_id ID de la orden.
 * @return bool True si el correo se envió con éxito, false en caso contrario.
 */
function send_licenses_email($order_id) {
    global $pdo;
    $stmt_order = $pdo->prepare("SELECT id, customer_email FROM orders WHERE id = ?");
    $stmt_order->execute([$order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        error_log("send_licenses_email: Orden #{$order_id} no encontrada.");
        return false;
    }

    $stmt_items = $pdo->prepare("SELECT p.name, l.license_key_encrypted FROM order_items oi JOIN licenses l ON oi.license_id = l.id JOIN products p ON l.product_id = p.id WHERE oi.order_id = ? AND oi.license_id IS NOT NULL");
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    if(empty($items)) {
        error_log("send_licenses_email: No hay ítems con licencias asignadas para la orden #{$order_id}.");
        return true; // Considera esto un éxito si no hay licencias para enviar
    }

    $email_body = "<h1>¡Gracias por tu compra!</h1>";
    $email_body .= "<p>Hola, hemos completado tu pedido <strong>#" . htmlspecialchars($order['id']) . "</strong>. Aquí tienes tus licencias:</p>";
    $email_body .= "<table border='1' cellpadding='10' cellspacing='0' width='100%'>";
    $email_body .= "<thead><tr><th>Producto</th><th>Clave de Licencia</th></tr></thead><tbody>";
    foreach ($items as $item) {
        $decrypted_key = decrypt_key($item['license_key_encrypted']);
        $email_body .= "<tr><td>" . htmlspecialchars($item['name']) . "</td><td><strong>" . htmlspecialchars($decrypted_key) . "</strong></td></tr>";
    }
    $email_body .= "</tbody></table>";
    $email_body .= "<p>Gracias por confiar en nosotros.<br>Atentamente,<br>" . SMTP_FROM_NAME . "</p>";

    $invoice_path = generate_invoice_pdf($order_id); // Ahora genera un recibo

    $mail = setup_mailer();
    try {
        $mail->addAddress($order['customer_email']);
        $mail->isHTML(true);
        $mail->Subject = 'Tus licencias del pedido #' . $order['id'] . ' de ' . SMTP_FROM_NAME;
        $mail->Body    = $email_body;
        
        if ($invoice_path && file_exists($invoice_path)) {
            $mail->addAttachment($invoice_path);
        }
        $mail->send();
        
        // Limpiar el archivo PDF después de enviarlo
        if ($invoice_path && file_exists($invoice_path)) {
            unlink($invoice_path);
        }
        return true;
    } catch (Exception $e) {
        // Asegurarse de limpiar el PDF si el envío de correo falla
        if ($invoice_path && file_exists($invoice_path)) {
            unlink($invoice_path);
        }
        error_log("Fallo al enviar email de licencias para orden #{$order_id}: {$mail->ErrorInfo} - Detalles: {$e->getMessage()}");
        return false;
    }
}

/**
 * Envía un correo electrónico para restablecer la contraseña a un usuario.
 * @param string $user_email Correo electrónico del usuario.
 * @param string $token Token de recuperación generado.
 * @return bool True si el correo se envió con éxito, false en caso contrario.
 */
function send_recovery_email($user_email, $token) {
    global $lang;
    $recovery_link = BASE_URL . '/reset_password.php?token=' . $token;
    
    $email_body = "<h1>Recuperación de Contraseña</h1>";
    $email_body .= "<p>Hola,</p>";
    $email_body .= "<p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:</p>";
    $email_body .= '<p><a href="' . htmlspecialchars($recovery_link) . '" style="padding:10px 15px; background-color:#6a0dad; color:white; text-decoration:none; border-radius:5px;">Restablecer Contraseña</a></p>';
    $email_body .= "<p>Si el botón no funciona, copia y pega este enlace en tu navegador: <br>" . htmlspecialchars($recovery_link) . "</p>";
    $email_body .= "<p>Este enlace expirará en 1 hora. Si no solicitaste esto, puedes ignorar este correo.</p>";

    $mail = setup_mailer();
    try {
        $mail->addAddress($user_email);
        $mail->isHTML(true);
        $mail->Subject = $lang['recovery_email_subject'];
        $mail->Body    = $email_body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Fallo al enviar email de recuperación a {$user_email}: {$mail->ErrorInfo} - Detalles: {$e->getMessage()}");
        return false;
    }
}

/**
 * Envía una notificación por correo electrónico al administrador sobre una nueva venta.
 * @param int $order_id ID de la nueva orden.
 * @return bool True si la notificación se envió con éxito, false en caso contrario.
 */
function send_new_order_admin_notification($order_id) {
    global $pdo, $site_settings;
    $stmt_order = $pdo->prepare("SELECT id, customer_email, total_amount, currency, payment_method FROM orders WHERE id = ?");
    $stmt_order->execute([$order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        error_log("send_new_order_admin_notification: Orden #{$order_id} no encontrada.");
        return false;
    }

    $stmt_items = $pdo->prepare("SELECT p.name, oi.quantity, oi.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? GROUP BY p.id, p.name, oi.quantity, oi.price");
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    
    $site_url = BASE_URL;
    $order_link = $site_url . '/admin/view_order.php?id=' . $order_id;
    
    $email_body = "<h1>¡Nueva Venta!</h1>";
    $email_body .= "<p>Se ha registrado la orden <strong>#{$order['id']}</strong> en tu tienda.</p>";
    $email_body .= "<h3>Detalles de la Orden:</h3>";
    $email_body .= "<ul>";
    $email_body .= "<li><strong>Cliente:</strong> " . htmlspecialchars($order['customer_email']) . "</li>";
    $email_body .= "<li><strong>Total:</strong> " . htmlspecialchars($order['currency']) . " " . number_format($order['total_amount'], 2) . "</li>";
    $email_body .= "<li><strong>Método de Pago:</strong> " . htmlspecialchars($order['payment_method']) . "</li>";
    $email_body .= "</ul>";

    $email_body .= "<h3>Productos Comprados:</h3>";
    $email_body .= "<ul>";
    foreach ($items as $item) {
        $email_body .= "<li>" . htmlspecialchars($item['name']) . " (Cantidad: " . $item['quantity'] . ", Precio Unitario: " . $order['currency'] . " " . number_format($item['price'], 2) . ")</li>";
    }
    $email_body .= "</ul>";
    
    $email_body .= "<p style='margin-top:25px;'><a href='" . htmlspecialchars($order_link) . "' style='padding:10px 15px; background-color:#6a0dad; color:white; text-decoration:none; border-radius:5px;'>Ver Orden en el Panel de Administración</a></p>";

    $mail = setup_mailer();
    try {
        if (empty($site_settings['site_admin_email'])) {
             throw new Exception("El correo del administrador no está configurado en los ajustes del sitio.");
        }
        $mail->addAddress($site_settings['site_admin_email']);
        $mail->isHTML(true);
        $mail->Subject = '¡Nueva Venta en tu Tienda! - Orden #' . $order['id'];
        $mail->Body    = $email_body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Fallo al enviar notificación de nueva orden al admin ({$site_settings['site_admin_email']}): {$mail->ErrorInfo} - Detalles: {$e->getMessage()}");
        return false;
    }
}

/**
 * Genera un recibo en PDF para una orden específica.
 * El recibo muestra los precios en USD y tiene un estilo que coincide con la paleta del sitio.
 * No incluye desglose fiscal de impuestos.
 * @param int $order_id ID de la orden.
 * @return string|false La ruta completa al archivo PDF generado, o false si falla.
 */
function generate_invoice_pdf($order_id) {
    global $pdo, $site_settings;
    
    $stmt_order = $pdo->prepare("SELECT id, customer_email, total_amount, currency, coupon_code, discount_amount, created_at FROM orders WHERE id = ?");
    $stmt_order->execute([$order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        error_log("generate_invoice_pdf: Orden #{$order_id} no encontrada.");
        return false;
    }

    $stmt_items = $pdo->prepare("SELECT p.name, oi.price, oi.quantity FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $pdf = new PDF_Invoice();
    $pdf->AliasNbPages(); // Para mostrar 'Página X de Y'
    $pdf->AddPage();
    
    // Información de la Orden y Cliente (Parte superior del contenido)
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor($pdf->text_rgb[0], $pdf->text_rgb[1], $pdf->text_rgb[2]); // Color de texto principal
    
    $pdf->Cell(40, 8, 'Recibo Nro:', 0, 0); $pdf->SetFont('Arial', 'B', 12); $pdf->Cell(0, 8, 'ORD-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT), 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(40, 8, 'Fecha:', 0, 0); $pdf->SetFont('Arial', 'B', 12); $pdf->Cell(0, 8, date('d/m/Y H:i', strtotime($order['created_at'])), 0, 1); // Incluir hora
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(40, 8, 'Cliente:', 0, 0); $pdf->SetFont('Arial', 'B', 12); $pdf->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $order['customer_email']), 0, 1);
    $pdf->Ln(10); // Espacio antes de la tabla de productos

    // Preparar datos para la tabla de OrderDetailsTable
    $table_header = ['Producto', 'Cant.', 'Precio Uni.', 'Subtotal'];
    $table_data = [];
    $raw_subtotal_usd = 0; // Calcularemos el subtotal de ítems aquí
    foreach ($items as $item) {
        $item_subtotal_usd = $item['price'] * $item['quantity'];
        $raw_subtotal_usd += $item_subtotal_usd;
        $table_data[] = [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'price_usd' => $item['price'],
            'subtotal_usd' => $item_subtotal_usd
        ];
    }

    // Información de la orden para pasar a la tabla
    $order_info_for_table = [
        'raw_subtotal_usd' => $raw_subtotal_usd,
        'discount_amount' => $order['discount_amount'],
        'coupon_code' => $order['coupon_code'],
        'final_total_usd' => $order['total_amount']
    ];

    $pdf->OrderDetailsTable($table_header, $table_data, $order_info_for_table);
    
    $pdf->Ln(15); // Espacio después de la tabla

    // Pie de Recibo (puedes añadir aquí más información si es necesaria)
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor($pdf->text_rgb[0], $pdf->text_rgb[1], $pdf->text_rgb[2]);
    $pdf->MultiCell(0, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Gracias por tu compra. Todas las claves de licencia se han enviado a tu correo electrónico registrado. Si tienes alguna pregunta, por favor contáctanos.'), 0, 'C');


    // Guardar el PDF en una carpeta temporal para adjuntarlo al correo
    $invoice_dir = __DIR__ . '/../invoices/';
    if (!is_dir($invoice_dir)) {
        mkdir($invoice_dir, 0777, true);
    }
    $filepath = $invoice_dir . 'recibo-ORD-' . $order['id'] . '-' . uniqid() . '.pdf';
    $pdf->Output('F', $filepath);

    return $filepath;
}

/**
 * Escribe un mensaje en un archivo de log personalizado.
 * @param string $message El mensaje a loguear.
 * @param string $log_file El nombre del archivo de log (ej. 'app.log', 'assign_license.log').
 */
function custom_log($message, $log_file = 'app.log') {
    $log_dir = __DIR__ . '/../logs/'; // Ruta a la carpeta 'logs'
    if (!is_dir($log_dir)) {
        // Intentar crear la carpeta si no existe, con permisos recursivos
        if (!mkdir($log_dir, 0777, true) && !is_dir($log_dir)) {
            // Falló la creación del directorio, no se puede loguear.
            error_log("ERROR: No se pudo crear el directorio de logs: {$log_dir}. Mensaje: {$message}");
            return;
        }
    }
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($log_dir . $log_file, $log_message, FILE_APPEND | LOCK_EX); // LOCK_EX para evitar problemas de concurrencia
}