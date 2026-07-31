<?php

namespace App\Modules\Admin\Controllers;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\MigrationRunner;
use Config\Database;

class MigrationsController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        if (! $this->isSuperAdmin($auth)) {
            return redirect()->to('admin')->with('error', 'Solo el super administrador puede administrar migraciones.');
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\migrations', [
            'title' => 'Migraciones',
        ]);
    }

    public function status()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->isSuperAdmin($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede consultar migraciones.'], 403);
        }

        try {
            $runner = $this->migrationRunner();
            $history = $runner->getHistory('default');
            $available = $runner->findMigrations();

            $historyByUid = [];
            $historyRows = [];

            foreach ($history as $item) {
                $uid = $runner->getObjectUid($item);
                $historyByUid[$uid] = true;
                $namespace = (string) ($item->namespace ?? '');
                $class = (string) ($item->class ?? '');
                $historyRows[] = [
                    'version' => (string) ($item->version ?? ''),
                    'class' => $class,
                    'namespace' => $namespace,
                    'module' => $this->resolveMigrationModule($namespace, $class),
                    'group' => (string) ($item->group ?? 'default'),
                    'batch' => (int) ($item->batch ?? 0),
                    'time' => (int) ($item->time ?? 0),
                ];
            }

            $pendingRows = [];
            foreach ($available as $migration) {
                $uid = (string) ($migration->uid ?? '');
                if ($uid !== '' && isset($historyByUid[$uid])) {
                    continue;
                }

                $namespace = (string) ($migration->namespace ?? '');
                $class = (string) ($migration->class ?? '');
                $path = (string) ($migration->path ?? '');

                $pendingRows[] = [
                    'version' => (string) ($migration->version ?? ''),
                    'name' => (string) ($migration->name ?? ''),
                    'class' => $class,
                    'namespace' => $namespace,
                    'path' => $path,
                    'module' => $this->resolveMigrationModule($namespace, $class, $path),
                ];
            }

            usort($pendingRows, static fn (array $a, array $b): int => strcmp($a['version'] . $a['class'], $b['version'] . $b['class']));
            usort($historyRows, static fn (array $a, array $b): int => $b['time'] <=> $a['time']);

            return $this->encryptedAdminResponse([
                'summary' => [
                    'applied' => count($historyRows),
                    'pending' => count($pendingRows),
                    'last_batch' => $runner->getLastBatch(),
                ],
                'applied' => $historyRows,
                'pending' => $pendingRows,
                'recent_errors' => $this->recentMigrationErrors(),
            ]);
        } catch (\Throwable $exception) {
            return $this->encryptedAdminResponse([
                'message' => 'No fue posible consultar el estado de migraciones.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function execute()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->isSuperAdmin($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede ejecutar migraciones.'], 403);
        }

        $action = strtolower(trim((string) $this->request->getPost('action')));
        $runner = $this->migrationRunner();

        try {
            if ($action === 'latest') {
                $runner->latest('default');

                return $this->encryptedAdminResponse([
                    'message' => 'Migraciones ejecutadas correctamente.',
                    'action' => 'latest',
                    'cli_messages' => $runner->getCliMessages(),
                ]);
            }

            if ($action === 'rollback') {
                $runner->regress(-1, 'default');

                return $this->encryptedAdminResponse([
                    'message' => 'Rollback del ultimo batch ejecutado correctamente.',
                    'action' => 'rollback',
                    'cli_messages' => $runner->getCliMessages(),
                ]);
            }

            return $this->encryptedAdminResponse([
                'message' => 'Accion no valida. Usa latest o rollback.',
            ], 422);
        } catch (DatabaseException $exception) {
            return $this->encryptedAdminResponse([
                'message' => 'Error de base de datos al ejecutar migraciones.',
                'error' => $exception->getMessage(),
            ], 500);
        } catch (\Throwable $exception) {
            return $this->encryptedAdminResponse([
                'message' => 'No fue posible ejecutar la accion solicitada.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    private function migrationRunner(): MigrationRunner
    {
        /** @var MigrationRunner $runner */
        $runner = service('migrations');
        $defaultGroup = config(Database::class)->defaultGroup;

        $runner->setGroup($defaultGroup);
        $runner->setNamespace(null);
        $runner->clearCliMessages();

        return $runner;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentMigrationErrors(): array
    {
        $logPath = WRITEPATH . 'logs';
        $files = glob(rtrim($logPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'log-*.log') ?: [];

        if ($files === []) {
            return [];
        }

        rsort($files);
        $results = [];

        foreach (array_slice($files, 0, 3) as $file) {
            $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (! is_array($lines)) {
                continue;
            }

            for ($index = count($lines) - 1; $index >= 0; $index--) {
                $line = $lines[$index];
                if (! preg_match('/(migration|migrate|DatabaseException)/i', $line)) {
                    continue;
                }

                $results[] = [
                    'file' => basename($file),
                    'line' => $line,
                ];

                if (count($results) >= 15) {
                    break 2;
                }
            }
        }

        return $results;
    }

    private function isSuperAdmin(array $auth): bool
    {
        return $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []));
    }

    private function resolveMigrationModule(string $namespace, string $class, string $path = ''): string
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, strtolower($path));

        if (preg_match('/(?:^|\\\\)Modules\\\\([^\\\\]+)/i', $namespace . '\\' . $class, $matches) === 1) {
            return (string) $matches[1];
        }

        if ($normalizedPath !== '' && preg_match('#app[\\/]modules[\\/]([^\\/]+)#i', $normalizedPath, $matches) === 1) {
            return (string) $matches[1];
        }

        if ($normalizedPath !== '' && str_contains($normalizedPath, 'vendor' . DIRECTORY_SEPARATOR)) {
            return 'vendor';
        }

        if (
            str_starts_with($namespace, 'App\\')
            || str_contains($namespace, '\\Database\\Migrations')
            || ($normalizedPath !== '' && str_contains($normalizedPath, 'app' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations'))
        ) {
            return 'app';
        }

        return 'core';
    }
}
