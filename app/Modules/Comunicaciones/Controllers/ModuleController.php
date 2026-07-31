<?php

namespace App\Modules\Comunicaciones\Controllers;

use App\Modules\GerenciaBaseController;

class ModuleController extends GerenciaBaseController
{
    protected string $moduleSlug = 'comunicaciones';

    public function index(string $section = 'base'): string
    {
        $section = strtolower(trim($section)) ?: 'base';

        return $this->renderModulePage('App\\Modules\\Comunicaciones\\Views\\module', [
            'moduleSlug' => $this->moduleSlug,
            'section' => $section,
        ], 'Modulo ' . strtoupper($section) . ' - COMUNICACIONES');
    }
}
