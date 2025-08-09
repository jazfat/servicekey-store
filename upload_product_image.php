<?php

// 1. Incluir el archivo de configuración centralizado
// ASEGÚRATE DE QUE LA RUTA SEA CORRECTA para la ubicación de tu config.php
require_once __DIR__ . '/includes/config.php';

// 2. Configuración de la conexión a la base de datos
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de Conexión a la Base de Datos: " . $e->getMessage() . "<br>Por favor, verifica los parámetros de conexión en tu archivo `config.php` (DB_HOST, DB_NAME, DB_USER, DB_PASS) y asegúrate de que el servidor MySQL esté en ejecución.");
}

// Array para almacenar mensajes de estado (éxito, error).
$messages = [];

/**
 * Función para añadir un mensaje al array de mensajes.
 * @param string $type Tipo de mensaje ('success' o 'error').
 * @param string $text Texto del mensaje.
 */
function addMessage($type, $text) {
    global $messages;
    $messages[] = ['type' => $type, 'text' => $text];
}

// 3. Procesamiento de la subida de imágenes (Lógica PHP para el POST final)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = 'uploads/';

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            addMessage('error', "✖ Error: No se pudo crear el directorio de subida: $upload_dir. Verifique los permisos.");
        }
    }

    // Límite de tamaño de archivo por imagen (2MB)
    $max_file_size_bytes = 2 * 1024 * 1024; // 2 MB

    // Verificar si hay archivos subidos en el array 'images'
    if (empty($_FILES['images']['name'][0]) && empty($_POST['product_id'][0])) {
        // Esto cubre el caso de que el formulario se envíe sin ninguna imagen seleccionada
        addMessage('error', "✖ No se seleccionó ninguna imagen para subir. Por favor, selecciona al menos una.");
    } else if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            // Verificar si el archivo temporal existe y no hay errores de subida de PHP
            if (
                $_FILES['images']['error'][$key] === UPLOAD_ERR_OK &&
                !empty($tmp_name) &&
                isset($_POST['product_id'][$key])
            ) {
                $product_id = (int)$_POST['product_id'][$key];
                $original_filename = basename($_FILES['images']['name'][$key]);
                $imageFileType = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                $file_size = $_FILES['images']['size'][$key];

                // Validación de tamaño de archivo
                if ($file_size > $max_file_size_bytes) {
                    addMessage('error', "✖ El archivo <strong>" . htmlspecialchars($original_filename) . "</strong> excede el tamaño máximo permitido (2MB).");
                    continue; // Saltar a la siguiente imagen
                }

                $new_filename = time() . '_' . uniqid() . '_' . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $original_filename);
                $target_file = $upload_dir . $new_filename;

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp']; 

                if (in_array($imageFileType, $allowed_types)) {
                    $check = @getimagesize($tmp_name);
                    if ($check === false) {
                        addMessage('error', "✖ El archivo <strong>" . htmlspecialchars($original_filename) . "</strong> no es una imagen válida o está corrupto.");
                        continue;
                    }

                    // Validación: Verificar si el product_id existe en la base de datos
                    $stmt_check_product = $pdo->prepare("SELECT id, name FROM products WHERE id = :id");
                    $stmt_check_product->execute([':id' => $product_id]);
                    $product_exists = $stmt_check_product->fetch();

                    if (!$product_exists) {
                        addMessage('error', "✖ El producto con ID <strong>$product_id</strong> (para la imagen <strong>" . htmlspecialchars($original_filename) . "</strong>) no existe. No se asignó la imagen.");
                        continue;
                    }

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $stmt = $pdo->prepare("UPDATE products SET image_url = :url WHERE id = :id");
                        $stmt->execute([':url' => $target_file, ':id' => $product_id]);
                        addMessage('success', "✔ Imagen <strong>" . htmlspecialchars($original_filename) . "</strong> asignada al producto: <strong>" . htmlspecialchars($product_exists['name']) . "</strong> (ID $product_id).");
                    } else {
                        addMessage('error', "✖ Error al mover la imagen <strong>" . htmlspecialchars($original_filename) . "</strong>. Posibles problemas de permisos en el directorio 'uploads'.");
                    }
                } else {
                    addMessage('error', "✖ Tipo de archivo no permitido: <strong>" . htmlspecialchars($original_filename) . "</strong>. Solo se permiten JPG, JPEG, PNG, GIF, WEBP.");
                }
            } else {
                // Manejo de errores de subida de PHP específicos o campos no válidos
                $error_code = $_FILES['images']['error'][$key] ?? UPLOAD_ERR_NO_FILE;
                $original_filename_display = htmlspecialchars($_FILES['images']['name'][$key] ?? 'archivo desconocido (error)');

                if ($error_code !== UPLOAD_ERR_NO_FILE && $error_code !== UPLOAD_ERR_OK) { // Solo si hubo un error de subida real
                    switch ($error_code) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            addMessage('error', "✖ El archivo <strong>$original_filename_display</strong> es demasiado grande (límites del servidor).");
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            addMessage('error', "✖ El archivo <strong>$original_filename_display</strong> se subió solo parcialmente. Inténtalo de nuevo.");
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            addMessage('error', "✖ Error del servidor: Falta un directorio temporal para la subida de archivos.");
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            addMessage('error', "✖ Error del servidor: No se pudo escribir el archivo en el disco.");
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            addMessage('error', "✖ Una extensión de PHP detuvo la subida del archivo <strong>$original_filename_display</strong>.");
                            break;
                        default:
                            addMessage('error', "✖ Error desconocido al subir el archivo <strong>$original_filename_display</strong>.");
                            break;
                    }
                }
                // Si el product_id no está definido o el archivo es UPLOAD_ERR_NO_FILE para un campo que se esperaba
                // Este caso se manejará mejor por la validación JS antes del envío.
            }
        }
    }
}

