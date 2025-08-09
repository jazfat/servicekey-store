// ================================================================
    // === MÓDULO 1: SLIDER DINÁMICO DE LA PÁGINA DE INICIO ===
    // ================================================================
    const sliderContainer = document.querySelector('.dynamic-slider-container');
    if (sliderContainer) {
        const slides = sliderContainer.querySelectorAll('.slide');
        const dots = sliderContainer.querySelectorAll('.dot');
        const prevBtn = sliderContainer.querySelector('.prev');
        const nextBtn = sliderContainer.querySelector('.next');

        if (slides.length > 1 && dots.length && prevBtn && nextBtn) {
            let currentSlide = 0;
            let slideInterval;

            const goToSlide = (slideIndex) => {
                if (!slides[currentSlide] || !dots[currentSlide]) return;
                slides[currentSlide].classList.remove('active');
                dots[currentSlide].classList.remove('active');
                currentSlide = slideIndex;
                slides[currentSlide].classList.add('active');
                dots[currentSlide].classList.add('active');
            };

            const nextSlide = () => { goToSlide((currentSlide + 1) % slides.length); };
            const prevSlide = () => { goToSlide((currentSlide - 1 + slides.length) % slides.length); };

            // === INICIO DE LA CORRECCIÓN ===
            // Definimos la función que detiene el slideshow
            const stopSlideShow = () => { clearInterval(slideInterval); }; 
            // === FIN DE LA CORRECCIÓN ===

            const startSlideShow = () => { 
                // La movemos aquí para asegurarnos de que siempre limpie el intervalo anterior antes de crear uno nuevo.
                stopSlideShow(); 
                slideInterval = setInterval(nextSlide, 7000); 
            };
            
            nextBtn.addEventListener('click', () => { nextSlide(); stopSlideShow(); });
            prevBtn.addEventListener('click', () => { prevSlide(); stopSlideShow(); });
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => { goToSlide(index); stopSlideShow(); });
            });
            
            // Inicia el slider automático al cargar la página
            startSlideShow();
        }
    
    }


    // ================================================================
    // === MÓDULO 2: CARRITO DE COMPRAS (VERSIÓN CON DEPURACIÓN) ===
    // ================================================================
    const floatingCartBtn = document.getElementById('floating-cart-btn');
    if (floatingCartBtn) {
        console.log("Depuración: Botón de carrito flotante encontrado. Iniciando módulo.");

        const cartPanel = document.getElementById('cart-slideout-panel');
        const cartOverlay = document.getElementById('cart-overlay');
        const closeCartBtn = cartPanel ? cartPanel.querySelector('.modal-close') : null;
        const cartBody = document.getElementById('cart-panel-body');
        const cartCountSpan = document.getElementById('floating-cart-count');
        const cartTotalPriceSpan = document.getElementById('cart-total-price');

        const toggleCartPanel = () => {
            if (cartPanel) cartPanel.classList.toggle('is-open');
            if (cartOverlay) cartOverlay.classList.toggle('is-visible');
        };

        const renderCart = (cartData) => {
    console.log("Paso 1: Iniciando renderCart. Datos recibidos:", cartData);

    if (cartCountSpan) {
        cartCountSpan.textContent = cartData.item_count || 0;
        cartCountSpan.style.display = (cartData.item_count > 0) ? 'flex' : 'none';
        console.log("Paso 2: Contador del icono actualizado a:", cartData.item_count || 0);
    } else {
        console.error("ERROR CRÍTICO: Elemento 'cartCountSpan' (ID: floating-cart-count) no fue encontrado en el HTML.");
    }

    if (cartBody) {
        if (!cartData.items || cartData.items.length === 0) {
            cartBody.innerHTML = `<p class="cart-empty-message">Tu carrito está vacío.</p>`;
            console.log("Paso 3: El carrito está vacío. Mostrando mensaje.");
        } else {
            const itemsHTML = cartData.items.map(item => `
                <div class="cart-panel-item">
                    <img src="${item.image_url}" alt="${item.name}" class="cart-item-image">
                    <div class="item-details"><p class="item-name">${item.name}</p><p class="item-subtotal">${item.subtotal_formatted}</p></div>
                    <div class="item-quantity"><span>Cant: ${item.quantity}</span></div>
                    <button class="btn-remove-item" data-product-id="${item.id}" title="Eliminar"><i class="bi bi-x-circle-fill"></i></button>
                </div>`).join('');
            
            cartBody.innerHTML = itemsHTML;
            console.log("Paso 3: Carrito con items. HTML generado y dibujado en 'cartBody'.");
        }
    } else {
        console.error("ERROR CRÍTICO: Elemento 'cartBody' (ID: cart-panel-body) no fue encontrado. Este es probablemente el problema principal.");
    }
    
    if (cartTotalPriceSpan) {
        cartTotalPriceSpan.textContent = cartData.total_formatted;
        console.log("Paso 4: Precio total actualizado a:", cartData.total_formatted);
    } else {
        console.error("ERROR CRÍTICO: Elemento 'cartTotalPriceSpan' (ID: cart-total-price) no fue encontrado.");
    }

    // ⭐ ¡SECCIÓN A AGREGAR! ⭐
    // Aquí es donde agregamos la lógica para mostrar u ocultar el botón de pago.
    const checkoutButton = document.querySelector('.cart-panel-footer .btn');
    if (checkoutButton) {
        // Si hay items en el carrito (cartData.items.length > 0), mostramos el botón.
        if (cartData.items && cartData.items.length > 0) {
            checkoutButton.style.display = 'block'; 
        } else {
            // Si no hay items, lo ocultamos.
            checkoutButton.style.display = 'none';
        }
    }

    console.log("Paso 5: renderCart finalizado.");
};


        const updateCartView = async () => {
            console.log("Iniciando updateCartView para buscar datos del carrito...");
            try {
                const response = await fetch('api/get_cart_data.php');
                if (!response.ok) throw new Error('Respuesta de red no fue OK.');
                const cartData = await response.json();

                // --- INICIO DE LA NUEVA LÓGICA ---
                // Verificamos si el carrito está vacío Y si estamos en la página de checkout.
                const isCheckoutPage = document.getElementById('checkout-form');
                if (cartData.item_count === 0 && isCheckoutPage) {
                    // Si ambas condiciones son ciertas, redirigimos al catálogo.
                    alert("Tu carrito está vacío. Serás redirigido al catálogo.");
                    window.location.href = 'catalogo.php';
                    return; // Detenemos la ejecución para no intentar renderizar nada.
                }
                // --- FIN DE LA NUEVA LÓGICA ---

                renderCart(cartData);
            } catch (error) {
                console.error('Error al actualizar el carrito:', error);
            }
        };


        const handleCartAction = async (formData) => {
            try {
                const response = await fetch('cart_manager.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('Network response was not ok.');
                const result = await response.json();
                if (result.success) {
                    updateCartView();
                } else {
                    alert(result.message || 'Ocurrió un error.');
                }
            } catch (error) {
                console.error('Error en la acción del carrito:', error);
                alert('Ocurrió un error de conexión.');
            }
        };

        floatingCartBtn.addEventListener('click', () => {
            updateCartView();
            toggleCartPanel();
        });

        if (cartOverlay) cartOverlay.addEventListener('click', toggleCartPanel);
        if (closeCartBtn) closeCartBtn.addEventListener('click', toggleCartPanel);

        if (cartBody) {
            cartBody.addEventListener('click', (event) => {
                if (event.target.classList.contains('btn-remove-item')) {
                    const productId = event.target.dataset.productId;
                    const formData = new FormData();
                    formData.append('action', 'remove');
                    formData.append('product_id', productId);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="csrf_token"]')?.value;

if (csrfToken) {
    formData.append('csrf_token', csrfToken);
} else {
    // Si no se encuentra el token, es mejor detenerse y avisar.
    console.error('Error de seguridad: No se pudo encontrar el token CSRF para la acción del carrito.');
    alert('Error de seguridad. No se pudo procesar la solicitud.');
    return; // Detiene la ejecución para no enviar una petición incorrecta.
}
                    handleCartAction(formData);
                }
            });
        }
        
        // ================================================================
        // === CORRECCIÓN DEFINITIVA PARA EVITAR RECARGA DE PÁGINA ===
        // ================================================================
        // Buscamos todos los formularios que apuntan a cart_manager.php y les añadimos el listener directamente.
        document.querySelectorAll('form[action="cart_manager.php"]').forEach(form => {
            form.addEventListener('submit', (event) => {
                // Prevenimos el comportamiento por defecto del formulario (la recarga)
                event.preventDefault(); 
                
                const formData = new FormData(form);
                handleCartAction(formData);

                // Disparamos la animación del botón flotante
                if (floatingCartBtn) {
                    floatingCartBtn.classList.add('is-animating');
                    setTimeout(() => floatingCartBtn.classList.remove('is-animating'), 600);
                }
            });
        });

        updateCartView(); // Carga inicial del estado del carrito
    }
    
    // ================================================================
