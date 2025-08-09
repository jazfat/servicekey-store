<?php
require_once '../includes/init.php';

header('Content-Type: application/json');

$item_count = 0;
if (!empty($_SESSION['cart'])) {
    // CORRECCIÓN: Sumar todos los valores (cantidades) del array del carrito.
    $item_count = array_sum($_SESSION['cart']);
}

$response = [
    'items' => [],
    'total_formatted' => $currencyConverter->convertAndFormat(0, $current_currency),
    'item_count' => $item_count
];

if (!empty($_SESSION['cart'])) {
    $grand_total_usd = 0;
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price_usd, image_url FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal_usd = $product['price_usd'] * $quantity;
        $grand_total_usd += $subtotal_usd;
        
        $response['items'][] = [
            'id' => $product['id'],
            'name' => htmlspecialchars($product['name']),
            'image_url' => htmlspecialchars($product['image_url']),
            'quantity' => $quantity,
            'subtotal_formatted' => $currencyConverter->convertAndFormat($subtotal_usd, $current_currency)
        ];
    }
    $response['total_formatted'] = $currencyConverter->convertAndFormat($grand_total_usd, $current_currency);
}

echo json_encode($response);
exit;