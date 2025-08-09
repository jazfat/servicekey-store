<?php
// includes/logo_scroller.php

// Este archivo también asume que $pdo ya está disponible.
try {
    $logos = $pdo->query("SELECT * FROM brand_logos ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener logos de marcas: " . $e->getMessage());
    $logos = [];
}
?>

<?php if (!empty($logos)): ?>
<div class="logo-scroller">
    <div class="scroller-inner">
        <?php 
        // Duplicamos el array de logos para que la animación de bucle infinito sea perfecta y sin saltos.
        $scrolling_logos = array_merge($logos, $logos);
        foreach ($scrolling_logos as $logo): 
        ?>
            <?php if (!empty($logo['link_url'])): ?>
                <a href="<?php echo htmlspecialchars($logo['link_url']); ?>" target="_blank" title="<?php echo htmlspecialchars($logo['name']); ?>">
                    <img src="<?php echo htmlspecialchars($logo['logo_url']); ?>" alt="<?php echo htmlspecialchars($logo['name']); ?>">
                </a>
            <?php else: ?>
                <img src="<?php echo htmlspecialchars($logo['logo_url']); ?>" alt="<?php echo htmlspecialchars($logo['name']); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>