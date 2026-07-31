<?php
$statusCode = 404;
$titleText = 'Pagina no encontrada';
$summaryText = 'La ruta solicitada no existe o fue movida.';
$hintText = 'Regresa al inicio y navega nuevamente desde el menu principal.';
$technicalMessage = (string) ($message ?? '');

require __DIR__ . DIRECTORY_SEPARATOR . 'friendly_error.php';
