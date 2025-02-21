<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";

// Obtener la respuesta de Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["callback_query"])) {
    $chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $message_id = $update["callback_query"]["message"]["message_id"];
    $data = $update["callback_query"]["data"];  // Saber qué botón se presionó

    // Definir el nuevo mensaje según el botón presionado
    if ($data == "completado") {
        $nuevo_texto = "✅ *Pago completado y procesado.*";
    } elseif ($data == "rechazado") {
        $nuevo_texto = "❌ *Pago rechazado.*";
    }

    // Editar el mensaje original eliminando los botones
    file_get_contents("https://api.telegram.org/bot$TOKEN/editMessageText?chat_id=$chat_id&message_id=$message_id&text=" . urlencode($nuevo_texto) . "&parse_mode=Markdown");
}
?>
