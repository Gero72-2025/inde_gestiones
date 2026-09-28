<?php

// ─── Panel Administrativo ECOE ────────────────────────────────────────────────
$routes->group('gerencias/ecoe', [
    'namespace' => 'App\Modules\Ecoe\Controllers',
], static function ($routes) {
    $routes->get('/', 'DashboardController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.dashboard.access']);
    $routes->get('dashboard', 'DashboardController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.dashboard.access']);
    $routes->get('modulo/(:segment)', 'ModuleController::index/$1', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.modulo.access']);

    // Motor de formularios ECOE
    $routes->get('formularios', 'FormularioController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.formularios.access']);
    $routes->post('formularios', 'FormularioController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.formularios.access']);

    // Grandes Usuarios
    $routes->get('grandes-usuarios', 'GuController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.gu.access']);
    $routes->post('grandes-usuarios', 'GuController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.gu.access']);
    $routes->get('grandes-usuarios/continuacion/(:segment)', 'GuController::descargarContinuacion/$1', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.gu.access']);

    // EEM
    $routes->get('eem', 'EemController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.eem.access']);
    $routes->post('eem', 'EemController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.eem.access']);

    // ── Tarifa Social: gestión de tickets ──────────────────────────────────
    $routes->get('tarifa-social', 'TarifaSocialController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);
    $routes->post('tarifa-social', 'TarifaSocialController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);
    $routes->get('tarifa-social/estados', 'TarifaSocialController::estadosIndex', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);
    $routes->post('tarifa-social/estados', 'TarifaSocialController::estadosIndex', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);
    $routes->get('tarifa-social/tarifas', 'TarifasMensualesController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.tarifas.access']);
    $routes->post('tarifa-social/tarifas', 'TarifasMensualesController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.tarifas.access']);

    // ── Tarifa Social: adjuntos (JSON) ─────────────────────────────────────
    $routes->get('tarifa-social/adjuntos', 'TarifaSocialController::adjuntosJson', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);
    $routes->get('tarifa-social/bitacora', 'TarifaSocialController::bitacoraJson', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);

    // ── Tarifa Social: descarga segura de adjunto ──────────────────────────
    $routes->get('tarifa-social/adjunto/(:num)', 'TarifaSocialController::descargarAdjunto/$1', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);

    // ── Tarifa Social: Base NIS / carga masiva XLSX ────────────────────────
    $routes->get('tarifa-social/nis', 'TarifaSocialController::nisIndex', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);
    $routes->post('tarifa-social/nis', 'TarifaSocialController::nisIndex', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);

    // ── Tarifa Social: plantillas PDF ───────────────────────────────────────
    $routes->get('tarifa-social/plantillas', 'TarifaSocialController::plantillasIndex', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);
    $routes->post('tarifa-social/plantillas', 'TarifaSocialController::plantillasIndex', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);
    $routes->get('tarifa-social/plantillas/preview/(:num)', 'TarifaSocialController::previewPlantillaPdf/$1', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.tarifa_social.access']);

    // ── CRUD Base NIS – Gestión completa de registro de usuarios NIS ─────
    $routes->get('nis-base', 'NisBaseAdminController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.nis_base.access']);
    $routes->post('nis-base', 'NisBaseAdminController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.nis_base.access']);
    $routes->get('nis-base/create', 'NisBaseAdminController::create', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.nis_base.access']);
    $routes->post('nis-base/store', 'NisBaseAdminController::store', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.nis_base.access']);
    $routes->get('nis-base/edit/(:num)', 'NisBaseAdminController::edit/$1', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.nis_base.access']);
    $routes->post('nis-base/update/(:num)', 'NisBaseAdminController::update/$1', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.nis_base.access']);

    // ── CRUD Distribuidoras – Gestión de empresas distribuidoras de energía ─
    $routes->get('distribuidoras', 'DistribuidoraAdminController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.distribuidoras.access']);
    $routes->post('distribuidoras', 'DistribuidoraAdminController::index', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.distribuidoras.access']);
    $routes->get('distribuidoras/create', 'DistribuidoraAdminController::create', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.distribuidoras.access']);
    $routes->post('distribuidoras/store', 'DistribuidoraAdminController::store', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.distribuidoras.access']);
    $routes->get('distribuidoras/edit/(:num)', 'DistribuidoraAdminController::edit/$1', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.distribuidoras.access']);
    $routes->post('distribuidoras/update/(:num)', 'DistribuidoraAdminController::update/$1', ['filter' => 'gerenciaAccess:ecoe,gerencia.ecoe.distribuidoras.access']);
});
