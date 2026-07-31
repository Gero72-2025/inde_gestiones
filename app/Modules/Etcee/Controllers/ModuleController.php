<?php

namespace App\Modules\Etcee\Controllers;

use App\Modules\GerenciaBaseController;

class ModuleController extends GerenciaBaseController
{
    protected string $moduleSlug = 'etcee';

    public function index(string $section = 'base'): string
    {
        $section = strtolower(trim($section)) ?: 'base';

        return $this->renderModulePage('App\\Modules\\Etcee\\Views\\module', [
            'moduleSlug' => $this->moduleSlug,
            'section' => $section,
        ], 'Modulo ' . strtoupper($section) . ' - ETCEE');
    }
}
