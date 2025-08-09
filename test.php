<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=lienvlra_pdv_movil;charset=utf8mb4", "lienvlra_servicekey", "Service2025");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión exitosa con lienvlra_servicekey";
} catch (PDOException $e) {
    echo "❌ Error de conexión a la base de datos: " . $e->getMessage();
}
?>
