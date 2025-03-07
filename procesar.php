<?php
date_default_timezone_set('America/La_Paz');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'utils/config.php';
require_once 'utils/whatsapp.php';
require_once 'utils/mensajes.php';

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

// Verificar número de documento
if (!isset($_POST['docNumber']) || empty(trim($_POST['docNumber']))) {
  http_response_code(400);
  echo json_encode(["message" => "Número de documento es requerido"]);
  exit;
}

// Verificar y tomar el monto directamente como lo recibe el formulario
if (!isset($_POST['monto']) || empty(trim($_POST['monto']))) {
  http_response_code(400);
  echo json_encode(["message" => "El monto es requerido"]);
  exit;
}

// Ruta del archivo de la base de datos SQLite
$dbFile = "unique_id.db";

// Conectar a la base de datos SQLite
$db = new SQLite3($dbFile);

// Crear la tabla si no existe
$db->exec("CREATE TABLE IF NOT EXISTS unique_id (id INTEGER PRIMARY KEY, last_unique_id INTEGER)");

// Insertar el valor inicial si la tabla está vacía
$result = $db->query("SELECT COUNT(*) as count FROM unique_id");
$row = $result->fetchArray();
if ($row['count'] == 0) {
    $db->exec("INSERT INTO unique_id (last_unique_id) VALUES (0)");

// Obtener el último uniqueId
$result = $db->query("SELECT last_unique_id FROM unique_id WHERE id = 1");
$row = $result->fetchArray();
$lastUniqueId = $row['last_unique_id'];

// Incrementar el número
$newUniqueId = $lastUniqueId + 1;

// Guardar el nuevo número en la base de datos
$db->exec("UPDATE unique_id SET last_unique_id = $newUniqueId WHERE id = 1");

// Formatear el uniqueId (ej: DP0001, DP0002, etc.)
$uniqueId = "DP" . str_pad($newUniqueId, 4, "0", STR_PAD_LEFT);

$docNumber = substr(trim($_POST['docNumber']), 0, 12);
$phoneNumber = preg_replace('/\D/', '', $_POST["phoneNumber"] ?? '');
$fullPhoneNumber = "591" . $phoneNumber;
  
$monto = $_POST['monto'];  // Tomar el monto directamente como lo recibe
$nombreArchivo = $_FILES["file"]["name"];
$rutaTemporal = $_FILES["file"]["tmp_name"];
$fecha = date('Y-m-d H:i:s');

// Preparar el mensaje que se enviará a Telegram
$caption = "🆔 Número de Orden: `$uniqueId`\n" .
           "📅 Fecha de carga: $fecha\n" .
           "🪪 Documento: $docNumber\n" .
           "📱 Teléfono: $fullPhoneNumber\n" .
           "💰 Monto: $monto\n\n" .
           "🔔 DEPOSITO PENDIENTE.";

$keyboard = json_encode([
    "inline_keyboard" => [
        [["text" => "✅ Completado", "callback_data" => "completado-$uniqueId-$monto-$docNumber"]],
        [["text" => "❌ Rechazado", "callback_data" => "rechazado-$uniqueId-$monto-$docNumber"]]
    ]
]);

$postData = [
    "chat_id" => TELEGRAM_CHAT_ID,
    "document" => new CURLFile($rutaTemporal, mime_content_type($rutaTemporal), $nombreArchivo),
    "caption" => $caption,
    "parse_mode" => "Markdown",
    "reply_markup" => $keyboard
];

$ch = curl_init("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendDocument");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

sendWhatsApp($fullPhoneNumber, mensajeRecepcion($fecha, $monto));
echo json_encode(["message" => "✅ Comprobante enviado"]);
?>
  
