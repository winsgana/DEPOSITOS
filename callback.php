<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";

// Capturar la entrada de Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Registrar para depuración (opcional)
file_put_contents("log.txt", json_encode($update, JSON_PRETTY_PRINT), FILE_APPEND);

if (isset($update["callback_query"])) {
    // Extraer información del administrador que hizo clic
    $admin_info = $update["callback_query"]["from"];
    $admin_name = isset($admin_info["first_name"]) ? $admin_info["first_name"] : "Administrador";
    if (isset($admin_info["username"])) {
        $admin_name .= " (@" . $admin_info["username"] . ")";
    }
    
    $chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $message_id = $update["callback_query"]["message"]["message_id"];
    $data = $update["callback_query"]["data"];  // Botón presionado

    // Definir el nuevo mensaje según el botón presionado e incluir el nombre del administrador
    if ($data == "completado") {
        $nuevo_texto = "✅ *Pago recibido.*\nAcción realizada por: " . $admin_name;
    } elseif ($data == "rechazado") {
        $nuevo_texto = "❌ *Pago rechazado.*\nAcción realizada por: " . $admin_name;
    }

    // Actualizar el mensaje usando editMessageCaption (mensaje con documento)
    $url = "https://api.telegram.org/bot$TOKEN/editMessageCaption?chat_id=$chat_id&message_id=$message_id&caption=" . urlencode($nuevo_texto) . "&parse_mode=Markdown";
    file_get_contents($url);
}
?>
