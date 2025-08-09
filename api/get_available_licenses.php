<?php
// api/get_available_licenses.php
require_once '../includes/init.php'; // Asegura que custom_log y decrypt_key estén disponibles

header('Content-Type: application/json'); // La respuesta siempre será JSON
$response = ['success' => false, 'message' => '', 'licenses' => []];

// Solo permitir solicitudes GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

try {
    // MODIFICACIÓN CLAVE: VALIDAR EL TOKEN CSRF PARA SOLICITUDES GET
    // Tu función `validate_csrf_token()` en `functions.php` no busca en `$_GET`.
    // Por lo tanto, realizaremos una validación manual aquí para este endpoint específico.
    $csrf_token_from_get = $_GET['csrf_token'] ?? '';
    
    // Es crucial que el token de sesión exista y que coincida.
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token_from_get)) {
        // Registra el intento fallido
        error_log("Intento de ataque CSRF detectado en get_available_licenses.php. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . " - Expected: " . ($_SESSION['csrf_token'] ?? 'N/A') . " - Received: " . ($csrf_token_from_get ?? 'N/A'));
        throw new Exception('Token CSRF inválido.');
    }

    $product_id = (int)($_GET['product_id'] ?? 0); // Obtiene el ID del producto

    if ($product_id <= 0) {
        throw new Exception('ID de producto inválido.'); // Lanza excepción si el ID es inválido
    }

    // Seguridad adicional: Solo permitir a administradores acceder a esta API
    // Si un usuario normal intenta acceder, también lanzará una excepción.
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Acceso no autorizado.'); // Lanza excepción si el usuario no es admin
    }

    // Consulta para obtener licencias disponibles para el producto específico
    $stmt = $pdo->prepare("SELECT id, license_key_encrypted FROM licenses WHERE product_id = ? AND status = 'disponible' ORDER BY id ASC");
    $stmt->execute([$product_id]);
    $available_licenses_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $licenses_formatted = [];
    foreach ($available_licenses_raw as $license) {
        // Para depuración: loggear intento de desencriptación
        custom_log("DEBUG: get_available_licenses.php: Intentando desencriptar licencia ID: " . $license['id'] . " - Clave encriptada (parcial): " . substr($license['license_key_encrypted'], 0, 50) . "...", 'decrypt_debug.log');
        $decrypted_key = decrypt_key($license['license_key_encrypted']); // Desencripta la clave

        if (!empty($decrypted_key)) {
            $licenses_formatted[] = [
                'id' => $license['id'],
                'key_masked' => substr($decrypted_key, -4) // Enmascara la clave para mostrar solo los últimos 4 caracteres
            ];
        } else {
            // Si falla la desencriptación, registra el ID de la licencia
            custom_log("ERROR: get_available_licenses.php: Fallo al desencriptar licencia ID: " . $license['id'] . ". Clave encriptada completa: " . $license['license_key_encrypted'], 'decrypt_debug.log');
        }
    }

    $response['success'] = true; // Marca la respuesta como exitosa
    $response['licenses'] = $licenses_formatted; // Añade las licencias formateadas
    $response['message'] = 'Licencias disponibles obtenidas.'; // Mensaje de éxito

} catch (Exception $e) {
    // Captura cualquier excepción y asegura que la respuesta sea un JSON válido de error.
    $response['message'] = 'Error al cargar licencias: ' . $e->getMessage();
    custom_log("ERROR en get_available_licenses.php (catch): " . $e->getMessage(), 'api_errors.log');
}

echo json_encode($response); // Devuelve la respuesta JSON
exit;