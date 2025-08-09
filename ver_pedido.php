<?php
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$order_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) { die("Orden no encontrada o no tienes permiso para verla."); }

$stmt_items = $pdo->prepare("
    SELECT oi.id, oi.price, oi.license_id, p.name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();
?>
<div class="container my-account-page">
    <h1>Detalles del Pedido #<?php echo $order['id']; ?></h1>
    <a href="mi-cuenta.php">&larr; Volver a Mis Pedidos</a>

    <h3>Productos Comprados</h3>
    <table class="account-orders-table">
        <thead><tr><th>Producto</th><th>Licencia</th></tr></thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td>
                    <?php if(empty($item['license_id'])): ?>
                        <span class="status-badge status-pendiente">Pendiente de asignación</span>
                    <?php else: ?>
                        <div class="license-viewer">
                            <span>••••-••••-••••-••••</span>
                            <button class="btn-action view-key" data-item-id="<?php echo $item['id']; ?>" title="Revelar clave"><i class="bi bi-eye-fill"></i></button>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="license-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3>Tu Clave de Licencia</h3>
        <div class="license-key-box">
            <span id="modal-license-key"></span>
            <button id="modal-copy-btn" class="btn btn-sm btn-secondary"><i class="bi bi-clipboard-check"></i> Copiar</button>
        </div>
        <p>Esta ventana se cerrará en <span id="modal-countdown">15</span> segundos.</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>