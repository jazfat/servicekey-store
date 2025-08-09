<?php
// Cargar init.php para que custom_log esté disponible
require_once 'includes/init.php';

custom_log("Prueba de log: Este es un mensaje de prueba.", 'test_log.log');
echo "Intenta revisar C:\\xampp\\htdocs\\licencias\\logs\\test_log.log";
?>