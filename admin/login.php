<?php
// admin/login.php

// Carga el archivo de inicialización principal.
// Desde 'admin/login.php', necesitas retroceder un nivel (..) para llegar a la raíz 'tu-tienda/'
// y luego entrar en 'includes/'.
require_once __DIR__ . '/../includes/init.php'; // Usa __DIR__ para rutas absolutas y más robustas

// NOTA: Para la página de login, no necesitamos el guardián de sesión aquí.
// Si el usuario ya está logueado, lo redirigimos más abajo.

$token = generate_csrf_token(); // Generar token para el formulario
$error_message = '';

// Si el usuario ya ha iniciado sesión, lo redirigimos a su panel correspondiente
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: index.php'); // Redirige a admin/index.php
    } else {
        header('Location: ../mi-cuenta.php'); // Redirige al frontend mi-cuenta.php
    }
    exit;
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token(); // <-- VALIDAR AQUÍ EL TOKEN CSRF

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Por favor, introduce tu correo y contraseña.';
    } else {
        try {
            // Buscar al usuario por su correo electrónico
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verificar si se encontró un usuario y si la contraseña es correcta
            if ($user && password_verify($password, $user['password'])) {
                // ¡Éxito! Creamos las variables de sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirigir según el rol del usuario
                if ($user['role'] === 'admin') {
                    header('Location: index.php'); // Redirige a admin/index.php
                } else {
                    header('Location: ../mi-cuenta.php'); // Redirige al frontend mi-cuenta.php
                }
                exit;

            } else {
                // Credenciales incorrectas
                $error_message = $lang['login_error_credentials']; // Usa la variable $lang
            }
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $error_message = $lang['login_error_generic']; // Usa la variable $lang
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['login_title']; ?></title> <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body class="admin-login-page"> <div class="login-box">
        <div class="login-logo">
            <img src="../<?php echo htmlspecialchars($site_settings['site_logo_url']); ?>" alt="Logo del Sitio">
        </div>
        <h2><?php echo $lang['nav_login']; ?></h2> <?php
        // Mensajes flash (si existen en la sesión)
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            // Usamos la clase CSS dinámica basada en el tipo de mensaje
            echo '<p class="' . htmlspecialchars($message['type']) . '-message">' . htmlspecialchars($message['text']) . '</p>';
            // Borramos el mensaje para que no vuelva a aparecer
            unset($_SESSION['flash_message']);
        }
        ?>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
        </form>
        <div class="login-footer">
            <a href="../index.php">&larr; Volver a la Tienda</a>
        </div>
    </div>
</body>
</html>