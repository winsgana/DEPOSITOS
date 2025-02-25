<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";  // Token del bot

$content = file_get_contents("php://input");
$update = json_decode($content, true);

file_put_contents("callback_log.txt", "📌 Callback recibido: " . json_encode($update, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

if (!$update || !isset($update["callback_query"])) {
    file_put_contents("callback_log.txt", "❌ Error: No hay callback_query en la solicitud.\n", FILE_APPEND);
    exit;
}

$callbackData = $update["callback_query"]["data"];
$chatId = $update["callback_query"]["message"]["chat"]["id"];
$messageId = $update["callback_query"]["message"]["message_id"];
$user = $update["callback_query"]["from"];
$photo = $update["callback_query"]["message"]["photo"] ?? null;

// Aquí extraemos el número de orden del caption que se envió en el mensaje original
$caption = $update["callback_query"]["message"]["caption"];
preg_match('/🆔 Número de Orden: `(DP\d{4})`/', $caption, $matches);  // Regular expression para capturar el número de orden

if (isset($matches[1])) {
    $uniqueId = $matches[1];  // El número de orden extraído
} else {
    file_put_contents("callback_log.txt", "❌ Error: No se encontró el número de orden.\n", FILE_APPEND);
    exit;
}

// Datos del cliente
$adminName = isset($user["first_name"]) ? $user["first_name"] : "Administrador";
if (isset($user["username"])) {
    $adminName .= " (@" . $user["username"] . ")";
}

// Acción tomada
$accionTexto = ($callbackData === "completado") ? "✅ COMPLETADO" : "❌ RECHAZADO";
$fechaAccion = date('Y-m-d H:i:s');

// Eliminar el mensaje original
$urlDelete = "https://api.telegram.org/bot$TOKEN/deleteMessage";
$postDataDelete = [
    "chat_id" => $chatId,
    "message_id" => $messageId
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlDelete);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataDelete);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$responseDelete = curl_exec($ch);
$curl_error = curl_error($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

file_put_contents("callback_log.txt", "📌 Respuesta de borrar mensaje: " . $responseDelete . "\n", FILE_APPEND);

if ($responseDelete === false || $http_status != 200) {
    file_put_contents("callback_log.txt", "❌ Error al borrar el mensaje: $curl_error\n", FILE_APPEND);
    exit;
}

// Enviar un nuevo mensaje con la información actualizada
$url = "https://api.telegram.org/bot$TOKEN/sendMessage";
$nuevoTexto = "🆔 Número de Orden: `$uniqueId`\n" .
              "👤 Administrador: $adminName\n" .
              "📅 Fecha de acción: $fechaAccion\n" .
              "$accionTexto";

$postDataSend = [
    "chat_id" => $chatId,
    "text" => $nuevoTexto,
    "parse_mode" => "Markdown"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataSend);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$responseSend = curl_exec($ch);
$curl_error = curl_error($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

file_put_contents("callback_log.txt", "📌 Respuesta de enviar mensaje nuevo: " . $responseSend . "\n", FILE_APPEND);

if ($responseSend === false || $http_status != 200) {
    file_put_contents("callback_log.txt", "❌ Error al enviar el mensaje: $curl_error\n", FILE_APPEND);
}

exit;
?>
