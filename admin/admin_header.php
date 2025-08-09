<?php
// admin/admin_header.php
require_once __DIR__ . '/../includes/init.php'; 

// Seguridad: Redirigir si no es admin (asegurar que solo admin vea el panel)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php'); // Redirige a la página de login del frontend
    exit;
}

$token = generate_csrf_token(); 

// Asegúrate de que estas rutas sean correctas desde la perspectiva del admin/
$admin_logo_url = $site_settings['site_logo_url'] ?? '../assets/images/default_logo.png';
$admin_icon_url = $site_settings['site_icon_url'] ?? '../assets/images/site_icon.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Panel de Administración'); ?> | Admin ServiceKey</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin_styles.css"> 

    <meta name="csrf-token" content="<?php echo htmlspecialchars($token); ?>">
</head>
<body class="admin-body">
    <div class="container-admin-panel">
        <?php include_once 'admin_nav.php'; ?>
