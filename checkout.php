<?php
require_once 'includes/header.php';

// Seguridad: Si el carrito está vacío, no se puede estar aquí.
if (empty($_SESSION['cart']) || count($_SESSION['cart']) === 0) {

    header('Location: catalogo.php');
    exit;
}

// --- Lógica para calcular totales y preparar datos para la vista ---
$grand_total_usd = 0;
$cart_items_for_display = [];
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    // CORRECCIÓN CLAVE: Usamos PDO::FETCH_ASSOC para obtener un array simple y predecible.
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products_in_cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CORRECCIÓN CLAVE: La forma de recorrer el array ahora es más simple y correcta.
    foreach ($products_in_cart as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        // Ahora $product['price_usd'] es un número (o string numérico), lo cual es correcto.
        $grand_total_usd += $product['price_usd'] * $quantity;
        $cart_items_for_display[] = ['product' => $product, 'quantity' => $quantity];
    }
}

$discount_percentage = $_SESSION['coupon']['discount'] ?? 0;
$discount_amount_usd = ($grand_total_usd * $discount_percentage) / 100;
$final_total_usd = $grand_total_usd - $discount_amount_usd;

// Pre-rellenar datos del usuario si ha iniciado sesión.
$user_name = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
// El token ya se genera en header.php y está disponible en la meta etiqueta
$page_title = 'Finalizar Compra';
?>
<div class="container checkout-page">
    <form id="checkout-form" method="POST" enctype="multipart/form-data">
        <h1><?php echo $page_title; ?></h1>
        <div id="form-error" class="error-message" style="display:none;"></div>
        <?php 
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            echo '<div class="' . htmlspecialchars($message['type']) . '-message">' . htmlspecialchars($message['text']) . '</div>';
            unset($_SESSION['flash_message']);
        }
        ?>

        <div class="checkout-grid">
            <div class="billing-details">
                <h2><?php echo $lang['checkout_billing_details']; ?></h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <p>Verifica que tus datos de facturación sean correctos. Puedes actualizarlos en <a href="mi-cuenta.php">Mi Cuenta</a>.</p>
                    <div class="form-group">
                        <label for="name"><?php echo $lang['checkout_name']; ?></label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="email"><?php echo $lang['checkout_email']; ?></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                    </div>
                <?php else: ?>
                    <p>Tu nombre y correo son necesarios para la factura.</p>
                    <div class="form-group"><label for="name"><?php echo $lang['checkout_name']; ?>*</label><input type="text" id="name" name="name" class="form-control" required></div>
                    <div class="form-group"><label for="email"><?php echo $lang['checkout_email']; ?>*</label><input type="email" id="email" name="email" class="form-control" required></div>
                    <div id="email-exists-message" class="info-message" style="display:none; text-align: left;">
                        <p>Este correo electrónico ya está asociado a una cuenta.</p>
                        <p>¿Tienes una cuenta? <a href="login.php" class="btn btn-sm btn-primary">Iniciar Sesión</a></p>
                        <p>Si continuas como invitado, podrás finalizar tu compra, pero el historial de esta compra no será visible en tu cuenta si inicias sesión más tarde, a menos que te registres con este mismo correo si aún no tienes cuenta.</p>
                        <button type="button" id="continue-as-guest-btn" class="btn btn-sm btn-secondary">Continuar como Invitado</button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="order-summary">
                <h2>Tu Pedido</h2>
                <ul class="order-items-list">
                    <?php foreach ($cart_items_for_display as $item): ?>
                    <li>
                        <span><?php echo htmlspecialchars($item['product']['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                        <span><?php echo $currencyConverter->convertAndFormat($item['product']['price_usd'] * $item['quantity'], $current_currency); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <hr>
                <div class="summary-row"><span>Subtotal:</span><span><?php echo $currencyConverter->convertAndFormat($grand_total_usd, $current_currency); ?></span></div>
                <?php if ($discount_amount_usd > 0): ?>
                <div class="summary-row discount"><span>Descuento (<?php echo htmlspecialchars($_SESSION['coupon']['code']); ?>):</span><span>- <?php echo $currencyConverter->convertAndFormat($discount_amount_usd, $current_currency); ?></span></div>
                <?php endif; ?>
                <div class="summary-row total"><strong>Total:</strong><strong><?php echo $currencyConverter->convertAndFormat($final_total_usd, $current_currency); ?></strong></div>
                
                <div class="coupon-form">
                    <input type="text" id="coupon-code" placeholder="Código de Cupón" class="form-control">
                    <button type="button" id="apply-coupon-btn" class="btn btn-secondary">Aplicar</button>
                </div>
                <div id="coupon-feedback" class="mt-2"></div>

                <div class="payment-methods">
                    <h3>Método de Pago</h3>
                    <div class="payment-option">
                        <input type="radio" id="paypal_radio" name="payment_method_radio" value="paypal" checked>
                        <label for="paypal_radio"><i class="bi bi-paypal"></i> PayPal o Tarjeta</label>
                    </div>
                    <div class="payment-option" style="display:none !important;">
                    <input type="radio" id="binance_radio" name="payment_method_radio" value="binance">
                    <label for="binance_radio"><img src="assets/images/binance-pay-logo.svg" alt="Binance Pay" style="height: 20px; vertical-align: middle; margin-right: 5px;"> Binance Pay</label>
                    </div>
                    
                      <div class="payment-option">
                        <input type="radio" id="manual_radio" name="payment_method_radio" value="manual">
                        <label for="manual_radio"><i class="bi bi-wallet2"></i> Pago Movil (VES) o Binance USDT</label>
                    </div>
                    
                    <div id="paypal-payment-section" class="payment-section">
                        <div id="paypal-button-container" class="mt-3"></div>
                        <p id="paypal-error" class="error-message" style="display:none;"></p>
                    </div>
                    
                    <div id="binance-payment-section" class="payment-section" style="display:none;">
                        <button type="button" id="binance-pay-btn" class="btn btn-warning btn-block mt-3"><img src="assets/images/binance-pay-logo.svg" alt="Binance Pay" style="height: 20px; vertical-align: middle; margin-right: 5px;"> Pagar con Binance Pay</button>
                        <p id="binance-error" class="error-message" style="display:none;"></p>
                        <p id="binance-instructions" class="mt-3" style="display:none;">
                            Serás redirigido a la pasarela de Binance Pay para completar tu pago. Una vez finalizado, regresa a la tienda.
                        </p>
                    </div>

                    <div id="manual-payment-section" class="payment-section" style="display:none;">
    <div class="payment-instructions">
        <p><?php echo $lang['checkout_manual_instructions_upload']; ?></p>
        <div class="payment-details-box">
            <strong><?php echo $lang['checkout_pagomovil']; ?> (<?php echo $lang['checkout_total']; ?>: <?php echo $currencyConverter->convertAndFormat($final_total_usd, 'VES'); ?>)</strong>
            <pre id="pagomovil-details"><?php echo nl2br(htmlspecialchars($site_settings['pago_movil_details'] ?? '')); ?></pre>
            <button type="button" class="btn btn-sm btn-secondary copy-btn" data-target="pagomovil-details"><i class="bi bi-clipboard-check"></i> <?php echo $lang['checkout_copy_button']; ?></button>
        </div>
        <div class="payment-details-box">
            <strong><?php echo $lang['checkout_binance_pay']; ?> (<?php echo $lang['checkout_total']; ?>: <?php echo number_format($final_total_usd, 2, '.', ','); ?> USDT)</strong>
            <?php if(!empty($site_settings['binance_qr_url'])): ?>
                <img src="<?php echo BASE_URL; ?><?php echo htmlspecialchars($site_settings['binance_qr_url']); ?>" alt="Binance QR" class="payment-qr">
            <?php endif; ?>
            ID:<pre id="binance-details"><?php echo nl2br(htmlspecialchars($site_settings['binance_details'] ?? '')); ?> </pre>
            <button type="button" class="btn btn-sm btn-secondary copy-btn" data-target="binance-details"><i class="bi bi-clipboard-check"></i> <?php echo $lang['checkout_copy_button']; ?></button>
        </div>
        <div class="form-group mt-3">
            <label for="payment_receipt"><strong><?php echo $lang['checkout_upload_receipt']; ?>*</strong></label>
            <input type="file" name="payment_receipt" id="payment_receipt" class="form-control" accept="image/*,application/pdf">
            <div id="receipt-error" class="error-message" style="display:none; margin-top: 5px;"></div>
        </div>
    </div>
    <button type="button" id="manual-payment-btn" class="btn btn-primary btn-block mt-3"><?php echo $lang['checkout_confirm_manual_order']; ?></button>
</div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=USD"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('checkout-form');
        const paymentMethodRadios = document.querySelectorAll('input[name="payment_method_radio"]');
        const paypalSection = document.getElementById('paypal-payment-section');
        const binanceSection = document.getElementById('binance-payment-section');
        const manualSection = document.getElementById('manual-payment-section');
        const manualPaymentBtn = document.getElementById('manual-payment-btn');
        const binancePayBtn = document.getElementById('binance-pay-btn');
        const formErrorDiv = document.getElementById('form-error');
        const paypalError = document.getElementById('paypal-error');
        const binanceErrorDiv = document.getElementById('binance-error');
        const binanceInstructions = document.getElementById('binance-instructions');

        const emailInput = document.getElementById('email');
        const emailExistsMessage = document.getElementById('email-exists-message');
        const continueAsGuestBtn = document.getElementById('continue-as-guest-btn');

        function togglePaymentSections() {
            const selectedMethod = document.querySelector('input[name="payment_method_radio"]:checked').value;
            paypalSection.style.display = (selectedMethod === 'paypal') ? 'block' : 'none';
            binanceSection.style.display = (selectedMethod === 'binance') ? 'block' : 'none';
            manualSection.style.display = (selectedMethod === 'manual') ? 'block' : 'none';

            formErrorDiv.style.display = 'none';
            paypalError.style.display = 'none';
            binanceErrorDiv.style.display = 'none';
            binanceInstructions.style.display = 'none';
        }
        paymentMethodRadios.forEach(radio => radio.addEventListener('change', togglePaymentSections));
        togglePaymentSections();

        function validateForm() {
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            if (!nameInput.value.trim() || !emailInput.value.trim()) {
                formErrorDiv.innerText = 'Por favor, completa tus detalles de facturación (Nombre y Correo).';
                formErrorDiv.style.display = 'block';
                nameInput.focus();
                return false;
            }
            if (emailExistsMessage && emailExistsMessage.style.display === 'block' && emailInput.getAttribute('data-email-exists') === 'true') {
                formErrorDiv.innerText = 'Por favor, inicie sesión o haga clic en "Continuar como Invitado" para proceder.';
                formErrorDiv.style.display = 'block';
                emailInput.focus();
                return false;
            }
            formErrorDiv.style.display = 'none';
            return true;
        }

        if (emailInput && emailExistsMessage && continueAsGuestBtn) {
            const checkEmailExistence = async () => {
                const email = emailInput.value.trim();
                if (email === '' || !emailInput.checkValidity()) {
                    emailExistsMessage.style.display = 'none';
                    emailInput.removeAttribute('data-email-exists');
                    return;
                }

                // Obtener el csrf_token de la meta etiqueta
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';
                
                try {
                    const response = await fetch('api/check_email.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email, csrf_token: csrfToken })
                    });
                    const result = await response.json();

                    if (result.exists) {
                        emailExistsMessage.style.display = 'block';
                        emailInput.setAttribute('data-email-exists', 'true');
                    } else {
                        emailExistsMessage.style.display = 'none';
                        emailInput.removeAttribute('data-email-exists');
                    }
                } catch (error) {
                    console.error('Error al verificar email:', error);
                    emailExistsMessage.style.display = 'none';
                    emailInput.removeAttribute('data-email-exists');
                }
            };

            emailInput.addEventListener('blur', checkEmailExistence);

            continueAsGuestBtn.addEventListener('click', () => {
                emailExistsMessage.style.display = 'none';
                emailInput.removeAttribute('data-email-exists');
            });

            if (emailInput.value.trim() !== '' && !<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                checkEmailExistence();
            }
        }

        // Lógica de PayPal
        paypal.Buttons({
            style: { layout: 'vertical', color: 'blue', shape: 'rect', label: 'pay' },
            onClick: (data, actions) => { return validateForm() ? actions.resolve() : actions.reject(); },
            createOrder: (data, actions) => {
                // Obtener el csrf_token de la meta etiqueta
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

                return fetch('api/create_paypal_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_name: document.getElementById('name').value,
                        customer_email: document.getElementById('email').value,
                        csrf_token: csrfToken // Usar token de meta tag
                    })
                }).then(res => res.json()).then(orderData => {
                    if (orderData.id) return orderData.id;
                    throw new Error(orderData.error || 'Error al crear la orden de pago.');
                });
            },
            onApprove: (data, actions) => {
                // Obtener el csrf_token de la meta etiqueta
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

                return fetch('api/capture_paypal_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        orderID: data.orderID,
                        csrf_token: csrfToken // Usar token de meta tag
                    })
                })
                .then(res => res.json())
                .then(details => {
                    if (details.success) {
                        window.location.href = 'gracias.php';
                    } else {
                        document.getElementById('paypal-error').innerText = details.error || 'No se pudo procesar tu pago.';
                        document.getElementById('paypal-error').style.display = 'block';
                    }
                });
            },
            onError: (err) => {
                console.error('Error de PayPal:', err);
                document.getElementById('paypal-error').innerText = 'Ocurrió un error inesperado con PayPal.';
            }
        }).render('#paypal-button-container');

        // Lógica para Pago Manual
        if(manualPaymentBtn) {
            manualPaymentBtn.addEventListener('click', async function() {
                if (!validateForm()) return;
                const receiptInput = document.getElementById('payment_receipt');
                const receiptErrorDiv = document.getElementById('receipt-error');
                if (receiptInput.files.length === 0) {
                    receiptErrorDiv.innerText = 'Es obligatorio subir un comprobante de pago.';
                    receiptErrorDiv.style.display = 'block';
                    return;
                }
                receiptErrorDiv.style.display = 'none';
                const formData = new FormData(form);
                formData.append('payment_method', 'manual');
                // Obtener el csrf_token de la meta etiqueta
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';
                formData.append('csrf_token', csrfToken); // Usar token de meta tag

                try {
                    const response = await fetch('place_order.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        window.location.href = 'gracias.php';
                    } else {
                        formErrorDiv.innerText = result.message || 'Ocurrió un error al procesar tu pedido.';
                        formErrorDiv.style.display = 'block';
                    }
                } catch (error) { console.error('Error en envío manual:', error); }
            });
        }

        // Lógica para Binance Pay (NUEVA)
        if(binancePayBtn) {
            binancePayBtn.addEventListener('click', async function() {
                if (!validateForm()) return;

                binancePayBtn.disabled = true;
                binancePayBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
                binanceErrorDiv.style.display = 'none';
                binanceInstructions.style.display = 'none';

                // Obtener el csrf_token de la meta etiqueta
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

                try {
                    const response = await fetch('api/create_binance_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            customer_name: document.getElementById('name').value,
                            customer_email: document.getElementById('email').value,
                            csrf_token: csrfToken // Usar token de meta tag
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        binanceInstructions.innerText = 'Redirigiendo a Binance Pay...';
                        binanceInstructions.style.display = 'block';
                        window.location.href = result.redirect_url;
                    } else {
                        binanceErrorDiv.innerText = result.message || 'Error al iniciar pago con Binance Pay.';
                        binanceErrorDiv.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error en Binance Pay:', error);
                    binanceErrorDiv.innerText = 'Ocurrió un error de red al iniciar el pago con Binance Pay.';
                    binanceErrorDiv.style.display = 'block';
                } finally {
                    binancePayBtn.disabled = false;
                    binancePayBtn.innerHTML = '<img src="assets/images/binance-pay-logo.svg" alt="Binance Pay" style="height: 20px; vertical-align: middle; margin-right: 5px;"> Pagar con Binance Pay';
                }
            });
        }

        // Lógica para los botones de copiar
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target; // Obtiene el ID del elemento a copiar
            const textElement = document.getElementById(targetId); // Encuentra el elemento
            
            if (textElement) {
                const textToCopy = textElement.innerText; // Obtiene el texto (innerText para preservar saltos de línea)
                navigator.clipboard.writeText(textToCopy)
                    .then(() => {
                        // Opcional: Mostrar feedback visual (ej. cambiar icono o texto temporalmente)
                        const originalHtml = this.innerHTML;
                        this.innerHTML = '<i class="bi bi-check-lg"></i> <?php echo $lang['checkout_copied_text']; ?>'; // Mensaje bilingüe
                        setTimeout(() => {
                            this.innerHTML = originalHtml; // Restaurar el botón
                        }, 2000);
                    })
                    .catch(err => {
                        console.error('Error al copiar el texto: ', err);
                        alert('<?php echo $lang['checkout_copy_error']; ?>'); // Mensaje bilingüe
                    });
            }
        });
    });
    });
</script>
<style> .payment-qr { max-width: 150px; margin-top: 10px; border-radius: 8px; } </style>
<?php require_once 'includes/footer.php'; ?>