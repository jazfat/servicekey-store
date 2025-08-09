<?php
// catalogo.php

// init.php se encarga de todo: sesión, funciones, conexión, etc.
require_once 'includes/header.php';

// --- LÓGICA DE FILTRADO Y PAGINACIÓN ACTUAL ---
$category_slug = $_GET['categoria'] ?? null;
$page_title = $lang['catalog_title'] ?? 'Catálogo de Productos';
$header_title = 'Todos los Productos'; // Se actualizará si hay filtro de categoría

// --- Preparar las consultas SQL dinámicamente ---
$sql_base = "FROM products p";
$sql_joins = "";
$sql_where = " WHERE 1=1";
$params = [];

if ($category_slug) {
    $sql_joins .= " JOIN product_categories pc ON p.id = pc.product_id JOIN categories c ON pc.category_id = c.id";
    $sql_where .= " AND c.slug = ?";
    $params[] = $category_slug;

    try {
        $cat_name_stmt = $pdo->prepare("SELECT name FROM categories WHERE slug = ?");
        $cat_name_stmt->execute([$category_slug]);
        $header_title = htmlspecialchars($cat_name_stmt->fetchColumn() ?: $header_title);
    } catch (PDOException $e) {
        error_log("Error al obtener nombre de categoría: " . $e->getMessage());
        $header_title = 'Error de Categoría';
    }
}

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 9;
$offset = ($page > 1) ? ($page * $perPage) - $perPage : 0;

try {
    $total_stmt = $pdo->prepare("SELECT COUNT(DISTINCT p.id) " . $sql_base . $sql_joins . $sql_where);
    $total_stmt->execute($params);
    $total = $total_stmt->fetchColumn();
    $pages = ceil($total / $perPage);
} catch (PDOException $e) {
    error_log("Error al contar productos para paginación: " . $e->getMessage());
    $total = 0;
    $pages = 1;
}

try {
    $sql_full = "SELECT p.* " . $sql_base . $sql_joins . $sql_where . " ORDER BY p.name ASC LIMIT " . $perPage . " OFFSET " . $offset;
    $stmt = $pdo->prepare($sql_full);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener productos: " . $e->getMessage());
    $products = [];
}
?>

<body>
    <div class="container page-content">
        <div class="catalog-page-layout">
            <?php include 'includes/category_sidebar.php'; ?>

            <main class="catalog-main">
                <h1 class="page-title-main"><?php echo htmlspecialchars($header_title); ?></h1>
                
                <div class="products-grid">
                    <?php if (empty($products)): ?>
                        <p class="info-message">No se encontraron productos en esta categoría o filtro.</p>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php 
                            // Incluimos la tarjeta de producto reutilizable
                            // Esta incluirá la lógica del botón "Añadir al Carrito" y los detalles visuales
                            include 'includes/product_card.php'; 
                            ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <nav class="pagination">
                    <?php for ($i = 1; $i <= $pages; $i++): 
                        $page_params = ['page' => $i];
                        if ($category_slug) $page_params['categoria'] = $category_slug;
                    ?>
                        <a href="catalogo.php?<?php echo http_build_query($page_params); ?>" class="<?php if ($page === $i) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </nav>
            </main>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>