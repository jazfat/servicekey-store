<?php
// includes/config.php

// 1. Configuración de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'tienda_licencias');
define('DB_USER', 'root');
define('DB_PASS', '123456');
define('DB_CHARSET', 'utf8mb4');


// 2. Claves de Cifrado de Licencias
// IMPORTANTE: ENCRYPTION_IV_HEX DEBE ELIMINARSE DE AQUÍ.
// El IV debe ser generado dinámicamente para CADA operación de encriptación.
define('ENCRYPTION_KEY', 'vFS2I4qqy2PJWibGHPlGw94M6rTaMinpe2nw6uUHLnw=');
define('ENCRYPTION_METHOD', 'aes-256-cbc'); // Mantiene este método

// 3. Configuración de Correo (SMTP)
define('SMTP_HOST', 'premium314.web-hosting.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'support@servicekey.store');
define('SMTP_PASS', 'ServiceKey2025.');
define('SMTP_FROM_EMAIL', 'support@servicekey.store');
define('SMTP_FROM_NAME', 'ServiceKey Store');

// 4. Configuración de la API de PayPal
define('PAYPAL_SANDBOX', true); // ¡CAMBIO A FALSE PARA PRODUCCIÓN!

if (PAYPAL_SANDBOX) {
    // Credenciales de Sandbox (para pruebas)
    define('PAYPAL_CLIENT_ID', 'AYalYwXCYDwgGYDvXHseKFy2Zf5UoHyClYxjxy4meOSu1rb2ymByJckoxgxelgX3xVEaoO4k61GEhwGA');
    define('PAYPAL_CLIENT_SECRET', 'EBJ2Vu54c2y6MEa9tFk3tMkmWaTKIRLClx3t44GsYo3GGXOXWN6CXPZem-cRfhMl6geq_H4_AbtTuIsI');
    define('PAYPAL_API_URL', 'https://api-m.sandbox.paypal.com');
} else {
    // Credenciales de Producción (para ventas reales)
    define('PAYPAL_CLIENT_ID', 'AYMica6VjNk11BTBBj8gDOraRJSvaPhdxnI6c8YkjcJukEqZ27E5rW9vieGAJQlnuOdHZMfM-48Fku5G');
    define('PAYPAL_CLIENT_SECRET', 'EE9Rtz71Q-mB7bLKHrWB5KP2V6r5RpNLFlrfDNEIMRoC4z2vx-uy2FLq3eCZRA5OC4FedCHXFRXSqins');
    define('PAYPAL_API_URL', 'https://api-m.paypal.com');
}

// 5. URL Base del Sitio (¡CRÍTICO! Ajusta esto a tu entorno)
// La lógica condicional detecta si estás en localhost para usar la ruta correcta.
if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '::1' || strpos($_SERVER['HTTP_HOST'], '192.168.') === 0)) {
    define('BASE_URL', 'http://localhost/licencias/'); 
} else {
    // CAMBIO CRÍTICO: PARA HOSTING REAL, SOLO LA RAÍZ DEL DOMINIO
    define('BASE_URL', 'https://servicekey.store/'); 
}


// 6. Configuración de Monedas
define('CURRENCY_CACHE_LIFETIME', 3600); 
?>