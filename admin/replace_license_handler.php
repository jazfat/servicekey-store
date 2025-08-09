<?php
// admin/replace_license_handler.php
// Este script se encarga de reemplazar una licencia asignada previamente por una nueva.

require_once '../includes/init.php'; // Ruta a init.php desde admin/

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error desconocido.'];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    custom_log("DEBUG: replace_license_handler.php: Acceso denegado o método no permitido. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'), 'admin_security.log');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado o método no permitido.']);
    exit;
}

try {
    validate_csrf_token(); 

    $order_id = (int)($_POST['order_id'] ?? 0);
    $order_item_id = (int)($_POST['order_item_id'] ?? 0);
    $old_license_id = (int)($_POST['old_license_id'] ?? 0);
    $new_license_id = (int)($_POST['new_license_id'] ?? 0);
    $replacement_reason = trim($_POST['replacement_reason'] ?? '');

    // Validación básica de IDs (ahora deben ser > 0)
    if ($order_id <= 0 || $order_item_id <= 0 || $old_license_id <= 0 || $new_license_id <= 0) {
        throw new Exception('Faltan IDs o son inválidos para el reemplazo.');
    }

    $pdo->beginTransaction();

    // 1. Verificar el ítem de la orden y la licencia antigua
    $stmt_verify_item = $pdo->prepare("SELECT product_id FROM order_items WHERE id = ? AND order_id = ? AND license_id = ?");
    $stmt_verify_item->execute([$order_item_id, $order_id, $old_license_id]);
    $item_data = $stmt_verify_item->fetch(PDO::FETCH_ASSOC);

    if (!$item_data) {
        throw new Exception('Ítem de orden o licencia antigua no coinciden/no son válidos.');
    }

    // 2. Verificar la nueva licencia
    $stmt_verify_new_license = $pdo->prepare("SELECT product_id, status FROM licenses WHERE id = ?");
    $stmt_verify_new_license->execute([$new_license_id]);
    $new_license_data = $stmt_verify_new_license->fetch(PDO::FETCH_ASSOC);

    if (!$new_license_data || $new_license_data['product_id'] !== $item_data['product_id'] || $new_license_data['status'] !== 'disponible') {
        throw new Exception('La nueva licencia no es válida para este producto o no está disponible.');
    }

    // 3. Marcar la licencia antigua como 'invalida' (o 'devuelta', si tienes ese estado)
    $stmt_update_old_license = $pdo->prepare("UPDATE licenses SET status = 'invalida', notes = CONCAT(COALESCE(notes, ''), '\nReemplazada en orden ', ?, ' ítem ', ?, ' por: ', ?) WHERE id = ?");
    $stmt_update_old_license->execute([$order_id, $order_item_id, $replacement_reason, $old_license_id]);

    // 4. Marcar la nueva licencia como 'vendida'
    $stmt_update_new_license = $pdo->prepare("UPDATE licenses SET status = 'vendida' WHERE id = ?");
    $stmt_update_new_license->execute([$new_license_id]);

    // 5. Actualizar el ítem de la orden con la nueva licencia
    $stmt_update_order_item = $pdo->prepare("UPDATE order_items SET license_id = ? WHERE id = ?");
    $stmt_update_order_item->execute([$new_license_id, $order_item_id]);

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Licencia reemplazada con éxito. La clave antigua ha sido invalidada.';
    error_log("INFO: Licencia {$old_license_id} reemplazada por {$new_license_id} en order_item {$order_item_id} (Orden {$order_id}). Motivo: {$replacement_reason}");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Error al reemplazar la licencia: ' . htmlspecialchars($e->getMessage());
    error_log("ERROR en replace_license_handler.php: " . $e->getMessage() . " en línea " . $e->getLine() . " en " . $e->getFile() . ". POST: " . print_r($_POST, true));
}

echo json_encode($response);
exit;