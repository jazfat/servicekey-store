<?php
// admin/manage_products.php

// Carga el header del admin, que a su vez carga init.php y realiza la comprobación de sesión.
require_once 'admin_header.php';

// Establecer el título de la página para el header
$page_title = 'Gestionar Productos';

// --- Procesar y mostrar mensajes flash ---
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']); // Limpiar el mensaje después de mostrarlo
}

// Generar un token CSRF para cualquier formulario de acción (eliminar, etc.)
$token = generate_csrf_token();

try {
    // Obtener todos los productos con su stock disponible y si permite pre-compra
    $stmt_products = $pdo->query("
        SELECT
            p.id,
            p.name,
            p.price_usd,
            p.image_url,
            p.is_featured,
            p.allow_preorder,
            (SELECT COUNT(l.id) FROM licenses l WHERE l.product_id = p.id AND l.status = 'disponible') as available_stock
        FROM products p
        ORDER BY p.name ASC
    ");
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    custom_log("ERROR PDO en manage_products.php: " . $e->getMessage(), 'admin_errors.log');
    $products = []; // Asegurar que $products esté vacío en caso de error
    $flash_message = ['type' => 'error', 'text' => 'Error al cargar productos: ' . htmlspecialchars($e->getMessage())];
}

?>

<div class="container-admin-panel">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if ($flash_message): ?>
        <div class="<?php echo htmlspecialchars($flash_message['type']); ?>-message">
            <?php echo htmlspecialchars($flash_message['text']); ?>
        </div>
    <?php endif; ?>

    <div class="admin-header-section">
        <h2>Listado de Productos</h2>
        <a href="edit_product.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Añadir Nuevo Producto</a>
    </div>

    <?php if (empty($products)): ?>
        <p>No hay productos registrados en el sistema. <a href="edit_product.php">¡Añade uno ahora!</a></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Precio (USD)</th>
                        <th>Stock Disp.</th>
                        <th>Pre-compra</th>
                        <th>Destacado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                            <td>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="Imagen de <?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>$<?php echo number_format($product['price_usd'], 2); ?></td>
                            <td>
                                <?php if ($product['available_stock'] > 0): ?>
                                    <span class="status-badge status-disponible"><?php echo htmlspecialchars($product['available_stock']); ?></span>
                                <?php else: ?>
                                    <span class="status-badge status-agotado">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['allow_preorder']): ?>
                                    <i class="bi bi-check-circle-fill status-icon status-icon-approved" title="Permitido"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill status-icon status-icon-denied" title="No permitido"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['is_featured']): ?>
                                    <i class="bi bi-star-fill status-icon status-icon-featured" title="Destacado"></i>
                                <?php else: ?>
                                    <i class="bi bi-star status-icon status-icon-inactive" title="No Destacado"></i>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <a href="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn-action edit" title="Editar Producto">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <form action="product_handler.php" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de eliminar este producto y sus licencias asociadas? Esta acción es irreversible.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                                    <button type="submit" class="btn-action delete" title="Eliminar Producto">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'admin_footer.php'; ?>

<style>
    /* Estilos base para todos los iconos de estado */
    .status-icon {
        font-size: 1.2rem;
        vertical-align: middle; /* Alinea los iconos con el texto si hay */
    }

    /* Icono para elementos aprobados/permitidos */
    .status-icon-approved {
        color: var(--success-color); /* Verde claro, sigue siendo serio y funcional */
    }

    /* Icono para elementos denegados/no permitidos/agotados */
    .status-icon-denied {
        color: var(--error-color); /* Rojo, para indicar "no" o "problema" */
    }

    /* Icono para elementos destacados */
    .status-icon-featured {
        color: var(--accent-color); /* Color cian para destacar */
    }

    /* Icono para elementos inactivos o no destacados */
    .status-icon-inactive {
        color: var(--text-color-light); /* Un gris más suave para indicar inactividad */
    }
</style>