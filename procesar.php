<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Configuración del bot de Telegram
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";  // Tu token de bot
$CHAT_ID = "-4633546693";  // ID de tu grupo de Telegram

// Solo se aceptan solicitudes POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["message" => "Method Not Allowed"]);
  exit;
}

// Verificar que se haya subido un archivo
if (!isset($_FILES['file'])) {
  http_response_code(400);
  echo json_encode(["message" => "No se ha subido ningún archivo"]);
  exit;
}

// Verificar si hay error en la subida
if ($_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(["message" => "Error al subir el archivo: " . $_FILES["file"]["error"]]);
  exit;
}

$nombreArchivo = $_FILES["file"]["name"];
$rutaTemporal = $_FILES["file"]["tmp_name"];
$fecha = date('Y-m-d H:i:s');  // Fecha y hora actual

$url = "https://api.telegram.org/bot$TOKEN/sendDocument";

// Preparar datos para enviar a Telegram
$postData = [
  "chat_id" => $CHAT_ID,
  "document" => new CURLFile($rutaTemporal, mime_content_type($rutaTemporal), $nombreArchivo),
  "caption" => "📎 Nuevo comprobante recibido:\n\n" .
                "📝 Archivo: $nombreArchivo\n" .
                "📅 Fecha de carga: $fecha\n\n" .
                "🔔 Por favor, verifica el pago."
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

echo json_encode(["message" => "✅ Comprobante enviado con éxito a Telegram"]);
?>
