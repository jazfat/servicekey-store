<?php
require_once '../includes/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { die('Acceso denegado.'); }

// Validar el token CSRF para cualquier acción
validate_csrf_token();

$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   if ($action === 'create' || $action === 'update') {
    $id = (int)$_POST['id'];
    $code = trim($_POST['code']);
    $discount = $_POST['discount_percentage'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
    $max_uses = (int)($_POST['max_uses'] ?? 0); // Obtenemos el nuevo campo

    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_percentage, is_active, expiration_date, max_uses) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$code, $discount, $is_active, $expiration_date, $max_uses]);
    } elseif ($action === 'update' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE coupons SET code = ?, discount_percentage = ?, is_active = ?, expiration_date = ?, max_uses = ? WHERE id = ?");
        $stmt->execute([$code, $discount, $is_active, $expiration_date, $max_uses, $id]);
    }

    } elseif ($action === 'delete') { // El borrado ahora también es POST
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header('Location: manage_coupons.php');
exit;