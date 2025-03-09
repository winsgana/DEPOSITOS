<?php
function mensajeRecepcion($fecha, $monto) {
    return "✅ ¡Recibimos tu solicitud de Depósito!\n" .
           "🗓 Fecha: $fecha\n" .
           "💰 Monto: $monto BOB\n\n" .
           "🔔 Estamos revisando tu solicitud. No te preocupes, te notificaremos una vez que esté completada.\n\n" .
           "🔔 Recuerda: este es un canal de notificaciones automáticas.";
}

function mensajeCompletado() {
    return "¡Es oficial! Tu solicitud ha sido aprobada.\n\n" .
           "Tu saldo ya está disponible, ¡prepárate para jugar! En WinsGana, la suerte está de tu lado, porque solo los valientes son los premiados.\n\n" .
           "🔔 Recuerda: este es un canal de notificaciones automáticas.";
}

function mensajeRechazado() {
    return "⚠️ Tu solicitud no pudo ser aprobada.\n\n" .
           "Por favor, verifica que tus datos coincidan y que tu cuenta en Winsgana esté verificada.\n\n" .
           "📞 Contáctanos para más información:\n" .
           "📱 WhatsApp: +59162162190\n" .
           "📧 Correo: soporte@winsgana.com\n\n" .
           "🔔 Recuerda: este es un canal de notificaciones automáticas.";
}
?>
