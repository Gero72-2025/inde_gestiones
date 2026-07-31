<?php
$statusCode = 403;
$titleText = 'Acceso denegado';
$summaryText = 'Tu usuario no cuenta con permisos para esta accion o modulo.';
$hintText = 'Si consideras que esto es un error, solicita acceso al administrador del sistema.';
$technicalMessage = (string) ($message ?? '');

require __DIR__ . DIRECTORY_SEPARATOR . 'friendly_error.php';