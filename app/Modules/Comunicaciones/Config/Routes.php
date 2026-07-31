<?php

$routes->group('gerencias/comunicaciones', [
    'namespace' => 'App\Modules\Comunicaciones\Controllers',
], static function ($routes) {
    $routes->get('/', 'DashboardController::index', ['filter' => 'gerenciaAccess:comunicaciones,gerencia.comunicaciones.dashboard.access']);
    $routes->get('dashboard', 'DashboardController::index', ['filter' => 'gerenciaAccess:comunicaciones,gerencia.comunicaciones.dashboard.access']);
    $routes->get('modulo/(:segment)', 'ModuleController::index/$1', ['filter' => 'gerenciaAccess:comunicaciones,gerencia.comunicaciones.modulo.access']);
});
