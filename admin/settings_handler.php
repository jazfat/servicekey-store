<?php
// admin/settings_handler.php
// Procesa la actualización de los ajustes del sitio.

require_once '../includes/init.php'; // Carga init.php, que a su vez carga $pdo y custom_log

// Seguridad: Solo admin y método POST
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    custom_log("DEBUG: settings_handler.php: Acceso denegado o método no permitido. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'), 'admin_security.log');
    die('Acceso denegado.');
}

try {
    validate_csrf_token(); // Validación CSRF

    $action = $_POST['action'] ?? '';

    // Depuración inicial
    custom_log("DEBUG: settings_handler.php: Action: {$action}", 'settings_handler.log');
    custom_log("DEBUG: settings_handler.php: POST Data: " . print_r($_POST, true), 'settings_handler.log');
    custom_log("DEBUG: settings_handler.php: FILES Data: " . print_r($_FILES, true), 'settings_handler.log');

    $pdo->beginTransaction(); // Iniciar transacción

    if ($action === 'update') {
        $settings_to_save = $_POST['settings'] ?? []; // Array asociativo de ajustes

        // --- Procesar subida de logo del sitio ---
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/'; // Subir al directorio de imágenes general
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

            $file_extension = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('Formato de logo no permitido. Solo JPG, PNG, WEBP.');
            }

            $new_logo_name = 'site_logo_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $new_logo_name;

            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target_file)) {
                // Obtener URL de logo antiguo para posible eliminación
                $stmt_old_logo = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_logo_url'");
                $stmt_old_logo->execute();
                $old_logo_url = $stmt_old_logo->fetchColumn();

                // Actualizar el ajuste
                $settings_to_save['site_logo_url'] = 'assets/images/' . $new_logo_name;
                custom_log("INFO: settings_handler.php: Nuevo logo subido: " . $settings_to_save['site_logo_url'], 'settings_handler.log');

                // Eliminar logo antiguo si no es el por defecto y no es el mismo
                if (!empty($old_logo_url) && $old_logo_url !== 'assets/images/default.png' && file_exists('../' . $old_logo_url)) {
                    unlink('../' . $old_logo_url);
                    custom_log("INFO: settings_handler.php: Logo antiguo eliminado: " . $old_logo_url, 'settings_handler.log');
                }
            } else {
                throw new Exception('Error al mover el archivo del logo.');
            }
        }

        // --- Procesar subida de QR de Binance Pay ---
        if (isset($_FILES['binance_qr_code']) && $_FILES['binance_qr_code']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

            $file_extension = strtolower(pathinfo($_FILES['binance_qr_code']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('Formato de imagen QR no permitido. Solo JPG, PNG, WEBP.');
            }

            $new_qr_name = 'binance_qr_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $new_qr_name;

            if (move_uploaded_file($_FILES['binance_qr_code']['tmp_name'], $target_file)) {
                // Obtener URL de QR antiguo para posible eliminación
                $stmt_old_qr = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'binance_qr_url'");
                $stmt_old_qr->execute();
                $old_qr_url = $stmt_old_qr->fetchColumn();

                // Actualizar el ajuste
                $settings_to_save['binance_qr_url'] = 'assets/images/' . $new_qr_name;
                custom_log("INFO: settings_handler.php: Nuevo QR de Binance subido: " . $settings_to_save['binance_qr_url'], 'settings_handler.log');

                // Eliminar QR antiguo si existe
                if (!empty($old_qr_url) && file_exists('../' . $old_qr_url)) {
                    unlink('../' . $old_qr_url);
                    custom_log("INFO: settings_handler.php: QR antiguo eliminado: " . $old_qr_url, 'settings_handler.log');
                }
            } else {
                throw new Exception('Error al mover el archivo QR de Binance.');
            }
        }

        // --- Actualizar los ajustes en la base de datos ---
        $stmt_update = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        foreach ($settings_to_save as $key => $value) {
            // Validaciones específicas para ciertos ajustes si es necesario (ej. formato de URL, email)
            if ($key === 'site_admin_email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El correo electrónico del administrador no es válido.');
            }
            // Puedes añadir más validaciones aquí...

            // Sanitizar valores (ej. para prevenir XSS al mostrar en el frontend)
            // Para HTML en textareas: strip_tags o un purificador de HTML si se guarda HTML
            // Para texto plano: htmlspecialchars es una buena opción al mostrar
            $sanitized_value = is_string($value) ? trim($value) : $value;

            $stmt_update->execute([$key, $sanitized_value]);
            custom_log("INFO: Ajuste guardado: {$key} = '{$sanitized_value}'", 'settings_handler.log');
        }

        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Ajustes guardados exitosamente.'];

    } else {
        throw new Exception('Acción no válida.');
    }

    $pdo->commit(); // Confirmar todas las operaciones

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Deshacer todas las operaciones si hubo una excepción
    }
    custom_log("ERROR en settings_handler.php: " . $e->getMessage() . " en línea " . $e->getLine() . " en " . $e->getFile() . ". POST: " . print_r($_POST, true), 'settings_handler.log');
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error al guardar los ajustes: ' . htmlspecialchars($e->getMessage())];
}

// Redirigir siempre de vuelta a la página de ajustes
header('Location: manage_settings.php');
exit;