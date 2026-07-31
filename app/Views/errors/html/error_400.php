<?php
$statusCode = 400;
$titleText = 'Solicitud invalida';
$summaryText = 'No fue posible interpretar correctamente la solicitud enviada.';
$hintText = 'Verifica los datos ingresados y vuelve a intentarlo.';
$technicalMessage = (string) ($message ?? '');

require __DIR__ . DIRECTORY_SEPARATOR . 'friendly_error.php';
