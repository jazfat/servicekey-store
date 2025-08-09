<?php
// admin/assign_license_handler.php
// Este script se encarga de ASIGNAR UNA LICENCIA ESPECÍFICA a un ítem de orden pendiente.

require_once '../includes/init.php'; // Ruta a init.php desde admin/

// Seguridad: Solo admin y método POST
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    custom_log("DEBUG: assign_license_handler.php: Acceso denegado o método no permitido. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'), 'admin_security.log');
    // Para AJAX, siempre devuelve JSON, incluso si hay un error de seguridad inicial
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado o método no permitido.']);
    exit;
}

header('Content-Type: application/json'); // La respuesta siempre será JSON

try {
    validate_csrf_token(); // Validación CSRF. Asume que lanza una excepción si falla.

    $order_item_id = (int)($_POST['order_item_id'] ?? 0);
    $license_id = (int)($_POST['license_id'] ?? 0);
    $order_id = (int)($_POST['order_id'] ?? 0); // Para redirigir de vuelta

    // --- Depuración Inicial ---
    custom_log("DEBUG ASSIGN ITEM: Recibido order_id: " . $order_id, 'assign_license_item.log');
    custom_log("DEBUG ASSIGN ITEM: Recibido order_item_id: " . $order_item_id, 'assign_license_item.log');
    custom_log("DEBUG ASSIGN ITEM: Recibido license_id: " . $license_id, 'assign_license_item.log');
    custom_log("DEBUG ASSIGN ITEM: POST Data: " . print_r($_POST, true), 'assign_license_item.log');
    // --- Fin Depuración ---

    // VALIDACIÓN CRÍTICA: Asegurarse de que los IDs no sean 0 (ahora que la DB está limpia).
    if ($order_item_id <= 0) {
        throw new Exception('ID de ítem de orden inválido. (Recibido: ' . $order_item_id . ')');
    }
    if ($license_id <= 0) {
        throw new Exception('ID de licencia inválido. Por favor, selecciona una licencia. (Recibido: ' . $license_id . ')');
    }
    if ($order_id <= 0) {
        throw new Exception('ID de orden inválido. (Recibido: ' . $order_id . ')');
    }

    $pdo->beginTransaction(); // Inicia la transacción

    // 1. Verificar y obtener datos del order_item
    $stmt_verify_item = $pdo->prepare("
        SELECT oi.product_id FROM order_items oi
        WHERE oi.id = ? AND oi.order_id = ? AND oi.license_id IS NULL
    ");
    $stmt_verify_item->execute([$order_item_id, $order_id]);
    $item_data = $stmt_verify_item->fetch(PDO::FETCH_ASSOC);

    if (!$item_data) {
        throw new Exception('Ítem de orden no encontrado, ya asignado, o no pertenece a la orden especificada.');
    }
    custom_log("DEBUG ASSIGN ITEM: Ítem de orden verificado. product_id del ítem: " . $item_data['product_id'], 'assign_license_item.log');


    // 2. Verificar y obtener datos de la licencia seleccionada
    $stmt_verify_license = $pdo->prepare("SELECT product_id, status FROM licenses WHERE id = ?");
    $stmt_verify_license->execute([$license_id]);
    $license_data = $stmt_verify_license->fetch(PDO::FETCH_ASSOC);

    if (!$license_data || $license_data['product_id'] !== $item_data['product_id'] || $license_data['status'] !== 'disponible') {
        throw new Exception('La licencia seleccionada no es válida para este producto o no está disponible.');
    }
    custom_log("DEBUG ASSIGN ITEM: Licencia verificada. Estado: " . $license_data['status'], 'assign_license_item.log');


    // 3. Asignar la license_id al ítem de la orden
    $stmt_update_item = $pdo->prepare("UPDATE order_items SET license_id = ? WHERE id = ? AND order_id = ?");
    $stmt_update_item->execute([$license_id, $order_item_id, $order_id]);
    custom_log("DEBUG ASSIGN ITEM: Licencia {$license_id} asignada a order_item {$order_item_id}.", 'assign_license_item.log');


    // 4. Marcar la licencia como 'vendida'
    $stmt_update_license = $pdo->prepare("UPDATE licenses SET status = 'vendida' WHERE id = ?");
    $stmt_update_license->execute([$license_id]);
    custom_log("DEBUG ASSIGN ITEM: Licencia {$license_id} marcada como 'vendida'.", 'assign_license_item.log');


    // 5. Verificar si todos los ítems de la orden tienen licencia y actualizar el estado de la orden
    $stmt_check_pending_items = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND license_id IS NULL");
    $stmt_check_pending_items->execute([$order_id]);
    $pending_items_count = $stmt_check_pending_items->fetchColumn();

    if ($pending_items_count == 0) {
        $pdo->prepare("UPDATE orders SET order_status = 'procesando' WHERE id = ? AND order_status = 'pendiente_stock'")->execute([$order_id]);
        custom_log("DEBUG ASSIGN ITEM: Todos los ítems asignados. Orden {$order_id} cambiada a 'procesando'.", 'assign_license_item.log');
    } else {
        custom_log("DEBUG ASSIGN ITEM: Quedan {$pending_items_count} ítems pendientes en la orden {$order_id}.", 'assign_license_item.log');
    }

    $pdo->commit(); // Confirmar todas las operaciones
    $response['success'] = true;
    $response['message'] = 'Licencia asignada correctamente al ítem de la orden.';
    custom_log("INFO: assign_license_handler.php: Licencia asignada exitosamente a order_item {$order_item_id} para orden {$order_id}.", 'assign_license_item.log');


} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Ocurrió un error al asignar la licencia: ' . htmlspecialchars($e->getMessage());
    error_log("ERROR en assign_license_handler.php: " . $e->getMessage() . " en línea " . $e->getLine() . " en " . $e->getFile() . ". POST: " . print_r($_POST, true), 'assign_license_item.log');
}

echo json_encode($response); // Siempre devuelve JSON
exit; // Siempre termina la ejecución