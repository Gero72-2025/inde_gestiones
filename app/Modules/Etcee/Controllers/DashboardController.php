<?php

namespace App\Modules\Etcee\Controllers;

use App\Modules\GerenciaBaseController;

class DashboardController extends GerenciaBaseController
{
    protected string $moduleSlug = 'etcee';

    public function index(): string
    {
        return $this->renderModulePage('App\\Modules\\Etcee\\Views\\dashboard', [
            'moduleSlug' => $this->moduleSlug,
        ], 'Dashboard de ETCEE');
    }
}
