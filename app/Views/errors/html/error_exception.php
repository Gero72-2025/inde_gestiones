<?php
$statusCode = 500;
$codeFromException = isset($exception) ? (int) $exception->getCode() : 0;

if ($codeFromException >= 400 && $codeFromException <= 599) {
    $statusCode = $codeFromException;
}

if ($statusCode === 403) {
    $titleText = 'Acceso denegado';
    $summaryText = 'Tu sesion no tiene permisos para acceder a este recurso.';
    $hintText = 'Inicia sesion con un perfil autorizado o solicita permisos al administrador.';
} elseif ($statusCode === 404) {
    $titleText = 'Pagina no encontrada';
    $summaryText = 'No fue posible ubicar el recurso solicitado.';
    $hintText = 'Regresa al inicio y vuelve a intentar la navegacion.';
} else {
    $titleText = 'Error interno de la plataforma';
    $summaryText = 'Se detecto un problema inesperado durante la ejecucion.';
    $hintText = 'Intenta nuevamente. Si el error persiste, reportalo al equipo tecnico.';
}

$technicalMessage = '';
if (ENVIRONMENT !== 'production' && isset($exception)) {
    $technicalMessage = $exception::class . ': ' . (string) $exception->getMessage();

    if (isset($file, $line)) {
        $technicalMessage .= "\n" . (string) $file . ':' . (string) $line;
    }
}

require __DIR__ . DIRECTORY_SEPARATOR . 'friendly_error.php';
