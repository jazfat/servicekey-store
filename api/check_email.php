<?php
// api/check_email.php
// Verifica si un email ya existe en la base de datos de usuarios.

require_once '../includes/init.php'; // Carga la conexión PDO y otras funciones

header('Content-Type: application/json');
$response = ['exists' => false, 'message' => ''];

// Solo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

try {
    // Leer el cuerpo JSON de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar el token CSRF (ya que la mayoría de las llamadas AJAX lo tienen)
    validate_csrf_token(); // Esta función busca en $_POST y JSON

    $email = trim($input['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido.');
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $response['exists'] = true;
        $response['message'] = 'Este correo electrónico ya está registrado.';
    } else {
        $response['message'] = 'Este correo electrónico está disponible.';
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    custom_log("ERROR en check_email.php: " . $e->getMessage(), 'api_errors.log');
}

echo json_encode($response);
exit;