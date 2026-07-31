<?php

namespace App\Modules\Auth\Services;

class RbacService
{
    public function getUserAccessProfile(int $userId): ?array
    {
        $db = db_connect();
        $user = $db->table('users u')
            ->select('u.id, u.gerencia_id, u.username, u.email, u.status, u.twofa_enabled, g.slug AS gerencia_slug, g.nombre AS gerencia_nombre')
            ->join('gerencias g', 'g.id = u.gerencia_id', 'left')
            ->where('u.id', $userId)
            ->where('u.deleted_at', null)
            ->get()
            ->getRowArray();

        if ($user === null) {
            return null;
        }

        $roles = $db->table('user_roles ur')
            ->select('r.nombre')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->get()
            ->getResultArray();

        $user['roles'] = array_values(array_unique(array_filter(array_map(
            static fn (array $row): string => (string) $row['nombre'],
            $roles
        ))));
        $user['permissions'] = $this->resolvePermissions($userId);
        $user['twofa_enabled'] = (bool) $user['twofa_enabled'];

        return $user;
    }

    public function userCanAccessModule(int $userId, string $moduleSlug, ?string $permissionSlug = null): bool
    {
        $profile = $this->getUserAccessProfile($userId);

        if ($profile === null || ($profile['status'] ?? 'inactive') !== 'active') {
            return false;
        }

        $permissions = $profile['permissions'] ?? [];
        $sameGerencia = ($profile['gerencia_slug'] ?? null) === $moduleSlug;
        $hasCrossGerenciaAccess = $this->isSuperAdminByPermissions($permissions);

        if (! $sameGerencia && ! $hasCrossGerenciaAccess) {
            return false;
        }

        $requiredPermission = $permissionSlug ?: ('gerencia.' . $moduleSlug . '.access');

        return $hasCrossGerenciaAccess || $this->hasPermission($permissions, $requiredPermission);
    }

    public function canManageGerenciaId(int $userId, int $gerenciaId): bool
    {
        $profile = $this->getUserAccessProfile($userId);

        if ($profile === null) {
            return false;
        }

        if ($this->isSuperAdminByPermissions($profile['permissions'] ?? [])) {
            return true;
        }

        return (int) ($profile['gerencia_id'] ?? 0) === $gerenciaId;
    }

    public function getAccessibleGerencias(int $userId): array
    {
        $profile = $this->getUserAccessProfile($userId);

        if ($profile === null) {
            return [];
        }

        $db = db_connect();
        $builder = $db->table('gerencias')->select('id, nombre, slug, status')->where('status', 'active');
        $permissions = (array) ($profile['permissions'] ?? []);

        if (! $this->isSuperAdminByPermissions($permissions)) {
            $gerenciaId = (int) ($profile['gerencia_id'] ?? 0);

            if ($gerenciaId > 0) {
                $builder->where('id', $gerenciaId);
            } else {
                $slugs = [];

                foreach ($permissions as $permission) {
                    if (preg_match('/^gerencia\.([a-z0-9_\-]+)\.access$/', (string) $permission, $matches) === 1) {
                        $slugs[] = (string) $matches[1];
                    }
                }

                $slugs = array_values(array_unique($slugs));

                if ($slugs !== []) {
                    $builder->whereIn('slug', $slugs);
                } else {
                    return [];
                }
            }
        }

        return $builder->orderBy('nombre', 'ASC')->get()->getResultArray();
    }

    public function getSidebarModules(int $userId): array
    {
        $profile = $this->getUserAccessProfile($userId);

        if ($profile === null) {
            return [];
        }

        $permissions = $profile['permissions'] ?? [];
        $isSuperAdmin = $this->isSuperAdminByPermissions($permissions);
        $gerencias = $this->getAccessibleGerencias($userId);
        $modules = [];

        foreach ($gerencias as $gerencia) {
            $slug = (string) $gerencia['slug'];
            $required = 'gerencia.' . $slug . '.access';
            $targetUrl = $slug === 'etcee'
                ? site_url('admin/etcee/cortes')
                : site_url('admin/' . $slug . '/dashboard');

            if ($isSuperAdmin || $this->hasPermission($permissions, $required)) {
                $modules[] = [
                    'id' => (int) $gerencia['id'],
                    'slug' => $slug,
                    'nombre' => (string) $gerencia['nombre'],
                    'url' => $targetUrl,
                ];
            }
        }

        return $modules;
    }

    public function isSuperAdminByPermissions(array $permissions): bool
    {
        return $this->hasPermission($permissions, 'gerencias.all.access')
            || $this->hasPermission($permissions, 'superadmin.access');
    }

    public function hasPermission(array $permissions, string $requiredPermission): bool
    {
        foreach ($permissions as $permission) {
            if ($permission === '*' || $permission === $requiredPermission) {
                return true;
            }

            $pattern = '/^' . str_replace('\\*', '.*', preg_quote((string) $permission, '/')) . '$/';

            if (preg_match($pattern, $requiredPermission) === 1) {
                return true;
            }
        }

        return false;
    }

    protected function resolvePermissions(int $userId): array
    {
        $db = db_connect();
        $permissions = [];

        $rolePermissions = $db->table('user_roles ur')
            ->select('p.slug')
            ->join('role_permissions rp', 'rp.role_id = ur.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('ur.user_id', $userId)
            ->get()
            ->getResultArray();

        foreach ($rolePermissions as $permission) {
            $permissions[(string) $permission['slug']] = true;
        }

        $userOverrides = $db->table('user_permissions up')
            ->select('p.slug, up.is_granted')
            ->join('permissions p', 'p.id = up.permission_id')
            ->where('up.user_id', $userId)
            ->get()
            ->getResultArray();

        foreach ($userOverrides as $override) {
            $slug = (string) $override['slug'];

            if ((int) $override['is_granted'] === 1) {
                $permissions[$slug] = true;
                continue;
            }

            unset($permissions[$slug]);
        }

        return array_values(array_keys($permissions));
    }
}