<?php
// ✅ Recibir el callback de Telegram y enviar a Google Sheets
$TOKEN = getenv("TELEGRAM_BOT_TOKEN");
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["callback_query"])) {
    $callback_id = $update["callback_query"]["id"];
    $message = $update["callback_query"]["message"];
    $chat_id = $message["chat"]["id"];
    $from_user = $update["callback_query"]["from"]["username"] ?? "Desconocido";
    $data = $update["callback_query"]["data"];

    // Extraer datos del mensaje original
    preg_match('/Fecha de carga: ([\d\-: ]+)/', $message["text"], $fecha);
    preg_match('/Documento: (\d+)/', $message["text"], $documento);
    preg_match('/Monto: (\d+)/', $message["text"], $monto);
    
    $estado = ($data == "completado") ? "Completado" : "Rechazado";
    $fecha_valor = $fecha[1] ?? date("Y-m-d H:i:s");
    $doc_valor = $documento[1] ?? "N/A";
    $monto_valor = $monto[1] ?? "N/A";

    // ✅ Enviar los datos a Google Sheets
    function sendToGoogleSheets($documento, $monto, $usuario, $estado) {
        $url = "https://script.google.com/macros/s/AKfycbxd_HaacOaXDZA6IoLe4LSl97a0RWPphonN_49r99vq2ftYQ7wLGyGecT3lH20ZbJslnw/exec";

        $postData = json_encode([
            "documento" => $documento,
            "monto" => $monto,
            "usuario" => $usuario,
            "estado" => $estado
        ]);

        $options = [
            "http" => [
                "header"  => "Content-Type: application/json",
                "method"  => "POST",
                "content" => $postData,
            ],
        ];

        $context  = stream_context_create($options);
        file_get_contents($url, false, $context);
    }

    // Enviar los datos a Google Sheets
    sendToGoogleSheets($doc_valor, $monto_valor, $from_user, $estado);

    // Responder a Telegram
    $response_text = "✅ Acción registrada: $estado\n📆 Fecha: $fecha_valor\n📜 Documento: $doc_valor\n💰 Monto: $monto_valor\n👤 Usuario: @$from_user";
    file_get_contents("https://api.telegram.org/bot$TOKEN/sendMessage?chat_id=$chat_id&text=" . urlencode($response_text));
}
?>
