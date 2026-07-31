<?php

$routes->group('gerencias/etcee', [
    'namespace' => 'App\Modules\Etcee\Controllers',
], static function ($routes) {
    $routes->get('/', 'DashboardController::index', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.dashboard.access']);
    $routes->get('dashboard', 'DashboardController::index', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.dashboard.access']);
    $routes->get('modulo/(:segment)', 'ModuleController::index/$1', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.modulo.access']);
    $routes->get('sni/wizard', 'SniController::wizard', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->post('sni/wizard', 'SniController::wizard', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->get('sni/lineas-sistema', 'SniController::lineasSistema', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->post('sni/lineas-sistema', 'SniController::lineasSistema', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->get('sni/lineas-sistema/get/(:num)', 'SniController::lineaSistemaGet/$1', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->get('sni', 'SniController::index', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->post('sni', 'SniController::index', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->get('sni/crud', 'SniController::index', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->post('sni/crud', 'SniController::index', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->get('sni/get/(:num)', 'SniController::get/$1', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->get('sni/capas', 'SniController::capas', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->post('sni/capas', 'SniController::capas', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->get('sni/capas/icons', 'SniController::capaIcons', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
    $routes->get('sni/capas/get/(:num)', 'SniController::capaGet/$1', ['filter' => 'gerenciaAccess:etcee,gerencia.etcee.sni.access']);
});
