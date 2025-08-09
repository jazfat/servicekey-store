<?php
require_once '../includes/init.php';
// --- INICIALIZACIÓN ---
$edit_mode = false;
$page_title = 'Crear Nuevo Cupón';
$action = 'create';
$token = generate_csrf_token();

// Valores por defecto para un cupón nuevo para evitar errores
$coupon = [
    'id' => '',
    'code' => '',
    'discount_percentage' => '',
    'is_active' => 1, // Por defecto, un nuevo cupón está activo
    'expiration_date' => '',
    'max_uses' => 0, // Por defecto, usos ilimitados
    'uses_count' => 0
];

// --- MODO EDICIÓN ---
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $coupon_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($coupon_data) {
        $coupon = $coupon_data;
        $page_title = 'Editar Cupón';
        $edit_mode = true;
        $action = 'update';
    }
}

include_once 'admin_header.php'; // Incluimos el nuevo header del admin
?>

<h1><?php echo htmlspecialchars($page_title); ?></h1>

<div class="form-section">
    <form action="coupon_handler.php" method="POST" class="admin-form">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <input type="hidden" name="id" value="<?php echo $coupon['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">

        <div class="form-group">
            <label for="code">Código del Cupón (ej: VERANO25)</label>
            <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($coupon['code']); ?>" required>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="discount_percentage">Porcentaje de Descuento (%)</label>
                <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($coupon['discount_percentage']); ?>" required>
            </div>
            <div class="form-group">
                <label for="expiration_date">Fecha de Expiración (Opcional)</label>
                <input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?php echo htmlspecialchars($coupon['expiration_date']); ?>">
            </div>
        </div>

        <div class="form-grid">
             <div class="form-group">
                <label for="max_uses">Límite de Usos Totales (0 para ilimitado)</label>
                <input type="number" class="form-control" id="max_uses" name="max_uses" min="0" value="<?php echo htmlspecialchars($coupon['max_uses']); ?>" required>
            </div>
             <?php if ($edit_mode): ?>
                <div class="form-group">
                    <label>Uso Actual</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($coupon['uses_count']); ?> / <?php echo ($coupon['max_uses'] > 0) ? $coupon['max_uses'] : '∞'; ?>" readonly>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-check form-switch mt-3 mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?php if(!empty($coupon['is_active'])) echo 'checked'; ?>>
            <label class="form-check-label" for="is_active">Cupón Activo</label>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary">Guardar Cupón</button>
            <a href="manage_coupons.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include_once 'admin_footer.php'; // Incluimos el footer del admin ?>