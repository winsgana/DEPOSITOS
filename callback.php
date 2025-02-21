<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";

// Capturar la entrada de Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Registrar para depuración
file_put_contents("log.txt", json_encode($update, JSON_PRETTY_PRINT), FILE_APPEND);

if (isset($update["callback_query"])) {
    $chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $message_id = $update["callback_query"]["message"]["message_id"];
    $data = $update["callback_query"]["data"];  // Botón presionado

    if ($data == "completado") {
        $nuevo_texto = "✅ *Pago completado y procesado.*";
    } elseif ($data == "rechazado") {
        $nuevo_texto = "❌ *Pago rechazado.*";
    }

    // Actualiza el mensaje usando editMessageCaption en lugar de editMessageText
    file_get_contents("https://api.telegram.org/bot$TOKEN/editMessageCaption?chat_id=$chat_id&message_id=$message_id&caption=" . urlencode($nuevo_texto) . "&parse_mode=Markdown");
}
?>
