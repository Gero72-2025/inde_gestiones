<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncModulePermissions extends BaseCommand
{
    protected $group       = 'Modules';
    protected $name        = 'sync:permissions';
    protected $description = 'Sincroniza los permisos de los módulos de gerencias con la base de datos';
    protected $usage       = 'php spark sync:permissions';
    protected $arguments   = [];
    protected $options     = [];

    public function run(array $params): int
    {
        $db = db_connect();

        // Verificar si existe el rol Super Administrador
        $superAdminRole = $db->table('roles')
            ->select('id')
            ->where('nombre', 'Super Administrador')
            ->get()
            ->getRowArray();

        if (!is_array($superAdminRole)) {
            CLI::error('No se encontró el rol "Super Administrador". Por favor, ejecuta primero las migraciones.');
            return 1;
        }

        $superAdminRoleId = (int) $superAdminRole['id'];

        // Descubrir módulos
        $moduleMap = $this->discoverGerenciaModules();
        $moduleSlugs = array_keys($moduleMap);

        if (empty($moduleSlugs)) {
            CLI::write('No se encontraron módulos de gerencias.', 'yellow');
            return 0;
        }

        CLI::write(sprintf('Se encontraron %d módulos de gerencias.', count($moduleSlugs)), 'cyan');

        $gerenciasCreated = 0;
        $gerenciasUpdated = 0;
        $permissionsCreated = 0;
        $permissionsUpdated = 0;
        $superadminLinksCreated = 0;

        // Procesar cada módulo
        foreach ($moduleSlugs as $moduleSlug) {
            $gerenciaNombre = 'Gerencia ' . ucwords(str_replace(['-', '_'], ' ', $moduleSlug));
            $existingGerencia = $db->table('gerencias')
                ->where('slug', $moduleSlug)
                ->get()
                ->getRowArray();

            if (!is_array($existingGerencia)) {
                $db->table('gerencias')->insert([
                    'nombre' => $gerenciaNombre,
                    'descripcion' => 'Gerencia sincronizada automáticamente',
                    'slug' => $moduleSlug,
                    'status' => 'active',
                ]);
                $gerenciasCreated++;
                $gerenciaId = (int) $db->insertID();
                CLI::write(sprintf('✓ Gerencia creada: %s', $moduleSlug), 'green');
            } else {
                $gerenciaId = (int) $existingGerencia['id'];
                $gerenciasUpdated++;
            }

            // Procesar permisos detectados
            $permissionSlugs = $moduleMap[$moduleSlug]['permissionSlugs'] ?? [];

            foreach ($permissionSlugs as $permissionSlug) {
                $displayName = ucfirst(str_replace(['.', '_'], ' ', $permissionSlug));
                $existingPerm = $db->table('permissions')
                    ->where('slug', $permissionSlug)
                    ->get()
                    ->getRowArray();

                if (!is_array($existingPerm)) {
                    $db->table('permissions')->insert([
                        'nombre' => $displayName,
                        'slug' => $permissionSlug,
                        'descripcion' => sprintf('Permiso para %s en %s', $permissionSlug, $moduleSlug),
                    ]);
                    $permissionId = (int) $db->insertID();
                    $permissionsCreated++;
                    CLI::write(sprintf('  ✓ Permiso creado: %s', $permissionSlug), 'green');
                } else {
                    $permissionId = (int) $existingPerm['id'];
                    $permissionsUpdated++;
                }

                // Vincular con Super Admin
                $alreadyLinked = $db->table('role_permissions')
                    ->where('role_id', $superAdminRoleId)
                    ->where('permission_id', $permissionId)
                    ->get()
                    ->getRowArray();

                if (!is_array($alreadyLinked)) {
                    $db->table('role_permissions')->insert([
                        'role_id' => $superAdminRoleId,
                        'permission_id' => $permissionId,
                    ]);
                    $superadminLinksCreated++;
                }
            }
        }

        CLI::write('', 'cyan');
        CLI::write('═══════════════════════════════════════', 'cyan');
        CLI::write(sprintf('Gerencias creadas: %d', $gerenciasCreated), 'yellow');
        CLI::write(sprintf('Gerencias actualizadas: %d', $gerenciasUpdated), 'yellow');
        CLI::write(sprintf('Permisos creados: %d', $permissionsCreated), 'yellow');
        CLI::write(sprintf('Permisos actualizados: %d', $permissionsUpdated), 'yellow');
        CLI::write(sprintf('Enlaces Super Admin: %d', $superadminLinksCreated), 'yellow');
        CLI::write('═══════════════════════════════════════', 'cyan');
        CLI::write('Sincronización completada exitosamente.', 'green');

        return 0;
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

            if (!is_dir($fullPath)) {
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

                if (preg_match_all('/gerenciaAccess\s*:\s*[a-z0-9_\-]+\s*,\s*([a-z0-9._\-]+)/i', $routeContent, $permissionMatches) >= 1) {
                    foreach ($permissionMatches[1] ?? [] as $permissionSlug) {
                        $permissionSlugs[] = strtolower(trim((string) $permissionSlug));
                    }
                }
            }

            $slug = $detectedSlug !== '' ? $detectedSlug : $entrySlug;

            if (preg_match('/^[a-z][a-z0-9_\-]*$/', $slug) !== 1) {
                continue;
            }

            if (!array_key_exists($slug, $result)) {
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

        return $result;
    }
}
