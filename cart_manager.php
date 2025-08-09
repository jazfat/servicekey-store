<?php
// init.php carga todo lo que necesitamos: sesión, conexión, funciones, etc.
require_once 'includes/init.php';

// Establecemos la cabecera para asegurar que la respuesta siempre sea de tipo JSON.
header('Content-Type: application/json');

// Inicializamos una respuesta por defecto para el caso de errores inesperados.
$response = ['success' => false, 'message' => 'Ocurrió un error inesperado.'];

try {
    // Solo procesamos si la petición es de tipo POST.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no válido.');
    }

    // Validamos el token CSRF para prevenir ataques.
    // Si la validación falla, la función validate_csrf_token() lanzará una excepción.
    validate_csrf_token();
    
    // Obtenemos y saneamos las variables de la petición.
    $action = $_POST['action'] ?? '';
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Si la cantidad es menor a 1, no es válida.
    if ($quantity < 1) {
        throw new Exception('La cantidad debe ser al menos 1.');
    }

    switch ($action) {
        case 'add':
            // Verificamos si el producto es válido y tiene stock o permite pre-compra.
            if (is_product_valid($product_id)) {
                // Si el carrito no existe, lo creamos.
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                // Añadimos el producto o actualizamos su cantidad.
                $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $quantity;
                $response = ['success' => true, 'message' => 'Producto añadido al carrito.'];
            } else {
                // Si el producto no es válido, enviamos un mensaje de error específico.
                $response = ['success' => false, 'message' => 'Este producto no se puede añadir (stock insuficiente o no disponible).'];
            }
            break;

        case 'remove':
            // Verificamos que el product_id sea válido.
            if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $response = ['success' => true, 'message' => 'Producto eliminado del carrito.'];
            } else {
                $response = ['success' => false, 'message' => 'El producto no se encontró en el carrito.'];
            }
            break;

        default:
            // Si la acción no es 'add' o 'remove', es una acción no válida.
            $response = ['success' => false, 'message' => 'Acción no reconocida.'];
            break;
    }
} catch (Exception $e) {
    // Si cualquier parte del código dentro del 'try' falla, capturamos el error aquí.
    // Esto asegura que siempre enviaremos una respuesta JSON válida, incluso si hay un error.
    $response['message'] = $e->getMessage();
}

// Codificamos el array de respuesta a formato JSON y lo imprimimos.
echo json_encode($response);

// Finalizamos la ejecución del script.
exit;