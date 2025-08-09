<?php
require_once 'includes/header.php';

$token = $_GET['token'] ?? '';
$error_message = '';
$user = null;

if (empty($token)) {
    $error_message = $lang['token_invalid'];
} else {
    $token_hash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->execute([$token_hash]);
    $user = $stmt->fetch();
    if (!$user) {
        $error_message = $lang['token_invalid'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error_message = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Las contraseñas no coinciden.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $stmt_update->execute([$hashed_password, $user['id']]);
        
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => $lang['reset_password_success']];
        header('Location: login.php');
        exit;
    }
}
?>

<div class="container auth-page">
    <div class="auth-form">
        <h1><?php echo $lang['reset_password_title']; ?></h1>
        
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
            <?php if (!$user && !empty($token)): ?>
                <a href="solicitar_recuperacion.php" class="btn btn-secondary btn-block">Solicitar Nuevo Enlace</a>
            <?php endif; ?>
        <?php else: ?>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?php echo $lang['reset_password_submit']; ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>