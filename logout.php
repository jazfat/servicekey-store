<?php
// 1. Iniciar o reanudar la sesión
// Es absolutamente necesario para poder acceder a las variables de sesión y destruirla.
session_start();

// 2. Limpiar todas las variables de sesión
// Elimina todos los datos guardados en la sesión, como $_SESSION['user_id'], etc.
session_unset();

// 3. Destruir la sesión del servidor
// Elimina el archivo de sesión en el servidor, invalidando el ID de sesión.
session_destroy();

// 4. Redirigir al usuario a la página de inicio
// Usamos header() para enviar al usuario a un lugar seguro después de cerrar sesión.
header('Location: index.php');

// 5. Detener la ejecución del script
// Es una buena práctica llamar a exit() después de una redirección para asegurar que nada más se ejecute.
exit;
?>