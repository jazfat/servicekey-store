<?php
// admin/manage_licenses.php

// Carga el header del admin, que a su vez carga init.php y realiza la comprobación de sesión.
require_once 'admin_header.php';

// Establecer el título de la página para el header
$page_title = 'Gestionar Licencias de Productos';

// --- Procesar y mostrar mensajes flash (si vienen de license_handler.php) ---
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']); // Limpiar el mensaje después de mostrarlo
}

// Generar un token CSRF para los formularios de esta página
$token = generate_csrf_token();

// Inicialización de variables para la lógica principal
$selected_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$licenses = [];
$stats = ['total' => 0, 'disponible' => 0, 'vendida' => 0]; // Inicializar para evitar 'undefined index'
$products = []; // Lista de todos los productos para el selector

// --- TEMPORAL: DEBUG VISUAL DEL selected_product_id (descomentar para activar) ---
// echo "<div style='background-color: red; color: white; padding: 10px; margin: 10px 0;'>";
// echo "DEBUG: \$selected_product_id = " . htmlspecialchars($selected_product_id);
// echo "</div>";
// --- FIN TEMPORAL ---

try {
    // Obtener todos los productos para el menú desplegable
    $stmt_products = $pdo->query("SELECT id, name FROM products ORDER BY name ASC");
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    // Si se ha seleccionado un producto, obtener sus licencias y estadísticas
    if ($selected_product_id > 0) {
        // Obtener las licencias para el producto seleccionado
        $stmt_licenses = $pdo->prepare("SELECT id, license_key_encrypted, status, created_at FROM licenses WHERE product_id = ? ORDER BY created_at DESC");
        $stmt_licenses->execute([$selected_product_id]);
        $licenses = $stmt_licenses->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener estadísticas de inventario para el producto seleccionado
        $stmt_stats = $pdo->prepare("SELECT status, COUNT(*) as count FROM licenses WHERE product_id = ? GROUP BY status");
        $stmt_stats->execute([$selected_product_id]);
        $results = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);

        // Actualizar el array de estadísticas con los conteos de la BD
        foreach ($results as $row) {
            $stats[$row['status']] = (int)$row['count'];
        }
        $stats['total'] = array_sum($stats); // Sumar todos los estados encontrados para el total general

        custom_log("DEBUG: manage_licenses.php: Estadísticas de licencias para producto " . $selected_product_id . ": " . print_r($stats, true), 'manage_licenses.log');
    }

} catch (PDOException $e) {
    custom_log("ERROR PDO en manage_licenses.php: " . $e->getMessage(), 'admin_errors.log');
    $products = []; // Limpiar las listas en caso de error de BD
    $licenses = [];
    $selected_product_id = 0;
    $flash_message = ['type' => 'error', 'text' => 'Error al cargar datos de licencias: ' . htmlspecialchars($e->getMessage())];
} catch (Exception $e) {
    custom_log("ERROR general en manage_licenses.php: " . $e->getMessage(), 'admin_errors.log');
    $products = [];
    $licenses = [];
    $selected_product_id = 0;
    $flash_message = ['type' => 'error', 'text' => 'Ha ocurrido un error inesperado: ' . htmlspecialchars($e->getMessage())];
}

?>

<div class="container-admin-panel">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if ($flash_message): ?>
        <div class="<?php echo htmlspecialchars($flash_message['type']); ?>-message">
            <?php echo htmlspecialchars($flash_message['text']); ?>
        </div>
    <?php endif; ?>

    <div class="admin-form-inline mb-4">
        <form method="GET" action="manage_licenses.php" class="admin-form-inline">
            <div class="form-group">
                <label for="product_id">Selecciona un Producto:</label>
                <select name="product_id" id="product_id" onchange="this.form.submit()" class="form-select">
                    <option value="0">-- Elige un producto --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php if($selected_product_id == $product['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selected_product_id > 0): ?>
                <a href="../producto.php?id=<?php echo htmlspecialchars($selected_product_id); ?>" target="_blank" class="btn btn-info mt-auto" title="Ver Producto en Tienda">
                    <i class="bi bi-eye"></i> Ver Producto
                </a>
                <a href="edit_product.php?id=<?php echo htmlspecialchars($selected_product_id); ?>" class="btn btn-secondary mt-auto" title="Editar Producto">
                    <i class="bi bi-pencil-fill"></i> Editar Producto
                </a>
            <?php endif; ?>
        </form>
    </div>

<?php if ($selected_product_id > 0): ?>
    <hr>
    <div class="admin-form-layout">
        <div class="form-column-main">
            <div class="form-section">
                <h3>Añadir Nuevas Licencias</h3>
                <form action="license_handler.php" method="POST" class="admin-form">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($selected_product_id); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="license_keys">Pega las claves de licencia aquí (una por línea):</label>
                        <textarea name="license_keys" id="license_keys" class="form-control" rows="10" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="costo_usd">Costo por Licencia (USD)</label>
                        <input type="number" name="costo_usd" id="costo_usd" class="form-control" step="0.01" min="0" value="0.00" required>
                        <small class="form-text text-muted">Introduce el costo de adquisición de cada una de estas licencias.</small>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Añadir Licencias</button>
                </form>
            </div>
        </div>
        <div class="form-column-sidebar">
            <div class="form-section">
                <h3>Inventario Actual</h3>
                <div class="inventory-stats">
                    <span>Total: <?php echo htmlspecialchars($stats['total']); ?></span> |
                    <span style="color: var(--success-color);">Disponibles: <?php echo htmlspecialchars($stats['disponible']); ?></span> |
                    <span style="color: var(--error-color);">Vendidas: <?php echo htmlspecialchars($stats['vendida']); ?></span>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Clave (últimos 8)</th>
                                <th>Estado</th>
                                <th>Fecha Añadida</th>
                                </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($licenses)): ?>
                                <tr><td colspan="4">No hay licencias registradas para este producto.</td></tr>
                            <?php else: ?>
                                <?php foreach ($licenses as $license): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($license['id']); ?></td>
                                        <td>...<?php echo htmlspecialchars(substr(decrypt_key($license['license_key_encrypted']), -8)); ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower($license['status']); ?>"><?php echo htmlspecialchars($license['status']); ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($license['created_at'])); ?></td>
                                        </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($selected_product_id === 0 && !empty($products)): // Mensaje si no se ha seleccionado producto, pero hay productos disponibles ?>
    <div class="info-message">
        <p>Por favor, selecciona un producto del menú desplegable de arriba para gestionar sus licencias.</p>
    </div>
<?php elseif (empty($products)): // Mensaje si no hay productos en absoluto ?>
    <div class="info-message">
        <p>No hay productos en el sistema. Debes crear productos primero para poder añadirles licencias.</p>
        <a href="edit_product.php" class="btn btn-primary mt-3">Crear Nuevo Producto</a>
    </div>
<?php else: // Mensaje si el ID del producto es inválido ?>
    <div class="error-message">
        <p>El ID de producto seleccionado no es válido.</p>
    </div>
<?php endif; ?>

<?php require_once 'admin_footer.php'; ?>