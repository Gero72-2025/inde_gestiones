<?php

namespace App\Modules\Admin\Controllers;

use App\Modules\GerenciaBaseController;

class GerenciaModuleController extends GerenciaBaseController
{
    public function dashboard(string $slug)
    {
        $this->moduleSlug = strtolower($slug);
        $auth = $this->requireModuleAccess($this->moduleSlug);
        $rbac = $this->rbac();

        return view('App\\Modules\\Admin\\Views\\layout', [
            'innerView' => 'App\\Modules\\Admin\\Views\\gerencia_module',
            'innerData' => [
                'title' => 'Dashboard de ' . strtoupper($this->moduleSlug),
                'moduleSlug' => $this->moduleSlug,
                'section' => 'dashboard',
            ],
            'auth' => $auth,
            'menuModules' => $rbac->getSidebarModules((int) $auth['user_id']),
            'ajaxCipherKey' => (string) env('security.ajaxCipherKey', ''),
        ]);
    }

    public function section(string $slug, string $section)
    {
        $this->moduleSlug = strtolower($slug);
        $auth = $this->requireModuleAccess($this->moduleSlug);
        $rbac = $this->rbac();

        return view('App\\Modules\\Admin\\Views\\layout', [
            'innerView' => 'App\\Modules\\Admin\\Views\\gerencia_module',
            'innerData' => [
                'title' => strtoupper($section) . ' - ' . strtoupper($this->moduleSlug),
                'moduleSlug' => $this->moduleSlug,
                'section' => strtolower($section),
            ],
            'auth' => $auth,
            'menuModules' => $rbac->getSidebarModules((int) $auth['user_id']),
            'ajaxCipherKey' => (string) env('security.ajaxCipherKey', ''),
        ]);
    }
}
