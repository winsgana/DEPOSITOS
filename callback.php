<?php
$TOKEN = getenv("7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI"); // Token del bot

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

// Obtener nombre del usuario
$adminName = isset($user["first_name"]) ? $user["first_name"] : "Administrador";
if (isset($user["username"])) {
    $adminName .= " (@" . $user["username"] . ")";
}

// Verificar que el callback data sea válido
if ($callbackData !== "completado" && $callbackData !== "rechazado") {
    file_put_contents("callback_log.txt", "❌ Error: callback_data desconocido ($callbackData).\n", FILE_APPEND);
    exit;
}

// Extraer el número de orden del mensaje original
$originalMessage = $update["callback_query"]["message"]["caption"];
preg_match('/🆔 Número de Orden: `(.*)`/', $originalMessage, $matches);
$orderId = $matches[1];

// Definir el nuevo mensaje basado en la acción del botón
$nuevoTexto = "🆔 Número de Orden: $orderId\n" .
              "👤 Administrador: $adminName\n" .
              "📅 Fecha de acción: " . date('Y-m-d H:i:s') . "\n" .
              ($callbackData === "completado" ? "✅ COMPLETADO" : "❌ RECHAZADO");

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

// Ahora enviamos los datos a `procesar.php`
$procesarUrl = "https://depositos.onrender.com/procesar.php"; // Asegúrate de que esta es la URL correcta

$data = [
    "usuario" => $adminName,
    "callback" => $callbackData
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