// === MÓDULO 3: MODAL SEGURO PARA VER LICENCIAS (MEJORADO)   ===
// ================================================================
const licenseModal = document.getElementById('license-modal');
if (licenseModal) {
    const modalKeySpan = document.getElementById('modal-license-key');
    const modalCountdownSpan = document.getElementById('modal-countdown');
    const modalCloseBtn = licenseModal.querySelector('.modal-close');
    const modalCopyBtn = document.getElementById('modal-copy-btn');
    let countdownInterval;

    const openModal = () => { licenseModal.style.display = 'flex'; };
    const closeModal = () => {
        clearInterval(countdownInterval);
        licenseModal.style.display = 'none';
        if (modalKeySpan) modalKeySpan.textContent = '';
    };

    // Escucha en el botón de ver clave en la tabla de mi-cuenta.php y view_order.php del admin
    document.querySelectorAll('.btn-view-key').forEach(button => { // Cambiado de .view-key a .btn-view-key
        button.addEventListener('click', async (e) => {
            if (!confirm('¿Estás seguro de que quieres revelar esta clave? Se mostrará por 15 segundos.')) return;
            
            // Obtener el product_id desde el botón (si viene de mi-cuenta.php)
            // O el order_item_id (si viene del admin/view_order.php)
            const itemId = e.currentTarget.dataset.itemId; 
            
            // Obtener el token CSRF del formulario o de la meta tag si lo tienes
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content;
            
            if (!csrfToken) {
                alert('Error de seguridad: CSRF token no encontrado.');
                console.error('CSRF token is missing.');
                return;
            }

            try {
                const response = await fetch(`api/get_license_key.php`, {
                    method: 'POST', // Cambiado a POST
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ // Enviar datos como JSON
                        item_id: itemId,
                        csrf_token: csrfToken
                    })
                });
                const data = await response.json();

                if (data.success) {
                    modalKeySpan.textContent = data.license_key; // Cambiado de 'key' a 'license_key'
                    openModal();
                    
                    let seconds = 15;
                    modalCountdownSpan.textContent = seconds;
                    countdownInterval = setInterval(() => {
                        seconds--;
                        modalCountdownSpan.textContent = seconds;
                        if (seconds <= 0) closeModal();
                    }, 1000);
                } else {
                    alert(data.message || 'Error al obtener la clave.');
                    console.error('Error al desencriptar la clave:', data.message);
                }
            } catch (error) { 
                alert('Error de red al solicitar la clave.');
                console.error('Fetch error:', error); 
            }
        });
    });

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    licenseModal.addEventListener('click', (e) => { if (e.target === licenseModal) closeModal(); });
    
    if (modalCopyBtn) {
        modalCopyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(modalKeySpan.textContent).then(() => {
                alert('¡Clave copiada al portapapeles!');
            }, () => { alert('Error al copiar la clave.'); });
        });
    }
}
    // ================================================================
    // === MÓDULO 4: LÓGICA DE LA PÁGINA DE CHECKOUT              ===
    // ================================================================
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
        const manualInstructions = document.getElementById('manual-payment-instructions');
        if (paymentMethodRadios.length > 0 && manualInstructions) {
            const toggleInstructions = () => {
                const selected = document.querySelector('input[name="payment_method"]:checked');
                manualInstructions.style.display = (selected && selected.value === 'manual') ? 'block' : 'none';
            };
            paymentMethodRadios.forEach(radio => radio.addEventListener('change', toggleInstructions));
            toggleInstructions();
        }

        const applyCouponBtn = document.getElementById('apply-coupon-btn');
        if (applyCouponBtn) {
            applyCouponBtn.addEventListener('click', async () => {
                const couponCodeInput = document.getElementById('coupon-code');
                const feedbackDiv = document.getElementById('coupon-feedback');
                const formData = new FormData();
                formData.append('code', couponCodeInput.value);
                const csrfToken = checkoutForm.querySelector('input[name="csrf_token"]')?.value;
                if(csrfToken) formData.append('csrf_token', csrfToken);

                try {
                    const response = await fetch('api/apply_coupon.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    
                    feedbackDiv.textContent = result.message;
                    feedbackDiv.style.color = result.success ? 'var(--success-color)' : 'var(--error-color)';

                    if (result.success) {
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error al aplicar cupón:', error);
                }
            });
        }
    }

    // En tu archivo js/main.js
$(document).ready(function() {
    $('.search-select').select2({
        placeholder: 'Buscar productos...',
        minimumInputLength: 2, // Empieza a buscar después de 2 caracteres
        language: {
            inputTooShort: function() {
                return 'Por favor, introduce 2 o más caracteres';
            },
            noResults: function() {
                return 'No se encontraron resultados';
            }
        },
        ajax: {
            url: 'api/search_products.php', // El nuevo archivo PHP que creaste
            dataType: 'json',
            delay: 250, // Espera 250ms después de que el usuario deja de escribir
            data: function (params) {
                return {
                    q: params.term // El término de búsqueda que se envía a la API
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });

    // Maneja la redirección cuando se selecciona un resultado
    $('.search-select').on('select2:select', function (e) {
        var data = e.params.data;
        // Puedes redirigir a una página de detalles del producto o a la página de búsqueda
        window.location.href = 'search.php?q=' + encodeURIComponent(data.text);
    });
});

