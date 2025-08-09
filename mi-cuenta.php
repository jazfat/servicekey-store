<?php
require_once 'includes/header.php';

// Redirigir al login si el usuario no ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener las órdenes del usuario logueado, ordenadas por fecha de creación descendente
try {
    $stmt = $pdo->prepare("SELECT id, created_at, total_amount, currency, order_status, payment_status FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    custom_log("ERROR PDO en mi-cuenta.php (obtener órdenes): " . $e->getMessage(), 'user_errors.log');
    $orders = []; // Vaciar órdenes en caso de error
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error al cargar tus pedidos. Por favor, intenta de nuevo.'];
}

// Obtener los datos actuales del usuario para los formularios
$user_data = [];
try {
    $stmt_user = $pdo->prepare("SELECT name, email, country, address, preferred_currency FROM users WHERE id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    custom_log("ERROR PDO en mi-cuenta.php (obtener datos de usuario): " . $e->getMessage(), 'user_errors.log');
    // No es un error crítico si no se obtienen datos del usuario, pero se registra.
}

// El token CSRF ya se genera y está en la meta etiqueta en includes/header.php
$token = generate_csrf_token();

// Lista de países para el dropdown (debe ser la misma que en registro.php)
$countries = [
    "US" => "Estados Unidos",
    "ES" => "España",
    "MX" => "México",
    "CO" => "Colombia",
    "AR" => "Argentina",
    "CL" => "Chile",
    "PE" => "Perú",
    "EC" => "Ecuador",
    "VE" => "Venezuela",
    "CA" => "Canadá",
    "GB" => "Reino Unido",
    "DE" => "Alemania",
    "FR" => "Francia",
    "IT" => "Italia",
    "BR" => "Brasil",
];
asort($countries); // Ordenar alfabéticamente
?>
<div class="container my-account-page">
    <h1>Mi Cuenta</h1>
    <p>Bienvenido, **<?php echo htmlspecialchars($_SESSION['user_name']); ?>**!</p>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="<?php echo htmlspecialchars($_SESSION['flash_message']['type']); ?>-message">
            <?php echo htmlspecialchars($_SESSION['flash_message']['text']); ?>
        </div>
        <?php unset($_SESSION['flash_message']); // Limpiar el mensaje ?>
    <?php endif; ?>
    
    <div class="account-tabs">
        <button class="tab-button active" data-tab="orders">Mis Pedidos</button>
        <button class="tab-button" data-tab="profile">Mi Perfil</button>
        <button class="tab-button" data-tab="password">Cambiar Contraseña</button>
    </div>

    <div id="tab-orders" class="tab-content active">
        <h2>Mis Pedidos</h2>
        <?php if (empty($orders)): ?>
            <div class="info-message">
                <p>Aún no has realizado ningún pedido. ¡Explora nuestro <a href="catalogo.php">catálogo</a> para empezar!</p>
            </div>
        <?php else: ?>
            <div class="accordion">
                <?php foreach ($orders as $order): ?>
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <div class="order-header-info">
                                <span class="order-id">Orden #<?php echo htmlspecialchars($order['id']); ?></span>
                                <span class="order-date"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-header-status">
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['payment_status'])); ?>"><?php echo htmlspecialchars($order['payment_status']); ?></span>
                                <span class="order-total"><?php echo $currencyConverter->convertAndFormat($order['total_amount'], $order['currency']); ?></span>
                            </div>
                        </button>
                        <div class="accordion-content">
                            <div class="order-items-list-myaccount">
                                <?php
                                $stmt_items = $pdo->prepare("
                                    SELECT oi.id as order_item_id, p.name, p.image_url, oi.license_id, l.license_key_encrypted 
                                    FROM order_items oi 
                                    JOIN products p ON oi.product_id = p.id 
                                    LEFT JOIN licenses l ON oi.license_id = l.id 
                                    WHERE oi.order_id = ?
                                ");
                                $stmt_items->execute([$order['id']]);
                                $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach($items as $item):
                                ?>
                                <div class="order-item-card-myaccount">
                                    <div class="item-product-details-myaccount">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="order-item-thumbnail-myaccount">
                                        <span class="product-name-myaccount"><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                    <div class="item-license-status-myaccount">
                                        <?php if ($order['order_status'] === 'completado' && $item['license_id']): ?>
                                            <div class="license-key-display-group">
                                                <span><?php echo mask_license_key(decrypt_key($item['license_key_encrypted'])); ?></span>
                                                <button class="btn-action view-key" data-item-id="<?php echo htmlspecialchars($item['order_item_id']); ?>" title="Ver Clave Completa"><i class="bi bi-eye-fill"></i></button>
                                            </div>
                                        <?php elseif ($order['payment_status'] === 'en_verificacion'): ?>
                                            <span class="status-info status-en-verificacion"><i class="bi bi-hourglass-split"></i> Validando pago...</span>
                                        <?php elseif ($order['order_status'] === 'pendiente_stock' || empty($item['license_id'])): ?>
                                            <span class="status-info status-pendiente"><i class="bi bi-box-seam"></i> Pendiente de asignación</span>
                                        <?php else: ?>
                                            <span class="status-info status-error"><i class="bi bi-exclamation-triangle-fill"></i> Clave no disponible</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div> </div> </div> <?php endforeach; ?>
            </div> <?php endif; ?>
    </div>

    <div id="tab-profile" class="tab-content">
        <h2>Mi Perfil</h2>
        <form id="profile-update-form" action="update_profile_handler.php" method="POST" class="auth-form" novalidate>
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div id="profile-message" class="info-message" style="display:none;"></div>

            <div class="form-group">
                <label for="profile_name">Nombre Completo o Razón Social</label>
                <input type="text" name="name" id="profile_name" class="form-control" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="profile_email">Correo Electrónico</label>
                <input type="email" name="email" id="profile_email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" readonly>
                <small class="form-text text-muted">El correo electrónico no puede ser modificado.</small>
            </div>
            <div class="form-group">
                <label for="profile_country">País</label>
                <select name="country" id="profile_country" class="form-select" required>
                    <option value="">-- Seleccione su país --</option>
                    <?php foreach ($countries as $code => $name): ?>
                        <option value="<?php echo htmlspecialchars($code); ?>" 
                            <?php echo (($user_data['country'] ?? '') == $code) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="profile_address">Dirección Fiscal (Opcional)</label>
                <textarea name="address" id="profile_address" class="form-control" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="profile_preferred_currency">Moneda Preferida</label>
                <select name="preferred_currency" id="profile_preferred_currency" class="form-select">
                    <option value="USD" <?php echo (($user_data['preferred_currency'] ?? '') == 'USD') ? 'selected' : ''; ?>>USD</option>
                    <option value="VES" <?php echo (($user_data['preferred_currency'] ?? '') == 'VES') ? 'selected' : ''; ?>>VES</option>
                    <option value="COP" <?php echo (($user_data['preferred_currency'] ?? '') == 'COP') ? 'selected' : ''; ?>>COP</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-4">Actualizar Perfil</button>
        </form>
    </div>

    <div id="tab-password" class="tab-content">
        <h2>Cambiar Contraseña</h2>
        <form id="password-change-form" action="update_profile_handler.php" method="POST" class="auth-form" novalidate>
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div id="password-message" class="info-message" style="display:none;"></div>

            <div class="form-group">
                <label for="current_password">Contraseña Actual</label>
                <input type="password" name="current_password" id="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="new_password">Nueva Contraseña</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="confirm_new_password">Confirmar Nueva Contraseña</label>
                <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-4">Cambiar Contraseña</button>
        </form>
    </div>

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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DEBUG: mi-cuenta.php DOMContentLoaded - Script iniciado para modal de licencia y pestañas.");

        // Lógica del acordeón para Mis Pedidos (ya existente)
        const accordionHeaders = document.querySelectorAll('.accordion-header');
        accordionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const item = this.closest('.accordion-item');
                const content = item.querySelector('.accordion-content');
                
                // Cierra otros acordeones abiertos
                document.querySelectorAll('.accordion-item.active').forEach(openItem => {
                    if (openItem !== item) {
                        openItem.classList.remove('active');
                        openItem.querySelector('.accordion-content').style.maxHeight = null;
                    }
                });

                // Abre o cierra el acordeón actual
                item.classList.toggle('active');
                if (item.classList.contains('active')) {
                    content.style.maxHeight = content.scrollHeight + "px";
                } else {
                    content.style.maxHeight = null;
                }
            });
        });

        // Lógica para abrir/cerrar el modal de licencia (ver clave) (ya existente)
        const licenseModal = document.getElementById('license-modal');
        if (!licenseModal) {
            console.error("ERROR: #license-modal no encontrado en mi-cuenta.php. El modal no funcionará.");
            return;
        }

        const modalCloseBtns = licenseModal.querySelectorAll('.modal-close');
        const modalLicenseKeySpan = document.getElementById('modal-license-key');
        const modalCopyBtn = document.getElementById('modal-copy-btn');
        const modalCountdownSpan = document.getElementById('modal-countdown');
        let countdownInterval;

        document.querySelectorAll('.view-key').forEach(button => {
            console.log("DEBUG: Adjuntando listener a .view-key:", button);
            button.addEventListener('click', async function() {
                console.log("DEBUG: Click en botón de ver licencia en mi-cuenta.php.");
                if (!confirm('¿Estás seguro de que quieres revelar esta clave? Se mostrará por 15 segundos.')) {
                    return;
                }

                const orderItemId = this.dataset.itemId;
                // Obtener el csrf_token de la meta etiqueta en el head
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';
                
                console.log("DEBUG MI-CUENTA: orderItemId para API:", orderItemId);
                console.log("DEBUG MI-CUENTA: csrfToken para API:", csrfToken);
                
                if (!orderItemId || !csrfToken) {
                    alert('Error: Datos necesarios (ID de ítem o token de seguridad) faltantes.');
                    return;
                }

                try {
                    // Llamada AJAX para obtener la clave desencriptada
                    const response = await fetch('api/get_license_key.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            item_id: orderItemId,
                            csrf_token: csrfToken
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        modalLicenseKeySpan.textContent = result.license_key;
                        licenseModal.style.display = 'flex'; // Usar flex para centrar
                        startCountdown();
                    } else {
                        alert('Error al obtener la clave: ' + (result.message || 'Error desconocido.'));
                        console.error('Error al desencriptar la clave en mi-cuenta.php:', result.message);
                    }
                } catch (error) {
                    alert('Error de red al obtener la clave. Consulta la consola para más detalles.');
                    console.error('Fetch error en mi-cuenta.php:', error);
                }
            });
        });

        modalCloseBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                licenseModal.style.display = 'none';
                clearInterval(countdownInterval);
            });
        });

        licenseModal.addEventListener('click', (e) => {
            if (e.target === licenseModal) {
                licenseModal.style.display = 'none';
                clearInterval(countdownInterval);
            }
        });

        function startCountdown() {
            let timeLeft = 15;
            modalCountdownSpan.textContent = timeLeft;
            clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                timeLeft--;
                modalCountdownSpan.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    licenseModal.style.display = 'none';
                }
            }, 1000);
        }

        modalCopyBtn.addEventListener('click', function() {
            const keyToCopy = modalLicenseKeySpan.textContent;
            navigator.clipboard.writeText(keyToCopy)
                .then(() => alert('¡Clave copiada al portapapeles!'))
                .catch(err => console.error('Error al copiar: ', err));
        });

        // ================================================================
        // === NUEVO: Lógica de Pestañas y Formularios de Actualización ===
        // ================================================================
        const tabButtons = document.querySelectorAll('.account-tabs .tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.dataset.tab;

                // Desactivar todas las pestañas y ocultar todos los contenidos
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Activar la pestaña clicada y mostrar su contenido
                button.classList.add('active');
                document.getElementById(`tab-${tabId}`).classList.add('active');
            });
        });

        // Helper para mostrar mensajes específicos de formulario (perfil y contraseña)
        function displayFormMessage(element, message, type) {
            element.style.display = 'block';
            element.innerHTML = message;
            element.className = ''; // Limpiar clases previas
            if (type) {
                element.classList.add(`${type}-message`);
            }
            // Ocultar mensaje después de unos segundos si es de éxito
            if (type === 'success') {
                setTimeout(() => {
                    element.style.display = 'none';
                    element.innerHTML = '';
                    element.className = '';
                }, 5000); // 5 segundos
            }
        }

        // Lógica para el formulario de Actualización de Perfil
        const profileUpdateForm = document.getElementById('profile-update-form');
        if (profileUpdateForm) {
            const profileMessageDiv = document.getElementById('profile-message');

            profileUpdateForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const formData = new FormData(profileUpdateForm);
                // Asegurarse de que el token CSRF esté presente
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                formData.append('csrf_token', csrfToken);

                // Deshabilitar botón y mostrar spinner
                const submitButton = profileUpdateForm.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Actualizando...';

                try {
                    const response = await fetch('update_profile_handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        displayFormMessage(profileMessageDiv, result.message, 'success');
                        // Opcional: Actualizar el nombre de usuario en el header si cambió
                        // window.location.reload(); 
                    } else {
                        displayFormMessage(profileMessageDiv, result.message || 'Error al actualizar el perfil.', 'error');
                    }
                } catch (error) {
                    console.error('Error al enviar formulario de perfil:', error);
                    displayFormMessage(profileMessageDiv, 'Error de conexión. Intenta de nuevo.', 'error');
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            });
        }

        // Lógica para el formulario de Cambio de Contraseña
        const passwordChangeForm = document.getElementById('password-change-form');
        if (passwordChangeForm) {
            const passwordMessageDiv = document.getElementById('password-message');

            passwordChangeForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const formData = new FormData(passwordChangeForm);
                // Asegurarse de que el token CSRF esté presente
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                formData.append('csrf_token', csrfToken);

                // Validaciones adicionales de frontend para contraseñas
                const newPassword = document.getElementById('new_password').value;
                const confirmNewPassword = document.getElementById('confirm_new_password').value;

                if (newPassword.length < 6) {
                    displayFormMessage(passwordMessageDiv, 'La nueva contraseña debe tener al menos 6 caracteres.', 'error');
                    return;
                }
                if (newPassword !== confirmNewPassword) {
                    displayFormMessage(passwordMessageDiv, 'La nueva contraseña y su confirmación no coinciden.', 'error');
                    return;
                }

                // Deshabilitar botón y mostrar spinner
                const submitButton = passwordChangeForm.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cambiando...';

                try {
                    const response = await fetch('update_profile_handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        displayFormMessage(passwordMessageDiv, result.message, 'success');
                        // Limpiar campos de contraseña después de un cambio exitoso
                        passwordChangeForm.reset(); 
                    } else {
                        displayFormMessage(passwordMessageDiv, result.message || 'Error al cambiar la contraseña.', 'error');
                    }
                } catch (error) {
                    console.error('Error al enviar formulario de contraseña:', error);
                    displayFormMessage(passwordMessageDiv, 'Error de conexión. Intenta de nuevo.', 'error');
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            });
        }

    });
