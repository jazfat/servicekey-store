<?php
require_once 'includes/header.php';

// Seguridad: si no hay una orden en la sesión, no se puede ver esta página.
if (!isset($_SESSION['last_order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_SESSION['last_order_id'];

// Obtenemos los datos de la orden recién creada para mostrar el mensaje correcto.
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Limpiamos la variable de sesión para que el mensaje no se muestre de nuevo si se recarga la página.
unset($_SESSION['last_order_id']);

$page_title = 'Gracias por tu Compra';
?>

<div class="container page-content" style="text-align: center; padding: 4rem 1rem;">
    <i class="bi bi-check-circle-fill" style="font-size: 5rem; color: var(--success-color);"></i>
    <h1 class="mt-3"><?php echo $lang['thank_you_title']; ?></h1>
    
    <p style="font-size: 1.2rem; max-width: 650px; margin: 1rem auto;">
        Tu pedido con el número de referencia <strong>#<?php echo htmlspecialchars($order['id']); ?></strong> ha sido recibido con éxito.
    </p>

    <?php if ($order && ($order['order_status'] === 'pendiente_stock' || $order['payment_status'] === 'en_verificacion')): ?>
        <div class="notice-box">
            <h4>Tu pedido está siendo procesado</h4>
            <p>
                Hemos recibido tu orden y, en el caso de pago manual, tu comprobante de pago. Nuestro equipo verificará la información a la brevedad posible.
            </p>
            <p>
                Recibirás una notificación por correo electrónico a <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong> tan pronto como tus licencias sean asignadas. También puedes revisar el estado de tu pedido en cualquier momento desde tu panel de usuario.
            </p>
        </div>
        <a href="mi-cuenta.php" class="btn btn-primary mt-4">Ver Estado de Mis Pedidos</a>

    <?php else: ?>
        <div class="notice-box">
            <h4>¡Entrega Inmediata!</h4>
            <p>
                Hemos enviado un correo electrónico a <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong> con los detalles de tu compra, tu(s) clave(s) de licencia y la factura en PDF adjunta.
            </p>
            <p>
                Si no lo ves en tu bandeja de entrada, por favor revisa tu carpeta de correo no deseado (spam).
            </p>
        </div>
        <a href="mi-cuenta.php" class="btn btn-primary mt-4">Ver Mis Licencias Ahora</a>

    <?php endif; ?>

</div>

<style>
    .notice-box {
        background-color: var(--card-bg);
        border-left: 5px solid var(--accent-color);
        padding: 1.5rem 2rem;
        margin-top: 2.5rem;
        max-width: 650px;
        margin-left: auto;
        margin-right: auto;
        border-radius: 5px;
        text-align: left;
    }
    .notice-box h4 {
        margin-top: 0;
        color: #fff;
    }
</style>

<?php require_once 'includes/footer.php'; ?>