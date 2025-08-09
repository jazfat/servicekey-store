<?php
require_once '../includes/init.php';

$edit_mode = false;
$slide_to_edit = ['id' => '','title' => '','subtitle' => '','button_text' => '','button_link' => '','image_desktop' => '','image_mobile' => '','sort_order' => 0,'is_active' => 1];
$token = generate_csrf_token(); // Generar token para todos los formularios

if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM slides WHERE id = ?");
    $stmt->execute([$edit_id]);
    $slide_to_edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($slide_to_edit_data) {
        $slide_to_edit = $slide_to_edit_data;
        $edit_mode = true;
    }
}
$slides = $pdo->query("SELECT * FROM slides ORDER BY sort_order ASC")->fetchAll();
$page_title = 'Gestión de Slides';
include_once 'admin_header.php'; // Incluimos el nuevo header del admin
?>


    <div class="container admin-panel">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="form-section">
        <h3><?php echo $edit_mode ? 'Editando Slide' : 'Añadir Nuevo Slide'; ?></h3>
        <form action="slide_handler.php" method="POST" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'create'; ?>">
            <input type="hidden" name="id" value="<?php echo $slide_to_edit['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
            <div class="form-grid">
                <div class="form-group"><label for="title">Título (opcional)</label><input type="text" name="title" value="<?php echo htmlspecialchars($slide_to_edit['title']); ?>"></div>
                <div class="form-group"><label for="subtitle">Subtítulo (opcional)</label><input type="text" name="subtitle" value="<?php echo htmlspecialchars($slide_to_edit['subtitle']); ?>"></div>
            </div>
            <div class="form-grid">
                <div class="form-group"><label for="button_text">Texto del Botón (opcional)</label><input type="text" name="button_text" value="<?php echo htmlspecialchars($slide_to_edit['button_text']); ?>"></div>
                <div class="form-group"><label for="button_link">Enlace del Botón (opcional)</label><input type="url" name="button_link" value="<?php echo htmlspecialchars($slide_to_edit['button_link']); ?>"></div>
            </div>
            <div class="form-grid">
                <div class="form-group"><label for="image_desktop">Imagen para Escritorio (requerido)</label><input type="file" name="image_desktop" <?php if (!$edit_mode) echo 'required'; ?> accept="image/*"><?php if ($edit_mode && $slide_to_edit['image_desktop']): ?><small>Actual: <img src="../<?php echo $slide_to_edit['image_desktop']; ?>" height="30"></small><?php endif; ?></div>
                <div class="form-group"><label for="image_mobile">Imagen para Móvil (opcional)</label><input type="file" name="image_mobile" accept="image/*"><?php if ($edit_mode && $slide_to_edit['image_mobile']): ?><small>Actual: <img src="../<?php echo $slide_to_edit['image_mobile']; ?>" height="30"></small><?php endif; ?></div>
            </div>
             <div class="form-grid">
                <div class="form-group"><label for="sort_order">Orden</label><input type="number" name="sort_order" value="<?php echo htmlspecialchars($slide_to_edit['sort_order']); ?>"></div>
                <div class="form-check form-switch" style="align-self: end; padding-bottom: 1rem;">
    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?php if (!empty($slide_to_edit['is_active'])) echo 'checked'; ?>>
    <label class="form-check-label" for="is_active">Slide Activo</label>
</div>
             </div>
            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Actualizar Slide' : 'Crear Slide'; ?></button>
            <?php if ($edit_mode): ?><a href="manage_slides.php" class="btn btn-secondary">Cancelar Edición</a><?php endif; ?>
        </form>
    </div>
    <h3>Slides Actuales</h3>
    <table class="admin-table">
        <thead><tr><th>Orden</th><th>Imagen</th><th>Título</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php foreach ($slides as $slide): ?>
            <tr>
                <td><?php echo $slide['sort_order']; ?></td>
                <td><img src="../<?php echo htmlspecialchars($slide['image_desktop']); ?>" alt="" class="logo-thumbnail"></td>
                <td><?php echo htmlspecialchars($slide['title']); ?></td>
                <td><span class="status-badge <?php echo $slide['is_active'] ? 'status-pagado' : 'status-cancelado'; ?>"><?php echo $slide['is_active'] ? 'Activo' : 'Inactivo'; ?></span></td>
                <td class="actions-cell">
                    <a href="manage_slides.php?edit_id=<?php echo $slide['id']; ?>" class="btn-action edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                    <form action="slide_handler.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $slide['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        <button type="submit" class="btn-action delete" title="Eliminar" onclick="return confirm('¿Estás seguro?');"><i class="bi bi-trash3-fill"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>