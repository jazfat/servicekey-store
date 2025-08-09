<?php
// includes/init.php

// Inicia la sesión para poder usar variables $_SESSION.
session_start();

// 1. Cargar la configuración principal (ej. credenciales de la base de datos).
require_once __DIR__ . '/config.php';

// 2. Establecer la conexión a la base de datos.
require_once __DIR__ . '/db_connection.php';

// 3. Cargar clases y funciones globales.
require_once __DIR__ . '/CurrencyConverter.php';
require_once __DIR__ . '/functions.php';

// 4. Cargar los ajustes del sitio desde la base de datos de forma segura.
try {
    $settings_stmt = $pdo->query("SELECT * FROM settings");
    $site_settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    // Si falla, se crea un array vacío para evitar que el sitio se caiga
    // y se registra el error para revisión.
    $site_settings = []; 
    error_log("Error al cargar los ajustes del sitio: " . $e->getMessage());
}

// 5. Crear la instancia global del conversor de moneda.
$currencyConverter = new CurrencyConverter($pdo, $site_settings);

// 6. Gestionar el idioma seleccionado por el usuario.
$allowed_langs = ['es', 'en'];
$default_lang = 'es';

// Si se pasa un idioma por la URL y es válido, se guarda en la sesión.
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
}
// Se establece el idioma actual, usando el de la sesión o el predeterminado.
$current_lang = $_SESSION['lang'] ?? $default_lang;
require_once __DIR__ . "/../lang/{$current_lang}.php";

// 7. Gestionar la moneda seleccionada por el usuario.
$allowed_currencies = ['USD', 'VES', 'COP'];
$default_currency = 'USD';

// Si se pasa una moneda por la URL y es válida, se guarda en la sesión.
if (isset($_GET['currency']) && in_array($_GET['currency'], $allowed_currencies)) {
    $_SESSION['currency'] = $_GET['currency'];
}
// Se establece la moneda actual, usando la de la sesión o la predeterminada.
$current_currency = $_SESSION['currency'] ?? $default_currency;