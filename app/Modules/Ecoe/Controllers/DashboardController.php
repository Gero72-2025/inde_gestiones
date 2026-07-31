<?php

namespace App\Modules\Ecoe\Controllers;

use App\Modules\GerenciaBaseController;

class DashboardController extends GerenciaBaseController
{
    protected string $moduleSlug = 'ecoe';

    public function index(): string
    {
        return $this->renderModulePage('App\\Modules\\Ecoe\\Views\\dashboard', [
            'moduleSlug' => $this->moduleSlug,
        ], 'Dashboard de ' . strtoupper($this->moduleSlug));
    }
}
