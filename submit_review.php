<?php
require_once 'includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

validate_csrf_token();

$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment']);

// Doble verificación en el backend para asegurar que el usuario compró el producto
$stmt_check = $pdo->prepare("SELECT o.id FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'completado' LIMIT 1");
$stmt_check->execute([$user_id, $product_id]);

if ($stmt_check->fetch() && $rating >= 1 && $rating <= 5) {
    try {
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $user_id, $rating, $comment]);
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => '¡Gracias! Tu reseña ha sido enviada y está pendiente de aprobación.'];
    } catch (PDOException $e) {
        // Error, probablemente porque ya existe una reseña (violación de la clave única)
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Ya has enviado una reseña para este producto.'];
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'No puedes dejar una reseña para un producto que no has comprado.'];
}

header('Location: producto.php?id=' . $product_id);
exit;