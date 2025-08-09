<?php
// admin/product_handler.php

// --- INICIO DE LÍNEAS DE DEPURACIÓN (ELIMINAR EN PRODUCCIÓN) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN DE LÍNEAS DE DEPURACIÓN ---

require_once '../includes/init.php'; // Carga init.php, que a su vez carga $pdo y custom_log

// Seguridad: Solo administradores y solicitudes POST
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    custom_log("DEBUG: product_handler.php: Acceso denegado o método no permitido. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'), 'admin_security.log');
    die('Acceso denegado.'); // Considerar redirigir a login.php o a una página de error amigable.
}

try {
    validate_csrf_token(); // Validación CSRF

    // Reemplazo del operador ?? con operador ternario para compatibilidad PHP < 7.0
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $product_id = isset($_POST['id']) ? (int)$_POST['id'] : 0; // Solo para update/delete

    // --- Depuración de Datos Recibidos ---
    custom_log("DEBUG: product_handler.php: Action recibido: '{$action}'", 'product_handler_debug.log');
    custom_log("DEBUG: product_handler.php: Product ID recibido: '{$product_id}'", 'product_handler_debug.log');
    custom_log("DEBUG: product_handler.php: Contenido de POST: " . print_r($_POST, true), 'product_handler_debug.log');
    custom_log("DEBUG: product_handler.php: Contenido de FILES: " . print_r($_FILES, true), 'product_handler_debug.log');
    // --- Fin Depuración ---

    // Iniciar transacción de base de datos
    $pdo->beginTransaction();

    switch ($action) {
        case 'create':
        case 'update':
            // --- Validar y sanitizar datos comunes ---
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
            $version = isset($_POST['version']) ? trim($_POST['version']) : '';
            $platform = isset($_POST['platform']) ? trim($_POST['platform']) : '';
            $devices_limit = isset($_POST['devices_limit']) ? trim($_POST['devices_limit']) : '';
            $validity_duration = isset($_POST['validity_duration']) ? trim($_POST['validity_duration']) : '';
            $delivery_type = isset($_POST['delivery_type']) ? trim($_POST['delivery_type']) : 'Entrega Digital';
            $price_usd = isset($_POST['price_usd']) ? (float)$_POST['price_usd'] : 0;
            $compare_at_price_usd = isset($_POST['compare_at_price_usd']) ? (float)$_POST['compare_at_price_usd'] : 0;
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $is_physical = isset($_POST['is_physical']) ? 1 : 0;
            $allow_preorder = isset($_POST['allow_preorder']) ? 1 : 0;
            $categories_selected = isset($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : []; // Array de IDs de categorías
            $related_products_selected = isset($_POST['related_products']) && is_array($_POST['related_products']) ? $_POST['related_products'] : []; // Array de IDs de productos relacionados

            // Validaciones básicas
            if (empty($name) || $price_usd <= 0) {
                throw new Exception('El nombre y el precio de venta son obligatorios y el precio debe ser mayor a 0.');
            }
            if ($compare_at_price_usd > 0 && $compare_at_price_usd <= $price_usd) {
                throw new Exception('El precio de comparación debe ser mayor que el precio de venta.');
            }

            // --- Manejo de la imagen del producto ---
            $image_url = '';
            $old_image_url = null;

            // En modo update, recuperamos la imagen actual para posible eliminación
            if ($action === 'update' && $product_id > 0) {
                $stmt_current_image = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
                $stmt_current_image->execute([$product_id]);
                $old_image_url = $stmt_current_image->fetchColumn();
            }

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../assets/images/products/'; // Ruta real: tu_tienda/assets/images/products/
                if (!is_dir($upload_dir)) {
                    // custom_log("INFO: Creando directorio de subida: " . $upload_dir, 'product_handler_debug.log');
                    mkdir($upload_dir, 0777, true); // Crea la carpeta si no existe
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

                // Validación de extensión y tipo MIME básico
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $_FILES['image']['tmp_name']);
                finfo_close($finfo);

                $valid_mime_types = ['image/jpeg', 'image/png', 'image/webp'];

                if (!in_array($file_extension, $allowed_extensions) || !in_array($mime_type, $valid_mime_types)) {
                    throw new Exception('Formato de imagen no permitido. Solo JPG, PNG, WEBP.');
                }
                
                // Validar tamaño (ej. max 5MB)
                $max_file_size = 5 * 1024 * 1024; // 5 MB
                if ($_FILES['image']['size'] > $max_file_size) {
                    throw new Exception('El tamaño de la imagen excede el límite permitido (5MB).');
                }

                // Generar nombre de archivo único
                $file_name = 'product_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = 'assets/images/products/' . $file_name; // Guardar la ruta relativa a la raíz del sitio
                    custom_log("INFO: product_handler.php: Imagen subida exitosamente: " . $image_url, 'product_handler.log');

                    // Eliminar imagen antigua en modo update si se subió una nueva y la antigua no es la por defecto
                    if ($action === 'update' && !empty($old_image_url) && $old_image_url !== 'assets/images/default.png' && file_exists('../' . $old_image_url)) {
                        unlink('../' . $old_image_url);
                        custom_log("INFO: product_handler.php: Imagen antigua eliminada: " . $old_image_url, 'product_handler.log');
                    }
                } else {
                    throw new Exception('Error al mover la imagen subida. Permisos de escritura incorrectos o disco lleno.');
                }
            } else if ($action === 'update') {
                // Si no se subió una nueva imagen en modo update, mantener la existente
                $image_url = $old_image_url;
            } else { // create mode and no image uploaded
                $image_url = 'assets/images/default.png'; // Usar imagen por defecto
            }


            // --- Lógica de Base de Datos (INSERT / UPDATE) ---
            if ($action === 'create') {
                $sql = "INSERT INTO products (name, description, brand, version, platform, devices_limit, validity_duration, delivery_type, price_usd, compare_at_price_usd, image_url, is_featured, is_physical, allow_preorder) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $description, $brand, $version, $platform, $devices_limit, $validity_duration, $delivery_type, $price_usd, $compare_at_price_usd, $image_url, $is_featured, $is_physical, $allow_preorder]);
                $product_id = $pdo->lastInsertId();
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Producto creado exitosamente.'];
                custom_log("INFO: product_handler.php: Nuevo producto creado con ID: {$product_id}.", 'product_handler.log');

            } elseif ($action === 'update' && $product_id > 0) {
                // Construir la consulta de UPDATE dinámicamente para incluir la imagen solo si se actualizó
                $sql = "UPDATE products SET name = ?, description = ?, brand = ?, version = ?, platform = ?, devices_limit = ?, validity_duration = ?, delivery_type = ?, price_usd = ?, compare_at_price_usd = ?, is_featured = ?, is_physical = ?, allow_preorder = ?";
                $params = [$name, $description, $brand, $version, $platform, $devices_limit, $validity_duration, $delivery_type, $price_usd, $compare_at_price_usd, $is_featured, $is_physical, $allow_preorder];

                if (!empty($image_url) && $image_url !== $old_image_url) { // Solo actualizar image_url si hay una nueva
                    $sql .= ", image_url = ?";
                    $params[] = $image_url;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $product_id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Producto actualizado exitosamente.'];
                custom_log("INFO: product_handler.php: Producto actualizado con ID: {$product_id}.", 'product_handler.log');

            } else {
                // Esta excepción se lanza si la acción es 'update' pero $product_id no es válido
                throw new Exception('Acción de creación/actualización inválida o ID de producto no válido.');
            }

            // --- Gestión de Categorías del Producto ---
            // Primero, eliminar todas las categorías actuales para este producto
            $stmt_delete_cats = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?");
            $stmt_delete_cats->execute([$product_id]);

            // Luego, insertar las categorías seleccionadas
            if (!empty($categories_selected)) {
                $sql_insert_cats = "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)";
                $stmt_insert_cats = $pdo->prepare($sql_insert_cats);
                foreach ($categories_selected as $cat_id) {
                    $stmt_insert_cats->execute([$product_id, (int)$cat_id]);
                }
            }
            custom_log("INFO: product_handler.php: Categorías actualizadas para producto {$product_id}.", 'product_handler.log');


            // --- Gestión de Productos Relacionados ---
            // Primero, eliminar todas las relaciones existentes donde este producto es el principal
            $stmt_delete_related = $pdo->prepare("DELETE FROM related_products WHERE product_id_main = ?");
            $stmt_delete_related->execute([$product_id]);

            // Luego, insertar las nuevas relaciones
            if (!empty($related_products_selected)) {
                $sql_insert_related = "INSERT INTO related_products (product_id_main, product_id_related) VALUES (?, ?)";
                $stmt_insert_related = $pdo->prepare($sql_insert_related);
                foreach ($related_products_selected as $related_id) {
                    // Asegúrate de que no se intente relacionar el producto consigo mismo
                    if ((int)$related_id !== $product_id) {
                         $stmt_insert_related->execute([$product_id, (int)$related_id]);
                    }
                }
            }
            custom_log("INFO: product_handler.php: Productos relacionados actualizados para producto {$product_id}.", 'product_handler.log');

            break; // Fin del caso 'create' / 'update'

        case 'delete':
            if ($product_id <= 0) {
                throw new Exception('ID de producto inválido para eliminar.');
            }

            // Opcional: Obtener la URL de la imagen antes de eliminar el producto para borrarla
            $stmt_image_to_delete = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
            $stmt_image_to_delete->execute([$product_id]);
            $image_to_delete = $stmt_image_to_delete->fetchColumn();

            // Eliminar producto de la tabla `products`
            $stmt_delete_product = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt_delete_product->execute([$product_id]);

            // IMPORTANTE: Asegúrate de que tu base de datos esté configurada con CASCADE DELETE
            // en las relaciones de foreign key para 'product_categories', 'related_products',
            // 'licenses' y 'order_items' (si eliminas un producto, sus licencias, sus entradas en order_items,
            // y sus relaciones de categoría/productos relacionados deberían eliminarse automáticamente).
            // Si no tienes CASCADE DELETE, necesitarías descomentar y ejecutar las siguientes líneas:
            /*
            $pdo->prepare("DELETE FROM licenses WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM related_products WHERE product_id_main = ? OR product_id_related = ?")->execute([$product_id, $product_id]);
            // Para order_items, sería más complejo ya que impactaría las órdenes.
            // Considera actualizar order_items para establecer product_id a NULL o tener un flag de producto_eliminado.
            */

            // Eliminar el archivo de imagen del servidor si no es la imagen por defecto
            if (!empty($image_to_delete) && $image_to_delete !== 'assets/images/default.png' && file_exists('../' . $image_to_delete)) {
                unlink('../' . $image_to_delete);
                custom_log("INFO: product_handler.php: Imagen de producto eliminada: " . $image_to_delete, 'product_handler.log');
            }

            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Producto eliminado exitosamente.'];
            custom_log("INFO: product_handler.php: Producto eliminado con ID: {$product_id}.", 'product_handler.log');
            break;

        default:
            throw new Exception('Acción no válida.');
    }

    $pdo->commit(); // Confirmar todas las operaciones si no hubo excepciones

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Deshacer todas las operaciones si hubo una excepción
    }
    custom_log("ERROR en product_handler.php: " . $e->getMessage() . " en línea " . $e->getLine() . " en " . $e->getFile() . ". POST: " . print_r($_POST, true), 'product_handler.log');
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error al procesar el producto: ' . htmlspecialchars($e->getMessage())];
}

// Redirigir siempre a la página de gestión de productos
header('Location: manage_products.php');
exit;