// 4. Obtención de productos para rellenar el select del formulario
$productos = [];
try {
    // CAMBIO APLICADO: REMOVER LA CLÁUSULA WHERE para ver TODOS los productos.
    // Si deseas filtrar solo los que NO tienen imagen, usa la línea comentada abajo.
    $stmt = $pdo->query("SELECT id, name FROM products ORDER BY name");
    
    // O si solo quieres productos SIN imagen:
    // $stmt = $pdo->query("SELECT id, name FROM products WHERE image_url IS NULL OR image_url = '' ORDER BY name");
    
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    addMessage('error', "✖ Error al cargar los productos de la base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Imágenes a Productos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos generales del body y contenedor */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #1a1a2e; /* Color de fondo oscuro */
            color: #fff; /* Texto blanco por defecto */
            overflow-x: hidden;
            position: relative;
        }

        .container {
            position: relative;
            z-index: 10; /* Para que el contenido esté por encima del fondo de matriz */
            max-width: 900px; /* Un poco más ancho para más espacio */
            margin: 0 auto;
            padding: 2rem;
        }

        h2 {
            color: #00ffff; /* Color de resaltado para los títulos */
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            background: linear-gradient(90deg, #6a0dad, #00ffff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* Estilos para el formulario principal */
        form {
            background: rgba(26, 26, 46, 0.7); /* Fondo semi-transparente */
            border: 1px solid #6a0dad; /* Borde con color de tema */
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 800px; /* Ajustado */
            margin: 20px auto;
            backdrop-filter: blur(8px); /* Efecto de desenfoque */
            box-shadow: 0 0 30px rgba(106, 13, 173, 0.3); /* Sombra brillante */
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            color: #00ffff; /* Resalta las etiquetas */
        }

        select {
            width: calc(100% - 22px);
            padding: 10px;
            margin: 5px 0 15px;
            border: 1px solid #6a0dad;
            border-radius: 6px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-size: 1em;
            box-sizing: border-box;
        }
        
        /* Estilos para cada bloque de producto-imagen (añadido dinámicamente) */
        .producto-block {
            display: flex; /* Usar flexbox para alinear elementos */
            align-items: flex-start; /* Alinear arriba para el label */
            gap: 15px; /* Espacio entre elementos */
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid rgba(106, 13, 173, 0.5);
            border-radius: 8px;
            background-color: rgba(0, 0, 0, 0.2);
            position: relative;
        }
        .producto-block > div { /* Para envolver label+select y label+preview */
            flex: 1; /* Permite que los divs internos crezcan */
            min-width: 0; /* Necesario para que flex-basis funcione con contenido largo */
        }
        .producto-block label {
            margin-top: 0; /* Ajustar margen */
        }

        .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4d4d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8em;
            opacity: 0.8;
            transition: opacity 0.3s ease;
            z-index: 20; /* Asegurar que esté encima de otros elementos en el bloque */
        }
        .remove-btn:hover {
            opacity: 1;
            background: #cc0000;
        }

        /* Estilos para los botones */
        button {
            background: linear-gradient(45deg, #6a0dad, #00ffff);
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 1.1em;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
            text-align: center;
            display: inline-block;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 255, 0.4);
        }
        .main-action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        #selectBulkImagesButton, #uploadImagesButton {
            background: linear-gradient(45deg, #28a745, #00ffff); /* Verde brillante */
        }
        #selectBulkImagesButton:hover, #uploadImagesButton:hover {
            background: linear-gradient(45deg, #218838, #00ffff);
        }

        /* Mensajes de éxito y error */
        .message-container { margin-bottom: 20px; text-align: center; }
        .success-message { color: #d4edda; background-color: #28a745; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }
        .error-message { color: #f8d7da; background-color: #dc3545; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }

        /* Estilos para la sección de búsqueda en Google */
        .google-search-section {
            background: rgba(26, 26, 46, 0.7);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            max-width: 700px;
            margin: 40px auto;
            text-align: center;
            backdrop-filter: blur(8px);
        }
        .google-search-section h3 { color: #00ffff; margin-bottom: 20px; }
        .google-search-section input[type="text"] {
            width: calc(100% - 120px);
            max-width: 400px;
            padding: 10px;
            border: 1px solid #6a0dad;
            border-radius: 5px;
            margin-right: 10px;
            box-sizing: border-box;
            background: rgba(0, 0, 0, 0.3);
            color: white;
        }
        .google-search-section button {
            background: #f4b400;
            color: white;
        }
        .google-search-section button:hover { background: #dba000; }
        .google-search-section p { font-size: 0.9em; color: #aaa; margin-top: 15px; }

        /* Fondo de matriz animado (canvas) */
        #matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.15;
        }

        /* VISTA PREVIA Y CARGA */
        .file-input-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid #00ffff;
            border-radius: 4px;
            padding: 10px;
            min-height: 100px; /* Asegurar un tamaño mínimo */
            background-color: rgba(0,0,0,0.1);
            text-align: center;
            position: relative; /* Para posicionar el ícono de zoom */
        }
        .file-input-display img {
            max-width: 100%;
            max-height: 80px; /* Limitar altura de miniatura */
            display: block;
            margin-bottom: 5px;
            object-fit: contain; /* Para que la imagen no se recorte */
            cursor: zoom-in; /* Indica que es clickeable para zoom */
        }
        .file-input-display .placeholder {
            color: #888;
            font-size: 0.8em;
        }
        .file-name-display {
            color: #ccc;
            font-size: 0.8em;
            word-break: break-all;
            margin-top: 5px;
            max-width: 100%; /* Asegurar que no desborde */
            overflow: hidden;
            text-overflow: ellipsis; /* Puntos suspensivos para nombres largos */
            white-space: nowrap; /* Evitar salto de línea si no es necesario */
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
            font-size: 1.5em;
            backdrop-filter: blur(5px);
        }

        .spinner {
            border: 8px solid rgba(255, 255, 255, 0.3);
            border-top: 8px solid #00ffff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Ocultar inputs de archivo originales */
        .hidden-file-input {
            display: none !important;
            visibility: hidden;
            position: absolute;
            width: 0;
            height: 0;
            overflow: hidden;
        }

        /* --- MODAL/LIGHTBOX PARA VISTA PREVIA AMPLIADA --- */
        .image-modal {
            display: none; /* Oculto por defecto */
            position: fixed;
            z-index: 200; /* Por encima de todo lo demás */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Habilitar scroll si la imagen es muy grande */
            background-color: rgba(0, 0, 0, 0.9); /* Fondo oscuro semitransparente */
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .image-modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            object-fit: contain; /* Asegura que la imagen se ajuste sin recortarse */
        }

        .close-modal-btn {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }

        .close-modal-btn:hover,
        .close-modal-btn:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Subiendo imágenes, por favor espera...</p>
    </div>

    <div id="imageZoomModal" class="image-modal">
        <span class="close-modal-btn">&times;</span>
        <img class="image-modal-content" id="imgZoomed">
    </div>

    <div class="container">
        <h2>Asignar Imágenes a Productos</h2>

        <div class="message-container">
            <?php foreach ($messages as $msg): ?>
                <div class="<?= htmlspecialchars($msg['type']) ?>-message">
                    <?= $msg['text'] ?>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div style="text-align: center; margin-bottom: 30px;">
                <input type="file" id="bulkImageInput" accept="image/*" multiple class="hidden-file-input">
                
                <button type="button" id="selectBulkImagesButton">
                    <i class="fas fa-images"></i> Seleccionar Imágenes en Lote
                </button>
            </div>

            <div id="uploads-container">
                <div class="producto-block" id="template-product-block" style="display:none;">
                    <div style="flex: 2;">
                        <label>Producto:</label>
                        <select name="product_id[]" required>
                            <option value="">Seleccione un producto</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?= htmlspecialchars($producto['id']) ?>">
                                    <?= htmlspecialchars($producto['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="flex: 1;">
                        <label>Imagen:</label>
                        <div class="file-input-display">
                            <img src="" alt="Vista previa" style="display:none;">
                            <span class="placeholder"><i class="fas fa-image"></i> Preview</span>
                            <div class="file-name-display"></div>
                        </div>
                        <input type="file" name="images[]" required class="hidden-file-input" data-temp-name="">
                    </div>
                    <button type="button" class="remove-btn"><i class="fas fa-trash-alt"></i> Eliminar</button>
                </div>
            </div>

            <div class="main-action-buttons">
                <button type="submit" id="uploadImagesButton"><i class="fas fa-upload"></i> Subir Imágenes</button>
            </div>
        </form>

        <div class="google-search-section">
            <h3>Buscar imagen desde Google <i class="fab fa-google"></i></h3>
            <form method="GET" action="https://www.google.com/search" target="_blank">
                <input type="hidden" name="tbm" value="isch">
                <input type="text" name="q" placeholder="Buscar imagen de producto" style="width: 70%; padding: 8px;">
                <button type="submit"><i class="fas fa-search"></i> Buscar</button>
            </form>
            <p style="font-size: 0.9em; color: #aaa; margin-top: 15px;">Se abrirá una búsqueda en Google Imágenes para ayudarte a encontrar una imagen del producto que puedas descargar manualmente.</p>
        </div>
    </div>

    <canvas id="matrix-bg"></canvas>

    <script>
        // Efecto Matrix para el fondo (se mantiene sin cambios)
        const canvas = document.getElementById('matrix-bg');
        const ctx = canvas.getContext('2d');

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const katakana = 'アァカサタナハマヤャラワガザダバパイィキシチニヒミリヰギジヂビピウゥクスツヌフムユュルグズブヅプエェケセテネヘメレヱゲゼデベペオォコソトノホモヨョロヲゴゾドボポヴッン';
        const latin = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const nums = '0123456789';
        const alphabet = katakana + latin + nums;

        const fontSize = 16;
        const columns = Math.floor(canvas.width / fontSize);

        const rainDrops = Array.from({ length: columns }).map((_, i) => {
            return {
                text: alphabet.charAt(Math.floor(Math.random() * alphabet.length)),
                x: i * fontSize,
                y: Math.random() * -canvas.height
            };
        });

        const drawMatrix = () => {
            ctx.fillStyle = 'rgba(26, 26, 46, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#6a0dad';
            ctx.font = `${fontSize}px monospace`;
            
            rainDrops.forEach(drop => {
                ctx.fillText(drop.text, drop.x, drop.y);
                drop.y += fontSize;
                
                if (drop.y > canvas.height && Math.random() > 0.975) {
                    drop.y = -fontSize;
                }
                
                drop.text = alphabet.charAt(Math.floor(Math.random() * alphabet.length));
            });
        };

        setInterval(drawMatrix, 30);

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });

        // *** Lógica de JavaScript para Subida Masiva y Mapeo (VERSION FINAL CORREGIDA) ***
        document.addEventListener('DOMContentLoaded', function() {
            const uploadsContainer = document.getElementById('uploads-container');
            const templateBlock = document.getElementById('template-product-block');
            const uploadForm = document.getElementById('uploadForm');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const bulkImageInput = document.getElementById('bulkImageInput');
            const selectBulkImagesButton = document.getElementById('selectBulkImagesButton');
            const uploadImagesButton = document.getElementById('uploadImagesButton');

            // --- Nuevas referencias para el modal de zoom ---
            const imageZoomModal = document.getElementById('imageZoomModal');
            const imgZoomed = document.getElementById('imgZoomed');
            const closeModalBtn = document.querySelector('.close-modal-btn');

            // --- Definiciones de funciones auxiliares (MOVIDAS AL PRINCIPIO del DCL) ---

            /**
             * Actualiza la visibilidad del botón "Subir Imágenes" basado en si hay bloques visibles.
             */
            function updateUploadButtonVisibility() {
                const visibleBlocks = uploadsContainer.querySelectorAll('.producto-block[style*="display: flex"]');
                if (visibleBlocks.length > 0) {
                    uploadImagesButton.style.display = 'inline-block';
                } else {
                    uploadImagesButton.style.display = 'none';
                }
            }

            /**
             * Función para añadir mensajes temporales a la interfaz.
             * @param {string} type Tipo de mensaje ('success' o 'error').
             * @param {string} text Texto del mensaje.
             */
            function addMessageToPage(type, text) {
                const messageContainer = document.querySelector('.message-container');
                if (messageContainer) {
                    const messageDiv = document.createElement('div');
                    messageDiv.classList.add(type + '-message');
                    messageDiv.innerHTML = text;
                    messageContainer.appendChild(messageDiv);
                    setTimeout(() => messageDiv.remove(), 7000); // Mensaje desaparece después de 7s
                }
            }

            /**
             * Crea y añade un nuevo bloque de "producto-imagen" con la imagen seleccionada.
             * @param {File} file El objeto File seleccionado.
             */
            function createAndAddProductBlock(file) {
                const newBlock = templateBlock.cloneNode(true);
                newBlock.style.display = 'flex'; // Mostrar el bloque
                newBlock.removeAttribute('id'); // Quitar ID de plantilla

                const selectElement = newBlock.querySelector('select[name="product_id[]"]');
                const fileInputElement = newBlock.querySelector('input[name="images[]"]'); // Referencia al input file oculto
                const imgElement = newBlock.querySelector('.file-input-display img');
                const placeholderSpan = newBlock.querySelector('.file-input-display .placeholder');
                const fileNameDisplay = newBlock.querySelector('.file-name-display');
                const removeButton = newBlock.querySelector('.remove-btn');
                const fileInputDisplayDiv = newBlock.querySelector('.file-input-display'); // Para el listener de click en el área de preview

                // Asegurar que el select esté limpio
                if (selectElement) {
                    selectElement.value = '';
                }

                // Adjuntar el File object al input type="file" oculto
                if (fileInputElement && file) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInputElement.files = dataTransfer.files;

                    // Mostrar vista previa y nombre del archivo
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imgElement.src = e.target.result;
                        imgElement.style.display = 'block';
                        placeholderSpan.style.display = 'none';
                        // Añadir listener para zoom al cargar la imagen (clic en la imagen)
                        imgElement.addEventListener('click', function(event) {
                            event.stopPropagation(); // Evitar que el clic se propague al div padre
                            imgZoomed.src = imgElement.src;
                            imageZoomModal.style.display = 'flex'; // Mostrar modal
                        });
                    };
                    reader.readAsDataURL(file);
                    fileNameDisplay.textContent = file.name;
                    fileNameDisplay.title = file.name; // Para tooltip

                    // Almacenar el nombre del archivo original para depuración si es necesario
                    fileInputElement.dataset.tempName = file.name;
                }

                // Asignar evento al botón de eliminar
                removeButton.addEventListener('click', function() {
                    // Confirmación al eliminar
                    if (confirm('¿Estás seguro de que quieres eliminar esta imagen del lote?')) {
                        uploadsContainer.removeChild(newBlock);
                        updateUploadButtonVisibility(); // Actualizar visibilidad del botón Subir
                        // Mensaje si no quedan bloques
                        if (uploadsContainer.children.length === 0) {
                            addMessageToPage('error', "No hay imágenes seleccionadas. Haz clic en 'Seleccionar Imágenes en Lote' para empezar.");
                        }
                    }
                });

                // Listener para hacer clic en el área de vista previa (no solo en la img) para el zoom
                fileInputDisplayDiv.addEventListener('click', function() {
                    if (imgElement.style.display !== 'none') { // Si hay una imagen mostrada
                        imgZoomed.src = imgElement.src;
                        imageZoomModal.style.display = 'flex';
                    }
                });

                uploadsContainer.appendChild(newBlock);
                updateUploadButtonVisibility(); // Actualizar visibilidad del botón Subir
            }

            // --- FIN Definiciones de funciones auxiliares ---


            // Listener para el botón "Seleccionar Imágenes en Lote"
            selectBulkImagesButton.addEventListener('click', function() {
                bulkImageInput.click(); // Dispara el clic en el input file oculto
            });

            // Listener para el input file de selección masiva (CORREGIDO: Array.from(files).forEach)
            bulkImageInput.addEventListener('change', function(event) {
                const files = event.target.files;
                if (files.length > 0) {
                    uploadsContainer.innerHTML = ''; // Limpiar campos existentes al seleccionar un nuevo lote
                    // CORRECCIÓN: Usar Array.from() para iterar FileList
                    Array.from(files).forEach(file => {
                        createAndAddProductBlock(file); // Crear un bloque por cada archivo seleccionado
                    });
                } else {
                    // Si el usuario cancela la selección, asegurarse de que el botón Subir se oculte
                    updateUploadButtonVisibility();
                    if (uploadsContainer.children.length === 0) {
                        addMessageToPage('error', "Selección cancelada o no se seleccionaron archivos. Haz clic en 'Seleccionar Imágenes en Lote' para empezar.");
                    }
                }
            });

            // Mostrar u ocultar el botón "Subir Imágenes" al cargar la página
            updateUploadButtonVisibility();

            // Mostrar el overlay de carga al enviar el formulario
            uploadForm.addEventListener('submit', function(event) {
                const visibleProductBlocks = uploadsContainer.querySelectorAll('.producto-block[style*="display: flex"]');
                let allValid = true;

                if (visibleProductBlocks.length === 0) {
                    addMessageToPage('error', "✖ Por favor, selecciona imágenes y asigna productos antes de subir.");
                    event.preventDefault(); // Detener el envío del formulario
                    return;
                }

                visibleProductBlocks.forEach(block => {
                    const select = block.querySelector('select[name="product_id[]"]');
                    const fileInput = block.querySelector('input[name="images[]"]'); // Referencia al input file oculto

                    if (!select || !fileInput) {
                        addMessageToPage('error', "✖ Error interno: Un campo de selección de producto o archivo está corrupto en un bloque.");
                        allValid = false;
                        event.preventDefault();
                        return;
                    }

                    // Validar que el producto esté seleccionado
                    if (!select.value) {
                        select.focus();
                        addMessageToPage('error', "✖ Selecciona un producto para la imagen: " + (fileInput.dataset.tempName || 'sin nombre') + ".");
                        allValid = false;
                        event.preventDefault();
                    }
                    // Validar que el archivo esté realmente adjunto (fileInput.files.length === 0 significa que no hay archivo)
                    if (fileInput.files.length === 0) {
                        addMessageToPage('error', "✖ Error: La imagen no está adjunta correctamente a un campo para el producto " + (select.value || 'desconocido') + ".");
                        allValid = false;
                        event.preventDefault();
                    }
                });

                if (allValid) {
                    loadingOverlay.style.display = 'flex'; // Muestra el spinner
                }
            });

            // Ocultar el overlay de carga al recargar la página (después de un POST)
            window.addEventListener('load', function() {
                loadingOverlay.style.display = 'none';
                const messageContainer = document.querySelector('.message-container');
                if (window.location.search === '' && messageContainer && messageContainer.children.length === 0 && uploadsContainer.children.length === 0) {
                     addMessageToPage('error', "No hay imágenes seleccionadas. Haz clic en 'Seleccionar Imágenes en Lote' para empezar.");
                } else if (messageContainer && messageContainer.children.length > 0) {
                    setTimeout(() => {
                        messageContainer.innerHTML = ''; // Limpiamos los mensajes de PHP para futuras interacciones limpias
                    }, 5000); // 5 segundos para leer los mensajes de PHP
                }
            });

            // --- Lógica del Modal/Lightbox (Vista previa aumentada) ---
            closeModalBtn.addEventListener('click', function() {
                imageZoomModal.style.display = 'none';
            });

            // Cerrar el modal si se hace clic fuera de la imagen
            imageZoomModal.addEventListener('click', function(event) {
                if (event.target === imageZoomModal) { // Solo si se hizo clic en el fondo del modal, no en la imagen
                    imageZoomModal.style.display = 'none';
                }
            });

            // Cerrar el modal con la tecla ESC
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    imageZoomModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>