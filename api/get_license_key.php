<?php
// api/get_license_key.php
// Endpoint para obtener una clave de licencia desencriptada para un order_item_id dado.

require_once __DIR__ . '/../includes/init.php'; // Ruta a init.php desde api/

header('Content-Type: application/json'); // La respuesta siempre será JSON

$response = ['success' => false, 'message' => 'Error desconocido.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true); // Lee el JSON del cuerpo de la petición

    $order_item_id = (int)($input['item_id'] ?? 0);
    $csrf_token = $input['csrf_token'] ?? '';

    try {
        validate_csrf_token($csrf_token); // Asume que validate_csrf_token puede validar desde un parámetro.

        if ($order_item_id <= 0) {
            throw new Exception('ID de ítem de orden inválido.');
        }

        // Obtener la licencia asignada a este ítem de la orden
        $stmt = $pdo->prepare("SELECT l.license_key_encrypted FROM order_items oi JOIN licenses l ON oi.license_id = l.id WHERE oi.id = ?");
        $stmt->execute([$order_item_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || empty($result['license_key_encrypted'])) {
            throw new Exception('Licencia no encontrada o no asignada a este ítem.');
        }

        // Desencriptar la clave
        $decrypted_key = decrypt_key($result['license_key_encrypted']); // Asume que decrypt_key() existe en functions.php

        if ($decrypted_key === false) { // decrypt_key debe devolver false en caso de error
            throw new Exception('Error al desencriptar la clave de licencia.');
        }

        $response['success'] = true;
        $response['license_key'] = htmlspecialchars($decrypted_key); // Enviar clave desencriptada
        $response['message'] = 'Clave obtenida con éxito.';

    } catch (Exception $e) {
        $response['message'] = htmlspecialchars($e->getMessage());
        error_log("ERROR en get_license_key.php: " . $e->getMessage() . " Item ID: " . $order_item_id . " POST Data: " . print_r($input, true));
    }
} else {
    $response['message'] = 'Método no permitido.';
}

echo json_encode($response);
exit; // CRÍTICO: Asegura que nada más se imprima