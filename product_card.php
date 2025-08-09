<?php
/**
 * Tarjeta de Producto Reutilizable.
 * * Este archivo se incluye dentro de un bucle `foreach ($products as $product)`.
 * Asume que las siguientes variables ya existen en el ámbito donde se incluye:
 * - $pdo: Objeto de conexión a la base de datos.
 * - $product: Array asociativo con los datos del producto actual.
 * - $currencyConverter: Objeto de la clase CurrencyConverter.
 * - $current_currency: El código de la moneda actual (ej. 'USD').
 * - $lang: Array con los textos del idioma actual.
 * - $token: El token de seguridad CSRF.
 */

// Obtenemos el stock y el estado de preventa para esta tarjeta específica
try {
    $stmt_stock_card = $pdo->prepare("SELECT COUNT(*) FROM licenses WHERE product_id = ? AND status = 'disponible'");
    $stmt_stock_card->execute([$product['id']]);
    $available_stock_card = $stmt_stock_card->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener stock para product_card (ID: {$product['id']}): " . $e->getMessage());
    $available_stock_card = 0;
}

// Calcular el porcentaje de descuento (duplicado de catalogo.php, pero necesario si este se incluye standalone)
$discount_percentage = 0;
if (isset($product['price_usd']) && isset($product['compare_at_price_usd']) && $product['compare_at_price_usd'] > $product['price_usd']) {
    $discount_percentage = round((($product['compare_at_price_usd'] - $product['price_usd']) / $product['compare_at_price_usd']) * 100);
}

// Mapeo de marcas a clases de iconos de Bootstrap Icons
$brand_icon_class = '';
switch (strtolower($product['brand'])) {
    case 'microsoft':
        $brand_icon_class = 'bi-windows';
        break;
    case 'adobe':
        $brand_icon_class = 'bi-bezier2'; // O bi-adobe si existiera
        break;
    case 'kaspersky':
        $brand_icon_class = 'bi-shield-fill-check';
        break;
    case 'autodesk':
        $brand_icon_class = 'bi-bounding-box';
        break;
    case 'apple':
        $brand_icon_class = 'bi-apple';
        break;
    case 'linux':
        $brand_icon_class = 'bi-ubuntu'; // O bi-linux
        break;
    default:
        $brand_icon_class = 'bi-tags-fill'; // Ícono genérico si no hay coincidencia
        break;
}
?>
<div class="product-card">
    <?php if ($discount_percentage > 0): ?>
        <span class="badge badge-discount">-<?php echo htmlspecialchars($discount_percentage); ?>%</span>
    <?php endif; ?>
    <?php if (!empty($product['devices_limit'])): ?>
        <span class="badge badge-device-limit"><?php echo htmlspecialchars($product['devices_limit']); ?></span>
    <?php endif; ?>
    
    <div class="product-card-image-container">
        <a href="producto.php?id=<?php echo htmlspecialchars($product['id']); ?>">
            <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'assets/img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-card-image" loading="lazy">
        </a>
    </div>
    <div class="product-card-body">
        <h3 class="product-card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
        <?php if (!empty($product['brand'])): ?>
            <p class="product-card-brand">
                <?php if (!empty($brand_icon_class)): ?>
                    <i class="bi <?php echo htmlspecialchars($brand_icon_class); ?> product-card-brand-logo"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($product['brand']); ?>
            </p>
        <?php endif; ?>
        
        <div class="product-card-price-container">
            <span class="product-card-price-current">$<?php echo number_format($product['price_usd'], 2); ?> USD</span>
            <?php if (isset($product['compare_at_price_usd']) && $product['compare_at_price_usd'] > $product['price_usd']): ?>
                <span class="product-card-price-old">$<?php echo number_format($product['compare_at_price_usd'], 2); ?> USD</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-action">
        <?php // --- LÓGICA DE BOTÓN "AÑADIR AL CARRITO" DESDE product_card.php --- ?>
        <?php if ($available_stock_card > 0 || $product['allow_preorder']): ?>
            <form action="cart_manager.php" method="POST" style="margin:0;">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                <button type="submit" class="product-card-button"><?php echo $lang['add_to_cart_button']; ?> <i class="fas fa-cart-plus"></i></button>
            </form>
        <?php else: ?>
            <a href="producto.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="product-card-button disabled" aria-disabled="true"><?php echo $lang['out_of_stock']; ?></a>
        <?php endif; ?>
    </div>
</div>