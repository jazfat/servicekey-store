<?php
require_once '../includes/init.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];
$code = isset($_POST['code']) ? trim($_POST['code']) : '';

if (empty($code)) {
    $response['message'] = 'Por favor, introduce un código.';
    echo json_encode($response);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expiration_date IS NULL OR expiration_date >= CURDATE())");
$stmt->execute([$code]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if ($coupon) {
    // ¡NUEVA VALIDACIÓN DE LÍMITE DE USOS!
    if ($coupon['max_uses'] > 0 && $coupon['uses_count'] >= $coupon['max_uses']) {
        $response['message'] = 'Este código de cupón ha alcanzado su límite de usos.';
    } else {
        // El cupón es válido, lo guardamos en la sesión
        $_SESSION['coupon'] = [
            'id' => $coupon['id'],
            'code' => $coupon['code'],
            'discount' => $coupon['discount_percentage']
        ];
        $response['success'] = true;
        $response['message'] = '¡Cupón aplicado con éxito!';
        $response['discount'] = $coupon['discount_percentage'];
    }
} else {
    unset($_SESSION['coupon']);
    $response['message'] = 'El código del cupón no es válido o ha expirado.';
}

echo json_encode($response);