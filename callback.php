<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";

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

$adminName = isset($user["first_name"]) ? $user["first_name"] : "Administrador";
if (isset($user["username"])) {
    $adminName .= " (@" . $user["username"] . ")";
}

if ($callbackData !== "completado" && $callbackData !== "rechazado") {
    file_put_contents("callback_log.txt", "❌ Error: callback_data desconocido ($callbackData).\n", FILE_APPEND);
    exit;
}

$fechaAccion = date('Y-m-d H:i:s');
$accionTexto = ($callbackData === "completado") ? "✅ COMPLETADO" : "❌ RECHAZADO";

if ($photo) {
    $nuevoTexto = "🆔 Número de Orden: `" . $update["callback_query"]["message"]["caption"] . "`\n" .
                  "👤 Administrador: $adminName\n" .
                  "📅 Fecha de acción: $fechaAccion\n" .
                  "$accionTexto";

    $url = "https://api.telegram.org/bot$TOKEN/editMessageCaption?" . http_build_query([
        "chat_id"    => $chatId,
        "message_id" => $messageId,
        "caption"    => $nuevoTexto,
        "parse_mode" => "Markdown"
    ]);

    $response = file_get_contents($url);
    file_put_contents("callback_log.txt", "📌 Respuesta de Telegram: " . $response . "\n", FILE_APPEND);
}

$procesarUrl = "https://depositos.onrender.com/procesar.php";

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

file_put_contents("callback_log.txt", "📌 Respuesta de procesar.php: " . $procesarResponse . "\n", FILE_APPEND);

exit;
?>
