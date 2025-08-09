<?php
// db_connection.php

// Definir constantes solo si no han sido definidas antes
if (!defined('DB_NAME')) {
    define('DB_NAME', 'lienvlra_licencias');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'lienvlra_servicekey');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', 'Service2025');
}
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("❌ Error de conexión a la base de datos: " . $e->getMessage());
}
?>
