<?php
require_once '../includes/init.php';

$page = ['id' => '', 'title' => '', 'content' => '', 'is_published' => 0];
$page_title = 'Crear Nueva Página';
$action = 'create';
$token = generate_csrf_token();

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $page_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($page_data) {
        $page = $page_data;
        $page_title = 'Editar Página';
        $action = 'update';
    }
}
include_once 'admin_header.php';
?>

<h1><?php echo htmlspecialchars($page_title); ?></h1>
<form action="page_handler.php" method="POST" class="admin-form" id="page-form">
    <input type="hidden" name="action" value="<?php echo $action; ?>">
    <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    
    <input type="hidden" name="content" id="hidden-content-input">

    <div class="form-group">
        <label for="title">Título de la Página</label>
        <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($page['title']); ?>" required>
    </div>
    
    <div class="form-group">
        <label for="content-editor-quill">Contenido</label>
        <div id="content-editor-quill" style="height: 600px; background-color: var(--bg-color); color: var(--text-color); border: 1px solid var(--secondary-color); border-radius: 5px;">
            <?php echo htmlspecialchars($page['content']); // Contenido inicial para el editor ?>
        </div>
    </div>

    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" <?php if(!empty($page['is_published'])) echo 'checked'; ?>>
        <label class="form-check-label" for="is_published">Publicar página (hacerla visible para los clientes)</label>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary btn-large">Guardar Página</button>
    </div>
</form>

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

        // Inicializar Quill en el div#content-editor-quill
        var quill = new Quill('#content-editor-quill', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow', // 'snow' es el tema por defecto, 'bubble' es otra opción
            placeholder: 'Escribe el contenido de la página aquí...'
        });

        // Sincronizar el contenido de Quill con el input oculto al enviar el formulario
        var pageForm = document.getElementById('page-form');
        var hiddenInput = document.getElementById('hidden-content-input');

        if (pageForm && hiddenInput) {
            pageForm.addEventListener('submit', function() {
                // Obtener el HTML del editor y asignarlo al input oculto
                hiddenInput.value = quill.root.innerHTML;
            });
        }
    });
</script>
<?php include_once 'admin_footer.php'; ?>