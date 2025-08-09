<?php
require_once 'includes/header.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND is_published = 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    // Si no se encuentra la página o no está publicada, mostrar un 404 simple
    http_response_code(404);
    echo "<div class='container page-content'><h1>Página no encontrada</h1><p>Lo sentimos, la página que buscas no existe.</p></div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="container page-content">
    <h1><?php echo htmlspecialchars($page['title']); ?></h1>
    <hr>
    <div class="static-content">
        <?php echo $page['content']; // Imprimimos el HTML directamente desde el editor ?>
    </div>
</div>

<style>
/* Estilos básicos para el contenido generado por el editor */
.static-content h2, .static-content h3 { color: var(--accent-color); margin-top: 2rem; }
.static-content a { color: var(--accent-color); }
.static-content ul, .static-content ol { margin-left: 1.5rem; }
</style>

<?php require_once 'includes/footer.php'; ?>