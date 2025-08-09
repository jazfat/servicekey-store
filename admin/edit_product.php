<?php
// admin/edit_product.php

// Carga el header del admin, que a su vez carga init.php y realiza la comprobación de sesión.
require_once 'admin_header.php';

// --- INICIALIZACIÓN DE VARIABLES ---
$edit_mode = false;
$page_title = 'Añadir Nuevo Producto';
$action = 'create'; // Acción por defecto: crear
$token = generate_csrf_token(); // Token CSRF para el formulario

// Valores por defecto para un producto nuevo para evitar errores y pre-rellenar
$product = [
    'id' => '',
    'name' => '',
    'slug' => '', // Nuevo: el slug del producto
    'description' => '',
    'brand' => '',
    'version' => '',
    'platform' => '',
    'devices_limit' => '',
    'validity_duration' => '',
    'delivery_type' => 'Entrega Digital', // Valor por defecto común
    'price_usd' => '',
    'compare_at_price_usd' => '', // Precio de comparación (ej. para descuentos)
    'image_url' => 'assets/images/default.png', // Imagen por defecto
    'is_featured' => 0, // No destacado por defecto
    'is_physical' => 0, // Digital por defecto
    'allow_preorder' => 0 // No permitir pre-compra por defecto
];

// IDs actuales de productos relacionados y categorías para el modo edición
$current_related_ids = [];
$current_category_ids = [];

// --- MODO EDICIÓN: Cargar datos del producto si se pasa un ID ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    try {
        $stmt_product = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt_product->execute([$product_id]);
        $product_data = $stmt_product->fetch(PDO::FETCH_ASSOC);
        
        if ($product_data) {
            $product = array_merge($product, $product_data); // Sobrescribe defaults con datos de DB
            $page_title = 'Editar Producto: ' . htmlspecialchars($product['name']);
            $edit_mode = true;
            $action = 'update'; // Acción para actualizar

            // Obtener productos relacionados actuales (excluyendo el propio producto)
            $stmt_related = $pdo->prepare("SELECT product_id_related FROM related_products WHERE product_id_main = ?");
            $stmt_related->execute([$product_id]);
            $current_related_ids = $stmt_related->fetchAll(PDO::FETCH_COLUMN, 0);

            // Obtener categorías actuales del producto
            $stmt_cats = $pdo->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
            $stmt_cats->execute([$product_id]);
            $current_category_ids = $stmt_cats->fetchAll(PDO::FETCH_COLUMN, 0);
        } else {
            // Producto no encontrado, redirigir o mostrar error
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Producto no encontrado.'];
            header('Location: manage_products.php');
            exit;
        }
    } catch (PDOException $e) {
        custom_log("ERROR PDO en edit_product.php (cargar producto {$product_id}): " . $e->getMessage(), 'admin_errors.log');
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error al cargar el producto para edición.'];
        header('Location: manage_products.php');
        exit;
    }
}

// --- DATOS PARA POBLAR LOS FORMULARIOS (selects, checkboxes) ---
try {
    $all_products = $pdo->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    custom_log("ERROR PDO en edit_product.php (cargar productos/categorías): " . $e->getMessage(), 'admin_errors.log');
    $all_products = [];
    $all_categories = [];
    // No detenemos la ejecución, pero los selects podrían estar vacíos
    $_SESSION['flash_message'] = $_SESSION['flash_message'] ?? ['type' => 'warning', 'text' => 'No se pudieron cargar todas las listas (productos/categorías).'];
}

// --- Procesar y mostrar mensajes flash (si vienen de product_handler.php) ---
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']);
}

?>

