<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";

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

// Validar el formato de callback_data
preg_match('/(completado|rechazado)-(DP\d{5})/', $callbackData, $matches);
if (!$matches) {
    file_put_contents("callback_log.txt", "❌ Error: callback_data desconocido ($callbackData).\n", FILE_APPEND);
    exit;
}

$accion = $matches[1];
$numeroOrden = $matches[2];
$fechaAccion = date('Y-m-d H:i:s');

// Construir el nuevo mensaje
$nuevoTexto = "📎 Nuevo QR recibido:\n\n" .
              "🆔 Número de Orden: `$numeroOrden`\n" .
              "👤 Administrador: $adminName\n" .
              "📅 Fecha de acción: $fechaAccion\n" .
              ($accion === "completado" ? "✅ COMPLETADO" : "❌ RECHAZADO");

// URL para editar el mensaje en Telegram
$url = "https://api.telegram.org/bot$TOKEN/editMessageCaption?" . http_build_query([
    "chat_id"    => $chatId,
    "message_id" => $messageId,
    "caption"    => $nuevoTexto,
    "parse_mode" => "Markdown",
    "reply_markup" => json_encode(["inline_keyboard" => []])
]);

// Enviar la solicitud a Telegram usando curl
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode != 200 || $response === false) {
    file_put_contents("callback_log.txt", "❌ Error al enviar a Telegram: $error\n", FILE_APPEND);
} else {
    file_put_contents("callback_log.txt", "📌 Respuesta de Telegram: $response\n", FILE_APPEND);
}

exit;
?>
