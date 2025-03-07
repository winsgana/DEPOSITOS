<?php
date_default_timezone_set('America/La_Paz');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Configura los valores de prueba directamente en el código
$TELEGRAM_TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";  // Token del bot de Telegram
$TELEGRAM_CHAT_ID = "-4633546693";  // Chat ID de Telegram
$API_KEY_SMS = '6d32dd80bef8d29e2652d9c68148193d1ff229c248e8f731';  // API key de WhatsApp

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed"]);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(["message" => "No se ha subido ningún archivo"]);
    exit;
}

if ($_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["message" => "Error al subir el archivo"]);
    exit;
}

$uniqueIdFile = "unique_id.txt";
$lastUniqueId = file_exists($uniqueIdFile) ? (int)file_get_contents($uniqueIdFile) : 0;
$newUniqueId = $lastUniqueId + 1;
file_put_contents($uniqueIdFile, $newUniqueId);

$uniqueId = "DP" . str_pad($newUniqueId, 4, "0", STR_PAD_LEFT);

$docNumber = substr(trim($_POST['docNumber'] ?? ''), 0, 12);
$phoneNumber = preg_replace('/\D/', '', $_POST["phoneNumber"] ?? '');
$fullPhoneNumber = "591" . $phoneNumber;

$monto = $_POST['monto'] ?? '';
$nombreArchivo = $_FILES["file"]["name"];
$rutaTemporal = $_FILES["file"]["tmp_name"];
$fecha = date('Y-m-d H:i:s');

$caption = "🆔 Número de Orden: `$uniqueId`\n" . 
           "📅 Fecha de carga: $fecha\n" . 
           "🪪 Documento: $docNumber\n" . 
           "📱 Teléfono: $fullPhoneNumber\n" . 
           "💰 Monto: $monto BOB\n\n" . 
           "🔔 Por favor, Realizar el pago.";

$keyboard = json_encode([
    "inline_keyboard" => [
        [["text" => "✅ Completado", "callback_data" => "completado-$uniqueId-$monto-$docNumber"]],
        [["text" => "❌ Rechazado", "callback_data" => "rechazado-$uniqueId-$monto-$docNumber"]]
    ]
]);

$postData = [
    "chat_id" => $TELEGRAM_CHAT_ID,
    "document" => new CURLFile($rutaTemporal, mime_content_type($rutaTemporal), $nombreArchivo),
    "caption" => $caption,
    "parse_mode" => "Markdown",
    "reply_markup" => $keyboard
];

$ch = curl_init("https://api.telegram.org/bot" . $TELEGRAM_TOKEN . "/sendDocument");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

sendWhatsApp($fullPhoneNumber, mensajeRecepcion($fecha, $monto));
echo json_encode(["message" => "✅ Comprobante enviado"]);

function sendWhatsApp($phoneNumber, $message) {
    $url = "https://api.smsmobileapi.com/sendsms/?" . http_build_query([
        "recipients" => $phoneNumber,
        "message" => $message,
        "apikey" => $API_KEY_SMS,
        "waonly" => "yes"
    ]);
    file_get_contents($url);
}
?>

