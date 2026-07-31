<?php

namespace App\Modules\Comunicaciones\Controllers;

use App\Modules\GerenciaBaseController;

class DashboardController extends GerenciaBaseController
{
    protected string $moduleSlug = 'comunicaciones';

    public function index(): string
    {
        return $this->renderModulePage('App\\Modules\\Comunicaciones\\Views\\dashboard', [
            'moduleSlug' => $this->moduleSlug,
        ], 'Dashboard de COMUNICACIONES');
    }
}
