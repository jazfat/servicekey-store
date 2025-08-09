<?php
require_once 'includes/header.php';

$token = generate_csrf_token();
$error_message = '';
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['form_data'] = $_POST;

    validate_csrf_token();

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    // identity_document ya no se procesa aquí
    $country = trim($_POST['country']); // Ahora viene de un select
    $address = trim($_POST['address']);
    $preferred_currency = $_POST['preferred_currency'];

    // Validaciones PHP
    if (strlen($password) < 6) {
        $error_message = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Las contraseñas no coinciden.';
    } elseif (empty($name) || empty($email) || empty($country)) { // identity_document eliminado de la validación
        $error_message = 'Por favor, completa todos los campos obligatorios (*).';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error_message = 'El correo electrónico ya está registrado.';
        } else {
            $pdo->beginTransaction();
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // ATENCIÓN: Asegúrate de que tu tabla 'users' permita que 'identity_document' sea NULL
                // o elimina la columna 'identity_document' de tu tabla si ya no la necesitas.
                // Si la columna existe y es NOT NULL, la siguiente consulta fallará si intentas insertar NULL.
                $sql = "INSERT INTO users (name, email, password, role, country, address, preferred_currency) VALUES (?, ?, ?, 'customer', ?, ?, ?)";
                $stmt_insert = $pdo->prepare($sql);
                // Asegúrate de que los parámetros coincidan con la cantidad de '?' en el SQL
                $stmt_insert->execute([$name, $email, $hashed_password, $country, $address, $preferred_currency]);
                $user_id = $pdo->lastInsertId();

                $stmt_update_orders = $pdo->prepare("UPDATE orders SET user_id = ? WHERE customer_email = ? AND user_id IS NULL");
                $stmt_update_orders->execute([$user_id, $email]);

                $pdo->commit();

                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['role'] = 'customer';
                
                unset($_SESSION['form_data']);

                header('Location: mi-cuenta.php');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error de registro: " . $e->getMessage());
                $error_message = 'No se pudo crear la cuenta. Inténtalo de nuevo.';
            }
        }
    }
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $error_message];
    header('Location: registro.php');
    exit;
}

$page_title = 'Crear una Cuenta';

// Lista de países para el dropdown
$countries = [
    "US" => "Estados Unidos",
    "ES" => "España",
    "MX" => "México",
    "CO" => "Colombia",
    "AR" => "Argentina",
    "CL" => "Chile",
    "PE" => "Perú",
    "EC" => "Ecuador",
    "VE" => "Venezuela", // Mantén Venezuela si es relevante para tus operaciones
    "CA" => "Canadá",
    "GB" => "Reino Unido",
    "DE" => "Alemania",
    "FR" => "Francia",
    "IT" => "Italia",
    "BR" => "Brasil",
    // Agrega más países según tu mercado objetivo
];
// Opcional: ordenar alfabéticamente por nombre de país
asort($countries);

?>
<div class="container auth-page">
    <form class="auth-form" action="registro.php" method="POST">
        <h1><?php echo $lang['register_title']; ?></h1>
        
        <?php 
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            echo '<p class="' . htmlspecialchars($message['type']) . '-message">' . htmlspecialchars($message['text']) . '</p>';
            unset($_SESSION['flash_message']);
        }
        ?>
        
        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">

        <h3>Información de la Cuenta</h3>
        <div class="form-group"><label for="email">Correo Electrónico*</label><input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required></div>
        <div class="form-grid">
            <div class="form-group"><label for="password">Contraseña*</label><input type="password" name="password" id="password" class="form-control" required></div>
            <div class="form-group"><label for="confirm_password">Confirmar Contraseña*</label><input type="password" name="confirm_password" id="confirm_password" class="form-control" required></div>
        </div>

        <h3 class="mt-4">Información de Facturación</h3>
        <div class="form-group"><label for="name">Nombre Completo o Razón Social*</label><input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required></div>
        <div class="form-grid">
            <div class="form-group">
                <label for="country">País*</label>
                <select name="country" id="country" class="form-select" required>
                    <option value="">-- Seleccione su país --</option>
                    <?php foreach ($countries as $code => $name): ?>
                        <option value="<?php echo htmlspecialchars($code); ?>" 
                            <?php echo (($form_data['country'] ?? '') == $code) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label for="address">Dirección Fiscal (Opcional)</label><textarea name="address" id="address" class="form-control" rows="3"><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea></div>
        <div class="form-group">
            <label for="preferred_currency">Moneda Preferida</label>
            <select name="preferred_currency" id="preferred_currency" class="form-select">
                <option value="USD" <?php echo (($form_data['preferred_currency'] ?? '') == 'USD') ? 'selected' : ''; ?>>USD</option>
                <option value="VES" <?php echo (($form_data['preferred_currency'] ?? '') == 'VES') ? 'selected' : ''; ?>>VES</option>
                <option value="COP" <?php echo (($form_data['preferred_currency'] ?? '') == 'COP') ? 'selected' : ''; ?>>COP</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block mt-4"><?php echo $lang['register_submit_button']; ?></button>
        <p class="auth-switch-link"><?php echo $lang['register_already_have_account']; ?> <a href="login.php">aquí</a>.</p>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>