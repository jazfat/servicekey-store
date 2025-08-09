<?php
require_once 'includes/header.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validate_csrf_token();
        
        $email = $_POST['email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception($lang['error_invalid_email']);
        }

        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $recovery_token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $recovery_token);
            $expires_at = date('Y-m-d H:i:s', time() + 3600); 

            $stmt_update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            $stmt_update->execute([$token_hash, $expires_at, $user['id']]);

            if (!send_recovery_email($user['email'], $recovery_token)) {
                error_log("Fallo al enviar correo de recuperación a: " . $user['email']);
            }
        }
        
        $message = $lang['recovery_email_sent_if_exists'];
        $message_type = 'success';

    } catch (PDOException $e) {
        error_log("Error de base de datos en solicitar_recuperacion: " . $e->getMessage());
        $message = $lang['error_generic_message'];
        $message_type = 'error';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}
?>

<div class="container auth-page">
    <form class="auth-form" action="solicitar_recuperacion.php" method="POST" novalidate>
        <h1><?php echo $lang['forgot_password_title']; ?></h1>
        <p class="auth-form-description"><?php echo $lang['forgot_password_instructions']; ?></p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

        <div class="form-group">
            <label for="email"><?php echo $lang['register_email']; ?></label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block"><?php echo $lang['forgot_password_submit']; ?></button>

        <div class="form-links">
            <a href="login.php"><?php echo $lang['back_to_login']; ?></a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>