</script>

<style>
    /* Estilos para las pestañas */
    .account-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 25px;
        border-bottom: 1px solid var(--secondary-color);
        padding-bottom: 10px;
    }
    .account-tabs .tab-button {
        background-color: var(--card-bg);
        border: 1px solid var(--secondary-color);
        border-bottom: none; /* Para que la línea inferior de la pestaña se una a la del contenedor */
        padding: 10px 20px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: bold;
        color: var(--text-color-light);
        border-radius: 8px 8px 0 0;
        transition: all 0.3s ease;
    }
    .account-tabs .tab-button:hover {
        background-color: rgba(var(--primary-color-rgb), 0.2);
        color: var(--accent-color);
    }
    .account-tabs .tab-button.active {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        box-shadow: 0 -2px 10px rgba(var(--primary-color-rgb), 0.4);
    }

    /* Contenido de las pestañas */
    .tab-content {
        background-color: var(--card-bg);
        border: 1px solid var(--primary-color);
        border-radius: 8px;
        padding: var(--spacing-lg);
        display: none; /* Ocultar todos por defecto */
        min-height: 300px; /* Para mantener un tamaño mínimo */
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    .tab-content.active {
        display: block; /* Mostrar la pestaña activa */
    }
    .tab-content h2 {
        margin-top: 0;
        margin-bottom: var(--spacing-lg);
        color: var(--accent-color);
        border-bottom: 1px solid var(--secondary-color);
        padding-bottom: 0.5rem;
    }
    .tab-content .auth-form { /* Reutilizamos el estilo del formulario de autenticación */
        max-width: 100%; /* Ajuste para que ocupe todo el ancho de la pestaña */
        margin: 0 auto;
        padding: 0; /* Quitamos el padding extra del auth-form para que se adapte al padding de tab-content */
        border: none;
        box-shadow: none;
        border-top: none;
        background: none;
    }
    .tab-content .auth-form small.form-text {
        text-align: left;
        margin-top: -5px; /* Ajuste para pegarlo más al input */
        margin-bottom: 10px;
    }
</style>