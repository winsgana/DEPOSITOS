<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI"; // Token del bot

// Capturar la entrada de Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Verificar que la entrada es válida
if (!$update || !isset($update["callback_query"])) {
    exit;
}

// Extraer datos del callback
$callbackData = explode("|", $update["callback_query"]["data"]); // Separar acción y número de orden
$accion = $callbackData[0];
$ordenId = $callbackData[1] ?? "Desconocido";
$chatId = $update["callback_query"]["message"]["chat"]["id"];
$messageId = $update["callback_query"]["message"]["message_id"];
$user = $update["callback_query"]["from"];

// Obtener nombre del usuario que realizó la acción
$adminName = isset($user["first_name"]) ? $user["first_name"] : "Administrador";
if (isset($user["username"])) {
    $adminName .= " (@" . $user["username"] . ")";
}

// Determinar el mensaje según la acción
$estado = ($accion === "completado") ? "✅ COMPLETADO" : "❌ RECHAZADO";
$fechaAccion = date('Y-m-d H:i:s');

// Mensaje actualizado sin botones
$nuevoMensaje = "🆔 Número de Orden: `$ordenId`\n" .
                "👤 Administrador: $adminName\n" .
                "📅 Fecha de acción: $fechaAccion\n" .
                "$estado";

$url = "https://api.telegram.org/bot$TOKEN/editMessageText?" . http_build_query([
    "chat_id"    => $chatId,
    "message_id" => $messageId,
    "text"       => $nuevoMensaje,
    "parse_mode" => "Markdown"
]);

// Enviar la solicitud a Telegram
file_get_contents($url);
exit;
?>
