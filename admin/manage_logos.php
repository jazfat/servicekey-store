<?php
require_once '../includes/init.php';

$logos = $pdo->query("SELECT * FROM brand_logos ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
$token = generate_csrf_token();
$page_title = 'Administrar Logos';
include_once 'admin_header.php'; // Incluimos el nuevo header del admin
?>


    <div class="container-admin-panel">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
   
    

    <div class="table-responsive">

    <div class="form-column-sidebar">
            <div class="form-section">
                <h3>Añadir Nuevo Logo</h3>
                <form action="logo_handler.php" method="POST" enctype="multipart/form-data" class="admin-form">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                    <div class="form-group">
                        <label for="name">Nombre de la Marca</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="link_url">Enlace (Opcional)</label>
                        <input type="url" name="link_url" id="link_url" placeholder="https://ejemplo.com">
                    </div>
                    <div class="form-group">
                        <label for="logo_image">Archivo del Logo (PNG o SVG)</label>
                        <input type="file" name="logo_image" id="logo_image" required accept="image/png, image/svg+xml">
                    </div>
                    <button type="submit" class="btn btn-primary">Añadir Logo</button>
                </form>
            </div>
        </div>
        <div class="form-column-main">
            
            <h3>Logos Actuales</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Nombre</th>
                        <th>Enlace</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logos)): ?>
                        <tr><td colspan="4">No hay logos para mostrar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logos as $logo): ?>
                        <tr>
                            <td><img src="../<?php echo htmlspecialchars($logo['logo_url']); ?>" alt="<?php echo htmlspecialchars($logo['name']); ?>" class="logo-thumbnail"></td>
                            <td><?php echo htmlspecialchars($logo['name']); ?></td>
                            <td><?php echo htmlspecialchars($logo['link_url'] ?? 'N/A'); ?></td>
                            <td class="actions-cell">
                                <form action="logo_handler.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $logo['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                    <button type="submit" class="btn-action delete" title="Eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar este logo?');">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
</div>
</body>
</html>