<?php
// test_debug.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/init.php';

echo "<h3>✅ init.php cargado correctamente</h3>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h4>Conexión exitosa. Tablas encontradas:</h4><ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<h3>❌ Error al acceder a la base de datos:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
