<?php
require_once 'includes/header.php';

$token = generate_csrf_token(); // Generar token para el formulario
$message = '';
$message_type = '';

$user_name = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';

// --- Procesar y mostrar mensajes flash (si vienen del envío del formulario) ---
$flash_message = $_SESSION['flash_message'] ?? null; // Obtener el mensaje flash
if ($flash_message) {
    unset($_SESSION['flash_message']); // Limpiar el mensaje después de mostrarlo
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token(); // Validar token
    
    // Obtener y sanitizar datos del formulario
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['message'] ?? '');

    // Validaciones básicas del lado del servidor
    if (empty($name) || empty($email) || empty($subject) || empty($body)) {
        $message = 'Por favor, completa todos los campos obligatorios.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'El correo electrónico proporcionado no es válido.';
        $message_type = 'error';
    } else {
        // Intentar enviar el correo electrónico
        if (send_contact_email($name, $email, $subject, $body)) {
            $message = $lang['contact_success']; // Mensaje de éxito del archivo de idioma
            $message_type = 'success';
        } else {
            $message = $lang['contact_error']; // Mensaje de error del archivo de idioma
            $message_type = 'error';
        }
    }
    // Guardar el mensaje en flash_message para que se muestre después de la recarga
    $_SESSION['flash_message'] = ['type' => $message_type, 'text' => $message];
    header('Location: contacto.php'); // Redirigir para evitar reenvío de formulario
    exit;
}

$page_title = 'Contactar'; // Título de la página para el navegador
?>
<div class="container page-content">
    <h1><?php echo $lang['contact_title']; ?></h1>
    <p>Si tienes alguna pregunta sobre un producto, un problema con tu orden o cualquier otra consulta, no dudes en usar el siguiente formulario para contactarnos.</p>
    
    <div class="auth-form"> <h2><?php echo $lang['contact_form_title']; ?></h2>
        
        <?php 
        // Mostrar mensajes flash (del envío del formulario anterior)
        if ($flash_message): 
        ?>
            <div class="<?php echo htmlspecialchars($flash_message['type']); ?>-message">
                <?php echo htmlspecialchars($flash_message['text']); ?>
            </div>
        <?php endif; ?>

        <form action="contacto.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-grid"> <div class="form-group">
                    <label for="name"><?php echo $lang['register_name']; ?>*</label> <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email"><?php echo $lang['register_email']; ?>*</label> <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="subject"><?php echo $lang['contact_form_subject']; ?>*</label> <input type="text" name="subject" id="subject" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="message"><?php echo $lang['contact_form_message']; ?>*</label> <textarea name="message" id="message" class="form-control" rows="6" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?php echo $lang['contact_form_submit']; ?></button>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>