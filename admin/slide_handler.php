<?php
require_once '../includes/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { die('Acceso denegado.'); }

// VALIDAR TOKEN CSRF
validate_csrf_token();

$action = $_REQUEST['action'] ?? '';
$upload_dir = '../assets/images/slides/';

function handle_upload($file_key, $current_path = null) {
    global $upload_dir;
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        if ($current_path && file_exists('../' . $current_path)) { unlink('../' . $current_path); }
        $file_name = uniqid('slide_') . '-' . basename($_FILES[$file_key]['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_file)) {
            return 'assets/images/slides/' . $file_name;
        }
    }
    return $current_path;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' && (!isset($_FILES['image_desktop']) || $_FILES['image_desktop']['error'] !== UPLOAD_ERR_OK)) {
        die('Error: La imagen para escritorio es obligatoria al crear un nuevo slide.');
    }
    $id = (int)($_POST['id'] ?? 0);
    $title = $_POST['title']; $subtitle = $_POST['subtitle']; $button_text = $_POST['button_text']; $button_link = $_POST['button_link'];
    $sort_order = (int)$_POST['sort_order']; $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($action === 'create' || $action === 'update') {
        $current_images = ['image_desktop' => null, 'image_mobile' => null];
        if ($action === 'update' && $id > 0) {
            $stmt = $pdo->prepare("SELECT image_desktop, image_mobile FROM slides WHERE id = ?");
            $stmt->execute([$id]);
            $current_images_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($current_images_data) { $current_images = $current_images_data; }
        }
        $desktop_path = handle_upload('image_desktop', $current_images['image_desktop']);
        $mobile_path = handle_upload('image_mobile', $current_images['image_mobile']);
        if ($action === 'create') {
            $sql = "INSERT INTO slides (title, subtitle, button_text, button_link, image_desktop, image_mobile, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$title, $subtitle, $button_text, $button_link, $desktop_path, $mobile_path, $sort_order, $is_active];
        } else {
            $sql = "UPDATE slides SET title=?, subtitle=?, button_text=?, button_link=?, image_desktop=?, image_mobile=?, sort_order=?, is_active=? WHERE id=?";
            $params = [$title, $subtitle, $button_text, $button_link, $desktop_path, $mobile_path, $sort_order, $is_active, $id];
        }
        $pdo->prepare($sql)->execute($params);
    }
}

if ($action === 'delete') { // Ya no necesita GET, solo viene de POST
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT image_desktop, image_mobile FROM slides WHERE id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($images) {
            if ($images['image_desktop'] && file_exists('../' . $images['image_desktop'])) unlink('../' . $images['image_desktop']);
            if ($images['image_mobile'] && file_exists('../' . $images['image_mobile'])) unlink('../' . $images['image_mobile']);
        }
        $pdo->prepare("DELETE FROM slides WHERE id = ?")->execute([$id]);
    }
}

header('Location: manage_slides.php');
exit;