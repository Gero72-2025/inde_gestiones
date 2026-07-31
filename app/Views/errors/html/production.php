<?php
$statusCode = 500;
$titleText = 'Error interno de la plataforma';
$summaryText = 'Ocurrio un problema inesperado al procesar la solicitud.';
$hintText = 'Intenta nuevamente en unos minutos. Si continua, reporta el incidente con fecha y hora.';
$technicalMessage = (string) ($message ?? '');

require __DIR__ . DIRECTORY_SEPARATOR . 'friendly_error.php';
