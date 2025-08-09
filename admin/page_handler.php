<?php
require_once '../includes/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') { die('Acceso denegado.'); }
validate_csrf_token();

$action = $_POST['action'];
$id = (int)$_POST['id'];
$title = trim($_POST['title']);
$content = $_POST['content']; // El contenido de TinyMCE ya viene como HTML seguro
$is_published = isset($_POST['is_published']) ? 1 : 0;
$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

if ($action === 'create' && !empty($title)) {
    $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, is_published) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $slug, $content, $is_published]);
} elseif ($action === 'update' && $id > 0 && !empty($title)) {
    $stmt = $pdo->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, is_published = ? WHERE id = ?");
    $stmt->execute([$title, $slug, $content, $is_published, $id]);
}

header('Location: manage_pages.php');
exit;