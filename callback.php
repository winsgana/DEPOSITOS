<?php
$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";

// Capturar los datos enviados por Telegram
$content = file_get_contents("php://input");
file_put_contents("log.txt", json_encode($content, JSON_PRETTY_PRINT)); // Guarda la respuesta en un archivo

echo "Datos guardados en log.txt"; // Mensaje de prueba para confirmar
?>
