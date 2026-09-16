<?php

$routes->group('admin', [
    'namespace' => 'App\Modules\Admin\Controllers',
    'filter' => ['adminAuth', 'activityLog'],
], static function ($routes) {
    $routes->get('/', 'DashboardController::index', ['filter' => 'adminPermission:admin.dashboard.view']);

    $routes->get('gerencias', 'GerenciasController::index', ['filter' => 'adminPermission:admin.gerencias.view']);
    $routes->get('usuarios', 'UsersController::index', ['filter' => 'adminPermission:admin.usuarios.view']);
    $routes->get('uploads', 'UploadController::index', ['filter' => 'adminPermission:admin.uploads.view']);
    $routes->get('logs', 'LogsController::index', ['filter' => 'adminPermission:admin.logs.view']);
    $routes->get('portal-publico', 'PublicMenuController::index', ['filter' => 'adminPermission:admin.portal_publico.view']);
    $routes->get('migraciones', 'MigrationsController::index', ['filter' => 'adminPermission:admin.migrations.view']);
    $routes->get('idiomas', 'TranslationsController::index', ['filter' => 'adminPermission:admin.idiomas.view']);
    $routes->get('idiomas/list', 'TranslationsController::list', ['filter' => 'adminPermission:admin.idiomas.view']);
    $routes->post('idiomas/save', 'TranslationsController::save', ['filter' => 'adminPermission:admin.idiomas.view']);
    $routes->post('idiomas/delete', 'TranslationsController::delete', ['filter' => 'adminPermission:admin.idiomas.view']);
    $routes->post('idiomas/clear-cache', 'TranslationsController::clearCache', ['filter' => 'adminPermission:admin.idiomas.view']);

    $routes->get('api/gerencias', 'GerenciasController::list', ['filter' => 'adminPermission:admin.gerencias.view']);
    $routes->post('api/gerencias', 'GerenciasController::store', ['filter' => 'adminPermission:admin.gerencias.view']);
    $routes->post('api/gerencias/(:num)', 'GerenciasController::update/$1', ['filter' => 'adminPermission:admin.gerencias.view']);
    $routes->post('api/gerencias/(:num)/delete', 'GerenciasController::delete/$1', ['filter' => 'adminPermission:admin.gerencias.view']);

    $routes->get('api/usuarios', 'UsersController::list', ['filter' => 'adminPermission:admin.usuarios.view']);
    $routes->post('api/usuarios', 'UsersController::store', ['filter' => 'adminPermission:admin.usuarios.view']);
    $routes->post('api/usuarios/(:num)', 'UsersController::update/$1', ['filter' => 'adminPermission:admin.usuarios.view']);
    $routes->post('api/usuarios/(:num)/delete', 'UsersController::delete/$1', ['filter' => 'adminPermission:admin.usuarios.view']);

    $routes->get('api/uploads', 'UploadController::list', ['filter' => 'adminPermission:admin.uploads.view']);
    $routes->post('api/uploads', 'UploadController::store', ['filter' => 'adminPermission:admin.uploads.view']);
    $routes->get('uploads/(:num)/download', 'UploadController::download/$1', ['filter' => 'adminPermission:admin.uploads.view']);

    $routes->get('roles', 'RolesController::index', ['filter' => 'adminPermission:admin.roles.view']);
    $routes->get('api/roles', 'RolesController::listRoles', ['filter' => 'adminPermission:admin.roles.view']);
    $routes->post('api/roles', 'RolesController::storeRole', ['filter' => 'adminPermission:admin.roles.view']);
    $routes->get('api/permissions', 'RolesController::listPermissions', ['filter' => 'adminPermission:admin.roles.view']);
    $routes->post('api/permissions', 'RolesController::storePermission', ['filter' => 'adminPermission:admin.roles.view']);
    $routes->post('api/roles/(:num)/permissions', 'RolesController::updateRolePermissions/$1', ['filter' => 'adminPermission:admin.roles.view']);
    $routes->post('api/permissions/sync-modules', 'RolesController::syncModulePermissions', ['filter' => 'adminPermission:admin.roles.view']);
    $routes->get('api/usuarios/(:num)/roles', 'RolesController::listUserRoles/$1', ['filter' => 'adminPermission:admin.roles.view']);
    $routes->post('api/usuarios/(:num)/roles', 'RolesController::updateUserRoles/$1', ['filter' => 'adminPermission:admin.roles.view']);
    $routes->get('api/logs', 'LogsController::list', ['filter' => 'adminPermission:admin.logs.view']);
    $routes->get('api/public-menu', 'PublicMenuController::list', ['filter' => 'adminPermission:admin.portal_publico.view']);
    $routes->post('api/public-menu', 'PublicMenuController::store', ['filter' => 'adminPermission:admin.portal_publico.view']);
    $routes->post('api/public-menu/(:num)', 'PublicMenuController::update/$1', ['filter' => 'adminPermission:admin.portal_publico.view']);
    $routes->post('api/public-menu/(:num)/delete', 'PublicMenuController::delete/$1', ['filter' => 'adminPermission:admin.portal_publico.view']);
    $routes->get('api/migraciones/status', 'MigrationsController::status', ['filter' => 'adminPermission:admin.migrations.view']);
    $routes->post('api/migraciones/execute', 'MigrationsController::execute', ['filter' => 'adminPermission:admin.migrations.view']);

    $routes->get('gero/comunidades', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::index', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->post('api/gero/comunidades/list', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::list', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->post('api/gero/comunidades', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::store', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->post('api/gero/comunidades/(:num)', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::update/$1', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->post('api/gero/comunidades/(:num)/delete', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::delete/$1', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->get('api/gero/comunidades/(:num)/bitacora', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::bitacora/$1', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->post('api/gero/comunidades/source-upload', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::sourceUpload', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->get('api/gero/comunidades/source-schema', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::sourceSchemaList', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->post('api/gero/comunidades/source-schema/save-mapping', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::sourceSchemaSaveMapping', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->get('api/gero/comunidades/source-logs', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::sourceLogsList', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);
    $routes->post('api/gero/comunidades/source-cleanup', '\\App\\Modules\\Gero\\Controllers\\ComunidadesController::sourceCleanup', ['filter' => 'adminPermission:gerencia.gero.comunidades.access']);

    $routes->get('etcee/dashboard', '\\App\\Modules\\Etcee\\Controllers\\CortesController::index', ['filter' => 'adminPermission:gerencia.etcee.modulo.access']);
    $routes->get('etcee/cortes', '\\App\\Modules\\Etcee\\Controllers\\CortesController::index', ['filter' => 'adminPermission:gerencia.etcee.modulo.access']);
    $routes->get('etcee/sni', '\\App\\Modules\\Etcee\\Controllers\\SniController::index', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->post('etcee/sni', '\\App\\Modules\\Etcee\\Controllers\\SniController::index', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->get('etcee/sni/crud', '\\App\\Modules\\Etcee\\Controllers\\SniController::index', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->post('etcee/sni/crud', '\\App\\Modules\\Etcee\\Controllers\\SniController::index', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->get('etcee/sni/get/(:num)', '\\App\\Modules\\Etcee\\Controllers\\SniController::get/$1', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->get('etcee/sni/wizard', '\\App\\Modules\\Etcee\\Controllers\\SniController::wizard', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->post('etcee/sni/wizard', '\\App\\Modules\\Etcee\\Controllers\\SniController::wizard', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->get('etcee/sni/lineas-sistema', '\\App\\Modules\\Etcee\\Controllers\\SniController::lineasSistema', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->post('etcee/sni/lineas-sistema', '\\App\\Modules\\Etcee\\Controllers\\SniController::lineasSistema', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->get('etcee/sni/lineas-sistema/get/(:num)', '\\App\\Modules\\Etcee\\Controllers\\SniController::lineaSistemaGet/$1', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->get('etcee/sni/capas', '\\App\\Modules\\Etcee\\Controllers\\SniController::capas', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->post('etcee/sni/capas', '\\App\\Modules\\Etcee\\Controllers\\SniController::capas', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->get('etcee/sni/capas/icons', '\\App\\Modules\\Etcee\\Controllers\\SniController::capaIcons', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->get('etcee/sni/capas/get/(:num)', '\\App\\Modules\\Etcee\\Controllers\\SniController::capaGet/$1', ['filter' => 'adminPermission:gerencia.etcee.sni.access']);
    $routes->post('api/etcee/cortes/catalogos', '\\App\\Modules\\Etcee\\Controllers\\CortesController::catalogos', ['filter' => 'adminPermission:gerencia.etcee.modulo.access']);
    $routes->post('api/etcee/cortes/list', '\\App\\Modules\\Etcee\\Controllers\\CortesController::list', ['filter' => 'adminPermission:gerencia.etcee.modulo.access']);
    $routes->post('api/etcee/cortes', '\\App\\Modules\\Etcee\\Controllers\\CortesController::store', ['filter' => 'adminPermission:gerencia.etcee.modulo.access']);
    $routes->post('api/etcee/cortes/(:num)', '\\App\\Modules\\Etcee\\Controllers\\CortesController::update/$1', ['filter' => 'adminPermission:gerencia.etcee.modulo.access']);
    $routes->post('api/etcee/cortes/(:num)/delete', '\\App\\Modules\\Etcee\\Controllers\\CortesController::delete/$1', ['filter' => 'adminPermission:gerencia.etcee.modulo.access']);

    $routes->get('(:segment)/(:segment)', 'GerenciaModuleController::section/$1/$2');
    $routes->get('(:segment)', 'GerenciaModuleController::dashboard/$1');
});
