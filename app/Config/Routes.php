<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('portal-inde/public/index.php/(:any)', static fn(string $path) => redirect()->to(site_url($path), 301));
$routes->get('portal-inde/public/(:any)', static fn(string $path) => redirect()->to(site_url($path), 301));

$routes->group('', ['filter' => 'readOnly'], static function ($routes) {
	$routes->get('/', 'PublicPortalController::index');
	$routes->get('consulta/beneficiados', 'PublicPortalController::beneficiadosPage');
	$routes->get('consulta/comunidades', 'PublicPortalController::comunidadesPage');
	$routes->get('consulta/tarifa-social', 'PublicPortalController::tarifaSocialPage');
	$routes->get('consulta/gu', 'PublicPortalController::guPage');
	$routes->get('consulta/eem', 'PublicPortalController::eemPage');
	$routes->get('consulta/ecoe-chat', 'PublicController::ecoeChatPage');
	$routes->get('consulta/sni', 'PublicPortalController::sniMap');
	$routes->get('consulta/cortes', 'PublicController::index');
	$routes->get('locale/(:segment)', 'PublicPortalController::changeLocale/$1');

	$routes->get('api/public/captcha', 'PublicPortalController::captcha');
	$routes->post('api/public/beneficiados', 'PublicPortalController::beneficiado');
	$routes->post('api/gero/comunidades/consultar', 'PublicPortalController::consultarComunidadesGero');
	$routes->get('api/public/comunidades/archivos', 'PublicPortalController::listarArchivosPublicosComunidades');
	$routes->get('api/public/cortes', 'PublicPortalController::cortes');
	$routes->get('api/public/sni/geometrias', 'PublicPortalController::sniGeometrias');

	// ECOE Tarifa Social – endpoints AJAX públicos
	$routes->post('api/ecoe/ts/consultar-nis', '\App\Modules\Ecoe\Controllers\TarifaSocialController::apiConsultarNis');
	$routes->post('api/ecoe/ts/rastrear', '\App\Modules\Ecoe\Controllers\TarifaSocialController::apiRastrear');
	$routes->post('api/ecoe/ts/crear-ticket', '\App\Modules\Ecoe\Controllers\TarifaSocialController::apiCrearTicket');
	$routes->get('api/ecoe/ts/solicitud-pdf', '\App\Modules\Ecoe\Controllers\TarifaSocialController::descargarSolicitudPdf');
	$routes->get('api/ecoe/ts/historial-pdf', '\App\Modules\Ecoe\Controllers\TarifaSocialController::descargarHistorialPdf');

	// ECOE Formularios dinámicos – captura pública
	$routes->post('api/ecoe/gu/oferta', '\App\Modules\Ecoe\Controllers\GuController::submitOferta');
	$routes->post('api/ecoe/gu/quejas', '\App\Modules\Ecoe\Controllers\GuController::submitQuejas');
	$routes->get('api/ecoe/gu/oferta/plantilla-word', '\App\Modules\Ecoe\Controllers\GuController::downloadGu1Template');
	$routes->post('api/ecoe/gu/oferta/validar-referencia', '\App\Modules\Ecoe\Controllers\GuController::validarOfertaReferencia');
	$routes->post('api/ecoe/gu/oferta/continuar', '\App\Modules\Ecoe\Controllers\GuController::continuarOferta');
	$routes->post('api/ecoe/eem/nueva-conexion', '\App\Modules\Ecoe\Controllers\EemController::submitNuevaConexion');
	$routes->post('api/ecoe/eem/gestionar-expediente', '\App\Modules\Ecoe\Controllers\EemController::submitGestionarExpediente');
	$routes->post('api/ecoe/eem/capacitacion-tecnica', '\App\Modules\Ecoe\Controllers\EemController::submitCapacitacionTecnica');
	$routes->get('api/ecoe/eem/estado', '\App\Modules\Ecoe\Controllers\EemController::consultarEstado');
	$routes->post('api/ecoe/eem/estado', '\App\Modules\Ecoe\Controllers\EemController::consultarEstado');
});

foreach (glob(APPPATH . 'Modules/*/Config/Routes.php') ?: [] as $moduleRoutes) {
	require $moduleRoutes;
}
