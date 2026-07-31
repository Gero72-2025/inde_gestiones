<?php

$routes->group('gerencias/gero', [
    'namespace' => 'App\Modules\Gero\Controllers',
    'filter' => 'gerenciaAccess:gero,gerencia.gero.dashboard.access',
], static function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('comunidades', 'ComunidadesController::index', ['filter' => 'gerenciaAccess:gero,gerencia.gero.comunidades.access']);
    $routes->post('api/comunidades/list', 'ComunidadesController::list', ['filter' => 'gerenciaAccess:gero,gerencia.gero.comunidades.access']);
    $routes->post('api/comunidades', 'ComunidadesController::store', ['filter' => 'gerenciaAccess:gero,gerencia.gero.comunidades.access']);
    $routes->post('api/comunidades/(:num)', 'ComunidadesController::update/$1', ['filter' => 'gerenciaAccess:gero,gerencia.gero.comunidades.access']);
    $routes->post('api/comunidades/(:num)/delete', 'ComunidadesController::delete/$1', ['filter' => 'gerenciaAccess:gero,gerencia.gero.comunidades.access']);
    $routes->get('api/comunidades/(:num)/bitacora', 'ComunidadesController::bitacora/$1', ['filter' => 'gerenciaAccess:gero,gerencia.gero.comunidades.access']);
});