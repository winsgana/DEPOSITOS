<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI"; // Token del bot

// Capturar la entrada de Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Registrar la entrada para depuración
file_put_contents("callback_log.txt", "Callback recibido: " . json_encode($update, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

if (!$update || !isset($update["callback_query"])) {
    file_put_contents("callback_log.txt", "Error: No hay callback_query en la solicitud.\n", FILE_APPEND);
    exit;
}

// Extraer datos del callback
$callbackData = $update["callback_query"]["data"];
$chatId = $update["callback_query"]["message"]["chat"]["id"];
$messageId = $update["callback_query"]["message"]["message_id"];
$user = $update["callback_query"]["from"];

// Obtener nombre del usuario
$adminName = isset($user["first_name"]) ? $user["first_name"] : "Administrador";
if (isset($user["username"])) {
    $adminName .= " (@" . $user["username"] . ")";
}

// Definir el nuevo mensaje basado en la acción del botón
if ($callbackData == "completado") {
    $nuevoTexto = "✅ *Pago recibido.*\nAcción realizada por: " . $adminName;
} elseif ($callbackData == "rechazado") {
    $nuevoTexto = "❌ *Pago rechazado.*\nAcción realizada por: " . $adminName;
} else {
    file_put_contents("callback_log.txt", "Error: callback_data desconocido ($callbackData).\n", FILE_APPEND);
    exit;
}

   // Construir la URL para editar el mensaje en Telegram
$url = "https://api.telegram.org/bot$TOKEN/editMessageCaption?" . http_build_query([
    "chat_id"    => $chatId,
    "message_id" => $messageId,
    "caption"    => $nuevoTexto,
    "parse_mode" => "Markdown"
]);

// Enviar la solicitud a Telegram
$response = file_get_contents($url);
file_put_contents("callback_log.txt", "Respuesta de Telegram: " . $response . "\n", FILE_APPEND);

if ($response === false) {
    file_put_contents("callback_log.txt", "Error: No se pudo actualizar el mensaje en Telegram.\n", FILE_APPEND);
}

// Mostrar el contenido del log en la respuesta para depuración
echo json_encode([
    "log_content" => file_exists("callback_log.txt") ? file_get_contents("callback_log.txt") : "No se encontró el archivo de log"
], JSON_PRETTY_PRINT);
exit;
?>
