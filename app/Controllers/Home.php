<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class Home extends BaseController
{
    public function index(): RedirectResponse
    {
        $auth = $this->session->get('auth');

        if (is_array($auth) && ! empty($auth['permissions'])) {
            $permissions = array_map('strval', (array) $auth['permissions']);

            if (in_array('superadmin.access', $permissions, true) || in_array('admin.access', $permissions, true)) {
                return redirect()->to('admin');
            }

            foreach ($permissions as $permission) {
                if (str_starts_with($permission, 'admin.') && str_ends_with($permission, '.view')) {
                    return redirect()->to('admin');
                }
            }
        }

        if (is_array($auth) && ! empty($auth['gerencia_slug'])) {
            return redirect()->to('gerencias/' . $auth['gerencia_slug']);
        }

        return redirect()->to('login');
    }
}
