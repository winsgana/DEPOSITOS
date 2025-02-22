<?php
// ✅ Enviar datos a Google Forms
function sendToGoogleForm($data) {
    $url = 'https://docs.google.com/forms/d/e/1FAIpQLScrqAwuMx5ge-MIngD2BbFIXcwaQ_8MHR6XUaz-iOQUyqq04g/formResponse';

    $postData = [
        'entry.1508003770' => $data['answer'] ?? 'Option 1',
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'method'  => 'POST',
            'content' => http_build_query($postData),
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result !== FALSE ? "✅ Datos enviados correctamente." : "❌ Error al enviar los datos.";
}
?>
