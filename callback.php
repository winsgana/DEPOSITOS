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

preg_match('/🆔 Número de Orden: `(DP\\d{5})`/', $update["callback_query"]["message"]["caption"], $matches);
$numeroOrden = $matches[1] ?? "Desconocido";

$fechaAccion = date('Y-m-d H:i:s');
$accionTexto = ($callbackData === "completado") ? "✅ COMPLETADO" : "❌ RECHAZADO";

if ($photo) {
    $nuevoTexto = "🆔 Número de Orden: `$numeroOrden`\n" .
                  "👤 Administrador: $adminName\n" .
                  "📅 Fecha de acción: $fechaAccion\n" .
                  "$accionTexto";

    $url = "https://api.telegram.org/bot$TOKEN/editMessageCaption";

    $postData = [
        "chat_id"    => $chatId,
        "message_id" => $messageId,
        "caption"    => $nuevoTexto,
        "parse_mode" => "Markdown"
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
}

// ✅ Envío a procesar.php
$procesarUrl = "http://localhost/procesar.php";
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

file_put_contents("callback_log.txt", "📌 Enviando datos a procesar.php...\n", FILE_APPEND);

$procesarResponse = file_get_contents($procesarUrl, false, $context);

file_put_contents("callback_log.txt", "📌 Respuesta de procesar.php: " . $procesarResponse . "\n", FILE_APPEND);

exit;
?>
