<?php
// ... al final de app/helpers/auth_functions.php

/**
 * Envía una notificación por correo al administrador sobre una nueva cita.
 * @param array $appointment_data Los datos de la cita que se acaba de crear.
 * @return void
 */
function send_new_appointment_notification(array $appointment_data): void {
    $pdo = Database::getInstance();
    $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'admin_email'");
    $admin_email = $stmt->fetchColumn();

    if (!$admin_email) {
        error_log("No se pudo enviar la notificación de nueva cita: No se encontró 'admin_email' en site_settings.");
        return;
    }

    $subject = "Nueva Solicitud de Cita de: " . ($appointment_data['client_name'] ?? 'N/A');
    
    $body_html = "<h1>Nueva Solicitud de Cita Recibida</h1>";
    $body_html .= "<p>Has recibido una nueva solicitud de cita a través del sitio web.</p>";
    $body_html .= "<h2>Detalles del Cliente:</h2>";
    $body_html .= "<ul>";
    $body_html .= "<li><strong>Nombre:</strong> " . htmlspecialchars($appointment_data['client_name'] ?? 'N/A') . "</li>";
    $body_html .= "<li><strong>Teléfono:</strong> " . htmlspecialchars($appointment_data['client_phone'] ?? 'N/A') . "</li>";
    $body_html .= "<li><strong>Email:</strong> " . htmlspecialchars($appointment_data['client_email'] ?? 'N/A') . "</li>";
    $body_html .= "<li><strong>Dirección:</strong> " . htmlspecialchars($appointment_data['service_address'] ?? 'N/A') . "</li>";
    $body_html .= "</ul>";
    $body_html .= "<h2>Detalles del Servicio:</h2>";
    $body_html .= "<ul>";
    $body_html .= "<li><strong>Servicio Solicitado:</strong> " . htmlspecialchars($appointment_data['requested_service_key'] ?? 'N/A') . "</li>";
    $body_html .= "<li><strong>Fecha Preferida:</strong> " . htmlspecialchars($appointment_data['preferred_date'] ?? 'N/A') . "</li>";
    $body_html .= "<li><strong>Franja Horaria:</strong> " . htmlspecialchars($appointment_data['preferred_time_slot'] ?? 'N/A') . "</li>";
    $body_html .= "<li><strong>Notas del Cliente:</strong><br>" . nl2br(htmlspecialchars($appointment_data['problem_description'] ?? 'Sin notas.')) . "</li>";
    $body_html .= "</ul>";
    $body_html .= "<p>Por favor, accede al panel de administración para gestionar esta cita.</p>";

    // Generar una versión de texto plano para clientes de correo que no soportan HTML
    $body_text = strip_tags(str_replace('<br>', "\n", $body_html));

    // Usamos la función genérica que ya existe para el envío
    send_email_generic($admin_email, $subject, $body_html, $body_text);
}