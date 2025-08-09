<?php
// producto.php

// Inicia la sesión y carga dependencias (pdo, csrf functions, etc.).
// Esto debe ir al inicio y NO debe generar ninguna salida HTML por sí mismo.
require_once 'includes/init.php'; 

// === Lógica CRÍTICA para manejar solicitudes AJAX (Añadir al Carrito) ===
// La condición para detectar AJAX y la acción 'add_to_cart'
$is_ajax_add_to_cart_request = 
    ($_SERVER['REQUEST_METHOD'] === 'POST' && 
     ($_POST['action'] ?? '') === 'add_to_cart');

// Si es una solicitud AJAX para añadir al carrito, procesamos y salimos.
if ($is_ajax_add_to_cart_request) {
    header('Content-Type: application/json'); // Indicar que la respuesta es JSON

    $response = ['success' => false, 'message' => 'Error desconocido.', 'cart_count' => 0];

    try {
        // Validar CSRF token
        validate_csrf_token(); 

        $product_id_to_add = (int)($_POST['product_id'] ?? 0);

        if ($product_id_to_add <= 0) {
            throw new Exception('ID de producto inválido.');
        }

        // Obtener detalles básicos del producto para añadir al carrito
        $stmt_product_data = $pdo->prepare("SELECT id, name, price_usd, is_physical, allow_preorder, image_url FROM products WHERE id = ?");
        $stmt_product_data->execute([$product_id_to_add]);
        $product_data = $stmt_product_data->fetch(PDO::FETCH_ASSOC);

        if (!$product_data) {
            throw new Exception('El producto especificado no existe.');
        }

        // Obtener el stock actual de licencias disponibles si es un producto físico
        $available_licenses = 0;
        if ($product_data['is_physical'] == 1) {
            $stmt_licenses = $pdo->prepare("SELECT COUNT(*) FROM licenses WHERE product_id = ? AND status = 'disponible'");
            $stmt_licenses->execute([$product_id_to_add]);
            $available_licenses = $stmt_licenses->fetchColumn();
            
            if ($available_licenses <= 0 && !$product_data['allow_preorder']) {
                throw new Exception('Producto físico agotado y no permite pre-orden.');
            }
        }
        // Para productos digitales, se asume que siempre hay stock disponible a menos que tengas otra lógica de stock virtual.

        // Lógica para añadir al carrito (gestionado en la sesión)
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $quantity_to_add = 1; // Por defecto, añade una unidad
        
        // Verifica si el producto ya está en el carrito para actualizar la cantidad o añadirlo
        if (isset($_SESSION['cart'][$product_id_to_add])) {
            $_SESSION['cart'][$product_id_to_add]['quantity'] += $quantity_to_add;
        } else {
            $_SESSION['cart'][$product_id_to_add] = [
                'product_id' => $product_id_to_add,
                'name' => $product_data['name'],
                'price' => $product_data['price_usd'],
                'quantity' => $quantity_to_add,
                'image_url' => $product_data['image_url'] ?? '',
            ];
        }

        // Calcular el total de ítems en el carrito (para el contador del icono)
        $total_cart_items = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_cart_items += $item['quantity'];
        }

        $response['success'] = true;
        $response['message'] = htmlspecialchars($product_data['name']) . ' añadido al carrito.';
        $response['cart_count'] = $total_cart_items;
        
        if ($product_data['is_physical'] == 1) {
            $response['new_stock'] = $available_licenses - $quantity_to_add;
            $response['allow_preorder'] = (bool)$product_data['allow_preorder'];
            if ($response['new_stock'] <= 0 && !$response['allow_preorder']) {
                 $response['disable_button'] = true;
            }
        }

        custom_log("INFO: Producto {$product_id_to_add} añadido al carrito por usuario " . ($_SESSION['user_id'] ?? 'Invitado'), 'cart_activity.log');

    } catch (Exception $e) {
        $response['message'] = htmlspecialchars($e->getMessage());
        custom_log("ERROR AJAX en producto.php (add_to_cart): " . $e->getMessage() . " POST: " . print_r($_POST, true), 'frontend_errors.log');
    }

    echo json_encode($response);
    exit; 
}

// === Si la solicitud NO es AJAX para añadir al carrito, entonces renderizamos la página normal ===
require_once 'includes/header.php'; 

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product_found = false;
$product = [];
$related_products = [];
$product_categories = [];
$available_licenses = 0;

if ($product_id <= 0) {
    header("Location: catalogo.php");
    exit;
}

