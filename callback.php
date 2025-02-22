<?php
$TOKEN = getenv("TELEGRAM_BOT_TOKEN"); // Token del bot

// Capturar la entrada de Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Registrar la entrada para depuración
file_put_contents("callback_log.txt", "📌 Callback recibido: " . json_encode($update, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

if (!$update || !isset($update["callback_query"])) {
    file_put_contents("callback_log.txt", "❌ Error: No hay callback_query en la solicitud.\n", FILE_APPEND);
    exit;
}

// Extraer datos del callback
$callbackData = $update["callback_query"]["data"];
$chatId = $update["callback_query"]["message"]["chat"]["id"];
$messageId = $update["callback_query"]["message"]["message_id"];
$user = $update["callback_query"]["from"];

// Obtener nombre del usuario (Si no tiene, usar "Administrador")
$adminName = isset($user["first_name"]) ? $user["first_name"] : "Administrador";
if (isset($user["username"])) {
    $adminName .= " (@" . $user["username"] . ")";
}

// Verificar que el callback data sea válido
if ($callbackData !== "completado" && $callbackData !== "rechazado") {
    file_put_contents("callback_log.txt", "❌ Error: callback_data desconocido ($callbackData).\n", FILE_APPEND);
    exit;
}

// Definir el nuevo mensaje basado en la acción del botón
$nuevoTexto = ($callbackData === "completado") 
    ? "✅ *Pago recibido.*\nAcción realizada por: " . $adminName 
    : "❌ *Pago rechazado.*\nAcción realizada por: " . $adminName;

// Construir la URL para editar el mensaje en Telegram
$url = "https://api.telegram.org/bot$TOKEN/editMessageCaption?" . http_build_query([
    "chat_id"    => $chatId,
    "message_id" => $messageId,
    "caption"    => $nuevoTexto,
    "parse_mode" => "Markdown"
]);

// Enviar la solicitud a Telegram
$response = file_get_contents($url);
file_put_contents("callback_log.txt", "📌 Respuesta de Telegram: " . $response . "\n", FILE_APPEND);

// Si hubo error en la solicitud, guardarlo en el log
if ($response === false) {
    file_put_contents("callback_log.txt", "❌ Error: No se pudo actualizar el mensaje en Telegram.\n", FILE_APPEND);
}

// 📌 Ahora enviamos el usuario a `procesar.php` para actualizar en Google Sheets
$procesarUrl = "https://github.com/winsgana/DEPOSITOS/blob/main/procesar.php"; // Reemplaza con tu dominio

$data = [
    "usuario" => $adminName,
    "callback" => $callbackData, // "completado" o "rechazado"
    "chat_id" => $chatId,
    "message_id" => $messageId
];

$options = [
    "http" => [
        "header"  => "Content-type: application/x-www-form-urlencoded",
        "method"  => "POST",
        "content" => http_build_query($data)
    ]
];

$context  = stream_context_create($options);
$procesarResponse = file_get_contents($procesarUrl, false, $context);

// Registrar respuesta en logs
file_put_contents("callback_log.txt", "📌 Respuesta de procesar.php: " . $procesarResponse . "\n", FILE_APPEND);

exit;
?>
