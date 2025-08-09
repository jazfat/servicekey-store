<?php
require_once '../includes/init.php';

$pages = $pdo->query("SELECT id, title, slug, is_published, updated_at FROM pages ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestionar Páginas';
include_once 'admin_header.php';
?>
<h1>Gestionar Páginas de Contenido</h1>
<div class="admin-header">
    <p>Crea y edita páginas como "Términos y Condiciones", "Políticas de Privacidad", etc.</p>
    <a href="edit_page.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Crear Nueva Página</a>
</div>

<table class="admin-table">
    <thead><tr><th>Título</th><th>URL (Slug)</th><th>Estado</th><th>Última Modificación</th><th>Acciones</th></tr></thead>
    <tbody>
        <?php foreach ($pages as $page): ?>
            <tr>
                <td><?php echo htmlspecialchars($page['title']); ?></td>
                <td>/pagina.php?slug=<?php echo htmlspecialchars($page['slug']); ?></td>
                <td><span class="status-badge <?php echo $page['is_published'] ? 'status-pagado' : 'status-pendiente'; ?>"><?php echo $page['is_published'] ? 'Publicada' : 'Borrador'; ?></span></td>
                <td><?php echo date('d/m/Y H:i', strtotime($page['updated_at'])); ?></td>
                <td class="actions-cell">
                    <a href="edit_page.php?id=<?php echo $page['id']; ?>" class="btn-action edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                    </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include_once 'admin_footer.php'; ?>