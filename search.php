<?php
require_once 'includes/header.php';

// Obtiene el término de búsqueda de la URL de forma segura.
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$products = [];
$total = 0; // Inicializar total de productos
$pages = 1; // Inicializar total de páginas

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 9; // 9 productos por página, igual que en catalogo.php
$offset = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Solo ejecuta la búsqueda si el usuario ha introducido un término.
if (!empty($query)) {
    $searchTerm = "%" . $query . "%";
    
    try {
        // 1. Contar el total de productos que coinciden para la paginación
        // CAMBIO: Nombres de placeholders únicos para :term
        $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name LIKE :term_name OR description LIKE :term_desc");
        $total_stmt->bindValue(':term_name', $searchTerm, PDO::PARAM_STR);
        $total_stmt->bindValue(':term_desc', $searchTerm, PDO::PARAM_STR);
        $total_stmt->execute(); // Ejecutar sin parámetros aquí, ya se bindearon
        $total = $total_stmt->fetchColumn();
        $pages = ceil($total / $perPage);

        // 2. Obtener los productos para la página actual
        // CAMBIO: Nombres de placeholders únicos para :term
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :term_name OR description LIKE :term_desc ORDER BY name ASC LIMIT :limit OFFSET :offset");
        
        $stmt->bindValue(':term_name', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':term_desc', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute(); // Ejecutar sin parámetros aquí, ya se bindearon
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error en la búsqueda de productos: " . $e->getMessage());
        $products = [];
        $total = 0;
        $pages = 1;
        // Podrías añadir un mensaje flash aquí si $lang está disponible:
        // $_SESSION['flash_message'] = ['type' => 'error', 'text' => $lang['search_error'] ?? 'Hubo un error al realizar la búsqueda.'];
    }
}

// Genera un token CSRF para la seguridad de los formularios (si es necesario en la tarjeta de producto).
$token = generate_csrf_token();
$page_title = 'Resultados de Búsqueda';
?>

<div class="container search-results-page">
    <h1 class="page-title-main">Resultados de búsqueda para: "<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>"</h1>

    <?php if (empty($query)): ?>
        <p class="info-message">Por favor, introduce un término de búsqueda para comenzar.</p>
    
    <?php elseif (empty($products)): ?>
        <p class="info-message">No se encontraron productos que coincidan con "<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>". Intenta con otras palabras.</p>
    
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <?php include 'includes/product_card.php'; // La tarjeta de producto se reutiliza aquí ?>
            <?php endforeach; ?>
        </div>

        <nav class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): 
                $page_params = ['q' => $query, 'page' => $i]; // Mantener el término de búsqueda
            ?>
                <a href="search.php?<?php echo http_build_query($page_params); ?>" class="<?php if ($page === $i) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>