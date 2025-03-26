<?php
$filename = 'unique_id.txt';

if (file_exists($filename)) {
    chmod($filename, 0666);
    echo "Permisos cambiados correctamente para $filename.";
} else {
    echo "$filename no existe.";
}
?>
