<?php
$TOKEN = getenv("TELEGRAM_BOT_TOKEN"); // Token del bot

// ✅ Incluir el archivo send_to_google_form.php
include_once 'send_to_google_form.php';

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
if (!in_array($callbackData, ["completado", "rechazado"])) {
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

// ✅ Enviar los datos a Google Forms
$data = [
    "usuario" => $adminName,
    "callback" => $callbackData
];

$result = sendToGoogleForm($data);
file_put_contents("callback_log.txt", "📌 Respuesta de Google Form: " . $result . "\n", FILE_APPEND);

exit;
?>
