<?php
require_once '../includes/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { die('Acceso denegado.'); }

// Validar token CSRF para todas las acciones
validate_csrf_token();

$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = $_POST['name'];
        $link_url = !empty($_POST['link_url']) ? $_POST['link_url'] : null;
        
        $logo_path = null;
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/brands/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            
            $file_name = uniqid('brand-') . '-' . basename($_FILES['logo_image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_file)) {
                $logo_path = 'assets/images/brands/' . $file_name;
            }
        }

        if ($logo_path) {
            $stmt = $pdo->prepare("INSERT INTO brand_logos (name, logo_url, link_url) VALUES (?, ?, ?)");
            $stmt->execute([$name, $logo_path, $link_url]);
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Opcional: Borrar el archivo del servidor antes de borrar el registro
            $stmt = $pdo->prepare("SELECT logo_url FROM brand_logos WHERE id = ?");
            $stmt->execute([$id]);
            $logo_path = $stmt->fetchColumn();
            if ($logo_path && file_exists('../' . $logo_path)) {
                unlink('../' . $logo_path);
            }
            // Borrar el registro de la base de datos
            $stmt_delete = $pdo->prepare("DELETE FROM brand_logos WHERE id = ?");
            $stmt_delete->execute([$id]);
        }
    }
}

header('Location: manage_logos.php');
exit;