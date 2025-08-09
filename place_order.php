<?php
// place_order.php (Versión Final como API)

// Carga la sesión, la conexión, las funciones y todas las configuraciones necesarias.
require_once 'includes/init.php';

// La respuesta siempre será en formato JSON.
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ocurrió un error inesperado.'];

// 1. Verificaciones de Seguridad Iniciales
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

try {
    // 2. Validar el token CSRF para prevenir ataques.
    validate_csrf_token();

    // 3. Validar que el carrito no esté vacío.
    if (empty($_SESSION['cart'])) {
        throw new Exception('Tu carrito está vacío.');
    }

    // 4. Validar los datos del cliente.
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El nombre y un correo electrónico válido son obligatorios.');
    }

    // 5. Manejar la Subida del Comprobante de Pago
    $receipt_path = null;
    if (isset($_FILES['payment_receipt']) && $_FILES['payment_receipt']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/receipts/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
                throw new Exception(sprintf('Error: El directorio de subida "%s" no se pudo crear.', $upload_dir));
            }
        }
        
        $file_extension = strtolower(pathinfo($_FILES['payment_receipt']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = 'receipt_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['payment_receipt']['tmp_name'], $upload_dir . $file_name)) {
                $receipt_path = $upload_dir . $file_name;
            }
        }
    }
    
    if ($receipt_path === null) {
        throw new Exception('Es obligatorio subir un comprobante de pago válido (JPG, PNG, PDF).');
    }

    // --- Inicio de la Operación Crítica en la Base de Datos ---
    $pdo->beginTransaction();
    
    // 6. Recalcular Totales y Validar Stock (siempre en el backend por seguridad)
    $grand_total_usd = 0;
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt_products = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt_products->execute($product_ids);
    $products_in_cart = $stmt_products->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        if (!isset($products_in_cart[$product_id])) {
            throw new Exception("El producto con ID $product_id ya no existe.");
        }
        if (!is_product_valid($product_id)) {
            throw new Exception("Lo sentimos, el producto '" . htmlspecialchars($products_in_cart[$product_id]['name']) . "' se ha agotado.");
        }
        $grand_total_usd += $products_in_cart[$product_id]['price_usd'] * $quantity;
    }

    $coupon_code = $_SESSION['coupon']['code'] ?? null;
    $discount_percentage = $_SESSION['coupon']['discount'] ?? 0;
    $discount_amount_usd = ($grand_total_usd * $discount_percentage) / 100;
    $final_total_usd = $grand_total_usd - $discount_amount_usd;

    // 7. Crear la Orden Principal en la Base de Datos
    $payment_method = 'manual';
    $order_status = 'procesando';
    $payment_status = 'en_verificacion'; // Estado para indicar que el pago debe ser revisado

    $sql_insert_order = "INSERT INTO orders (user_id, customer_email, total_amount, currency, payment_method, order_status, payment_status, coupon_code, discount_amount, exchange_rate_ves, exchange_rate_cop, payment_receipt_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_order = $pdo->prepare($sql_insert_order);
    $stmt_order->execute([
        $_SESSION['user_id'] ?? null, $email, $final_total_usd, $current_currency, $payment_method, 
        $order_status, $payment_status, $coupon_code, $discount_amount_usd, 
        $currencyConverter->getVesRate(), $currencyConverter->getCopRate(), $receipt_path
    ]);
    $order_id = $pdo->lastInsertId();

    // 8. Crear los Items de la Orden (asignando licencias o registrando como pre-compra)
    $order_has_preorder = false;
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        for ($i = 0; $i < $quantity; $i++) {
            $stmt_license = $pdo->prepare("SELECT id FROM licenses WHERE product_id = ? AND status = 'disponible' LIMIT 1 FOR UPDATE");
            $stmt_license->execute([$product_id]);
            $license = $stmt_license->fetch();

            if ($license) {
                $pdo->prepare("UPDATE licenses SET status = 'vendida' WHERE id = ?")->execute([$license['id']]);
                $pdo->prepare("INSERT INTO order_items (order_id, product_id, license_id, price, quantity) VALUES (?, ?, ?, ?, 1)")->execute([$order_id, $product_id, $license['id'], $products_in_cart[$product_id]['price_usd']]);
            } else {
                $order_has_preorder = true;
                $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (?, ?, ?, 1)")->execute([$order_id, $product_id, $products_in_cart[$product_id]['price_usd']]);
            }
        }
    }
    
    if ($order_has_preorder) {
        $pdo->prepare("UPDATE orders SET order_status = 'pendiente_stock' WHERE id = ?")->execute([$order_id]);
    }

    // 9. Actualizar el contador de uso del cupón si se usó uno
    if (isset($_SESSION['coupon']['id'])) {
        $pdo->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE id = ?")->execute([$_SESSION['coupon']['id']]);
    }

    // 10. Confirmar todos los cambios
    $pdo->commit();

    // 11. Acciones Post-Compra Exitosa
    $_SESSION['last_order_id'] = $order_id;
    send_new_order_admin_notification($order_id);
    
    unset($_SESSION['cart']);
    unset($_SESSION['coupon']);
    
    $response = ['success' => true];

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
    error_log("Error en place_order.php: " . $e->getMessage());
}

echo json_encode($response);
exit;
?>