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

// Obtener el número de orden desde la caption
preg_match('/🆔 Número de Orden: `(DP\d{5})`/', $update["callback_query"]["message"]["caption"], $matches);
$uniqueId = $matches[1] ?? "Desconocido";  // Usar el número de orden

$adminName = isset($user["first_name"]) ? $user["first_name"] : "Administrador";
if (isset($user["username"])) {
    $adminName .= " (@" . $user["username"] . ")";
}

// Acción tomada
$accionTexto = ($callbackData === "completado") ? "✅ COMPLETADO" : "❌ RECHAZADO";
$fechaAccion = date('Y-m-d H:i:s');

// Actualizar el mensaje con la nueva información
$url = "https://api.telegram.org/bot$TOKEN/editMessageCaption";
$nuevoTexto = "🆔 Número de Orden: `$uniqueId`\n" .
              "👤 Administrador: $adminName\n" .
              "📅 Fecha de acción: $fechaAccion\n" .
              "$accionTexto";

$postData = [
    "chat_id"    => $chatId,
    "message_id" => $messageId,
    "caption"    => $nuevoTexto,
    "parse_mode" => "Markdown",
    "reply_markup" => json_encode([
        "remove_keyboard" => true  // Eliminar los botones
    ])
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

file_put_contents("callback_log.txt", "📌 Respuesta completa de Telegram: " . $response . "\n", FILE_APPEND);

if ($response === false || $http_status != 200) {
    file_put_contents("callback_log.txt", "❌ Error al editar el mensaje: $curl_error\n", FILE_APPEND);
}

exit;
?>
