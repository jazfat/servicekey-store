<?php
// includes/footer.php
// Este archivo cierra la página y contiene elementos globales como el footer.
?>
    </main> <footer class="site-footer">
        <div class="container">
            <div class="footer-main">
                <div class="footer-column about">
                    <a href="index.php" class="footer-logo">
                        <img src="<?php echo htmlspecialchars($site_settings['site_logo_url']); ?>" alt="Logo del Sitio">
                    </a>
                    <p>Tu tienda de confianza para licencias digitales de software. Activación instantánea y soporte dedicado.</p>
                </div>

                <div class="footer-column links">
                    <h4>Navegación</h4>
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="catalogo.php">Catálogo</a></li>
                        <li><a href="mi-cuenta.php">Mi Cuenta</a></li>
                        <li><a href="contacto.php">Contacto</a></li>
                    </ul>
                </div>

                <div class="footer-column links">
                    <h4>Información</h4>
                    <ul>
                        <li><a href="pagina.php?slug=terminos-y-condiciones">Términos y Condiciones</a></li>
                        <li><a href="pagina.php?slug=politica-de-privacidad">Políticas de Privacidad</a></li>
                        <li><a href="pagina.php?slug=preguntas-frecuentes">Preguntas Frecuentes</a></li>
                    </ul>
                </div>

                <div class="footer-column social">
                    <h4>Síguenos</h4>
                    <div class="social-icons">
                        <?php if (!empty($site_settings['social_facebook_url'])): ?><a href="<?php echo htmlspecialchars($site_settings['social_facebook_url']); ?>" target="_blank" title="Facebook"><i class="bi bi-facebook"></i></a><?php endif; ?>
                        <?php if (!empty($site_settings['social_instagram_url'])): ?><a href="<?php htmlspecialchars($site_settings['social_instagram_url']); ?>" target="_blank" title="Instagram"><i class="bi bi-instagram"></i></a><?php endif; ?>
                        <?php if (!empty($site_settings['social_x_url'])): ?><a href="<?php echo htmlspecialchars($site_settings['social_x_url']); ?>" target="_blank" title="X (Twitter)"><i class="bi bi-twitter-x"></i></a><?php endif; ?>
                        </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> ServiceKey Store. Todos los derechos reservados. <br>
                    <?php if (!empty($site_settings['developer_credit'])): ?>
                        <span><?php echo htmlspecialchars($site_settings['developer_credit']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="payment-icons">
                    <span>Métodos de Pago:</span>
                    <i class="bi bi-paypal" title="PayPal"></i>
                    <i class="bi bi-credit-card-2-back-fill" title="Tarjetas de Crédito/Débito"></i>
                    <svg class="payment-icon-svg" title="Zinli" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-1.071 16.486L5.514 10.029l1.414-1.414 4.004 4.004 5.92-5.92 1.414 1.414-7.334 7.334z"/>
                    </svg>
                    <i class="bi bi-currency-bitcoin" title="Binance Pay / Criptomonedas"></i>
                </div>
            </div>
        </div>
    </footer>
    
    <div id="cart-overlay" class="cart-overlay"></div>

    <div id="cart-slideout-panel" class="cart-slideout-panel">
        <div class="cart-panel-header">
            <h3>Carrito de Compras</h3>
            <button class="modal-close" title="Cerrar">&times;</button>
        </div>
        <div id="cart-panel-body" class="cart-panel-body">
            </div>
        <div class="cart-panel-footer">
            <div class="summary-total">
                <span>Total:</span>
                <strong id="cart-total-price">$ 0.00 USD</strong>
            </div>
            <a href="checkout.php" class="btn btn-primary btn-block">Proceder al Pago</a>
        </div>
    </div>

    <div id="floating-cart-btn" class="floating-cart-btn">
        <i class="bi bi-cart3"></i>
        <span id="floating-cart-count" class="cart-count">0</span>
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

    <script src="js/main.js"></script> <script type="text/javascript">
        var Tawk_API = Tawk_API || {};
        Tawk_API.customStyle = {
        	'visibility' : {
        		'desktop' : { 'position' : 'bl', 'xOffset' : 30, 'yOffset' : 30 },
        		'mobile' : { 'position' : 'bl', 'xOffset' : 15, 'yOffset' : 15 }
        	}
        };
        var Tawk_LoadStart = new Date();
        (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/684e5637603cf9190a79a6f8/1itp01i3j';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
        })();
    </script>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(registration => {
                    console.log('Service Worker registrado con éxito:', registration.scope);
                }).catch(error => {
                    console.log('Fallo en el registro del Service Worker:', error);
                });
            });
        }
    </script>
    
</body>
</html>