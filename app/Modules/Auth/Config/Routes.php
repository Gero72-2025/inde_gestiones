<?php

$routes->group('', [
    'namespace' => 'App\Modules\Auth\Controllers',
    'filter' => 'activityLog',
], static function ($routes) {
    $routes->get('login', 'AuthController::loginView');
    $routes->post('login', 'AuthController::login');
    $routes->get('2fa/verify', 'AuthController::twoFactorView');
    $routes->post('2fa/verify', 'AuthController::verifyTwoFactor');
    $routes->get('2fa/setup', 'AuthController::setupTwoFactorView');
    $routes->post('2fa/setup', 'AuthController::confirmTwoFactorSetup');
    $routes->post('logout', 'AuthController::logout');
});