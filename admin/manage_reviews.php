<?php
require_once '../includes/init.php';

// Generamos un token que usarán todos los formularios de acción en esta página
$token = generate_csrf_token();

// Obtenemos todas las reseñas y las unimos con las tablas de productos y usuarios para tener más contexto
$reviews = $pdo->query("
    SELECT r.id, r.rating, r.comment, r.is_approved, r.created_at, p.name as product_name, u.name as user_name 
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestionar Reseñas';
include_once 'admin_header.php';
?>

<div class="container-admin-panel">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>
<p>Aquí puedes ver, aprobar y eliminar las reseñas enviadas por los usuarios.</p>

<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Usuario</th>
                <th>Calificación</th>
                <th style="width: 30%;">Comentario</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th class="actions-cell">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reviews)): ?>
                <tr><td colspan="7">No hay reseñas para mostrar.</td></tr>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><a href="../producto.php?id=<?php echo $review['product_id']; ?>" target="_blank"><?php echo htmlspecialchars($review['product_name']); ?></a></td>
                        <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                        <td><span class="star-rating-display" title="<?php echo $review['rating']; ?> estrellas"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></span></td>
                        <td><?php echo nl2br(htmlspecialchars($review['comment'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $review['is_approved'] ? 'status-pagado' : 'status-pendiente'; ?>">
                                <?php echo $review['is_approved'] ? 'Aprobado' : 'Pendiente'; ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <?php if (!$review['is_approved']): ?>
                                <form action="review_handler.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                    <button type="submit" class="btn-action edit" title="Aprobar Reseña"><i class="bi bi-check-lg"></i></button>
                                </form>
                            <?php endif; ?>
                            <form action="review_handler.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                <button type="submit" class="btn-action delete" title="Eliminar Reseña" onclick="return confirm('¿Estás seguro de que quieres eliminar esta reseña permanentemente?');"><i class="bi bi-trash3-fill"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include_once 'admin_footer.php'; ?>