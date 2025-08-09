<?php
require_once 'includes/init.php'; // Asegúrate de que este archivo carga la conexión $pdo y define BASE_URL

// Configurar la cabecera para indicar que es un archivo XML
header('Content-Type: application/xml; charset=utf-8');

// Iniciar el documento XML del sitemap
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// 1. Añadir URLs de páginas estáticas importantes
$static_pages = [
    BASE_URL . '/', // Página de inicio
    BASE_URL . '/catalogo.php', // Catálogo general de productos
    BASE_URL . '/contacto.php', // Página de contacto
    BASE_URL . '/mi-cuenta.php', // Página de Mi Cuenta (si es accesible públicamente o relevante para indexar)
    BASE_URL . '/login.php', // Página de Login
    BASE_URL . '/registro.php', // Página de Registro
    // BASE_URL . '/gracias.php', // No debería ser indexada, es una página de éxito post-compra
];

foreach ($static_pages as $url) {
    echo '<url>' . PHP_EOL;
    echo '  <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
    echo '  <changefreq>daily</changefreq>' . PHP_EOL; // Frecuencia de cambio sugerida
    echo '  <priority>0.8</priority>' . PHP_EOL; // Prioridad de la URL
    echo '</url>' . PHP_EOL;
}

// 2. Añadir URLs de Categorías (si tu sistema las gestiona dinámicamente y tienen páginas propias)
try {
    $stmt_categories = $pdo->prepare("SELECT slug FROM categories WHERE is_active = 1"); // Asume que tienes un campo is_active
    $stmt_categories->execute();
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categories as $category) {
        $url = BASE_URL . '/catalogo.php?categoria=' . urlencode($category['slug']);
        echo '<url>' . PHP_EOL;
        echo '  <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
        echo '  <changefreq>weekly</changefreq>' . PHP_EOL;
        echo '  <priority>0.7</priority>' . PHP_EOL;
        echo '</url>' . PHP_EOL;
    }
} catch (PDOException $e) {
    error_log("Error al generar sitemap (categorías): " . $e->getMessage());
    // No se detiene la generación del sitemap, simplemente no se incluyen las categorías.
}


// 3. Añadir URLs de Productos
try {
    // Asume que tienes un campo 'is_active' o 'is_published' en tu tabla de productos
    $stmt_products = $pdo->prepare("SELECT slug, updated_at FROM products WHERE is_active = 1");
    $stmt_products->execute();
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $url = BASE_URL . '/producto.php?slug=' . urlencode($product['slug']);
        echo '<url>' . PHP_EOL;
        echo '  <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
        // La fecha de última modificación es útil para los rastreadores
        echo '  <lastmod>' . date('Y-m-d', strtotime($product['updated_at'])) . '</lastmod>' . PHP_EOL;
        echo '  <changefreq>daily</changefreq>' . PHP_EOL;
        echo '  <priority>1.0</priority>' . PHP_EOL; // Productos suelen tener la mayor prioridad
        echo '</url>' . PHP_EOL;
    }
} catch (PDOException $e) {
    error_log("Error al generar sitemap (productos): " . $e->getMessage());
    // No se detiene la generación del sitemap, simplemente no se incluyen los productos.
}

// 4. Añadir URLs de Páginas de Contenido Estático (tabla 'pages')
try {
    $stmt_pages = $pdo->prepare("SELECT slug, updated_at FROM pages WHERE is_published = 1");
    $stmt_pages->execute();
    $cms_pages = $stmt_pages->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cms_pages as $page) {
        $url = BASE_URL . '/pagina.php?slug=' . urlencode($page['slug']);
        echo '<url>' . PHP_EOL;
        echo '  <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
        echo '  <lastmod>' . date('Y-m-d', strtotime($page['updated_at'])) . '</lastmod>' . PHP_EOL;
        echo '  <changefreq>monthly</changefreq>' . PHP_EOL;
        echo '  <priority>0.6</priority>' . PHP_EOL;
        echo '</url>' . PHP_EOL;
    }
} catch (PDOException $e) {
    error_log("Error al generar sitemap (páginas estáticas CMS): " . $e->getMessage());
}

// Cerrar el documento XML
echo '</urlset>' . PHP_EOL;
?>