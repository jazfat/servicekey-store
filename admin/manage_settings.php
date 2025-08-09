<?php
// admin/manage_settings.php

require_once 'admin_header.php';

$page_title = 'Ajustes del Sitio';

$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']);
}

$token = generate_csrf_token();
?>
<style> 

.product-thumbnail2 {
        max-height: 240px;
        max-width: 300px;
    }
</style>

<div class="container-admin-panel">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if ($flash_message): ?>
        <div class="<?php echo htmlspecialchars($flash_message['type']); ?>-message">
            <?php echo htmlspecialchars($flash_message['text']); ?>
        </div>
    <?php endif; ?>
        
    <form action="settings_handler.php" method="POST" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="hidden" name="action" value="update">
        <div class="admin-form-layout"> <div class="form-column-main"> <div class="form-section"> <h3>Información General del Sitio</h3>
                    <div class="form-group">
                        <label for="site_title">Título de la Tienda</label>
                        <input type="text" class="form-control" id="site_title" name="settings[site_title]" value="<?php echo htmlspecialchars($site_settings['site_title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="site_logo_url">Logo del Sitio</label>
                        <input type="file" class="form-control" name="site_logo" id="site_logo_url" accept="image/png, image/jpeg, image/webp">
                        <small class="form-text text-muted">Sube una nueva imagen para el logo (JPG, PNG, WEBP). Se recomienda un logo transparente.</small>
                        <?php if (!empty($site_settings['site_logo_url'])): ?>
                            <div class="mt-2">
                                <img src="../<?php echo htmlspecialchars($site_settings['site_logo_url']); ?>" alt="Logo actual" class="product-thumbnail2">
                                <small class="form-text text-muted">Logo actual.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="developer_credit">Crédito del Desarrollador (Opcional, en el Footer)</label>
                        <input type="text" class="form-control" id="developer_credit" name="settings[developer_credit]" value="<?php echo htmlspecialchars($site_settings['developer_credit'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="site_admin_email">Correo Electrónico del Administrador</label>
                        <input type="email" class="form-control" id="site_admin_email" name="settings[site_admin_email]" value="<?php echo htmlspecialchars($site_settings['site_admin_email'] ?? ''); ?>" required>
                        <small class="form-text text-muted">Aquí recibirás notificaciones de nuevas ventas y del sistema.</small>
                    </div>
                </div>

                <div class="form-section"> <h3>Ajustes de Monedas y Tasas</h3>
                    <div class="form-group">
                        <label for="cop_exchange_rate">Tasa de Cambio COP/USD (Respaldo Manual)</label>
                        <input type="number" class="form-control" id="cop_exchange_rate" name="settings[cop_exchange_rate]" step="0.01" min="0" value="<?php echo htmlspecialchars($site_settings['cop_exchange_rate'] ?? 0); ?>">
                        <small class="form-text text-muted">Esta tasa es un respaldo si la API de TRM de Colombia no está disponible. Se actualizará automáticamente si la API funciona.</small>
                    </div>
                    <div class="form-group">
                        <label for="last_known_bcv_rate">Tasa de Cambio VES/USD (Respaldo Manual)</label>
                        <input type="number" class="form-control" id="last_known_bcv_rate" name="settings[last_known_bcv_rate]" step="0.01" min="0" value="<?php echo htmlspecialchars(number_format($site_settings['last_known_bcv_rate'] ?? 0, 2, '.', '')); ?>"
                        <small class="form-text text-muted">Esta tasa es un respaldo si el scrapeo del BCV falla. Se actualizará automáticamente.</small>
                    </div>
                </div>
            </div>

            <div class="form-column-sidebar"> <div class="form-section"> <h3>Detalles de Contacto y Redes Sociales</h3>
                    <div class="form-group">
                        <label for="contact_phone">Teléfono de Contacto</label>
                        <input type="text" class="form-control" id="contact_phone" name="settings[contact_phone]" value="<?php echo htmlspecialchars($site_settings['contact_phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_email">Correo Electrónico de Contacto Público</label>
                        <input type="email" class="form-control" id="contact_email" name="settings[contact_email]" value="<?php echo htmlspecialchars($site_settings['contact_email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="social_facebook_url">URL de Facebook</label>
                        <input type="url" class="form-control" id="social_facebook_url" name="settings[social_facebook_url]" value="<?php echo htmlspecialchars($site_settings['social_facebook_url'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="social_instagram_url">URL de Instagram</label>
                        <input type="url" class="form-control" id="social_instagram_url" name="settings[social_instagram_url]" value="<?php echo htmlspecialchars($site_settings['social_instagram_url'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="social_x_url">URL de X (Twitter)</label>
                        <input type="url" class="form-control" id="social_x_url" name="settings[social_x_url]" value="<?php echo htmlspecialchars($site_settings['social_x_url'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-section"> <h3>Ajustes de Pagos Manuales (Transferencias, Cripto)</h3>
                    <div class="form-group">
                        <label for="pago_movil_details">Detalles de PagoMóvil (Texto)</label>
                        <textarea class="form-control" id="pago_movil_details" name="settings[pago_movil_details]" rows="5"><?php echo htmlspecialchars($site_settings['pago_movil_details'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">Información mostrada a clientes que pagan con PagoMóvil.</small>
                    </div>
                    <div class="form-group">
                        <label for="binance_details">Detalles de Binance Pay (Texto)</label>
                        <textarea class="form-control" id="binance_details" name="settings[binance_details]" rows="5"><?php echo htmlspecialchars($site_settings['binance_details'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">Información de tu billetera o ID de Binance Pay.</small>
                    </div>
                    <div class="form-group">
                        <label for="binance_qr_url_upload">QR de Binance Pay (Imagen)</label>
                        <input type="file" class="form-control" name="binance_qr_code" id="binance_qr_url_upload" accept="image/png, image/jpeg, image/webp">
                        <small class="form-text text-muted">Sube una nueva imagen para el QR de Binance Pay.</small>
                        <?php if (!empty($site_settings['binance_qr_url'])): ?>
                            <div class="mt-2">
                                <img src="../<?php echo htmlspecialchars($site_settings['binance_qr_url']); ?>" alt="QR Binance actual" class="product-thumbnail2">
                                <small class="form-text text-muted">QR actual.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-4">
            <button type="submit" class="btn btn-primary btn-large"><i class="bi bi-save"></i> Guardar Ajustes</button>
        </div>
    </form>
</div>

<?php require_once 'admin_footer.php'; ?>