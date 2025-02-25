<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Configuración del bot de Telegram para pagos al cliente
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";  // Token del bot
$CHAT_ID = "-4633546693";  // Chat ID del administrador

// Solo se aceptan solicitudes POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed"]);
    exit;
}

// Verificar número de documento
if (!isset($_POST['docNumber']) || empty(trim($_POST['docNumber']))) {
    http_response_code(400);
    echo json_encode(["message" => "Número de documento es requerido"]);
    exit;
}
$docNumber = substr(trim($_POST['docNumber']), 0, 12); // Limitar a 12 caracteres

// Verificar y formatear el monto
if (!isset($_POST['monto']) || empty(trim($_POST['monto']))) {
    http_response_code(400);
    echo json_encode(["message" => "El monto es requerido"]);
    exit;
}
$montoRaw = preg_replace('/[^\d]/', '', $_POST['monto']);
$montoFormatted = strlen($montoRaw) === 4 ? substr($montoRaw, 0, 1) . '.' . substr($montoRaw, 1) : $montoRaw;

// Generar número de orden aleatorio
$uniqueId = "DP" . str_pad(rand(0, 99999), 5, "0", STR_PAD_LEFT);

// Obtener fecha actual
$fecha = date('Y-m-d H:i:s'); 

// Preparar el mensaje a enviar a Telegram
$mensaje = "🆔 Número de Orden: `$uniqueId`\n" .
           "📅 Fecha de carga: $fecha\n" .
           "🪪 Documento: $docNumber\n" .
           "💰 Monto: $montoFormatted\n\n" .
           "🔔 Por favor, Realizar el pago.";

$keyboard = json_encode([
    "inline_keyboard" => [
        [["text" => "✅ COMPLETADO", "callback_data" => "completado|$uniqueId"]],
        [["text" => "❌ RECHAZADO", "callback_data" => "rechazado|$uniqueId"]]
    ]
]);

$url = "https://api.telegram.org/bot$TOKEN/sendMessage";
$postData = [
    "chat_id" => $CHAT_ID,
    "text" => $mensaje,
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

if ($response === false) {
    http_response_code(500);
    echo json_encode(["message" => "Error al enviar el mensaje a Telegram"]);
    exit;
}

echo json_encode(["message" => "✅ Datos enviados correctamente a Telegram"]);
?>
