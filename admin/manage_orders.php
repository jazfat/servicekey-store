<?php
// admin/manage_orders.php

require_once 'admin_header.php'; // Carga admin_header, que a su vez carga init.php

$page_title = 'Gestionar Órdenes';

// Obtener todas las órdenes con detalles relevantes
$stmt = $pdo->prepare("SELECT o.id, o.customer_email, o.total_amount, o.currency, o.order_status, o.payment_status, o.created_at
                       FROM orders o
                       ORDER BY o.created_at DESC");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Token CSRF para formularios futuros (ej. cambiar estado)
$token = generate_csrf_token();
?>

<div class="container-admin-panel">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <div class="admin-header">
        <h1>Órdenes del Sistema</h1>
        </div>

    <?php if (empty($orders)): ?>
        <p>No hay órdenes en el sistema.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Moneda</th>
                    <th>Estado de Orden</th>
                    <th>Estado de Pago</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                    <td><?php echo $currencyConverter->convertAndFormat($order['total_amount'], $order['currency']); ?></td>
                    <td><?php echo htmlspecialchars($order['currency']); ?></td>
                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['payment_status'])); ?>"><?php echo htmlspecialchars($order['payment_status']); ?></span></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                    <td class="actions-cell">
                        <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn-action edit" title="Ver Detalles"><i class="bi bi-eye"></i></a>
                        </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'admin_footer.php'; ?>