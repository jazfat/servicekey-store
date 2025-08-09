<?php
// admin/manage_categories.php

// Carga el header del admin, que a su vez carga init.php y realiza la comprobación de sesión.
require_once 'admin_header.php';

// Establecer el título de la página para el header
$page_title = 'Gestionar Categorías';

// --- Procesar y mostrar mensajes flash ---
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']); // Limpiar el mensaje después de mostrarlo
}

// Generar un token CSRF para los formularios de esta página
$token = generate_csrf_token();

try {
    // Obtener todas las categorías y contar cuántos productos tienen asociados
    $stmt_categories = $pdo->query("
        SELECT
            c.id,
            c.name,
            c.slug,
            (SELECT COUNT(pc.product_id) FROM product_categories pc WHERE pc.category_id = c.id) as product_count
        FROM categories c
        ORDER BY c.name ASC
    ");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    custom_log("ERROR PDO en manage_categories.php: " . $e->getMessage(), 'admin_errors.log');
    $categories = []; // Asegurar que $categories esté vacío en caso de error
    $flash_message = ['type' => 'error', 'text' => 'Error al cargar categorías: ' . htmlspecialchars($e->getMessage())];
}

?>

<div class="container-admin-panel">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if ($flash_message): ?>
        <div class="<?php echo htmlspecialchars($flash_message['type']); ?>-message">
            <?php echo htmlspecialchars($flash_message['text']); ?>
        </div>
    <?php endif; ?>

    <div class="admin-form-layout">
        <div class="form-column-main">
            <div class="form-section">
                <h3>Añadir Nueva Categoría</h3>
                <form action="category_handler.php" method="POST" class="admin-form">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="name">Nombre de la Categoría</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="Ej. Sistemas Operativos, Antivirus">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Añadir Categoría</button>
                </form>
            </div>
        </div>

        <div class="form-column-sidebar">
            <div class="form-section">
                <h3>Listado de Categorías</h3>
                <?php if (empty($categories)): ?>
                    <p class="info-message">No hay categorías registradas. ¡Añade una usando el formulario de la izquierda!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Slug</th>
                                    <th>Productos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['id']); ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                        <td>
                                            <?php if ($category['product_count'] > 0): ?>
                                                <a href="manage_products.php?category_id=<?php echo htmlspecialchars($category['id']); ?>" title="Ver Productos" class="status-badge status-disponible">
                                                    <?php echo htmlspecialchars($category['product_count']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="status-badge status-agotado">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-cell">
                                            <form action="category_handler.php" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de eliminar la categoría <?php echo htmlspecialchars($category['name']); ?>? Esto también desasociará todos los productos de ella.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($category['id']); ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                                                <button type="submit" class="btn-action delete" title="Eliminar Categoría" <?php echo ($category['product_count'] > 0) ? 'disabled' : ''; // Deshabilitar si tiene productos ?>>
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($categories) > 0 && $categories[0]['product_count'] > 0): // Solo para mostrar la nota si hay categorías con productos ?>
                        <small class="form-text text-muted mt-3">No puedes eliminar categorías que aún tienen productos asociados.</small>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>