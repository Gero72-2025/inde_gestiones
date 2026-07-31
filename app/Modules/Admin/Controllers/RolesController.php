<?php

namespace App\Modules\Admin\Controllers;

class RolesController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\roles', [
            'title' => 'Roles y Permisos',
        ]);
    }

    public function listRoles()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $db = db_connect();
        $roles = $db->table('roles')->orderBy('id', 'ASC')->get()->getResultArray();

        foreach ($roles as &$role) {
            $role['permissions'] = $db->table('role_permissions rp')
                ->select('p.id, p.nombre, p.slug')
                ->join('permissions p', 'p.id = rp.permission_id')
                ->where('rp.role_id', (int) $role['id'])
                ->orderBy('p.slug', 'ASC')
                ->get()->getResultArray();
        }
        unset($role);

        return $this->encryptedAdminResponse(['roles' => $roles]);
    }

    public function listPermissions()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $db = db_connect();
        $permissions = $db->table('permissions')->orderBy('slug', 'ASC')->get()->getResultArray();

        return $this->encryptedAdminResponse(['permissions' => $permissions]);
    }

    public function storeRole()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []))) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede crear roles.'], 403);
        }

        $nombre = trim((string) $this->request->getPost('nombre'));
        $desc   = trim((string) $this->request->getPost('descripcion'));

        if ($nombre === '') {
            return $this->encryptedAdminResponse(['message' => 'El nombre del rol es obligatorio.'], 422);
        }

        $db = db_connect();

        if ($db->table('roles')->where('nombre', $nombre)->countAllResults() > 0) {
            return $this->encryptedAdminResponse(['message' => 'Ya existe un rol con ese nombre.'], 422);
        }

        $db->table('roles')->insert([
            'nombre' => $nombre,
            'descripcion' => $desc,
        ]);

        return $this->encryptedAdminResponse([
            'message' => 'Rol creado correctamente.',
            'id' => $db->insertID(),
        ]);
    }

    public function updateRolePermissions(int $roleId)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []))) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede modificar permisos de roles.'], 403);
        }

        $db = db_connect();
        $role = $db->table('roles')->where('id', $roleId)->get()->getRowArray();

        if (! is_array($role)) {
            return $this->encryptedAdminResponse(['message' => 'Rol no encontrado.'], 404);
        }

        $permissionIds = array_filter(array_map('intval', (array) ($this->request->getPost('permission_ids') ?? [])));

        $db->transStart();
        $db->table('role_permissions')->where('role_id', $roleId)->delete();

        foreach ($permissionIds as $pid) {
            $db->table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $pid]);
        }
        $db->transComplete();

        return $this->encryptedAdminResponse(['message' => 'Permisos del rol actualizados correctamente.']);
    }

    public function storePermission()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []))) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede crear permisos.'], 403);
        }

        $nombre = trim((string) $this->request->getPost('nombre'));
        $slug   = strtolower(trim((string) $this->request->getPost('slug')));
        $desc   = trim((string) $this->request->getPost('descripcion'));

        if ($nombre === '' || $slug === '') {
            return $this->encryptedAdminResponse(['message' => 'Nombre y slug son obligatorios.'], 422);
        }

        $db = db_connect();

        if ($db->table('permissions')->where('slug', $slug)->countAllResults() > 0) {
            return $this->encryptedAdminResponse(['message' => 'Ya existe un permiso con ese slug.'], 422);
        }

        $db->table('permissions')->insert(['nombre' => $nombre, 'slug' => $slug, 'descripcion' => $desc]);

        return $this->encryptedAdminResponse(['message' => 'Permiso creado correctamente.', 'id' => $db->insertID()]);
    }

    public function syncModulePermissions()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []))) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede sincronizar permisos.'], 403);
        }

        $db = db_connect();
        $moduleMap = $this->discoverGerenciaModules();
        $moduleSlugs = array_keys($moduleMap);

        $gerenciasCreated = 0;
        $gerenciasUpdated = 0;
        $permissionsCreated = 0;
        $permissionsUpdated = 0;
        $superadminLinksCreated = 0;

        $superAdminRole = $db->table('roles')->select('id')->where('nombre', 'Super Administrador')->get()->getRowArray();
        $superAdminRoleId = is_array($superAdminRole) ? (int) $superAdminRole['id'] : null;

        foreach ($moduleSlugs as $moduleSlug) {
            $gerenciaNombre = 'Gerencia ' . ucwords(str_replace(['-', '_'], ' ', $moduleSlug));

            $existingGerencia = $db->table('gerencias')->where('slug', $moduleSlug)->get()->getRowArray();

            if (! is_array($existingGerencia)) {
                $db->table('gerencias')->insert([
                    'nombre' => $gerenciaNombre,
                    'descripcion' => 'Gerencia sincronizada automaticamente desde App/Modules/' . $moduleSlug,
                    'slug' => $moduleSlug,
                    'status' => 'active',
                ]);
                $gerenciasCreated++;
                $gerenciaId = (int) $db->insertID();
            } else {
                $db->table('gerencias')->where('id', (int) $existingGerencia['id'])->update([
                    'nombre' => (string) ($existingGerencia['nombre'] ?: $gerenciaNombre),
                    'status' => (string) ($existingGerencia['status'] ?: 'active'),
                ]);
                $gerenciasUpdated++;
                $gerenciaId = (int) $existingGerencia['id'];
            }

            $permissionSlugs = array_values(array_unique(array_filter(array_merge(
                ['gerencia.' . $moduleSlug . '.access'],
                $moduleMap[$moduleSlug]['permissionSlugs'] ?? []
            ))));

            foreach ($permissionSlugs as $permissionSlug) {
                $permissionMeta = $this->buildPermissionMetadata($moduleSlug, $gerenciaNombre, $permissionSlug);
                $existingPermission = $db->table('permissions')->where('slug', $permissionSlug)->get()->getRowArray();

                if (! is_array($existingPermission)) {
                    $db->table('permissions')->insert($permissionMeta);
                    $permissionId = (int) $db->insertID();
                    $permissionsCreated++;
                } else {
                    $permissionId = (int) $existingPermission['id'];
                    $db->table('permissions')->where('id', $permissionId)->update([
                        'nombre' => $permissionMeta['nombre'],
                        'descripcion' => $permissionMeta['descripcion'],
                    ]);
                    $permissionsUpdated++;
                }

                if ($superAdminRoleId !== null && $permissionId > 0) {
                    $existsLink = $db->table('role_permissions')
                        ->where('role_id', $superAdminRoleId)
                        ->where('permission_id', $permissionId)
                        ->countAllResults();

                    if ($existsLink === 0) {
                        $db->table('role_permissions')->insert([
                            'role_id' => $superAdminRoleId,
                            'permission_id' => $permissionId,
                        ]);
                        $superadminLinksCreated++;
                    }
                }
            }

            if ($gerenciaId > 0) {
                // no-op; explicit variable use for readability/future hooks.
            }
        }

        $adminPermissionResult = $this->syncAdminMenuPermissions($superAdminRoleId);
        $permissionsCreated += $adminPermissionResult['created'];
        $permissionsUpdated += $adminPermissionResult['updated'];
        $superadminLinksCreated += $adminPermissionResult['links_created'];

        return $this->encryptedAdminResponse([
            'message' => 'Sincronizacion finalizada.',
            'total_modulos' => count($moduleSlugs),
            'total_rutas_detectadas' => array_sum(array_map(static fn (array $mod): int => count($mod['permissionSlugs'] ?? []), $moduleMap)),
            'gerencias_created' => $gerenciasCreated,
            'gerencias_updated' => $gerenciasUpdated,
            'permissions_created' => $permissionsCreated,
            'permissions_updated' => $permissionsUpdated,
            'superadmin_links_created' => $superadminLinksCreated,
        ]);
    }

    private function syncAdminMenuPermissions(?int $superAdminRoleId): array
    {
        $db = db_connect();
        $created = 0;
        $updated = 0;
        $linksCreated = 0;

        $definitions = [
            ['slug' => 'admin.dashboard.view', 'nombre' => 'Ver dashboard administrativo', 'descripcion' => 'Permite ver el inicio del panel administrativo.'],
            ['slug' => 'admin.gerencias.view', 'nombre' => 'Gestionar gerencias', 'descripcion' => 'Permite ver y administrar la seccion de gerencias.'],
            ['slug' => 'admin.usuarios.view', 'nombre' => 'Gestionar usuarios', 'descripcion' => 'Permite ver y administrar la seccion de usuarios.'],
            ['slug' => 'admin.roles.view', 'nombre' => 'Gestionar roles y permisos', 'descripcion' => 'Permite ver y administrar roles/permisos del sistema.'],
            ['slug' => 'admin.portal_publico.view', 'nombre' => 'Gestionar portal publico', 'descripcion' => 'Permite administrar el menu y secciones del portal publico.'],
            ['slug' => 'admin.migrations.view', 'nombre' => 'Gestionar migraciones', 'descripcion' => 'Permite consultar estado de migraciones y ejecutar controles de migracion.'],
            ['slug' => 'admin.logs.view', 'nombre' => 'Ver logs', 'descripcion' => 'Permite consultar logs y actividad del sistema.'],
            ['slug' => 'admin.uploads.view', 'nombre' => 'Gestionar cargas masivas', 'descripcion' => 'Permite acceder a carga masiva y descarga de archivos.'],
        ];

        foreach ($definitions as $def) {
            $existing = $db->table('permissions')->where('slug', $def['slug'])->get()->getRowArray();

            if (! is_array($existing)) {
                $db->table('permissions')->insert($def);
                $permissionId = (int) $db->insertID();
                $created++;
            } else {
                $permissionId = (int) $existing['id'];
                $db->table('permissions')->where('id', $permissionId)->update([
                    'nombre' => $def['nombre'],
                    'descripcion' => $def['descripcion'],
                ]);
                $updated++;
            }

            if ($superAdminRoleId !== null && $superAdminRoleId > 0 && $permissionId > 0) {
                $existsLink = $db->table('role_permissions')
                    ->where('role_id', $superAdminRoleId)
                    ->where('permission_id', $permissionId)
                    ->countAllResults();

                if ($existsLink === 0) {
                    $db->table('role_permissions')->insert([
                        'role_id' => $superAdminRoleId,
                        'permission_id' => $permissionId,
                    ]);
                    $linksCreated++;
                }
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'links_created' => $linksCreated,
        ];
    }

    private function discoverGerenciaModules(): array
    {
        $modulesPath = APPPATH . 'Modules';
        $entries = is_dir($modulesPath) ? scandir($modulesPath) : [];
        $result = [];

        foreach ((array) $entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $modulesPath . DIRECTORY_SEPARATOR . $entry;

            if (! is_dir($fullPath)) {
                continue;
            }

            $entrySlug = strtolower(trim((string) $entry));

            if (in_array($entrySlug, ['auth', 'admin'], true)) {
                continue;
            }

            $routeFile = $fullPath . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php';
            $routeContent = is_file($routeFile) ? (string) file_get_contents($routeFile) : '';
            $detectedSlug = '';
            $permissionSlugs = [];

            if ($routeContent !== '') {
                if (preg_match('/gerenciaAccess\s*:\s*([a-z0-9_\-]+)/i', $routeContent, $slugMatch) === 1) {
                    $detectedSlug = strtolower((string) $slugMatch[1]);
                }

                if (preg_match_all('/gerenciaAccess\s*:\s*[a-z0-9_\-]+\s*,\s*([a-z0-9._\-]+)/i', $routeContent, $permissionMatches) === 1 || ! empty($permissionMatches[1])) {
                    foreach ($permissionMatches[1] as $permissionSlug) {
                        $permissionSlugs[] = strtolower(trim((string) $permissionSlug));
                    }
                }
            }

            $slug = $detectedSlug !== '' ? $detectedSlug : $entrySlug;

            if (preg_match('/^[a-z][a-z0-9_\-]*$/', $slug) !== 1) {
                continue;
            }

            if (! array_key_exists($slug, $result)) {
                $result[$slug] = [
                    'entry' => $entry,
                    'permissionSlugs' => [],
                ];
            }

            $result[$slug]['permissionSlugs'] = array_values(array_unique(array_filter(array_merge(
                $result[$slug]['permissionSlugs'],
                $permissionSlugs
            ))));
        }

        ksort($result);

        return $result;
    }

    private function buildPermissionMetadata(string $moduleSlug, string $gerenciaNombre, string $permissionSlug): array
    {
        $nombre = 'Acceso al modulo ' . $gerenciaNombre;
        $descripcion = 'Permite ingresar al modulo de la gerencia ' . $gerenciaNombre . '.';
        $prefix = 'gerencia.' . $moduleSlug . '.';

        if (str_starts_with($permissionSlug, $prefix)) {
            $tail = substr($permissionSlug, strlen($prefix));

            if ($tail !== false && $tail !== '' && $tail !== 'access') {
                $segment = str_replace('.access', '', $tail);
                $segment = trim(str_replace(['.', '_', '-'], ' ', $segment));
                $segmentLabel = ucwords($segment);
                $nombre = 'Acceso ' . $segmentLabel . ' - ' . $gerenciaNombre;
                $descripcion = 'Permite acceder a la ruta/funcion ' . $segmentLabel . ' del modulo ' . $gerenciaNombre . '.';
            }
        }

        return [
            'nombre' => $nombre,
            'slug' => $permissionSlug,
            'descripcion' => $descripcion,
        ];
    }

    public function listUserRoles(int $userId)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $db   = db_connect();
        $user = $db->table('users')->where('id', $userId)->where('deleted_at', null)->get()->getRowArray();

        if (! is_array($user)) {
            return $this->encryptedAdminResponse(['message' => 'Usuario no encontrado.'], 404);
        }

        if (! $this->rbac->canManageGerenciaId((int) $auth['user_id'], (int) $user['gerencia_id'])) {
            return $this->encryptedAdminResponse(['message' => 'Sin acceso a este usuario.'], 403);
        }

        $assigned = $db->table('user_roles ur')
            ->select('r.id')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->get()->getResultArray();

        $assignedIds = array_column($assigned, 'id');
        $allRoles    = $db->table('roles')->orderBy('id', 'ASC')->get()->getResultArray();

        return $this->encryptedAdminResponse([
            'assigned_role_ids' => $assignedIds,
            'all_roles'         => $allRoles,
        ]);
    }

    public function updateUserRoles(int $userId)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $db   = db_connect();
        $user = $db->table('users')->where('id', $userId)->where('deleted_at', null)->get()->getRowArray();

        if (! is_array($user)) {
            return $this->encryptedAdminResponse(['message' => 'Usuario no encontrado.'], 404);
        }

        if (! $this->rbac->canManageGerenciaId((int) $auth['user_id'], (int) $user['gerencia_id'])) {
            return $this->encryptedAdminResponse(['message' => 'Sin acceso a este usuario.'], 403);
        }

        $roleIds = array_filter(array_map('intval', (array) ($this->request->getPost('role_ids') ?? [])));

        $db->transStart();
        $db->table('user_roles')->where('user_id', $userId)->delete();

        foreach ($roleIds as $rid) {
            $db->table('user_roles')->insert(['user_id' => $userId, 'role_id' => $rid]);
        }
        $db->transComplete();

        return $this->encryptedAdminResponse(['message' => 'Roles del usuario actualizados correctamente.']);
    }
}
