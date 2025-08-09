<?php
// admin/license_handler.php
// Este script se encarga de AÑADIR NUEVAS LICENCIAS a un producto.

require_once '../includes/init.php'; // Carga init.php, que a su vez carga $pdo y custom_log

// Seguridad: Solo admin y método POST
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    custom_log("DEBUG: license_handler.php: Acceso denegado o método no permitido. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'), 'admin_security.log');
    // Si se accede directamente y no es admin/POST, se redirige o se da un error.
    // Para handlers puros, es mejor devolver JSON de error.
    header('Content-Type: application/json'); // En este caso, ya que es un handler, se espera JSON.
    echo json_encode(['success' => false, 'message' => 'Acceso denegado o método no permitido.']);
    exit;
}

// Ya que es un handler que solo devuelve JSON, configuramos el header al inicio
header('Content-Type: application/json');

try {
    validate_csrf_token(); // Validación CSRF para el formulario

    $product_id = (int)($_POST['product_id'] ?? 0);
    $license_keys_raw = trim($_POST['license_keys'] ?? '');
    $costo_usd = (float)($_POST['costo_usd'] ?? 0.00);

    // --- Depuración Inicial ---
    custom_log("DEBUG ADD LICENSE: Recibido product_id: " . $product_id, 'add_license.log');
    custom_log("DEBUG ADD LICENSE: Costo USD: " . $costo_usd, 'add_license.log');
    custom_log("DEBUG ADD LICENSE: Claves recibidas (primeras 100 chars): " . substr($license_keys_raw, 0, 100) . "...", 'add_license.log');
    // --- Fin Depuración ---

    // Validaciones de entrada
    if ($product_id <= 0) {
        throw new Exception('ID de producto inválido. Por favor, selecciona un producto.');
    }
    if (empty($license_keys_raw)) {
        throw new Exception('Debes proporcionar al menos una clave de licencia.');
    }
    if ($costo_usd < 0) {
        throw new Exception('El costo de la licencia no puede ser negativo.');
    }

    // Procesar las claves de licencia (una por línea)
    $license_keys = array_filter(array_map('trim', explode("\n", $license_keys_raw)));
    $added_count = 0;

    if (empty($license_keys)) {
        throw new Exception('No se encontraron claves de licencia válidas para añadir.');
    }

    $pdo->beginTransaction(); // Inicia la transacción para asegurar atomicidad

    $stmt_insert = $pdo->prepare("INSERT INTO licenses (product_id, license_key_encrypted, status, costo_usd) VALUES (?, ?, 'disponible', ?)");

    foreach ($license_keys as $key) {
        if (!empty($key)) {
            // Asegúrate de que encrypt_key() y decrypt_key() existan en includes/functions.php
            $encrypted_key = encrypt_key($key); // Encriptar la clave
            if (!empty($encrypted_key)) {
                $stmt_insert->execute([$product_id, $encrypted_key, $costo_usd]);
                $added_count++;
            } else {
                custom_log("ERROR: license_handler.php: Fallo al encriptar la clave: '" . $key . "'. Posiblemente ENCRYPTION_KEY o METHOD incorrectos.", 'add_license.log');
            }
        }
    }

    $pdo->commit(); // Confirma los cambios si todo fue bien
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => "¡Éxito! Se han añadido {$added_count} licencias al producto."];
    custom_log("INFO: license_handler.php: Se añadieron {$added_count} licencias para el producto {$product_id}.", 'add_license.log');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Deshace los cambios si algo falla
    }
    custom_log("ERROR en license_handler.php: " . $e->getMessage() . " en línea " . $e->getLine() . " en " . $e->getFile() . ". POST: " . print_r($_POST, true), 'add_license.log');
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error al añadir licencias: ' . htmlspecialchars($e->getMessage())];
}

// REDIRECCIÓN FINAL (SIEMPRE AL FINAL)
// Siempre redirige de vuelta a manage_licenses.php, preferiblemente al producto que se estaba viendo.
$redirect_url = 'manage_licenses.php';
if ($product_id > 0) {
    $redirect_url .= '?product_id=' . $product_id;
}

custom_log("DEBUG: license_handler.php: Redirigiendo a: " . $redirect_url, 'add_license.log');
header('Location: ' . $redirect_url);
exit; // CRÍTICO: Detener la ejecución para asegurar la redirección
// NO HAY TAG DE CIERRE PHP (?>