try {
    $stmt_product = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt_product->execute([$product_id]);
    $product = $stmt_product->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $product_found = true;

        $stmt_licenses = $pdo->prepare("SELECT COUNT(*) FROM licenses WHERE product_id = ? AND status = 'disponible'");
        $stmt_licenses->execute([$product_id]);
        $available_licenses = $stmt_licenses->fetchColumn();

        $stmt_related = $pdo->prepare("SELECT p.* FROM related_products rp JOIN products p ON rp.product_id_related = p.id WHERE rp.product_id_main = ? LIMIT 4");
        $stmt_related->execute([$product_id]);
        $related_products = $stmt_related->fetchAll(PDO::FETCH_ASSOC);

        $stmt_cats = $pdo->prepare("SELECT c.name, c.slug FROM categories c JOIN product_categories pc ON c.id = pc.category_id WHERE pc.product_id = ? ORDER BY c.name ASC");
        $stmt_cats->execute([$product_id]);
        $product_categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

        $stmt_rating = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE product_id = ? AND is_approved = 1");
        $stmt_rating->execute([$product_id]);
        $rating_summary = $stmt_rating->fetch(PDO::FETCH_ASSOC);

        $stmt_reviews = $pdo->prepare("
            SELECT r.rating, r.comment, r.created_at, u.name as user_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? AND r.is_approved = 1 
            ORDER BY r.created_at DESC
        ");
        $stmt_reviews->execute([$product_id]);
        $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

    }
} catch (PDOException $e) {
    custom_log("ERROR PDO en producto.php (render): " . $e->getMessage(), 'frontend_errors.log');
    $product_found = false;
}

