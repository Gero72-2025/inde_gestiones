<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = session('auth');

        if (! is_array($auth) || empty($auth['user_id'])) {
            return redirect()->to('login')->with('error', 'Debes iniciar sesion para acceder al panel administrativo.');
        }

        if (($auth['twofa_enabled'] ?? false) && ! ($auth['is_2fa_verified'] ?? false)) {
            return redirect()->to('2fa/verify')->with('error', 'Debes verificar el segundo factor para entrar al panel.');
        }

        $permissions = array_map('strval', (array) ($auth['permissions'] ?? []));
        $roles = array_map('strval', (array) ($auth['roles'] ?? []));

        $hasGerenciaPermission = false;
        $hasAdminSectionPermission = false;
        foreach ($permissions as $permission) {
            if (str_starts_with($permission, 'gerencia.') && str_ends_with($permission, '.access')) {
                $hasGerenciaPermission = true;
            }

            if (str_starts_with($permission, 'admin.') && str_ends_with($permission, '.view')) {
                $hasAdminSectionPermission = true;
            }
        }

        $canEnterAdmin = in_array('superadmin.access', $permissions, true)
            || in_array('gerencias.all.access', $permissions, true)
            || in_array('admin.access', $permissions, true)
            || in_array('Super Administrador', $roles, true)
            || in_array('Administrador de Gerencia', $roles, true)
            || $hasAdminSectionPermission
            || $hasGerenciaPermission;

        if (! $canEnterAdmin) {
            $slug = (string) ($auth['gerencia_slug'] ?? 'gero');

            return redirect()->to('gerencias/' . $slug)->with('error', 'Tu usuario no tiene acceso al panel administrativo.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
