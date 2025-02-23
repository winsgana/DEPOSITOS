<?php
// ✅ Recibir datos del cliente y procesarlos para Telegram y Google Sheets
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";
$CHAT_ID = "-4633546693";

// Verificar si los datos llegan correctamente desde el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $documento = $_POST["documento"] ?? "No especificado";
    $monto = $_POST["monto"] ?? "No especificado";
    $fecha = date("Y-m-d H:i:s");

    // ✅ Enviar datos a Telegram
    $mensaje = "📌 Nuevo depósito recibido:\n📜 Documento: $documento\n💰 Monto: $monto\n📆 Fecha: $fecha\n\n⚠️ Por favor, validar el pago.";
    $bot_url = "https://api.telegram.org/bot$TOKEN/sendMessage";
    
    $telegram_data = [
        "chat_id" => $CHAT_ID,
        "text" => $mensaje,
        "reply_markup" => json_encode([
            "inline_keyboard" => [[
                ["text" => "✅ Completado", "callback_data" => "completado"],
                ["text" => "❌ Rechazado", "callback_data" => "rechazado"]
            ]]
        ])
    ];
    
    file_get_contents($bot_url . "?" . http_build_query($telegram_data));

    // ✅ Enviar datos a Google Sheets
    function sendToGoogleSheets($documento, $monto, $fecha) {
        $url = "https://script.google.com/macros/s/AKfycbxd_HaacOaXDZA6IoLe4LSl97a0RWPphonN_49r99vq2ftYQ7wLGyGecT3lH20ZbJslnw/exec";
        
        $postData = json_encode([
            "documento" => $documento,
            "monto" => $monto,
            "usuario" => "Cliente", // Se registra como cliente
            "estado" => "Pendiente"
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

    // Enviar datos a Google Sheets
    sendToGoogleSheets($documento, $monto, $fecha);

    echo json_encode(["status" => "success", "message" => "Datos enviados correctamente."]);
} else {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
}
?>
