<?php
require_once '../includes/init.php';

// Seguridad: Verificar que sea un admin y que la petición sea POST
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Acceso denegado.');
}
validate_csrf_token(); // Validar el token CSRF

$action = $_POST['action'] ?? '';
$review_id = (int)($_POST['review_id'] ?? 0);

if ($review_id > 0) {
    if ($action === 'approve') {
        // Cambia el estado 'is_approved' a 1 (verdadero)
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$review_id]);
    } elseif ($action === 'delete') {
        // Elimina permanentemente la reseña de la base de datos
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
    }
}

// Redirigir de vuelta a la página de gestión
header('Location: manage_reviews.php');
exit;