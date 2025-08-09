<?php
// login.php (Frontend - Inicio de sesión de clientes)

// --- LÓGICA DE PROCESAMIENTO DEL FORMULARIO DE LOGIN ---
// ESTA SECCIÓN DEBE IR AL PRINCIPIO, ANTES DE CUALQUIER SALIDA HTML O INCLUSIONES CON HTML.

// 1. Incluir solo lo mínimo necesario para el procesamiento (sesión, PDO, funciones).
// init.php es ideal porque ya hace session_start() y carga $pdo, validate_csrf_token, etc.
require_once 'includes/init.php'; 

// 2. Si el usuario ya ha iniciado sesión, redirigirlo inmediatamente.
// Esto evita que se intente renderizar la página de login si ya está autenticado.
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/index.php'); // Redirige al panel de administración
    } else {
        header('Location: mi-cuenta.php'); // Redirige al panel del cliente
    }
    exit; // ¡CRUCIAL! Termina la ejecución después de la redirección.
}

$error_message = ''; // Inicializar la variable de mensaje de error local.

// 3. Procesar el formulario cuando se envía por POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar el token CSRF. 
        // Si validate_csrf_token() lanza una excepción, será capturada.
        validate_csrf_token(); 

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error_message = 'Por favor, introduce tu correo y contraseña.';
        } else {
            // Buscar al usuario por su correo electrónico.
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si se encontró un usuario y si la contraseña es correcta.
            if ($user && password_verify($password, $user['password'])) {
                // Autenticación exitosa: establecer variables de sesión.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirigir según el rol del usuario.
                // Estas llamadas a header() se hacen ANTES de cualquier salida HTML.
                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: mi-cuenta.php');
                }
                exit; // ¡CRUCIAL! Termina la ejecución después de la redirección exitosa.

            } else {
                // Credenciales incorrectas.
                // Almacenar el mensaje de error para mostrarlo DESPUÉS en el HTML.
                $error_message = $lang['login_error_credentials'] ?? 'Credenciales inválidas.'; 
            }
        }
    } catch (PDOException $e) {
        custom_log("ERROR PDO en login.php: " . $e->getMessage(), 'auth_errors.log');
        $error_message = $lang['login_error_generic'] ?? 'Hubo un error en el servidor. Inténtalo de nuevo.';
    } catch (Exception $e) {
        custom_log("ERROR General en login.php: " . $e->getMessage(), 'auth_errors.log');
        $error_message = $lang['login_error_generic'] ?? 'Hubo un error en el servidor. Inténtalo de nuevo.';
    }
    
    // Si llegamos aquí, el login falló o hubo una excepción.
    // El mensaje de error_message ya está establecido.
}

// 4. Procesar y mostrar mensajes flash (si vienen de otra página, ej. solicitar_recuperacion.php o registro.php)
// Esto se hace DESPUÉS de intentar el login, para que un error del login no pise un flash message anterior.
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']); // Limpiar el mensaje después de mostrarlo
}

// --- FIN DE LA LÓGICA PHP. AHORA COMENZAMOS A RENDERIZAR EL HTML ---
// Incluimos el header aquí, ya que toda la lógica de redirección ya se ha ejecutado.
require_once 'includes/header.php'; 
?>

<div class="container auth-page">
    <form class="auth-form" action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
        
        <h1 class="page-title-main" style="text-align: center;"><?php echo htmlspecialchars($lang['login_title'] ?? 'Iniciar Sesión'); ?></h1>

        <?php 
        // Mostrar mensajes flash de la sesión.
        if ($flash_message): 
        ?>
            <p class="<?php echo htmlspecialchars($flash_message['type']); ?>-message"><?php echo htmlspecialchars($flash_message['text']); ?></p>
        <?php endif; ?>

        <?php 
        // Mostrar mensaje de error local si existe (por validación de formulario).
        if ($error_message): 
        ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <div class="form-group">
            <label for="email"><?php echo htmlspecialchars($lang['register_email'] ?? 'Correo Electrónico'); ?></label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password"><?php echo htmlspecialchars($lang['register_password'] ?? 'Contraseña'); ?></label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        
        <div class="form-group-extra">
            <a href="solicitar_recuperacion.php"><?php echo htmlspecialchars($lang['forgot_password'] ?? '¿Olvidaste tu contraseña?'); ?></a>
        </div>

        <button type="submit" class="btn btn-primary btn-block"><?php echo htmlspecialchars($lang['nav_login'] ?? 'Iniciar Sesión'); ?></button>
        
        <p class="auth-switch-link"><?php echo htmlspecialchars($lang['login_no_account'] ?? '¿No tienes una cuenta?'); ?> <a href="registro.php"><?php echo htmlspecialchars($lang['login_register_here'] ?? 'Regístrate aquí'); ?></a>.</p>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>