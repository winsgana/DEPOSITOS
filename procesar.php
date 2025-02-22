<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// ✅ Incluir el archivo send_to_google_form.php
include_once 'send_to_google_form.php';

// Habilitar logs de error para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 📌 Capturar los datos enviados desde Telegram
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar que los datos sean válidos
if (!$data || !isset($data['usuario']) || !isset($data['callback'])) {
    file_put_contents("procesar_log.txt", "❌ Error: Datos incompletos recibidos.\n", FILE_APPEND);
    echo json_encode(["message" => "❌ Datos incompletos."]);
    exit;
}

// Obtener la fecha actual
$fecha = date("Y-m-d H:i:s");

// Preparar los datos para actualizar en Google Forms
$formData = [
    "usuario" => $data['usuario'], // Nombre del usuario que realizó la acción en Telegram
    "estado" => ($data['callback'] === "completado") ? "✅ Completado" : "❌ Rechazado",
    "fecha" => $fecha
];

// ✅ Enviar datos al formulario de Google
$result = sendToGoogleForm($formData);

// Registrar respuesta en logs
file_put_contents("procesar_log.txt", "📌 Respuesta de Google Form: " . $result . "\n", FILE_APPEND);

echo json_encode(["message" => "✅ Estado actualizado correctamente en Google Forms."]);
exit;
?>
