<?php

namespace App\Modules\Admin\Controllers;

class DashboardController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\dashboard', [
            'title' => 'Dashboard Administrativo',
        ]);
    }
}
