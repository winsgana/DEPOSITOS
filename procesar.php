<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Habilitar logs de error para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración del bot de Telegram para pagos al cliente (QR)
$TOKEN = getenv("TELEGRAM_BOT_TOKEN");  
$CHAT_ID = "-4633546693";  

// Solo se aceptan solicitudes POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["message" => "Method Not Allowed"]);
  exit;
}

// 📌 Verificar si se recibe una solicitud desde `callback.php`
if (isset($_POST['usuario']) && isset($_POST['callback'])) {
    $adminName = $_POST["usuario"];
    $estado = $_POST["callback"];

    // 📌 URL de Google Apps Script (Reemplázala con la correcta)
    $googleUrl = "https://script.google.com/macros/s/AKfycbwjW8KsLbMnGPyvEOZiZFFkh9o-0LjcBGHNe3k5Q1Q5inVogA2zO_R3demepP3XQCxW/exec";

    $data = [
        "usuario" => $adminName,
        "estado" => $estado
    ];

    $options = [
        "http" => [
            "header"  => "Content-type: application/x-www-form-urlencoded",
            "method"  => "POST",
            "content" => http_build_query($data)
        ]
    ];

    $context  = stream_context_create($options);
    $response = file_get_contents($googleUrl, false, $context);

    file_put_contents("google_sheets_log.txt", "📌 Usuario actualizado en Google Sheets: " . $response . "\n", FILE_APPEND);
    echo json_encode(["message" => "✅ Usuario registrado en Google Sheets"]);
    exit;
}

// 📌 Si no es un callback, significa que es una solicitud desde el cliente (archivo QR)
if (!isset($_FILES['file'])) {
  http_response_code(400);
  echo json_encode(["message" => "❌ No se ha subido ningún archivo."]);
  exit;
}

$rutaTemporal = $_FILES["file"]["tmp_name"];
$nombreArchivo = $_FILES["file"]["name"];
$fecha = date('Y-m-d H:i:s');  

// 📌 URL de Telegram para enviar el documento
$url = "https://api.telegram.org/bot$TOKEN/sendDocument";

$caption = "📎 Nuevo QR recibido:\n\n" .
           "📝 Archivo: $nombreArchivo\n" .
           "📅 Fecha de carga: $fecha\n\n" .
           "🔔 Por favor, Realizar el pago.";

$keyboard = json_encode([
    "inline_keyboard" => [
        [["text" => "✅ Completado", "callback_data" => "completado"]],
        [["text" => "❌ Rechazado", "callback_data" => "rechazado"]]
    ]
]);

$postData = [
  "chat_id" => $CHAT_ID,
  "document" => new CURLFile($rutaTemporal, mime_content_type($rutaTemporal), $nombreArchivo),
  "caption" => $caption,
  "parse_mode" => "Markdown",
  "reply_markup" => $keyboard
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

file_put_contents("telegram_error_log.txt", "📌 Respuesta de Telegram: " . $response . "\n", FILE_APPEND);

echo json_encode(["message" => "✅ QR enviado con éxito a Telegram"]);
exit;
?>
