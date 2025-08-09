<?php
// admin/admin_nav.php
// Barra de navegación reutilizable para el panel de administración.
?>
<nav class="admin-nav">
    <a href="index.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
    <a href="manage_orders.php"><i class="bi bi-receipt-cutoff"></i> Órdenes</a>
    <a href="manage_reviews.php"><i class="bi bi-chat-quote-fill"></i> Reseñas</a>
    <a href="manage_products.php"><i class="bi bi-box-seam-fill"></i> Productos</a>
    <a href="manage_licenses.php"><i class="bi bi-key-fill"></i> Licencias</a>
    <a href="manage_categories.php"><i class="bi bi-tags-fill"></i> Categorías</a>
    <a href="manage_coupons.php"><i class="bi bi-ticket-detailed-fill"></i> Cupones</a>
    <a href="manage_slides.php"><i class="bi bi-collection-play-fill"></i> Slides</a>
    <a href="manage_logos.php"><i class="bi bi-images"></i> Logos de Marcas</a>
    <a href="manage_settings.php"><i class="bi bi-gear-fill"></i> Ajustes</a>
    <a href="manage_pages.php"><i class="bi bi-file-earmark-text-fill"></i> Páginas</a>
    <a href="<?php echo BASE_URL; ?>index.php" target="_blank"><i class="bi bi-eye-fill"></i> Ver Tienda</a>
    <a href="logout.php" class="logout"><i class="bi bi-box-arrow-right"></i> Salir</a>
</nav>
<hr class="admin-nav-divider">