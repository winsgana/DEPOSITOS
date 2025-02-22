<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Habilitar logs de error para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración del bot de Telegram para pagos al cliente (QR)
$TOKEN = getenv("TELEGRAM_BOT_TOKEN");  // Tu token de bot
$CHAT_ID = "-4633546693";  // Chat ID para pagos al cliente

// Solo se aceptan solicitudes POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["message" => "Method Not Allowed"]);
  exit;
}

// Verificar que se haya subido un archivo
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(["message" => "No se ha subido ningún archivo o hubo un error en la subida"]);
  exit;
}

$rutaTemporal = $_FILES["file"]["tmp_name"];
$nombreArchivo = $_FILES["file"]["name"];

if (!file_exists($rutaTemporal)) {
    file_put_contents("error_log.txt", "Error: El archivo temporal no existe.\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(["message" => "Error al subir el archivo"]);
    exit;
}

// Verificar número de documento
if (!isset($_POST['docNumber']) || empty(trim($_POST['docNumber']))) {
  http_response_code(400);
  echo json_encode(["message" => "Número de documento es requerido"]);
  exit;
}
$docNumber = substr(trim($_POST['docNumber']), 0, 12); // Limitar a 12 caracteres

$file_mime_type = mime_content_type($rutaTemporal) ?: "application/octet-stream";

// Verificar y formatear el monto
if (!isset($_POST['monto']) || empty(trim($_POST['monto']))) {
  http_response_code(400);
  echo json_encode(["message" => "El monto es requerido"]);
  exit;
}

// Eliminar cualquier carácter que no sea dígito (excepto el punto)
$montoRaw = preg_replace('/[^\d]/', '', $_POST['monto']);
$montoFormatted = (strlen($montoRaw) === 4) ? substr($montoRaw, 0, 1) . '.' . substr($montoRaw, 1) : $montoRaw;

// Formatear el monto si tiene más de 3 dígitos (para 4 dígitos se inserta punto)
if (strlen($montoRaw) === 4) {
  $montoFormatted = substr($montoRaw, 0, 1) . '.' . substr($montoRaw, 1);
} else {
  $montoFormatted = $montoRaw;
}

$nombreArchivo = $_FILES["file"]["name"];
$rutaTemporal = $_FILES["file"]["tmp_name"];
$fecha = date('Y-m-d H:i:s');  // Fecha y hora actual

// URL de Telegram para enviar el documento
$url = "https://api.telegram.org/bot$TOKEN/sendDocument";

// Preparar el mensaje que se enviará a Telegram
$caption = "📎 Nuevo QR recibido:\n\n" .
           "📝 Archivo: $nombreArchivo\n" .
           "📅 Fecha de carga: $fecha\n" .
           "🪪 Documento: $docNumber\n" .
           "💰 Monto: $montoFormatted\n\n" .
           "🔔 Por favor, Realizar el pago.";

// Inline keyboard: botones "✅ Completado" y "❌ Rechazado"
$keyboard = json_encode([
    "inline_keyboard" => [
        [["text" => "✅ Completado", "callback_data" => "completado"]],
        [["text" => "❌ Rechazado", "callback_data" => "rechazado"]]
    ]
]);

// Preparar los datos para enviar
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

// Guardar respuesta de Telegram para diagnóstico
file_put_contents("telegram_error_log.txt", "HTTP Status: $http_status\nResponse: $response\nCurl Error: $curl_error\n", FILE_APPEND);

// Si hubo error en la solicitud o el código HTTP no es 200
if ($response === false || $http_status != 200) {
  http_response_code(500);
  echo json_encode([
      "message"    => "Error al enviar a Telegram.",
      "curl_error" => $curl_error,
      "http_status"=> $http_status,
      "response"   => $response
  ]);
  exit;
}

$googleUrl = "https://script.google.com/macros/s/AKfycbwy45iDMEOGL1gybIHXR9edTyxl9HJsI956RaNH4IqOUZEu5CSZzhoVJo-O5c5OdKEn/exec"; // Reemplázalo con la URL de tu Apps Script

// Verificar qué datos llegan en POST
file_put_contents("google_sheets_log.txt", "POST recibido: " . json_encode($_POST) . "\n", FILE_APPEND);

// Asegurar que el usuario está definido correctamente
$adminName = isset($_POST["usuario"]) ? $_POST["usuario"] : "Desconocido";
$docNumber = isset($_POST["documento"]) ? $_POST["documento"] : "Sin documento";
$montoFormatted = isset($_POST["monto"]) ? $_POST["monto"] : "0.00";

// Depuración: Verificar en log si el usuario tiene valor
file_put_contents("google_sheets_log.txt", "Usuario enviado: " . $adminName . "\n", FILE_APPEND);

// Datos para enviar a Google Sheets
$data = [
    "usuario" => $adminName,
    "documento" => $docNumber,
    "monto" => $montoFormatted
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

// Guardar la respuesta en un log
file_put_contents("google_sheets_log.txt", "Respuesta de Google Sheets: " . $response . "\n", FILE_APPEND);

// Mostrar respuesta en pantalla para depuración
echo json_encode(["google_response" => $response], JSON_PRETTY_PRINT);
exit;
?>
