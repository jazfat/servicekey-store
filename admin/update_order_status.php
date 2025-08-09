<?php
// admin/update_order_status.php

require_once '../includes/init.php'; // Carga init.php, que a su vez carga $pdo y custom_log

// Seguridad: Solo admin y POST
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Acceso denegado.');
}
validate_csrf_token();

$order_id = (int)($_POST['order_id'] ?? 0);
$new_order_status = $_POST['order_status'] ?? null;
$new_payment_status = $_POST['payment_status'] ?? null;

if ($order_id <= 0) {
    header('Location: manage_orders.php');
    exit;
}

$pdo->beginTransaction();
try {
    // 1. Actualizar el estado de la orden
    $update_query = "UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$new_order_status, $new_payment_status, $order_id]);

    // 2. Lógica para enviar licencias y completar la orden
    // Solo si el nuevo estado de orden es 'completado' y el pago es 'pagado'
    if ($new_order_status === 'completado' && $new_payment_status === 'pagado') {
        // Verificar cuántos ítems de la orden aún NO tienen licencia asignada
        $stmt_pending_items = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND license_id IS NULL");
        $stmt_pending_items->execute([$order_id]);
        $pending_items_count = $stmt_pending_items->fetchColumn();

        if ($pending_items_count == 0) {
            // Todos los ítems tienen licencia, podemos intentar enviar el correo.
            // Asegúrate de que la función send_licenses_email($order_id) existe y funciona en includes/functions.php
            if (function_exists('send_licenses_email') && send_licenses_email($order_id)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Estados actualizados y licencias enviadas al cliente.'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Estados actualizados, pero hubo un problema al enviar las licencias.'];
            }
        } else {
             $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Estados actualizados. Aún quedan licencias por asignar antes de completar la orden y enviar el correo.'];
        }
    } else {
        // Si no se cumplen las condiciones para completar y enviar licencias, solo se actualizan los estados.
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Estados de la orden actualizados correctamente.'];
    }

    $pdo->commit(); // Confirma la transacción

} catch (Exception $e) {
    $pdo->rollBack(); // Deshace los cambios si algo falla
    error_log("Error al actualizar estado de orden: " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error al procesar la actualización: ' . $e->getMessage()];
}

// Redirigir siempre de vuelta a la página de detalle de la orden
header('Location: view_order.php?id=' . $order_id);
exit;