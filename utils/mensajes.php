<?php
function mensajeRecepcion($fecha, $monto) {
    return "📢 Confirmación de Solicitud de Depósito\n\n" .
           "🗓 Fecha: $fecha\n" .
           "💰 Monto: $monto BOB\n\n" .
           "Tu solicitud ha sido recibida con éxito y está en proceso. Te informaremos una vez que haya sido completada."
           "🔔 Recuerda que este canal es exclusivamente para notificaciones automáticas. Si necesitas asistencia, por favor contacta a nuestro equipo de soporte por los medios oficiales.";
}

function mensajeCompletado() {
    return "¡Es oficial! ✅ Tu solicitud está completamente lista.\n\n" .
           "Gracias por ser parte de Winsgana, donde cada jugada cuenta y cada momento puede ser épico.\n\n" .
           "🔥 Te deseamos la mejor de las suertes, porque la suerte premia a los valientes.\n\n" .
           "🔔 Recuerda: este es un canal de notificaciones automáticas.";
}

function mensajeRechazado() {
    return "⚠️ Tu solicitud de depósito ha sido rechazada.\n\n" .
           "Esto puede deberse a que no cumples con nuestras políticas de verificación de identidad (KYC) o porque los datos ingresados son incorrectos.\n\n" .
           "Para conocer más detalles y resolver esta situación, te pedimos que contactes a nuestro equipo de soporte:\n" .
           "📱 WhatsApp: [http://wa.me/59162162190]\n" .
           "📧 Correo: [soporte@winsgana.com]\n" .
           "🔔 Recuerda: este es un canal de notificaciones automáticas.";
}
?>
