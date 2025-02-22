<?php
$TOKEN = getenv("TELEGRAM_BOT_TOKEN"); // Token del bot

// Capturar la entrada de Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Registrar la entrada para depuración
file_put_contents("callback_log.txt", "Callback recibido: " . json_encode($update, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

if (!$update || !isset($update["callback_query"])) {
    file_put_contents("callback_log.txt", "❌ Error: No hay callback_query en la solicitud.\n", FILE_APPEND);
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

// 📌 Enviar información del pago a `procesar.php`
$procesarUrl = "https://tu-servidor.com/procesar.php"; // Reemplaza con tu dominio

$data = [
    "usuario" => $adminName,
    "callback" => $callbackData // Indica si fue "completado" o "rechazado"
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
