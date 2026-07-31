<?php

namespace App\Modules\Gero\Controllers;

use App\Modules\GerenciaBaseController;

class DashboardController extends GerenciaBaseController
{
    protected string $moduleSlug = 'gero';

    public function index(): string
    {
        return $this->renderModulePage('App\\Modules\\Gero\\Views\\dashboard', [
            'moduleSlug' => $this->moduleSlug,
        ], 'Dashboard de GERO');
    }
}