<?php
// admin/category_handler.php

require_once '../includes/init.php'; // Carga init.php, que a su vez carga $pdo y custom_log

// Seguridad: Solo administradores y solicitudes POST
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    custom_log("DEBUG: category_handler.php: Acceso denegado o método no permitido. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'), 'admin_security.log');
    die('Acceso denegado.');
}

try {
    validate_csrf_token(); // Validación CSRF

    $action = $_POST['action'] ?? '';
    $category_id = (int)($_POST['id'] ?? 0); // Para acciones de eliminación

    // Depuración inicial de la acción y el ID
    custom_log("DEBUG: category_handler.php: Action: {$action}, Category ID: {$category_id}", 'category_handler.log');
    custom_log("DEBUG: category_handler.php: POST Data: " . print_r($_POST, true), 'category_handler.log');

    $pdo->beginTransaction(); // Iniciar transacción

    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? '');

            if (empty($name)) {
                throw new Exception('El nombre de la categoría es obligatorio.');
            }

            // Verificar si ya existe una categoría con ese nombre (case-insensitive)
            $stmt_check_name = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
            $stmt_check_name->execute([$name]);
            if ($stmt_check_name->fetch()) {
                throw new Exception('Ya existe una categoría con este nombre.');
            }

            // Generar un 'slug' amigable para la URL
            $base_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = $base_slug;
            $counter = 1;

            // Asegurar que el slug sea único
            while (true) {
                $stmt_check_slug = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                $stmt_check_slug->execute([$slug]);
                if (!$stmt_check_slug->fetch()) {
                    break; // Slug es único
                }
                $slug = $base_slug . '-' . $counter++; // Añadir sufijo para hacerlo único
            }

            $stmt_insert = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt_insert->execute([$name, $slug]);

            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Categoría "' . htmlspecialchars($name) . '" creada exitosamente.'];
            custom_log("INFO: category_handler.php: Nueva categoría creada: {$name} (Slug: {$slug}).", 'category_handler.log');
            break;

        case 'delete':
            if ($category_id <= 0) {
                throw new Exception('ID de categoría inválido para eliminar.');
            }

            // Verificar si la categoría tiene productos asociados antes de eliminar
            $stmt_check_products = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE category_id = ?");
            $stmt_check_products->execute([$category_id]);
            $product_count = $stmt_check_products->fetchColumn();

            if ($product_count > 0) {
                throw new Exception('No se puede eliminar la categoría porque tiene ' . $product_count . ' producto(s) asociado(s). Desasocia los productos primero.');
            }

            // Eliminar la categoría
            $stmt_delete = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt_delete->execute([$category_id]);

            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Categoría eliminada exitosamente.'];
            custom_log("INFO: category_handler.php: Categoría eliminada con ID: {$category_id}.", 'category_handler.log');
            break;

        default:
            throw new Exception('Acción no válida.');
    }

    $pdo->commit(); // Confirmar todas las operaciones si no hubo excepciones

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Deshacer todas las operaciones si hubo una excepción
    }
    custom_log("ERROR en category_handler.php: " . $e->getMessage() . " en línea " . $e->getLine() . " en " . $e->getFile() . ". POST: " . print_r($_POST, true), 'category_handler.log');
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error al procesar la categoría: ' . htmlspecialchars($e->getMessage())];
}

// Redirigir siempre a la página de gestión de categorías
header('Location: manage_categories.php');
exit;