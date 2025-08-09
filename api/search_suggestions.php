<?php
// api/search_suggestions.php
// Este script proporciona sugerencias de productos para la búsqueda en vivo.

// Asegúrate de que init.php se encarga de la conexión PDO ($pdo) y cualquier otra configuración necesaria.
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json'); // La respuesta será JSON

$suggestions = []; // Array para almacenar las sugerencias

// Obtener el término de búsqueda de forma segura
$query = $_GET['q'] ?? '';
$query = trim($query);

if (!empty($query)) {
    try {
        // Buscar productos por nombre o descripción (puedes ajustar los campos)
        $searchTerm = "%" . $query . "%";
        $stmt = $pdo->prepare("SELECT id, name, image_url FROM products WHERE name LIKE :term OR description LIKE :term ORDER BY name ASC LIMIT 10"); // Limita a 10 sugerencias
        $stmt->execute([':term' => $searchTerm]);
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Opcional: Asegurarse de que las URLs de imagen sean correctas si se usan en la sugerencia
        foreach ($suggestions as &$suggestion) {
            $suggestion['image_url'] = htmlspecialchars($suggestion['image_url'] ?: 'assets/img/placeholder.png');
        }

    } catch (PDOException $e) {
        // En un entorno de producción, registrar este error a un log en lugar de mostrarlo al usuario.
        error_log("Error en api/search_suggestions.php: " . $e->getMessage());
        // No devolver error detallado al frontend por seguridad.
        echo json_encode(['error' => 'Hubo un error al buscar sugerencias.']);
        exit;
    }
}

echo json_encode($suggestions); // Devuelve las sugerencias en formato JSON
exit;