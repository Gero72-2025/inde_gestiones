<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Auth\Services\RbacService;
use CodeIgniter\HTTP\RedirectResponse;

abstract class AdminBaseController extends BaseController
{
    protected RbacService $rbac;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->rbac = new RbacService();
    }

    protected function authProfile(): array|RedirectResponse
    {
        $auth = $this->session->get('auth');

        if (! is_array($auth) || empty($auth['user_id'])) {
            return redirect()->to('login')->with('error', 'Debes iniciar sesion para acceder al panel.');
        }

        if (($auth['twofa_enabled'] ?? false) && ! ($auth['is_2fa_verified'] ?? false)) {
            return redirect()->to('2fa/verify')->with('error', 'Debes completar el segundo factor.');
        }

        $fresh = $this->rbac->getUserAccessProfile((int) $auth['user_id']);

        if (! is_array($fresh) || ($fresh['status'] ?? 'inactive') !== 'active') {
            $this->session->destroy();

            return redirect()->to('login')->with('error', 'Tu perfil de acceso ya no es valido. Inicia sesion nuevamente.');
        }

        $auth = array_merge($auth, [
            'gerencia_id' => isset($fresh['gerencia_id']) ? (int) $fresh['gerencia_id'] : null,
            'gerencia_slug' => $fresh['gerencia_slug'] ?? null,
            'gerencia_nombre' => $fresh['gerencia_nombre'] ?? null,
            'username' => $fresh['username'] ?? ($auth['username'] ?? null),
            'email' => $fresh['email'] ?? ($auth['email'] ?? null),
            'roles' => (array) ($fresh['roles'] ?? []),
            'permissions' => (array) ($fresh['permissions'] ?? []),
            'twofa_enabled' => (bool) ($fresh['twofa_enabled'] ?? false),
            'is_2fa_verified' => (bool) ($auth['is_2fa_verified'] ?? false),
        ]);

        $this->session->set('auth', $auth);

        return $auth;
    }

    protected function adminView(string $view, array $data = []): string
    {
        $auth = $this->session->get('auth') ?? [];
        $isSuperAdmin = $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []));
        $permissions = array_map('strval', (array) ($auth['permissions'] ?? []));
        $menuModules = is_array($auth) && ! empty($auth['user_id'])
            ? $this->rbac->getSidebarModules((int) $auth['user_id'])
            : [];
        $adminNavItems = [];

        $navDefinitions = [
            ['label' => 'Inicio', 'icon' => 'bi-house', 'url' => site_url('admin'), 'permission' => 'admin.dashboard.view'],
            ['label' => 'Gerencias', 'icon' => 'bi-building', 'url' => site_url('admin/gerencias'), 'permission' => 'admin.gerencias.view'],
            ['label' => 'Usuarios', 'icon' => 'bi-people', 'url' => site_url('admin/usuarios'), 'permission' => 'admin.usuarios.view'],
            ['label' => 'Roles y Permisos', 'icon' => 'bi-shield-lock', 'url' => site_url('admin/roles'), 'permission' => 'admin.roles.view'],
            ['label' => 'Portal Publico', 'icon' => 'bi-globe2', 'url' => site_url('admin/portal-publico'), 'permission' => 'admin.portal_publico.view'],
            ['label' => 'Idiomas', 'icon' => 'bi-translate', 'url' => site_url('admin/idiomas'), 'permission' => 'admin.idiomas.view'],
            ['label' => 'Consejos de ahorro', 'icon' => 'bi-lightbulb', 'url' => site_url('admin/consejos'), 'permission' => 'admin.consejos.view'],
            ['label' => 'Migraciones', 'icon' => 'bi-database-gear', 'url' => site_url('admin/migraciones'), 'permission' => 'admin.migrations.view'],
            ['label' => 'Logs', 'icon' => 'bi-journal-text', 'url' => site_url('admin/logs'), 'permission' => 'admin.logs.view'],
            ['label' => 'Carga masiva', 'icon' => 'bi-cloud-upload', 'url' => site_url('admin/uploads'), 'permission' => 'admin.uploads.view'],
        ];

        foreach ($navDefinitions as $item) {
            if ($isSuperAdmin || $this->rbac->hasPermission($permissions, $item['permission'])) {
                $adminNavItems[] = $item;
            }
        }

        $innerData = array_merge($data, [
            'auth' => $auth,
            'isSuperAdmin' => $isSuperAdmin,
        ]);

        return view('App\\Modules\\Admin\\Views\\layout', [
            'innerView'   => $view,
            'innerData'   => $innerData,
            'auth'        => $auth,
            'menuModules' => $menuModules,
            'adminNavItems' => $adminNavItems,
            'ajaxCipherKey' => (string) env('security.ajaxCipherKey', ''),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    protected function encryptedAdminResponse(array $payload, int $statusCode = 200)
    {
        return $this->encryptedJsonResponse([
            'ok' => $statusCode < 400,
            'data' => $payload,
        ], $statusCode);
    }
}
