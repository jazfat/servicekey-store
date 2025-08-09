<?php
// C:\xampp\htdocs\licencias\index.php (Este es el index del FRONTEND de la tienda)

// SIEMPRE carga el header del frontend
require_once 'includes/header.php';

try {
    // Consulta de productos destacados
    $stmt_products = $pdo->prepare("SELECT * FROM products WHERE is_featured = ? ORDER BY name ASC");
    $stmt_products->execute([true]);
    $featured_products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    // Generamos el token aquí para que esté disponible para el product_card.php
    $token = generate_csrf_token(); 
} catch (PDOException $e) {
    error_log("Error en la página de inicio: " . $e->getMessage());
    $featured_products = [];
}
?>

<?php include 'includes/slider.php'; ?>
<?php include 'includes/logo_scroller.php'; ?>

<section class="featured-products">
    <div class="container">
        <h2><?php echo $lang['featured_products_title']; ?></h2> 
        <div class="products-grid">
            <?php if (empty($featured_products)): ?>
                <p>No hay productos destacados disponibles en este momento.</p>
            <?php else: ?>
                <?php foreach ($featured_products as $product): ?>
                    <?php include 'includes/product_card.php'; // Ahora la tarjeta tendrá acceso a $token ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// SIEMPRE carga el footer del frontend
require_once 'includes/footer.php';
?>