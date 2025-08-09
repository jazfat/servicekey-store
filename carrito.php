<?php
require_once 'includes/header.php';

$cart_items = [];
$grand_total = 0;

// Inicializamos el carrito como vacío
$cart_is_empty = empty($_SESSION['cart']);

if (!$cart_is_empty) {
    $product_ids = array_keys($_SESSION['cart']);
    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price_usd, image_url FROM products WHERE id IN ($placeholders)");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $subtotal = $product['price_usd'] * $quantity;
            $grand_total += $subtotal;
            $cart_items[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'image_url' => $product['image_url'],
                'price_usd' => $product['price_usd'],
                'quantity' => $quantity,
                'subtotal_usd' => $subtotal
            ];
        }
    }
}

$token = generate_csrf_token();
?>

<div class="container cart-page">
    <h1><?php echo $lang['cart_title']; ?></h1>

    <?php if ($cart_is_empty): ?>
        <div class="cart-empty-message info-message">
            <p><?php echo $lang['cart_empty']; ?></p>
            <a href="catalogo.php" class="btn btn-primary"><?php echo $lang['cart_continue_shopping']; ?></a>
        </div>
    <?php else: ?>
        <div class="cart-grid">
            <div class="cart-items-table">
                <div class="table-responsive">
                    <table class="full-width-table">
                        <thead>
                            <tr>
                                <th><?php echo $lang['cart_product']; ?></th>
                                <th><?php echo $lang['cart_price']; ?></th>
                                <th><?php echo $lang['cart_quantity']; ?></th>
                                <th><?php echo $lang['cart_total']; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-product-img">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </td>
                                    <td>$<?php echo number_format($item['price_usd'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['subtotal_usd'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="cart-summary">
                <h3><?php echo $lang['cart_summary']; ?></h3>
                <p><strong><?php echo $lang['cart_total']; ?>:</strong> $<?php echo number_format($grand_total, 2); ?></p>

                <form action="checkout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                    <button type="submit" class="btn btn-success btn-block">
                        <?php echo $lang['cart_checkout']; ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>