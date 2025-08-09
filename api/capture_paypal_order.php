<?php
// api/capture_paypal_order.php (Versión Final y Limpia)

require_once '../includes/init.php';

header('Content-Type: application/json');
$response = ['success' => false, 'error' => 'Ocurrió un error inesperado.'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $paypalOrderID = $data['orderID'] ?? null;
    $_POST['csrf_token'] = $data['csrf_token'] ?? '';
    validate_csrf_token();
    if (!$paypalOrderID) { throw new Exception('No se proporcionó un ID de orden de PayPal.'); }
    
    $accessToken = get_paypal_access_token();
    if (!$accessToken) { throw new Exception("No se pudo autenticar con PayPal."); }

    $url = PAYPAL_API_URL . "/v2/checkout/orders/$paypalOrderID/capture";
    $ch = curl_init();
    curl_setopt_array($ch, [ CURLOPT_URL => $url, CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken] ]);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $paypal_data = json_decode($result, true);
    
    if ($http_code === 201 && isset($paypal_data['status']) && $paypal_data['status'] === 'COMPLETED') {
        
        $pdo->beginTransaction();
        try {
            $checkout_data = $_SESSION['checkout_data'];
            $customer_email = $checkout_data['customer_email'];
            $final_total_usd = $checkout_data['final_total'];
            $captured_amount = $paypal_data['purchase_units'][0]['payments']['captures'][0]['amount']['value'];

            if (abs($captured_amount - $final_total_usd) > 0.01) { throw new Exception("Discrepancia en el monto del pago."); }
            
            // Lógica de creación de orden
            $coupon_code = $_SESSION['coupon']['code'] ?? null;
            $discount_percentage = $_SESSION['coupon']['discount'] ?? 0;
            $subtotal = ($discount_percentage > 0) ? $final_total_usd / (1 - ($discount_percentage / 100)) : $final_total_usd;
            $discount_amount = $subtotal - $final_total_usd;
            $sql_insert_order = "INSERT INTO orders (user_id, customer_email, total_amount, currency, payment_method, order_status, payment_status, coupon_code, discount_amount, exchange_rate_ves, exchange_rate_cop) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_order = $pdo->prepare($sql_insert_order);
            $stmt_order->execute([ $_SESSION['user_id'] ?? null, $customer_email, $final_total_usd, 'USD', 'paypal', 'procesando', 'pagado', $coupon_code, $discount_amount, $currencyConverter->getVesRate(), $currencyConverter->getCopRate() ]);
            $our_order_id = $pdo->lastInsertId();
            
            $order_has_preorder = false;
            $product_ids = array_keys($_SESSION['cart']);
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $stmt_prods = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt_prods->execute($product_ids);
            $products_in_cart = $stmt_prods->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

            foreach($_SESSION['cart'] as $product_id => $quantity) {
                for ($i=0; $i < $quantity; $i++) {
                    $stmt_license = $pdo->prepare("SELECT id FROM licenses WHERE product_id = ? AND status = 'disponible' LIMIT 1 FOR UPDATE");
                    $stmt_license->execute([$product_id]);
                    $license = $stmt_license->fetch();
                    if ($license) {
                        $pdo->prepare("UPDATE licenses SET status = 'vendida', order_id = ? WHERE id = ?")->execute([$our_order_id, $license['id']]);
                        $pdo->prepare("INSERT INTO order_items (order_id, product_id, license_id, price) VALUES (?, ?, ?, ?)")->execute([$our_order_id, $product_id, $license['id'], $products_in_cart[$product_id]['price_usd']]);
                    } else {
                        $order_has_preorder = true;
                        $pdo->prepare("INSERT INTO order_items (order_id, product_id, price) VALUES (?, ?, ?)")->execute([$our_order_id, $product_id, $products_in_cart[$product_id]['price_usd']]);
                    }
                }
            }

            $order_final_status = $order_has_preorder ? 'pendiente_stock' : 'completado';
            $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?")->execute([$order_final_status, $our_order_id]);

            if ($coupon_code) {
                $pdo->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE id = ?")->execute([$_SESSION['coupon']['id']]);
            }
            
            $pdo->commit();
            
            $_SESSION['last_order_id'] = $our_order_id;
            send_new_order_admin_notification($our_order_id);
            if (!$order_has_preorder) {
                send_licenses_email($our_order_id);
            }
            
            unset($_SESSION['cart'], $_SESSION['coupon'], $_SESSION['checkout_data']);
            $response = ['success' => true];

        } catch (Exception $e) { $pdo->rollBack(); throw $e; }
    } else {
        throw new Exception("El pago no pudo ser verificado por PayPal.");
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en captura de PayPal: " . $e->getMessage());
    $response = ['success' => false, 'error' => 'Error interno al procesar el pago.'];
}

echo json_encode($response);
exit;