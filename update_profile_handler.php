<?php
// update_profile_handler.php

require_once 'includes/init.php'; // Carga sesión, PDO, funciones, etc.

// La respuesta siempre será en formato JSON.
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ocurrió un error inesperado.'];

// Seguridad: Solo usuarios autenticados y solicitudes POST
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'No autenticado. Por favor, inicia sesión.';
    echo json_encode($response);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

try {
    // Validar el token CSRF
    validate_csrf_token(); 

    $user_id = $_SESSION['user_id'];
    // Reemplazo del operador ?? con operador ternario para compatibilidad PHP < 7.0
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    $pdo->beginTransaction();

    switch ($action) {
        case 'update_profile':
            // Reemplazo del operador ?? con operador ternario para compatibilidad PHP < 7.0
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $country = isset($_POST['country']) ? trim($_POST['country']) : '';
            $address = isset($_POST['address']) ? trim($_POST['address']) : '';
            $preferred_currency = isset($_POST['preferred_currency']) ? trim($_POST['preferred_currency']) : '';

            // Validaciones de entrada
            if (empty($name)) {
                throw new Exception('El nombre completo es obligatorio.');
            }
            if (empty($country)) {
                throw new Exception('El país es obligatorio.');
            }
            // Puedes añadir más validaciones para el país si lo deseas

            $stmt_update = $pdo->prepare("UPDATE users SET name = ?, country = ?, address = ?, preferred_currency = ? WHERE id = ?");
            $stmt_update->execute([$name, $country, $address, $preferred_currency, $user_id]);

            // Actualizar el nombre en la sesión si ha cambiado
            $_SESSION['user_name'] = $name;

            $response['success'] = true;
            $response['message'] = 'Tu perfil ha sido actualizado exitosamente.';
            custom_log("INFO: Usuario ID {$user_id} actualizó su perfil.", 'user_actions.log');
            break;

        case 'change_password':
            // Reemplazo del operador ?? con operador ternario para compatibilidad PHP < 7.0
            $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_new_password = isset($_POST['confirm_new_password']) ? $_POST['confirm_new_password'] : '';

            // 1. Obtener la contraseña hasheada actual del usuario
            $stmt_get_password = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt_get_password->execute([$user_id]);
            $user = $stmt_get_password->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['password'])) {
                throw new Exception('La contraseña actual es incorrecta.');
            }

            // 2. Validar la nueva contraseña
            if (strlen($new_password) < 6) {
                throw new Exception('La nueva contraseña debe tener al menos 6 caracteres.');
            }
            if ($new_password !== $confirm_new_password) {
                throw new Exception('La nueva contraseña y su confirmación no coinciden.');
            }

            // 3. Hashear y actualizar la nueva contraseña
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update_password = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update_password->execute([$hashed_new_password, $user_id]);

            $response['success'] = true;
            $response['message'] = 'Tu contraseña ha sido cambiada exitosamente.';
            custom_log("INFO: Usuario ID {$user_id} cambió su contraseña.", 'user_actions.log');
            break;

        default:
            throw new Exception('Acción no válida.');
    }

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
    custom_log("ERROR en update_profile_handler.php para usuario ID " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'N/A') . ": " . $e->getMessage(), 'user_errors.log');
}

echo json_encode($response);
exit;