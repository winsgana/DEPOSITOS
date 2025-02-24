<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Habilitar logs de error para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 📌 Configuración del bot de Telegram para pagos
$TOKEN = getenv("7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI");
$CHAT_ID = "-4633546693";  // Reemplázalo con tu Chat ID

// 📌 Solo se aceptan solicitudes POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["message" => "❌ Método no permitido"]);
    exit;
}

// 📌 Verificar que se haya subido un archivo
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["message" => "❌ No se ha subido ningún archivo"]);
    exit;
}

$rutaTemporal = $_FILES["file"]["tmp_name"];
$nombreArchivo = $_FILES["file"]["name"];

// 📌 Verificar si el archivo realmente existe
if (!file_exists($rutaTemporal)) {
    file_put_contents("error_log.txt", "❌ Error: El archivo temporal no existe.\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(["message" => "❌ Error al subir el archivo"]);
    exit;
}

// 📌 Verificar número de documento
if (!isset($_POST['docNumber']) || empty(trim($_POST['docNumber']))) {
    http_response_code(400);
    echo json_encode(["message" => "❌ Número de documento es requerido"]);
    exit;
}
$docNumber = substr(trim($_POST['docNumber']), 0, 12); // Limitar a 12 caracteres

$file_mime_type = mime_content_type($rutaTemporal) ?: "application/octet-stream";

// 📌 Verificar y formatear el monto
if (!isset($_POST['monto']) || empty(trim($_POST['monto']))) {
    http_response_code(400);
    echo json_encode(["message" => "❌ El monto es requerido"]);
    exit;
}

$montoRaw = preg_replace('/[^\d]/', '', $_POST['monto']);
$montoFormatted = (strlen($montoRaw) === 4) ? substr($montoRaw, 0, 1) . '.' . substr($montoRaw, 1) : $montoRaw;

$fecha = date('Y-m-d H:i:s');  // Fecha y hora actual

// ✅ **Obtener usuario correctamente**
$adminName = isset($_POST["usuario"]) ? $_POST["usuario"] : "Desconocido";

// ✅ **Verificar datos antes de enviar a Google Sheets**
file_put_contents("google_sheets_log.txt", "📌 Datos recibidos en procesar.php: " . json_encode($_POST) . "\n", FILE_APPEND);

// 📌 **URL de Google Sheets (REEMPLAZA CON TU URL)**
$googleUrl = "https://script.google.com/macros/s/AKfycbxDrVkMvT-blML1TaSr6Jos6xwsuSF68To73E7JUC6f5oQbnbTJmHC8iMHHiCbVnz8g/exec";

// 📌 **Si se recibe una actualización desde `callback.php`**
if (isset($_POST['usuario']) && isset($_POST['callback'])) {
    $adminName = $_POST["usuario"];
    $estado = $_POST["callback"]; // "completado" o "rechazado"

    // 📌 **Enviar datos a Google Sheets**
    $data = [
        "usuario" => $adminName,
        "documento" => $docNumber,
        "monto" => $montoFormatted,
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

    // 📌 **Guardar respuesta en un log**
    file_put_contents("google_sheets_log.txt", "📌 Respuesta de Google Sheets: " . $response . "\n", FILE_APPEND);

    echo json_encode(["message" => "✅ Usuario registrado en Google Sheets"]);
    exit;
}

// 📌 **URL de Telegram para enviar el documento**
$url = "https://api.telegram.org/bot$TOKEN/sendDocument";

// 📌 **Preparar mensaje para Telegram**
$caption = "📎 Nuevo QR recibido:\n\n" .
           "📝 Archivo: $nombreArchivo\n" .
           "📅 Fecha de carga: $fecha\n" .
           "🪪 Documento: $docNumber\n" .
           "💰 Monto: $montoFormatted\n\n" .
           "🔔 Por favor, Realizar el pago.";

// 📌 **Inline keyboard (botones)**
$keyboard = json_encode([
    "inline_keyboard" => [
        [["text" => "✅ Completado", "callback_data" => "completado"]],
        [["text" => "❌ Rechazado", "callback_data" => "rechazado"]]
    ]
]);

// 📌 **Datos para enviar a Telegram**
$postData = [
    "chat_id" => $CHAT_ID,
    "document" => new CURLFile($rutaTemporal, $file_mime_type, $nombreArchivo),
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
$curl_error = curl_error($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 📌 **Guardar respuesta de Telegram en log**
file_put_contents("telegram_error_log.txt", "HTTP Status: $http_status\nResponse: $response\nCurl Error: $curl_error\n", FILE_APPEND);

// 📌 **Verificar si hubo error en la solicitud a Telegram**
if ($response === false || $http_status != 200) {
    http_response_code(500);
    echo json_encode([
        "message"    => "❌ Error al enviar a Telegram.",
        "curl_error" => $curl_error,
        "http_status"=> $http_status,
        "response"   => $response
    ]);
    exit;
}

// ✅ **Si todo fue exitoso**
echo json_encode(["message" => "✅ QR enviado con éxito a Telegram y Google Sheets"]);
exit;
?>
