<?php
// includes/slider.php

// Este archivo asume que la variable $pdo ya está disponible desde el script que lo incluye (ej. index.php)

try {
    $stmt_slides = $pdo->query("SELECT * FROM slides WHERE is_active = 1 ORDER BY sort_order ASC");
    $slides = $stmt_slides->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener slides: " . $e->getMessage());
    $slides = [];
}

// Bucle 1: Generar los <link rel="preload"> para el <head>
if (!empty($slides)) {
    foreach ($slides as $index => $slide) {
        // Preparamos las rutas para la precarga
        $preload_desktop_path = !empty($slide['image_desktop']) ? '/' . ltrim(htmlspecialchars($slide['image_desktop']), '/') : '';
        $preload_mobile_path = !empty($slide['image_mobile']) ? '/' . ltrim(htmlspecialchars($slide['image_mobile']), '/') : $preload_desktop_path;

        // Damos prioridad alta a las imágenes del primer slide, que es el visible al cargar la página
        $fetch_priority = ($index === 0) ? 'high' : 'low';

        if ($preload_desktop_path) {
            echo '<link rel="preload" fetchpriority="' . $fetch_priority . '" as="image" href="' . $preload_desktop_path . '">';
        }
        if ($preload_mobile_path && $preload_mobile_path !== $preload_desktop_path) {
             echo '<link rel="preload" fetchpriority="' . $fetch_priority . '" as="image" href="' . $preload_mobile_path . '">';
        }
    }
}
?>

<?php if (!empty($slides)): ?>
<section class="dynamic-slider-container">
    <div class="slider">
        <?php foreach ($slides as $index => $slide): ?>
            <?php
                // =================================================================
                // === INICIO DE LA CORRECCIÓN ===
                // Volvemos a definir las variables de ruta DENTRO de este bucle
                // para que cada slide tenga su propia imagen correcta.
                $desktop_path = !empty($slide['image_desktop']) ? '/' . ltrim(htmlspecialchars($slide['image_desktop']), '/') : '';
                $mobile_path = !empty($slide['image_mobile']) ? '/' . ltrim(htmlspecialchars($slide['image_mobile']), '/') : $desktop_path;
                // === FIN DE LA CORRECCIÓN ===
            ?>
            <div class="slide <?php if ($index === 0) echo 'active'; ?>" 
                 style="--bg-desktop: url('<?php echo $desktop_path; ?>'); --bg-mobile: url('<?php echo $mobile_path; ?>');">
    
                <div class="container slide-content">
                    <?php if (!empty($slide['title'])): ?>
                        <h1><?php echo htmlspecialchars($slide['title']); ?></h1>
                    <?php endif; ?>
                    <?php if (!empty($slide['subtitle'])): ?>
                        <p><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($slide['button_text']) && !empty($slide['button_link'])): ?>
                        <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" class="btn btn-primary"><?php echo htmlspecialchars($slide['button_text']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($slides) > 1): // Solo mostrar controles si hay más de 1 slide ?>
        <button class="slider-control prev" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
        <button class="slider-control next" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
        <div class="slider-dots">
            <?php foreach ($slides as $index => $slide): ?>
                <button class="dot <?php if ($index === 0) echo 'active'; ?>" data-slide-to="<?php echo $index; ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>