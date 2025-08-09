<?php
// includes/category_sidebar.php
// Este archivo asume que la variable $pdo ya está disponible.

try {
    // Obtenemos todas las categorías junto con la cantidad de productos en cada una.
    // CAMBIO: Añadir ORDER BY c.sort_order ASC si la columna existe y es usada para orden personalizado.
    // Si no tienes 'sort_order' aún, simplemente usa 'ORDER BY c.name ASC' como fallback.
    $stmt_cats = $pdo->query("
        SELECT c.id, c.name, c.slug, COUNT(pc.product_id) as product_count
        FROM categories c
        LEFT JOIN product_categories pc ON c.id = pc.category_id
        GROUP BY c.id, c.name, c.slug
        HAVING product_count > 0
        ORDER BY c.sort_order ASC, c.name ASC
    ");
    $categories_with_count = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories_with_count = [];
    error_log("Error al obtener categorías para el sidebar: " . $e->getMessage()); // Loguear el error
}

// Determinamos cuál es la categoría activa para darle un estilo diferente.
$active_slug = $_GET['categoria'] ?? '';
?>

<aside class="category-sidebar">
    <h3>Categorías</h3>
    <ul class="category-list">
        <li>
            <a href="catalogo.php" class="<?php echo empty($active_slug) ? 'active' : ''; ?>">
                Todos los Productos
            </a>
        </li>
        <?php foreach ($categories_with_count as $category): ?>
            <li>
                <a href="catalogo.php?categoria=<?php echo htmlspecialchars($category['slug']); ?>" 
                   class="<?php echo ($active_slug === $category['slug']) ? 'active' : ''; ?>">
                    <span><?php echo htmlspecialchars($category['name']); ?></span>
                    <span class="product-count">(<?php echo $category['product_count']; ?>)</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>