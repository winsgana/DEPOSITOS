<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$TOKEN = "7957554764:AAHUzfquZDDVEiwOy_u292haqMmPK2uCKDI";

// Captura el contenido recibido
$content = file_get_contents("php://input");

// Registra el contenido en un archivo log.txt en el mismo directorio
$logFile = __DIR__ . "/log.txt";
$logEntry = "Callback triggered at " . date("Y-m-d H:i:s") . "\nRaw input: " . $content . "\n\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// También registra en el log de errores del servidor (útil para verlos en el Dashboard de Render)
error_log($logEntry);

echo "Callback recibido";
?>
