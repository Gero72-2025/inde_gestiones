<?php

namespace App\Filters;

use App\Modules\Auth\Services\RbacService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminPermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = session('auth');

        if (! is_array($auth) || empty($auth['user_id'])) {
            return redirect()->to('login')->with('error', 'Debes iniciar sesion para continuar.');
        }

        $requiredPermission = trim((string) ($arguments[0] ?? ''));

        if ($requiredPermission === '') {
            return null;
        }

        $rbac = new RbacService();
        $permissions = array_map('strval', (array) ($auth['permissions'] ?? []));

        if ($rbac->isSuperAdminByPermissions($permissions) || $rbac->hasPermission($permissions, $requiredPermission)) {
            return null;
        }

        $isAjax = strtolower((string) $request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

        if ($isAjax) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'ok' => false,
                    'message' => 'No tienes permiso para acceder a este recurso.',
                    'required_permission' => $requiredPermission,
                ]);
        }

        return redirect()->to('admin')->with('error', 'No tienes permisos para acceder a esta seccion.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
