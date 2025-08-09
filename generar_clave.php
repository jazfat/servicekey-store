<?php
// generar_clave.php
// Este script genera una clave criptográficamente segura de 64 caracteres.

// Generamos 32 bytes de datos aleatorios y los convertimos a formato hexadecimal.
$clave_secreta = bin2hex(random_bytes(32));

echo "<!DOCTYPE html><html lang='es'><head><title>Generador de Clave</title>";
echo "<style>body{font-family: sans-serif; padding: 2em;} textarea{width: 100%; font-size: 1.5rem; padding: 10px; margin-top: 10px;}</style>";
echo "</head><body>";
echo "<h1>Tu Clave de Cifrado Única</h1>";
echo "<p>Copia esta clave y pégala en tu archivo <strong>includes/config.php</strong>.</p>";
echo "<p><strong>¡MUY IMPORTANTE: Guárdala en un lugar seguro y no la compartas con nadie!</strong></p>";
echo '<textarea rows="3" readonly onclick="this.select();">' . htmlspecialchars($clave_secreta) . '</textarea>';
echo "</body></html>";

?>