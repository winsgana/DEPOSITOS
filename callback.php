<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";

// Capturar la entrada de Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$content || !$update) {
    file_put_contents("log.txt", "// Error: No se recibió contenido válido o la decodificación falló.\n", FILE_APPEND);
    exit;
}

// Registrar para depuración (opcional)
file_put_contents("log.txt", "// Datos recibidos:\n" . json_encode($update, JSON_PRETTY_PRINT), FILE_APPEND);

// Verificar si es una callback query
if (isset($update["callback_query"])) {
    // Extraer información del administrador que hizo clic
    $admin_info = $update["callback_query"]["from"];
    $admin_name = isset($admin_info["first_name"]) ? $admin_info["first_name"] : "Administrador";
    
    if (isset($admin_info["username"])) {
        $admin_name .= " (@" . $admin_info["username"] . ")";
    }

    // Obtener información del mensaje
    $chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $message_id = $update["callback_query"]["message"]["message_id"];
    $data = $update["callback_query"]["data"];  // Botón presionado

    // Validar datos de la consulta
    if (!in_array($data, ["completado", "rechazado"])) {
        file_put_contents("log.txt", "// Error: Callback data no válida.\n", FILE_APPEND);
        exit;
    }

    // Definir el nuevo mensaje según el botón presionado e incluir el nombre del administrador
    $nuevo_texto = ($data == "completado") ? 
        "✅ *Pago recibido.*\nAcción realizada por: " . $admin_name : 
        "❌ *Pago rechazado.*\nAcción realizada por: " . $admin_name;
    }

    // Actualizar el mensaje usando editMessageCaption (mensaje con documento)
    $url = "https://api.telegram.org/bot$TOKEN/editMessageCaption?chat_id=$chat_id&message_id=$message_id&caption=" . urlencode($nuevo_texto) . "&parse_mode=Markdown";
    file_get_contents($url);
}
?>
