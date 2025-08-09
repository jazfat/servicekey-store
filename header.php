<?php
// includes/header.php

require_once 'includes/init.php';

$token = generate_csrf_token();
$queryParams = $_GET;

// Obtener la URL del logo de icono para móvil desde site_settings si existe, o usar un fallback
$site_icon_url = $site_settings['site_icon_url'] ?? 'assets/images/site_icon.png';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lang['store_title']); ?></title> 
    <meta name="description" content="Compra licencias originales de Windows, Office y Antivirus al mejor precio. Entrega inmediata y segura.">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php // <link rel="manifest" href="manifest.json"> ?> 
    
    <meta name="theme-color" content="#6a0dad">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <meta name="csrf-token" content="<?php echo htmlspecialchars($token); ?>">
    <!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '681678444923521');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=681678444923521&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
</head>
<body class="main-store">
    <header class="main-header">
        <div class="container header-container">
            <a href="<?php echo BASE_URL; ?>index.php" class="logo logo-desktop">
                <img src="<?php echo BASE_URL; ?><?php echo htmlspecialchars($site_settings['site_logo_url'] ?? 'assets/images/default_logo.png'); ?>" alt="Logo del Sitio">
            </a>

            <button class="menu-toggle-btn" aria-label="Abrir menú de navegación">
                <i class="bi bi-list"></i>
            </button>

            <a href="<?php echo BASE_URL; ?>index.php" class="logo logo-mobile">
                <img src="<?php echo BASE_URL; ?><?php echo htmlspecialchars($site_icon_url); ?>" alt="Logo icono del Sitio">
            </a>

            <nav class="main-nav">
                <a href="<?php echo BASE_URL; ?>index.php"><?php echo htmlspecialchars($lang['nav_home']); ?></a>
                <a href="<?php echo BASE_URL; ?>catalogo.php"><?php echo htmlspecialchars($lang['nav_catalog']); ?></a>
                <a href="<?php echo BASE_URL; ?>contacto.php"><?php echo htmlspecialchars($lang['nav_contact']); ?></a>
            </nav>

            <div class="header-search">
                <form action="<?php echo BASE_URL; ?>search.php" method="GET">
                    <input type="search" name="q" id="search-input" placeholder="Buscar productos..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" autocomplete="off" required>
                    <button type="submit" title="Buscar"><i class="bi bi-search"></i></button>
                    <div id="autocomplete-dropdown" class="autocomplete-dropdown"></div>
                </form>
            </div>

            <div class="header-actions">
                <div class="settings-selector">
                    <?php $queryParams['lang'] = 'es'; ?><a href="?<?php echo http_build_query($queryParams); ?>" class="<?php if($current_lang == 'es') echo 'active';?>">ES</a>
                    <span>|</span>
                    <?php $queryParams['lang'] = 'en'; ?><a href="?<?php echo http_build_query($queryParams); ?>" class="<?php if($current_lang == 'en') echo 'active';?>">EN</a>
                </div>
                <div class="settings-selector">
                    <?php unset($queryParams['lang']); ?>
                    <?php $queryParams['currency'] = 'USD'; ?><a href="?<?php echo http_build_query($queryParams); ?>" class="<?php if($current_currency == 'USD') echo 'active';?>">USD</a>
                    <span>|</span>
                    <?php $queryParams['currency'] = 'COP'; ?><a href="?<?php echo http_build_query($queryParams); ?>" class="<?php if($current_currency == 'COP') echo 'active';?>">COP</a>
                    <span>|</span>
                    <?php $queryParams['currency'] = 'VES'; ?><a href="?<?php echo http_build_query($queryParams); ?>" class="<?php if($current_currency == 'VES') echo 'active';?>">VES</a>
                </div>
                <div class="user-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo htmlspecialchars($_SESSION['role'] === 'admin' ? BASE_URL . 'admin/index.php' : BASE_URL . 'mi-cuenta.php'); ?>" title="Mi Cuenta"><i class="bi bi-person-fill"></i></a>
                        <a href="<?php echo BASE_URL; ?>logout.php" title="Salir"><i class="bi bi-box-arrow-right"></i></a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php"><?php echo htmlspecialchars($lang['nav_login']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="mobile-nav-panel">
        <button class="modal-close mobile-menu-close" aria-label="Cerrar menú">&times;</button>
        <div class="mobile-nav-content">
            <a href="<?php echo BASE_URL; ?>index.php" class="logo mobile-panel-logo">
                <img src="<?php echo BASE_URL; ?><?php echo htmlspecialchars($site_icon_url); ?>" alt="Logo icono del Sitio">
            </a>
            <nav class="mobile-main-nav">
                <a href="<?php echo BASE_URL; ?>index.php"><?php echo htmlspecialchars($lang['nav_home']); ?></a>
                <a href="<?php echo BASE_URL; ?>catalogo.php"><?php echo htmlspecialchars($lang['nav_catalog']); ?></a>
                <a href="<?php echo BASE_URL; ?>contacto.php"><?php echo htmlspecialchars($lang['nav_contact']); ?></a>
            </nav>

            <div class="mobile-settings-actions">
                <div class="settings-selector">
                    <?php $tempQueryParams = $_GET; ?>
                    <?php $tempQueryParams['lang'] = 'es'; ?><a href="<?php echo BASE_URL; ?>?<?php echo http_build_query($tempQueryParams); ?>" class="<?php if($current_lang == 'es') echo 'active';?>">ES</a>
                    <span>|</span>
                    <?php $tempQueryParams['lang'] = 'en'; ?><a href="<?php echo BASE_URL; ?>?<?php echo http_build_query($tempQueryParams); ?>" class="<?php if($current_lang == 'en') echo 'active';?>">EN</a>
                </div>
                <div class="settings-selector">
                    <?php unset($tempQueryParams['lang']); ?>
                    <?php $tempQueryParams['currency'] = 'USD'; ?><a href="<?php echo BASE_URL; ?>?<?php echo http_build_query($tempQueryParams); ?>" class="<?php if($current_currency == 'USD') echo 'active';?>">USD</a>
                    <span>|</span>
                    <?php $tempQueryParams['currency'] = 'COP'; ?><a href="<?php echo BASE_URL; ?>?<?php echo http_build_query($tempQueryParams); ?>" class="<?php if($current_currency == 'COP') echo 'active';?>">COP</a>
                    <span>|</span>
                    <?php $tempQueryParams['currency'] = 'VES'; ?><a href="<?php echo BASE_URL; ?>?<?php echo http_build_query($tempQueryParams); ?>" class="<?php if($current_currency == 'VES') echo 'active';?>">VES</a>
                </div>
                <div class="user-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo htmlspecialchars($_SESSION['role'] === 'admin' ? BASE_URL . 'admin/index.php' : BASE_URL . 'mi-cuenta.php'); ?>"><i class="bi bi-person-fill"></i> Mi Cuenta</a>
                        <a href="<?php echo BASE_URL; ?>logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php"><i class="bi bi-box-arrow-in-right"></i> <?php echo htmlspecialchars($lang['nav_login']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-nav-overlay"></div>

    <main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggleBtn = document.querySelector('.menu-toggle-btn');
    const mobileNavPanel = document.querySelector('.mobile-nav-panel');
    const mobileMenuCloseBtn = document.querySelector('.mobile-menu-close');
    const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');
    const body = document.body;

    function openMobileNav() {
        mobileNavPanel.classList.add('is-open');
        mobileNavOverlay.classList.add('is-visible');
        body.classList.add('mobile-nav-open');
    }

    function closeMobileNav() {
        mobileNavPanel.classList.remove('is-open');
        mobileNavOverlay.classList.remove('is-visible');
        body.classList.remove('mobile-nav-open');
    }

    if (menuToggleBtn) {
        menuToggleBtn.addEventListener('click', openMobileNav);
    }

    if (mobileMenuCloseBtn) {
        mobileMenuCloseBtn.addEventListener('click', closeMobileNav);
    }

    if (mobileNavOverlay) {
        mobileNavOverlay.addEventListener('click', closeMobileNav);
    }

    mobileNavPanel.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeMobileNav);
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && mobileNavPanel.classList.contains('is-open')) {
            closeMobileNav();
        }
    });

    // --- Búsqueda Dinámica (Autocomplete) ---
    const searchInput = document.getElementById('search-input');
    const autocompleteDropdown = document.getElementById('autocomplete-dropdown');
    let searchTimeout;

    if (searchInput && autocompleteDropdown) {
        searchInput.addEventListener('input', function() {
            const query = this.value;

            // Limpiar cualquier timeout anterior para evitar peticiones rápidas
            clearTimeout(searchTimeout);

            if (query.length < 2) { // Mínimo 2 caracteres para empezar a buscar sugerencias
                autocompleteDropdown.innerHTML = ''; // Limpiar dropdown si el query es muy corto
                autocompleteDropdown.style.display = 'none';
                return;
            }

            // Esperar un poco antes de enviar la petición (debounce)
            searchTimeout = setTimeout(() => {
                // CAMBIO DE RUTA AQUÍ: Usar BASE_URL para la ruta absoluta
                fetch(`<?php echo BASE_URL; ?>api/search_suggestions.php?q=${encodeURIComponent(query)}`) 
                    .then(response => {
                        if (!response.ok) {
                            console.error('Error fetching suggestions:', response.statusText);
                            return Promise.reject('Server error');
                        }
                        return response.json();
                    })
                    .then(data => {
                        autocompleteDropdown.innerHTML = ''; // Limpiar sugerencias anteriores
                        if (data.length > 0) {
                            data.forEach(product => {
                                const item = document.createElement('div');
                                item.classList.add('autocomplete-item');
                                // RUTA ABSOLUTA para la imagen del producto en la sugerencia
                                item.innerHTML = `
                                    <img src="<?php echo BASE_URL; ?>${product.image_url}" alt="${product.name}" class="autocomplete-item-image">
                                    <span>${product.name}</span>
                                `;
                                item.addEventListener('click', function() {
                                    // RUTA ABSOLUTA para la redirección al producto
                                    window.location.href = `<?php echo BASE_URL; ?>producto.php?id=${product.id}`;
                                });
                                autocompleteDropdown.appendChild(item);
                            });
                            autocompleteDropdown.style.display = 'block';
                        } else {
                            autocompleteDropdown.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error en la búsqueda de sugerencias:', error);
                        autocompleteDropdown.style.display = 'none';
                    });
            }, 300); // Retraso de 300ms
        });

        // Ocultar el dropdown cuando el input pierde el foco (con un pequeño retraso)
        // para permitir el clic en los elementos del dropdown
        searchInput.addEventListener('blur', function() {
            setTimeout(() => {
                autocompleteDropdown.style.display = 'none';
            }, 150); 
        });

        // Mostrar el dropdown de nuevo si se hace foco y hay texto
        searchInput.addEventListener('focus', function() {
            if (this.value.length >= 2 && autocompleteDropdown.children.length > 0) {
                autocompleteDropdown.style.display = 'block';
            }
        });
    }
});
</script>