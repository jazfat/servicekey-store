<?php
// lang/es.php

$lang = [
    // Globales y Tienda
    'store_title' => 'ServiceKey Store - Licencias Digitales',
    'site_description' => 'Compra licencias originales de Windows, Office y Antivirus al mejor precio. Entrega inmediata y segura.',

    // Navegación principal
    'nav_home' => 'Inicio',
    'nav_catalog' => 'Catálogo',
    'nav_contact' => 'Contacto',
    'nav_login' => 'Iniciar Sesión',

    // Catálogo y Productos
    'catalog_title' => 'Catálogo de Productos',
    'add_to_cart_button' => '¡Comprar ahora!',
    'out_of_stock' => 'Agotado',
    'featured_products_title' => 'Nuestras Licencias Destacadas',

    // Búsqueda
    'search_placeholder' => 'Buscar productos...',
    'search_button' => 'Buscar',
    'search_results_for' => 'Resultados de búsqueda para: ',
    'search_no_query' => 'Por favor, introduce un término de búsqueda para comenzar.',
    'search_no_results' => 'No se encontraron productos que coincidan con tu búsqueda. Intenta con otras palabras.',
    'search_error' => 'Hubo un error al realizar la búsqueda.',

    // Carrito y Checkout
    'cart_empty_checkout_redirect' => 'Tu carrito está vacío. No se puede proceder al pago.',
    'checkout_validation_required_fields' => 'Por favor, completa todos los campos obligatorios.',
    'checkout_validation_invalid_email' => 'El formato del correo electrónico no es válido.',
    'bank_transfer_success_message' => 'Orden creada con éxito. Por favor, completa la transferencia bancaria con los siguientes datos:',
    'checkout_invalid_payment_method' => 'Método de pago no válido.',
    'checkout_title' => 'Finalizar Compra',
    'billing_details_title' => 'Detalles de Facturación y Envío',
    'full_name_label' => 'Nombre Completo',
    'email_label' => 'Correo Electrónico',
    'address_label' => 'Dirección',
    'city_label' => 'Ciudad',
    'zip_code_label' => 'Código Postal',
    'country_label' => 'País',
    'phone_label' => 'Teléfono (WhatsApp)',
    'order_summary_title' => 'Resumen del Pedido',
    'cart_empty_message' => 'Tu carrito está vacío.',
    'subtotal_label' => 'Subtotal',
    'shipping_label' => 'Envío',
    'free_label' => 'Gratis',
    'taxes_label' => 'Impuestos',
    'total_label' => 'Total',
    'payment_method_title' => 'Método de Pago',
    'bank_transfer_label' => 'Transferencia Bancaria',
    'place_order_button' => 'Realizar Pedido',
    
    // Login / Registro
    'login_title' => 'Iniciar Sesión',
    'login_error_credentials' => 'Correo o contraseña incorrectos.',
    'login_error_generic' => 'Hubo un error en el servidor. Inténtalo de nuevo.',
    'register_email' => 'Correo Electrónico',
    'register_password' => 'Contraseña',
    'forgot_password' => '¿Olvidaste tu contraseña?',
    'login_no_account' => '¿No tienes una cuenta?',
    'login_register_here' => 'Regístrate aquí',
    'already_have_account' => '¿Ya tienes una cuenta?',
    'register_title' => 'Crear una Cuenta',
    'register_name_label' => 'Nombre',
    'register_confirm_password' => 'Confirmar Contraseña',
    'register_button' => 'Registrarse',
    'register_success_message' => '¡Registro exitoso! Ya puedes iniciar sesión.',
    'register_error_email_exists' => 'El correo electrónico ya está registrado.',
    'register_error_password_mismatch' => 'Las contraseñas no coinciden.',
    'register_error_generic' => 'Hubo un error al registrarte. Inténtalo de nuevo.',

    // Reseñas
    'reviews_section_title' => 'Reseñas de Clientes',
    'reviews_no_reviews' => 'Este producto aún no tiene reseñas. ¡Sé el primero en dejar tu opinión!',
    'reviews_leave_review' => 'Deja tu Reseña',
    'reviews_already_reviewed' => 'Ya has enviado una reseña para este producto. Gracias por tu opinión.',
    'reviews_must_purchase' => 'Necesitas haber comprado este producto para dejar una reseña.',
    'reviews_login_to_review' => 'Por favor, <a href="login.php">inicia sesión</a> o <a href="registro.php">regístrate</a> para poder dejar una reseña después de tu compra.',
    'reviews_your_rating' => 'Tu Calificación:',
    'reviews_your_comment' => 'Tu Comentario (Opcional):',
    'reviews_submit_review' => 'Enviar Reseña',
    'reviews_published_on' => 'Publicado el',

    // Botones Generales
    'button_save' => 'Guardar',
    'button_edit' => 'Editar',
    'button_delete' => 'Eliminar',
    'button_view' => 'Ver',
    'button_approve' => 'Aprobar',
    'button_reject' => 'Rechazar',
    'button_create_new' => 'Crear Nuevo',
    'button_back_to_catalog' => 'Volver al Catálogo',
    'button_continue_shopping' => 'Continuar Comprando',
    'button_go_to_checkout' => 'Ir a Pagar',
    'button_view_cart' => 'Ver Carrito',

    // Admin Panel (solo texto de navegación, los títulos de página se manejan por $page_title)
    'admin_nav_dashboard' => 'Dashboard',
    'admin_nav_products' => 'Productos',
    'admin_nav_categories' => 'Categorías',
    'admin_nav_licenses' => 'Licencias',
    'admin_nav_orders' => 'Órdenes',
    'admin_nav_reviews' => 'Reseñas',
    'admin_nav_users' => 'Usuarios',
    'admin_nav_settings' => 'Ajustes',
    'admin_nav_coupons' => 'Cupones',
    'admin_nav_slides' => 'Slides',
    'admin_nav_logos' => 'Logos de Marcas',
    'admin_nav_pages' => 'Páginas',
    'admin_nav_view_store' => 'Ver Tienda',
    'admin_nav_logout' => 'Salir',

    // Status Badges (si se usan directamente los textos del idioma)
    'status_completed' => 'Completado',
    'status_pending' => 'Pendiente',
    'status_in_verification' => 'En Verificación',
    'status_cancelled' => 'Cancelado',
    'status_processing' => 'Procesando',
    'status_paid' => 'Pagado',
    'status_sold' => 'Vendida',
    'status_available' => 'Disponible',

    // Mensajes generales del sistema (no relacionados con acciones específicas)
    'system_error_generic' => 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo más tarde.',
    'site_title_default' => 'Tienda Online', // Si no se puede cargar de la DB


  
  'checkout_copy_button' => 'Copiar',
    'checkout_copied_text' => 'Copiado!',
    'checkout_copy_error' => 'Error al copiar. Por favor, copia manualmente.',

   
    'nav_my_account' => 'Mi Cuenta', // Usado en header para el enlace de usuario
    'nav_logout' => 'Salir',

    // --- Página de Inicio (index.php) ---
   
    'no_featured_products' => 'No hay productos destacados disponibles en este momento.',
    
    // --- Carrito de Compras ---
    'cart_title' => 'Carrito de Compras',
    'cart_empty' => 'Tu carrito está vacío.',
    'cart_product' => 'Producto',
    'cart_quantity' => 'Cantidad',
    'cart_price' => 'Precio',
    'cart_subtotal' => 'Subtotal',
    'cart_total' => 'Total',
    'cart_update' => 'Actualizar Carrito',
    'cart_checkout' => 'Proceder al Pago',
    'cart_continue_shopping' => 'Continuar Comprando',
    'cart_remove' => 'Eliminar',
    
    // --- Checkout (checkout.php) ---
    'checkout_page_title' => 'Finalizar Compra',
    'checkout_billing_details' => 'Detalles de Facturación',
    'checkout_verify_data' => 'Verifica que tus datos de facturación sean correctos. Puedes actualizarlos en',
    'checkout_my_account' => 'Mi Cuenta',
    'checkout_name_email_required' => 'Tu nombre y correo son necesarios para la factura.',
    'checkout_name' => 'Nombre',
    'checkout_email' => 'Correo Electrónico',
    'checkout_email_already_registered' => 'Este correo electrónico ya está asociado a una cuenta.',
    'checkout_have_account' => '¿Ya tienes una cuenta?',
    'checkout_guest_warning' => 'Si continúas como invitado, podrás finalizar tu compra, pero el historial de esta compra no será visible en tu cuenta si inicias sesión más tarde, a menos que te registres con este mismo correo si aún no tienes cuenta.',
    'checkout_continue_as_guest' => 'Continuar como Invitado',
    'checkout_your_order' => 'Tu Pedido',
    'checkout_subtotal' => 'Subtotal',
    'checkout_discount' => 'Descuento',
    'checkout_total' => 'Total',
    'checkout_coupon_code' => 'Código de Cupón',
    'checkout_apply' => 'Aplicar',
    'checkout_payment_method' => 'Método de Pago',
    'checkout_manual_payment' => 'Pago Manual', // General para pagos manuales
    'checkout_pagomovil' => 'PagoMóvil', // Para la sección específica de PagoMóvil
    'checkout_binance_pay_manual' => 'Binance (USDT)', // Para la sección específica de Binance manual
    'checkout_upload_receipt' => 'Subir Comprobante de Pago',
    'checkout_manual_instructions_upload' => 'Por favor, realiza la transferencia o pago a una de las siguientes cuentas y sube tu comprobante para que podamos verificarlo.',
    'checkout_confirm_manual_order' => 'Confirmar Pedido Manual',
    'checkout_processing' => 'Procesando', // Texto para spinner
  

    // --- Mensajes de Error/Feedback en Checkout ---
    'checkout_error_name_email_empty' => 'Por favor, completa tus detalles de facturación (Nombre y Correo).',
    'checkout_error_email_exists' => 'Por favor, inicie sesión o haga clic en "Continuar como Invitado" para proceder.',
    'checkout_error_create_order' => 'Error al crear la orden de pago.',
    'checkout_error_server_processing' => 'Error del servidor al procesar el pago.',
    'checkout_error_process_payment' => 'No se pudo procesar tu pago.',
    'checkout_error_unexpected_paypal' => 'Ocurrió un error inesperado con PayPal.',
    'checkout_error_upload_receipt_required' => 'Es obligatorio subir un comprobante de pago.',
    'checkout_error_process_order' => 'Ocurrió un error al procesar tu pedido.',
    'checkout_error_network_manual_payment' => 'Error de conexión al enviar el pago manual.',
    'checkout_error_enter_coupon' => 'Por favor, introduce un código de cupón.',
    'checkout_applying_coupon' => 'Aplicando cupón',
    'checkout_error_network_coupon' => 'Error de conexión al aplicar el cupón.',
    
    // --- Página de Agradecimiento (gracias.php) ---
    'thanks_title' => '¡Gracias por tu compra!',
    'thanks_order_id' => 'Tu número de pedido es',
    'thanks_email_sent' => 'Hemos enviado un correo con los detalles de tu compra y licencias a',
    'thanks_check_spam' => 'Por favor, revisa tu bandeja de entrada o la carpeta de spam.',
    'thanks_pending_stock' => 'Tu pedido incluye productos pendientes de stock. Te notificaremos cuando las licencias estén listas.',
    'thanks_contact_support' => 'Si tienes alguna pregunta, no dudes en contactar a nuestro soporte.',
    'thanks_go_home' => 'Volver al inicio',
    'thanks_view_order' => 'Ver mi pedido', // Para clientes
    
    // --- Login (login.php) ---
   
    'login_email' => 'Correo Electrónico',
    'login_password' => 'Contraseña',
    'login_button' => 'Ingresar',
    'login_forgot_password' => '¿Olvidaste tu contraseña?',
    
    'login_back_to_store' => 'Volver a la Tienda',
   
    
    // --- Registro (register.php) ---
   
    'register_name' => 'Nombre Completo',
   
   
    'register_already_account' => '¿Ya tienes una cuenta?',
    
    // --- Recuperación de Contraseña (forgot_password.php) ---
    'forgot_password_title' => 'Recuperar Contraseña',
    'forgot_password_instructions' => 'Introduce tu correo electrónico para recibir un enlace de recuperación.',
    'forgot_password_send_link' => 'Enviar Enlace de Recuperación',
    'forgot_password_email_sent' => 'Si tu correo está registrado, recibirás un enlace de recuperación en breve.',
    
    // --- Restablecer Contraseña (reset_password.php) ---
    'reset_password_title' => 'Restablecer Contraseña',
    'reset_password_new' => 'Nueva Contraseña',
    'reset_password_confirm_new' => 'Confirmar Nueva Contraseña',
    'reset_password_button' => 'Restablecer Contraseña',
    'reset_password_success' => 'Tu contraseña ha sido restablecida exitosamente. Ahora puedes iniciar sesión.',
    'reset_password_invalid_token' => 'Token inválido o expirado.',

    // --- Mi Cuenta (mi-cuenta.php) ---
    'my_account_title' => 'Mi Cuenta',
    'my_account_orders' => 'Mis Órdenes',
    'my_account_profile' => 'Mi Perfil',
    'my_account_settings' => 'Ajustes',
    'my_account_addresses' => 'Direcciones', // Si se implementa
    'my_account_no_orders' => 'No tienes órdenes registradas.',
    'my_account_order_id' => 'Orden #',
    'my_account_date' => 'Fecha',
    'my_account_total' => 'Total',
    'my_account_status' => 'Estado',
    'my_account_view_details' => 'Ver Detalles',
    'my_account_order_details' => 'Detalles de la Orden',
    'my_account_product' => 'Producto',
    'my_account_license_key' => 'Clave de Licencia',
    'my_account_quantity' => 'Cantidad',
    'my_account_price' => 'Precio',
    'my_account_status_info_pending' => 'Pendiente de asignación',
    'my_account_status_info_verifying' => 'Verificando pago',
    'my_account_status_info_error' => 'Error en la clave', // si alguna clave tiene error
    'my_account_update_profile' => 'Actualizar Perfil',
    'my_account_change_password' => 'Cambiar Contraseña',
    'my_account_current_password' => 'Contraseña Actual',
    'my_account_new_password' => 'Nueva Contraseña',
    'my_account_confirm_new_password' => 'Confirmar Nueva Contraseña',
    'my_account_password_updated' => 'Contraseña actualizada exitosamente.',
    'my_account_profile_updated' => 'Perfil actualizado exitosamente.',
    'my_account_old_password_incorrect' => 'La contraseña actual es incorrecta.',
    'my_account_password_mismatch' => 'Las nuevas contraseñas no coinciden.',
    
    // --- Contacto (contacto.php) ---
    'contact_title' => 'Contáctanos',
    'contact_message' => 'Mensaje',
    'contact_send_message' => 'Enviar Mensaje',
    'contact_form_success' => '¡Gracias! Tu mensaje ha sido enviado.',
    'contact_form_error' => 'Ocurrió un error al enviar tu mensaje. Inténtalo de nuevo.',

    // --- Mensajes de Base de Datos y Sistema ---
    'db_error_generic' => 'Hubo un problema al conectar con la base de datos. Por favor, contacta a soporte.',
    'security_error_csrf' => 'Error de seguridad. Por favor, intenta de nuevo. Si el problema persiste, limpia la caché de tu navegador.',
    'email_error_send' => 'Error al enviar el correo electrónico. Inténtalo de nuevo más tarde.',
    'email_verification_success' => 'Tu correo ha sido verificado. Ahora puedes iniciar sesión.',
    'email_verification_error' => 'Error al verificar tu correo o el enlace es inválido/expirado.',
    
    // --- Paginación ---
    'pagination_previous' => 'Anterior',
    'pagination_next' => 'Siguiente',

    // --- Varias ---
    'general_yes' => 'Sí',
    'general_no' => 'No',
    'general_na' => 'N/A', // Not Applicable
    // ... el resto de tus claves ...

];