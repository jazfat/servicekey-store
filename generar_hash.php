<?php
// Archivo temporal: generar_hash.php

$password_plano = '060486';
$hash_seguro = password_hash($password_plano, PASSWORD_DEFAULT);

echo "<h1>Generador de Hash de Contraseña</h1>";
echo "<p>Contraseña en texto plano: <strong>" . htmlspecialchars($password_plano) . "</strong></p>";
echo "<p>Copia el siguiente hash para usarlo en el comando SQL:</p>";

// Usamos un textarea para que sea fácil de copiar
echo '<textarea rows="3" cols="80" readonly style="font-size: 1.1rem; padding: 10px;">' . htmlspecialchars($hash_seguro) . '</textarea>';

?>