<?php
require_once '../includes/init.php';

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$token = generate_csrf_token(); // Generar token para los formularios de borrado
$page_title = 'Gestionar Cupones de Descuento';
include_once 'admin_header.php'; // Incluimos el nuevo header del admin
?>


  <div class="container-admin-panel">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="edit_coupon.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Crear Nuevo Cupón</a>
    
    <div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descuento (%)</th>
                <th>Activo</th>
                <th>Fecha de Expiración</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($coupons as $coupon): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($coupon['discount_percentage']); ?>%</td>
                    <td>
                        <span class="status-badge <?php echo $coupon['is_active'] ? 'status-pagado' : 'status-cancelado'; ?>">
                            <?php echo $coupon['is_active'] ? 'Sí' : 'No'; ?>
                        </span>
                    </td>
                    <td><?php echo $coupon['expiration_date'] ? date('d/m/Y', strtotime($coupon['expiration_date'])) : 'N/A'; ?></td>
                    <td class="actions-cell">
                        <a href="edit_coupon.php?id=<?php echo $coupon['id']; ?>" class="btn-action edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                        <form action="coupon_handler.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $coupon['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                            <button type="submit" class="btn-action delete" title="Eliminar" onclick="return confirm('¿Estás seguro?');"><i class="bi bi-trash3-fill"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>