$user_can_review = false;
$user_has_reviewed = false;
if (isset($_SESSION['user_id'])) {
    $stmt_check_review = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
    $stmt_check_review->execute([$product_id, $_SESSION['user_id']]);
    if ($stmt_check_review->fetch()) {
        $user_has_reviewed = true;
    } else {
        $stmt_check_purchase = $pdo->prepare("
            SELECT o.id FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'completado'
            LIMIT 1
        ");
        $stmt_check_purchase->execute([$_SESSION['user_id'], $product_id]);
        if ($stmt_check_purchase->fetch()) {
            $user_can_review = true;
        }
    }
}

$page_title = $product_found ? htmlspecialchars($product['name']) : 'Producto no Encontrado';
$site_url = BASE_URL; 
$token = generate_csrf_token();
?>

<?php include_once 'includes/page_meta.php'; ?>

<div class="container page-content">
    <?php if ($product_found): ?>
        <div class="product-details-grid">
            <div class="product-image-container">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image-large">
            </div>

            <div class="product-info">
                <h1 class="page-title-main" style="text-align: left; margin-bottom: var(--spacing-md);"><?php echo htmlspecialchars($product['name']); ?></h1>

                <?php if ($rating_summary && $rating_summary['total_reviews'] > 0): ?>
                    <div class="rating-summary">
                        <span class="star-rating-display" title="<?php echo number_format($rating_summary['avg_rating'], 1); ?> de 5 estrellas">
                            <?php 
                            $rounded_rating = round($rating_summary['avg_rating']);
                            echo str_repeat('★', $rounded_rating); 
                            echo str_repeat('☆', 5 - $rounded_rating);
                            ?>
                        </span>
                        <a href="#reviews-section" class="reviews-link">(<?php echo $rating_summary['total_reviews']; ?> reseñas)</a>
                    </div>
                <?php else: ?>
                    <div class="rating-summary">
                        <span class="star-rating-display">☆☆☆☆☆</span>
                        <span class="no-reviews-text">Sé el primero en reseñar este producto.</span>
                    </div>
                <?php endif; ?>
                
                <div class="price-container-large">
                    <?php if (!empty($product['compare_at_price_usd']) && $product['compare_at_price_usd'] > $product['price_usd']): ?>
                        <span class="compare-at-price"><?php echo $currencyConverter->convertAndFormat($product['compare_at_price_usd'], $current_currency); ?></span>
                        <span class="sale-price"><?php echo $currencyConverter->convertAndFormat($product['price_usd'], $current_currency); ?></span>
                    <?php else: ?>
                        <span class="regular-price"><?php echo $currencyConverter->convertAndFormat($product['price_usd'], $current_currency); ?></span>
                    <?php endif; ?>
                </div>

                <div class="stock-status">
                    <?php if ($available_licenses > 0 || $product['allow_preorder']): ?>
                        <span class="in-stock"><i class="bi bi-check-circle-fill"></i> En Stock</span>
                        <?php if ($available_licenses <= 5 && $available_licenses > 0 && !$product['allow_preorder']): ?>
                            <span class="low-stock-alert">(¡Solo quedan <?php echo htmlspecialchars($available_licenses); ?>!)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="out-of-stock"><i class="bi bi-x-circle-fill"></i> Agotado</span>
                    <?php endif; ?>
                </div>

                <div class="product-description">
                    <?php echo $product['description']; ?>
                </div>

                <div class="product-specs">
                    <h3>Especificaciones</h3>
                    <dl>
                        <?php if (!empty($product['brand'])): ?><dt>Marca</dt><dd><?php echo htmlspecialchars($product['brand']); ?></dd><?php endif; ?>
                        <?php if (!empty($product['version'])): ?><dt>Versión</dt><dd><?php echo htmlspecialchars($product['version']); ?></dd><?php endif; ?>
                        <?php if (!empty($product['platform'])): ?><dt>Plataforma</dt><dd><?php echo htmlspecialchars($product['platform']); ?></dd><?php endif; ?>
                        <?php if (!empty($product['devices_limit'])): ?><dt>Dispositivos</dt><dd><?php echo htmlspecialchars($product['devices_limit']); ?></dd><?php endif; ?>
                        <?php if (!empty($product['validity_duration'])): ?><dt>Validez</dt><dd><?php echo htmlspecialchars($product['validity_duration']); ?></dd><?php endif; ?>
                        <?php if (!empty($product_categories)): ?><dt>Categorías</dt><dd class="product-categories-list"><?php
                            $cat_links = [];
                            foreach ($product_categories as $cat) {$cat_links[] = '<a href="catalogo.php?categoria=' . urlencode($cat['slug']) . '">' . htmlspecialchars($cat['name']) . '</a>';}
                            echo implode(', ', $cat_links);?>
                            </dd>
                        <?php endif; ?>                        
                    </dl>
                </div>

                <form action="cart_manager.php" method="POST" class="add-to-cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                    <button type="submit" class="btn btn-primary btn-large" <?php if ($available_licenses <= 0 && !$product['allow_preorder']) echo 'disabled'; ?>>
                        <i class="bi bi-cart-plus-fill"></i>
                        <?php 
                        if ($available_licenses > 0 || $product['allow_preorder']) {
                            echo $lang['add_to_cart_button'];
                        } else {
                            echo $lang['out_of_stock'];
                        }
                        ?>
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($related_products)): ?>
        <div class="related-products-section">
            <hr>
            <h2 class="page-title-main" style="text-align: left;">También te podría interesar</h2>
            <div class="products-grid">
                <?php foreach ($related_products as $product_item): ?>
                    <?php include 'includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="reviews-section" id="reviews-section">
            <h2 class="page-title-main" style="text-align: left;">Reseñas de Clientes</h2>
            <?php if (empty($reviews)): ?>
                <p class="info-message">Este producto aún no tiene reseñas. ¡Sé el primero en dejar tu opinión!</p>
            <?php endif; ?>

            <?php if ($user_can_review): ?>
                <div class="review-form-container">
                    <h3>Deja tu Reseña</h3>
                    <?php if ($user_has_reviewed): ?>
                        <p class="warning-message">Ya has enviado una reseña para este producto. Gracias por tu opinión.</p>
                    <?php else: ?>
                        <form action="submit_review.php" method="POST" class="review-form">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="form-group">
                                <label>Tu Calificación:</label>
                                <div class="star-rating">
                                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 estrellas">★</label>
                                    <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 estrellas">★</label>
                                    <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 estrellas">★</label>
                                    <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 estrellas">★</label>
                                    <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 estrella">★</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="comment">Tu Comentario (Opcional):</label>
                                <textarea name="comment" id="comment" class="form-control" rows="5"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Enviar Reseña</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php elseif (isset($_SESSION['user_id'])): ?>
                <p class="info-message">Necesitas haber comprado este producto para dejar una reseña.</p>
            <?php else: ?>
                <p class="info-message">Por favor, <a href="login.php">inicia sesión</a> o <a href="registro.php">regístrate</a> para poder dejar una reseña después de tu compra.</p>
            <?php endif; ?>

            <div class="existing-reviews-list mt-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                            <span class="star-rating-display" title="<?php echo $review['rating']; ?> de 5 estrellas">
                                <?php echo str_repeat('★', $review['rating']); ?>
                            </span>
                        </div>
                        <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        <small class="review-date">Publicado el <?php echo date('d/m/Y', strtotime($review['created_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="product-not-found">
            <h2>Oops... Producto no encontrado</h2>
            <p>El producto que buscas no existe o no está disponible.</p>
            <a href="catalogo.php" class="btn btn-primary">Volver al Catálogo</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>