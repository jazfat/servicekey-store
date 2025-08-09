<?php
// Carga todas nuestras configuraciones, funciones y la conexión a la BD.
require_once '../includes/init.php';

// La respuesta siempre será en formato JSON.
header('Content-Type: application/json');

try {
    // Seguridad: Si no hay nada en el carrito, no se puede crear una orden.
    if (empty($_SESSION['cart'])) {
        throw new Exception("El carrito está vacío.");
    }
    
    // Obtenemos los datos enviados por el JavaScript del checkout.
    $customer_data = json_decode(file_get_contents('php://input'), true);
    $_POST['csrf_token'] = $customer_data['csrf_token'] ?? ''; // Hacemos accesible el token para la validación.
    validate_csrf_token();

    // Recalculamos el total final en el backend por seguridad. Nunca confiamos en el precio que envía el cliente.
    $grand_total_usd = 0;
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, price_usd FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products_in_cart = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $grand_total_usd += $products_in_cart[$product_id] * $quantity;
    }
    $discount_percentage = $_SESSION['coupon']['discount'] ?? 0;
    $final_total_usd = number_format($grand_total_usd - ($grand_total_usd * $discount_percentage / 100), 2, '.', '');
    
    // Guardamos los datos del cliente y el total en la sesión para usarlos de forma segura en el siguiente paso (captura del pago).
    $_SESSION['checkout_data'] = [
        'customer_name' => $customer_data['customer_name'] ?? 'Invitado',
        'customer_email' => $customer_data['customer_email'] ?? '',
        'final_total' => $final_total_usd
    ];

        // Llamamos a la función global para obtener el token de acceso de PayPal.
    $accessToken = get_paypal_access_token();
    if (!$accessToken) {
        throw new Exception("No se pudo autenticar con PayPal en este momento.");
    }
    
    // Preparamos y enviamos la petición para crear la orden en los servidores de PayPal.
    $url = PAYPAL_API_URL . '/v2/checkout/orders';
    $payload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'amount' => [
                'currency_code' => 'USD',
                'value' => $final_total_usd
            ]
        ]],
        'application_context' => [
            'brand_name' => 'ServiceKey Store',
            'shipping_preference' => 'NO_SHIPPING' // Importante para productos digitales.
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $result = curl_exec($ch);
    curl_close($ch);
    
    // Devolvemos la respuesta de PayPal (que contiene el ID de la orden) al JavaScript del checkout.
    echo $result;

} catch (Exception $e) {
    http_response_code(500); // Código de error del servidor.
    echo json_encode(['error' => $e->getMessage()]);
}
exit;