<div class="container-admin-panel">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if ($flash_message): ?>
        <div class="<?php echo htmlspecialchars($flash_message['type']); ?>-message">
            <?php echo htmlspecialchars($flash_message['text']); ?>
        </div>
    <?php endif; ?>

    <form action="product_handler.php" method="POST" enctype="multipart/form-data" class="admin-form" id="product-form">
        <input type="hidden" name="action" value="<?php echo htmlspecialchars($action); ?>">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
        
        <input type="hidden" name="description" id="hidden-description-input">

        <div class="admin-form-layout">
            <div class="form-column-main">
                <div class="form-section">
                    <h3>Detalles Principales</h3>
                    <div class="form-group">
                        <label for="name">Nombre del Producto</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description-editor">Descripción</label>
                        <div id="description-editor" style="height: 400px; background-color: var(--bg-color); color: var(--text-color); border: 1px solid var(--secondary-color); border-radius: 5px;">
                            <?php echo htmlspecialchars($product['description']); // Contenido inicial para el editor ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="image">Imagen del Producto</label>
                        <input type="file" class="form-control" name="image" id="image" accept="image/png, image/jpeg, image/webp">
                        <small class="form-text text-muted">Sube una nueva imagen para reemplazar la actual (JPG, PNG, WEBP).</small>
                        <?php if ($edit_mode && !empty($product['image_url'])): ?>
                            <div class="mt-2">
                                <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="Imagen actual" class="product-thumbnail-large">
                                <small class="form-text text-muted">Imagen actual.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Categorías</h3>
                    <div class="form-group">
                        <?php if(empty($all_categories)): ?>
                            <p class="info-message">No hay categorías creadas. Por favor, ve a <a href="manage_categories.php">Gestionar Categorías</a> para crear algunas primero.</p>
                        <?php else: ?>
                            <div class="checkbox-scroll-box">
                                <?php foreach ($all_categories as $category): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo htmlspecialchars($category['id']); ?>" id="cat-<?php echo htmlspecialchars($category['id']); ?>" <?php if (in_array($category['id'], $current_category_ids)) echo 'checked'; ?>>
                                        <label class="form-check-label" for="cat-<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-column-sidebar">
                <div class="form-section">
                    <h3>Precios y Visibilidad</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="price_usd">Precio de Venta (USD)</label>
                            <input type="number" class="form-control" id="price_usd" name="price_usd" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price_usd']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="compare_at_price_usd">Precio Comparación (Opcional)</label>
                            <input type="number" class="form-control" id="compare_at_price_usd" name="compare_at_price_usd" step="0.01" min="0" value="<?php echo htmlspecialchars($product['compare_at_price_usd']); ?>">
                            <small class="form-text text-muted">Precio original, si hay un descuento.</small>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1" <?php if(!empty($product['is_featured'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="is_featured">Destacar en la página de inicio</label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Especificaciones</h3>
                    <div class="form-group"><label for="brand">Marca</label><input type="text" class="form-control" id="brand" name="brand" value="<?php echo htmlspecialchars($product['brand']); ?>"></div>
                    <div class="form-group"><label for="version">Versión (Pro, etc.)</label><input type="text" class="form-control" id="version" name="version" value="<?php echo htmlspecialchars($product['version']); ?>"></div>
                    <div class="form-group"><label for="platform">Plataforma (Windows, macOS, etc.)</label><input type="text" class="form-control" id="platform" name="platform" value="<?php echo htmlspecialchars($product['platform']); ?>"></div>
                    <div class="form-group"><label for="devices_limit">Límite de Dispositivos (ej. 1, 5, Ilimitado)</label><input type="text" class="form-control" id="devices_limit" name="devices_limit" value="<?php echo htmlspecialchars($product['devices_limit']); ?>"></div>
                    <div class="form-group"><label for="validity_duration">Duración (ej. 1 Año, Lifetime)</label><input type="text" class="form-control" id="validity_duration" name="validity_duration" value="<?php echo htmlspecialchars($product['validity_duration']); ?>"></div>
                </div>

                <div class="form-section">
                    <h3>Tipo y Entrega</h3>
                    <div class="form-group">
                        <label for="delivery_type">Tipo de Entrega</label>
                        <select name="delivery_type" id="delivery_type" class="form-select">
                            <option value="Entrega Digital" <?php if($product['delivery_type'] == 'Entrega Digital') echo 'selected'; ?>>Entrega Digital</option>
                            <option value="Envio Físico" <?php if($product['delivery_type'] == 'Envio Físico') echo 'selected'; ?>>Envío Físico</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_physical" name="is_physical" value="1" <?php if(!empty($product['is_physical'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="is_physical">Es un producto físico (requiere envío)</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="allow_preorder" name="allow_preorder" value="1" <?php if(!empty($product['allow_preorder'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="allow_preorder">Permitir venta sin stock (pre-compra)</label>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Productos Relacionados</h3>
                    <div class="form-group">
                        <label for="related_products">Asocia productos (Ctrl/Cmd + click para seleccionar varios)</label>
                        <?php if(empty($all_products)): ?>
                             <p class="info-message">No hay otros productos creados para asociar.</p>
                        <?php else: ?>
                            <select name="related_products[]" id="related_products" multiple class="multi-select-box form-select">
                                <?php foreach ($all_products as $p): ?>
                                    <?php if ($p['id'] != $product['id']): // Excluir el producto actual de la lista de relacionados ?>
                                        <option value="<?php echo htmlspecialchars($p['id']); ?>" <?php if (in_array($p['id'], $current_related_ids)) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <small class="form-text text-muted">Los productos relacionados se mostrarán en la página de detalle.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-4">
            <button type="submit" class="btn btn-primary btn-large">Guardar Producto</button>
            <a href="manage_products.php" class="btn btn-secondary btn-large">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once 'admin_footer.php'; ?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuración de la barra de herramientas de Quill
        var toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
            ['blockquote', 'code-block'],

            [{ 'header': 1 }, { 'header': 2 }],               // custom button values
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
            [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
            [{ 'direction': 'rtl' }],                         // text direction

            [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],

            [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
            [{ 'font': [] }],
            [{ 'align': [] }],

            ['link', 'image', 'video'],                        // link and media buttons
            ['clean']                                         // remove formatting button
        ];

        // Inicializar Quill en el div#description-editor
        var quill = new Quill('#description-editor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow', // 'snow' es el tema por defecto, 'bubble' es otra opción
            placeholder: 'Escribe la descripción del producto aquí...'
        });

        // Sincronizar el contenido de Quill con el input oculto al enviar el formulario
        var productForm = document.getElementById('product-form');
        var hiddenInput = document.getElementById('hidden-description-input');

        if (productForm && hiddenInput) {
            productForm.addEventListener('submit', function() {
                // Obtener el HTML del editor y asignarlo al input oculto
                hiddenInput.value = quill.root.innerHTML;
            });
        }
    });
</script>