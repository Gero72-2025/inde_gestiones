<?php

namespace App\Modules\Ecoe\Controllers;

use App\Modules\GerenciaBaseController;

class ModuleController extends GerenciaBaseController
{
    protected string $moduleSlug = 'ecoe';

    public function index(string $section = 'base'): string
    {
        $section = strtolower(trim($section)) ?: 'base';

        return $this->renderModulePage('App\\Modules\\Ecoe\\Views\\module', [
            'moduleSlug' => $this->moduleSlug,
            'section' => $section,
        ], 'Modulo ' . strtoupper($section) . ' - ' . strtoupper($this->moduleSlug));
    }
}
