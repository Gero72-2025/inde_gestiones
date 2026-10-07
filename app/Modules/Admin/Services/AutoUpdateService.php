<?php

namespace App\Modules\Admin\Services;

use CodeIgniter\Database\MigrationRunner;
use Config\Database;
use RuntimeException;
use ZipArchive;

class AutoUpdateService
{
    private const MAX_ARCHIVE_BYTES = 25_000_000;
    private const MAX_UNCOMPRESSED_BYTES = 100_000_000;
    private const MAX_ENTRIES = 2_000;
    private const MAX_SELECTED_RECORDS = 50_000;
    private const PREVIEW_ROWS = 30;
    private const ADMIN_DATA_MODULES = [
        'admin-gerencias' => ['label' => 'Admin / Gerencias', 'tables' => ['gerencias']],
        'admin-usuarios' => ['label' => 'Admin / Usuarios', 'tables' => ['users', 'user_roles', 'user_permissions']],
        'admin-roles' => ['label' => 'Admin / Roles y Permisos', 'tables' => ['roles', 'permissions', 'role_permissions']],
        'admin-portal-publico' => ['label' => 'Admin / Portal Público', 'tables' => ['public_menu_items']],
        'admin-idiomas' => ['label' => 'Admin / Idiomas', 'tables' => ['languages', 'translations']],
        'admin-consejos' => ['label' => 'Admin / Consejos', 'tables' => ['ecoe_ts_consejos']],
    ];

    public function analyzeModule(string $moduleId): array
    {
        $module = $this->resolveModule($moduleId);
        $tables = [];

        foreach ($this->moduleTables($module['id']) as $table) {
            $tables[] = $this->loadTablePreview($table, 1, $module['id'] === 'admin-usuarios');
        }

        return [
            'module' => $module,
            'tables' => $tables,
            'preview_limit' => self::PREVIEW_ROWS,
            'max_selected_records' => self::MAX_SELECTED_RECORDS,
            'migration_files' => array_map('basename', $this->collectModuleMigrations($module['name'])),
            'migrations' => count($this->collectModuleMigrations($module['name'])),
            'data_mode' => $module['admin_update_only'] ? 'update_existing' : 'insert_missing',
            'update_only' => $module['admin_update_only'],
        ];
    }

    public function previewTable(string $moduleId, string $table, int $page = 1): array
    {
        $module = $this->resolveModule($moduleId);
        if (! in_array($table, $this->moduleTables($module['id']), true)) {
            throw new RuntimeException('La tabla no pertenece al modulo seleccionado.');
        }
        return $this->loadTablePreview($table, max(1, $page), $module['id'] === 'admin-usuarios');
    }

    public function scanSelectedData(string $moduleId, array $selection): array
    {
        $module = $this->resolveModule($moduleId);
        [$tables, $recordCount] = $this->readSelectedRows($module['id'], $selection);
        $files = [];
        $missingFiles = [];
        $unresolvedFiles = [];

        foreach ($tables as $tableBundle) {
            foreach ($tableBundle['rows'] as &$row) {
                foreach ($tableBundle['file_columns'] as $column) {
                    $value = $row[$column] ?? null;
                    if (! is_string($value) || trim($value) === '') {
                        continue;
                    }
                    $resolved = $this->resolveReferencedFile($module['id'], $value);
                    if ($resolved === null) {
                        $trimmedValue = trim($value);
                        $isExternalUrl = filter_var($trimmedValue, FILTER_VALIDATE_URL) !== false;
                        if (! $isExternalUrl && (str_contains($trimmedValue, '/') || str_contains($trimmedValue, '\\') || preg_match('/^[a-z]:/i', $trimmedValue))) {
                            $unresolvedFiles[] = ['path' => $value, 'table' => $tableBundle['name'], 'column' => $column];
                        }
                        continue;
                    }
                    if (! is_file($resolved['source'])) {
                        $missingFiles[] = ['path' => $value, 'table' => $tableBundle['name'], 'column' => $column];
                        continue;
                    }
                    $files[$resolved['package_path']] = $resolved['source'];
                    if (str_starts_with($resolved['package_path'], 'writable/') && (str_starts_with($value, '/') || preg_match('#^[a-z]:[\\\\/]#i', $value))) {
                        $row[$column] = substr($resolved['package_path'], strlen('writable/'));
                    }
                }
            }
            unset($row);
        }

        return [
            'tables' => $tables,
            'record_count' => $recordCount,
            'files' => array_map(static fn (string $packagePath, string $source): array => [
                'path' => $packagePath,
                'size' => filesize($source) ?: 0,
            ], array_keys($files), array_values($files)),
            'missing_files' => $missingFiles,
            'unresolved_files' => $unresolvedFiles,
            'file_sources' => $files,
        ];
    }

    public function listModules(): array
    {
        $modules = [];
        $directories = glob(APPPATH . 'Modules/*', GLOB_ONLYDIR) ?: [];

        foreach ($directories as $directory) {
            $name = basename($directory);
            $id = strtolower($name);
            if ($this->isSafeModuleId($name) && ! in_array($id, ['admin', 'auth'], true)) {
                $tableCount = count($this->moduleTables($id));
                $modules[] = ['id' => $id, 'name' => $name, 'table_count' => $tableCount];
            }
        }

        foreach (self::ADMIN_DATA_MODULES as $id => $definition) {
            $tableCount = count($this->moduleTables($id));
            $modules[] = [
                'id' => $id,
                'name' => $id,
                'label' => $definition['label'],
                'table_count' => $tableCount,
                'admin_update_only' => true,
            ];
        }

        usort($modules, static function (array $left, array $right): int {
            $byTableCount = $right['table_count'] <=> $left['table_count'];
            return $byTableCount !== 0 ? $byTableCount : strcasecmp($left['name'], $right['name']);
        });

        return $modules;
    }

    public function createPackage(string $moduleId, array $selection = []): string
    {
        $module = $this->resolveModule($moduleId);
        $files = $this->collectModuleFiles($module['source_name'], $module['id']);
        $selectionResult = null;
        $packageDataJson = null;

        if ($selection !== []) {
            $selectionResult = $this->scanSelectedData($module['id'], $selection);
            if ($selectionResult['missing_files'] !== [] || $selectionResult['unresolved_files'] !== []) {
                throw new RuntimeException('Hay referencias de archivo ausentes o fuera de las rutas permitidas. Corrige las referencias antes de generar el paquete.');
            }
            $files = array_merge($files, $selectionResult['file_sources']);
            $packageDataJson = json_encode([
                'format' => 'inde-module-data',
                'format_version' => 1,
                'module_id' => $module['id'],
                'data_mode' => $module['admin_update_only'] ? 'update_existing' : 'insert_missing',
                'tables' => $selectionResult['tables'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        if ($files === []) {
            throw new RuntimeException('El modulo no contiene archivos empaquetables.');
        }

        $manifestFiles = [];
        $totalBytes = 0;
        foreach ($files as $packagePath => $sourcePath) {
            $size = filesize($sourcePath);
            if ($size === false || $size > self::MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('Un archivo del modulo excede el limite permitido.');
            }
            $totalBytes += $size;
            if ($totalBytes > self::MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('El contenido del modulo excede el limite de 100 MB.');
            }
            $manifestFiles[] = [
                'path' => $packagePath,
                'sha256' => hash_file('sha256', $sourcePath),
                'size' => $size,
            ];
        }
        if ($packageDataJson !== null) {
            $dataSize = strlen($packageDataJson);
            $totalBytes += $dataSize;
            if ($totalBytes > self::MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('Los datos seleccionados superan el limite de 100 MB.');
            }
            $manifestFiles[] = [
                'path' => 'package-data/data.json',
                'sha256' => hash('sha256', $packageDataJson),
                'size' => $dataSize,
            ];
        }

        $manifest = [
            'format' => 'inde-module-package',
            'format_version' => 1,
            'module' => [
                'id' => $module['id'],
                'name' => $module['name'],
                'version' => '1.0.0',
                'dependencies' => ['php >=8.1', 'codeigniter4/framework'],
                'data_mode' => $module['admin_update_only'] ? 'update_existing' : 'insert_missing',
            ],
            'generated_at' => date(DATE_ATOM),
            'data_summary' => $selectionResult === null ? null : [
                'tables' => count($selectionResult['tables']),
                'records' => $selectionResult['record_count'],
                'files' => count($selectionResult['files']),
                'missing_files' => $selectionResult['missing_files'],
            ],
            'files' => $manifestFiles,
        ];

        $directory = WRITEPATH . 'tmp' . DIRECTORY_SEPARATOR . 'auto-update';
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar el directorio temporal.');
        }

        $packagePath = $directory . DIRECTORY_SEPARATOR . $module['id'] . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($packagePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('No fue posible crear el paquete ZIP.');
        }

        try {
            foreach ($files as $packageFile => $sourcePath) {
                if (! $zip->addFile($sourcePath, $packageFile)) {
                    throw new RuntimeException('No fue posible agregar un archivo al paquete.');
                }
            }
            if ($packageDataJson !== null && ! $zip->addFromString('package-data/data.json', $packageDataJson)) {
                throw new RuntimeException('No fue posible agregar los datos seleccionados al paquete.');
            }
            $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (! $zip->addFromString('manifest.json', $json) || ! $zip->close()) {
                throw new RuntimeException('No fue posible finalizar el paquete ZIP.');
            }
                if ((filesize($packagePath) ?: 0) > self::MAX_ARCHIVE_BYTES) {
                    throw new RuntimeException('El paquete generado supera el limite de tamano permitido.');
                }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($packagePath);
            throw $exception;
        }

        return $packagePath;
    }

    public function installPackage(string $uploadedPath): array
    {
        $report = [];
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive no esta disponible en el servidor.');
        }
        $archiveSize = filesize($uploadedPath);
        if ($archiveSize === false || $archiveSize > self::MAX_ARCHIVE_BYTES) {
            throw new RuntimeException('El ZIP supera el limite de 25 MB.');
        }
        $zip = new ZipArchive();
        if ($zip->open($uploadedPath) !== true) {
            throw new RuntimeException('El archivo no es un ZIP valido o ZipArchive no esta disponible.');
        }
        $zipOpened = true;

        $stage = WRITEPATH . 'tmp' . DIRECTORY_SEPARATOR . 'auto-update' . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12));
        $changes = [];
        $migrationsStarted = false;

        try {
            $manifest = $this->readAndValidateManifest($zip);
            $module = $this->resolveModuleIdFromManifest($manifest);
            $expectedDataMode = ! empty($module['admin_update_only']) ? 'update_existing' : 'insert_missing';
            $declaredDataMode = (string) ($manifest['module']['data_mode'] ?? 'insert_missing');
            if ($declaredDataMode !== $expectedDataMode) {
                throw new RuntimeException('La politica de datos del paquete no coincide con la política permitida para este módulo.');
            }
            $entries = $this->validateArchiveEntries($zip, $manifest, $module);

            if (! mkdir($stage, 0750, true) && ! is_dir($stage)) {
                throw new RuntimeException('No fue posible crear el area temporal de instalacion.');
            }

            foreach ($entries as $entry) {
                $contents = $zip->getFromName($entry['path']);
                if (! is_string($contents) || strlen($contents) !== $entry['size'] || ! hash_equals($entry['sha256'], hash('sha256', $contents))) {
                    throw new RuntimeException('El checksum o tamano no coincide: ' . $entry['path']);
                }

                $stagedPath = $stage . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry['path']);
                $this->ensureParentDirectory(dirname($stagedPath));
                if (file_put_contents($stagedPath, $contents, LOCK_EX) !== strlen($contents)) {
                    throw new RuntimeException('No fue posible preparar: ' . $entry['path']);
                }
            }
            $zipClosed = $zip->close();
            $zipOpened = false;
            if (! $zipClosed) {
                throw new RuntimeException('No fue posible cerrar el paquete ZIP despues de validarlo.');
            }

            $backupDirectory = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . 'auto-update' . DIRECTORY_SEPARATOR . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
            foreach ($entries as $entry) {
                if ($entry['path'] === 'package-data/data.json') {
                    $report[] = ['status' => 'success', 'path' => $entry['path'], 'message' => 'Datos seleccionados validados y preparados.'];
                    continue;
                }
                $destination = $this->destinationFor($entry['path']);
                $stagedPath = $stage . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry['path']);
                $newHash = $entry['sha256'];

                if (is_file($destination) && hash_equals($newHash, (string) hash_file('sha256', $destination))) {
                    $report[] = ['status' => 'omitted', 'path' => $entry['path'], 'message' => 'Sin cambios; se conserva el archivo existente.'];
                    continue;
                }

                if (str_starts_with($entry['path'], 'app/Database/Migrations/') && is_file($destination)) {
                    throw new RuntimeException('No se reemplazan migraciones existentes: ' . $entry['path']);
                }

                $this->ensureParentDirectory(dirname($destination));
                if (is_file($destination)) {
                    $backupPath = $backupDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry['path']);
                    $this->ensureParentDirectory(dirname($backupPath));
                    if (! copy($destination, $backupPath)) {
                        throw new RuntimeException('No se pudo respaldar: ' . $entry['path']);
                    }
                    $changes[] = ['path' => $entry['path'], 'destination' => $destination, 'backup' => $backupPath];
                    $message = 'Actualizado; respaldo creado.';
                } else {
                    $changes[] = ['path' => $entry['path'], 'destination' => $destination, 'backup' => null];
                    $message = 'Instalado.';
                }

                if (! copy($stagedPath, $destination)) {
                    throw new RuntimeException('No fue posible instalar: ' . $entry['path']);
                }
                $report[] = ['status' => 'success', 'path' => $entry['path'], 'message' => $message];
            }

            $migrationsStarted = true;
            foreach ($this->runPendingMigrations($entries) as $migrationResult) {
                $report[] = $migrationResult;
            }
            $dataPath = $stage . DIRECTORY_SEPARATOR . 'package-data' . DIRECTORY_SEPARATOR . 'data.json';
            if (is_file($dataPath)) {
                foreach ($this->importPackageData($dataPath, $module['id']) as $dataResult) {
                    $report[] = $dataResult;
                }
            }

            $report[] = ['status' => 'success', 'path' => 'package', 'message' => 'Paquete ' . $module['id'] . ' ' . $manifest['module']['version'] . ' procesado.'];
            log_message('info', 'Auto-update package installed for module {module}: {report}', [
                'module' => $module['id'],
                'report' => json_encode($report, JSON_UNESCAPED_SLASHES),
            ]);

            return $report;
        } catch (\Throwable $exception) {
            if ($zipOpened) {
                try {
                    $zip->close();
                } catch (\Throwable) {
                    // Preserve the package-processing error instead of masking it during cleanup.
                }
                $zipOpened = false;
            }
            $rollbackErrors = $migrationsStarted ? [] : $this->restoreChanges($changes);
            $report[] = ['status' => 'error', 'path' => 'package', 'message' => $exception->getMessage()];
            if ($migrationsStarted) {
                $report[] = ['status' => 'omitted', 'path' => 'rollback', 'message' => 'Los archivos instalados se conservaron porque ya habia comenzado la fase de migraciones. Verifica el estado de la base de datos antes de reintentar.'];
            }
            foreach ($rollbackErrors as $rollbackError) {
                $report[] = ['status' => 'error', 'path' => $rollbackError, 'message' => 'No fue posible restaurar el archivo respaldado.'];
            }
            log_message('error', 'Auto-update package failed: {message}; report={report}', [
                'message' => $exception->getMessage(),
                'report' => json_encode($report, JSON_UNESCAPED_SLASHES),
            ]);
            return $report;
        } finally {
            $this->removeDirectory($stage);
        }
    }

    private function collectModuleFiles(string $sourceName, string $moduleId): array
    {
        $files = [];
        $packageName = isset(self::ADMIN_DATA_MODULES[$moduleId]) ? 'Admin' : $sourceName;
        $moduleRoot = APPPATH . 'Modules' . DIRECTORY_SEPARATOR . $sourceName;
        $this->collectDirectory($moduleRoot, 'app/Modules/' . $packageName, $files);

        foreach ($this->modulePublicRoots($moduleId) as $assetPath) {
            $assetRoot = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $assetPath);
            if (is_dir($assetRoot)) {
                $this->collectDirectory($assetRoot, 'public/' . $assetPath, $files);
            }
        }

        foreach ($this->collectModuleMigrations($moduleId) as $path) {
            $files['app/Database/Migrations/' . basename($path)] = $path;
        }

        ksort($files);
        if (count($files) > self::MAX_ENTRIES - 1) {
            throw new RuntimeException('El modulo excede el maximo de archivos por paquete.');
        }

        return $files;
    }

    private function moduleTables(string $moduleId): array
    {
        if (isset(self::ADMIN_DATA_MODULES[$moduleId])) {
            $db = db_connect();
            return array_values(array_filter(
                self::ADMIN_DATA_MODULES[$moduleId]['tables'],
                static fn (string $table): bool => $db->tableExists($table)
            ));
        }

        $prefixes = [
            'gero' => ['gero_'],
            'ecoe' => ['ecoe_', 'dyn_ecoe_'],
            'etcee' => ['etcee_'],
        ][$moduleId] ?? [str_replace('-', '_', $moduleId) . '_'];
        $tables = [];

        foreach (db_connect()->listTables() as $table) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with(strtolower((string) $table), $prefix)) {
                    $tables[] = (string) $table;
                    break;
                }
            }
        }

        sort($tables, SORT_STRING);
        return $tables;
    }

    private function loadTablePreview(string $table, int $page, bool $allowSensitiveColumns = false): array
    {
        $db = db_connect();
        $fields = $db->getFieldData($table);
        $primaryKeys = [];
        $columns = [];
        $displayColumns = [];
        $fileColumns = [];
        $sensitiveColumns = [];
        $binaryColumns = [];

        foreach ($fields as $field) {
            $name = (string) $field->name;
            $type = strtolower((string) ($field->type ?? ''));
            $columns[] = ['name' => $name, 'type' => $type];
            if ((int) ($field->primary_key ?? 0) === 1) {
                $primaryKeys[] = $name;
            }
            if ($this->isFileColumn($name)) {
                $fileColumns[] = $name;
            }
            if ($this->isSensitiveColumn($name)) {
                $sensitiveColumns[] = $name;
            }
            if (preg_match('/(?:blob|binary)/', $type)) {
                $binaryColumns[] = $name;
            } else {
                $displayColumns[] = $name;
            }
        }

        $totalRows = (int) $db->table($table)->countAllResults();
        $pages = max(1, (int) ceil($totalRows / self::PREVIEW_ROWS));
        $page = min($page, $pages);
        $query = $db->table($table);
        if ($displayColumns !== []) {
            $query->select($displayColumns);
        }
        foreach ($primaryKeys as $primaryKey) {
            $query->orderBy($primaryKey, 'ASC');
        }
        $rows = $totalRows > 0 ? $query->get(self::PREVIEW_ROWS, ($page - 1) * self::PREVIEW_ROWS)->getResultArray() : [];
        $preview = [];
        foreach ($rows as $row) {
            $keyValues = array_intersect_key($row, array_flip($primaryKeys));
            $key = count($primaryKeys) === 1
                ? (string) ($row[$primaryKeys[0]] ?? '')
                : ($primaryKeys !== [] ? json_encode($keyValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null);
            $values = [];
            foreach ($row as $column => $value) {
                $values[$column] = in_array($column, $sensitiveColumns, true) ? '[redactado]' : $this->previewValue($value);
            }
            foreach ($binaryColumns as $column) {
                $values[$column] = '[contenido binario omitido]';
            }
            $preview[] = ['key' => $key, 'values' => $values];
        }

        return [
            'name' => $table,
            'columns' => $columns,
            'primary_key' => count($primaryKeys) === 1 ? $primaryKeys[0] : ($primaryKeys !== [] ? implode(' + ', $primaryKeys) : null),
            'primary_keys' => $primaryKeys,
            'selectable' => $primaryKeys !== [] && ($sensitiveColumns === [] || $allowSensitiveColumns) && $binaryColumns === [],
            'excluded_columns' => $allowSensitiveColumns ? $sensitiveColumns : [],
            'sensitive_columns' => $sensitiveColumns,
            'file_columns' => $fileColumns,
            'total_rows' => $totalRows,
            'preview_rows' => count($preview),
            'page' => $page,
            'pages' => $pages,
            'rows' => $preview,
        ];
    }

    private function collectModuleMigrations(string $moduleName): array
    {
        $directory = APPPATH . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';
        $migrations = [];
        $moduleToken = preg_replace('/[^a-z0-9]/', '', strtolower($moduleName));
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*.php') ?: [] as $path) {
            $migrationToken = preg_replace('/[^a-z0-9]/', '', strtolower(basename($path)));
            if (str_contains($migrationToken, $moduleToken) && is_file($path) && ! is_link($path)) {
                $migrations[] = $path;
            }
        }
        return $migrations;
    }

    private function readSelectedRows(string $moduleId, array $selection): array
    {
        $allowedTables = $this->moduleTables($moduleId);
        $allowSensitiveOmission = $moduleId === 'admin-usuarios';
        $db = db_connect();
        $tables = [];
        $recordCount = 0;

        foreach ($selection as $table => $keys) {
            if (! is_string($table) || ! in_array($table, $allowedTables, true) || ! is_array($keys)) {
                throw new RuntimeException('La seleccion contiene una tabla no asociada al modulo.');
            }
            if ($keys === []) {
                continue;
            }

            $fields = $db->getFieldData($table);
            $primaryKeys = [];
            $columns = [];
            $fileColumns = [];
            $binaryColumns = [];
            $sensitiveColumns = [];
            foreach ($fields as $field) {
                $name = (string) $field->name;
                $type = strtolower((string) ($field->type ?? ''));
                if ((int) ($field->primary_key ?? 0) === 1) {
                    $primaryKeys[] = $name;
                }
                if ($this->isFileColumn($name)) {
                    $fileColumns[] = $name;
                }
                if ($this->isSensitiveColumn($name)) {
                    $sensitiveColumns[] = $name;
                    if (! $allowSensitiveOmission) {
                        throw new RuntimeException('La tabla ' . $table . ' contiene datos sensibles y no puede exportarse.');
                    }
                    continue;
                }
                $columns[] = $name;
                if (preg_match('/(?:blob|binary)/', $type)) {
                    $binaryColumns[] = $name;
                }
            }
            if ($primaryKeys === []) {
                throw new RuntimeException('La tabla ' . $table . ' no tiene clave primaria para seleccionar registros.');
            }
            if ($binaryColumns !== []) {
                throw new RuntimeException('La tabla ' . $table . ' contiene columnas binarias no exportables como JSON.');
            }

            if (count($keys) === 1 && (string) $keys[0] === '*') {
                $tableRows = (int) $db->table($table)->countAllResults();
                $recordCount += $tableRows;
                if ($recordCount > self::MAX_SELECTED_RECORDS) {
                    throw new RuntimeException('La selección completa excede el limite de ' . number_format(self::MAX_SELECTED_RECORDS) . ' registros por paquete.');
                }
                $query = $db->table($table);
                if ($allowSensitiveOmission) {
                    $query->select($columns);
                }
                foreach ($primaryKeys as $primaryKey) {
                    $query->orderBy($primaryKey, 'ASC');
                }
                $rows = $query->get(self::MAX_SELECTED_RECORDS + 1)->getResultArray();
                if (count($rows) !== $tableRows) {
                    throw new RuntimeException('La tabla cambio durante la lectura; vuelve a analizarla antes de empaquetar.');
                }
                $tables[] = [
                    'name' => $table,
                    'primary_key' => $primaryKeys[0],
                    'columns' => $columns,
                    'file_columns' => $fileColumns,
                    'rows' => $rows,
                ];
                continue;
            }

            $normalizedKeys = [];
            foreach ($keys as $key) {
                if (count($primaryKeys) === 1) {
                    if (! is_string($key) && ! is_int($key)) {
                        throw new RuntimeException('La seleccion de registros contiene identificadores no validos.');
                    }
                    $key = (string) $key;
                    if ($key === '' || strlen($key) > 128) {
                        throw new RuntimeException('La seleccion contiene un identificador vacio o demasiado largo.');
                    }
                    $normalizedKeys[$key] = $key;
                    continue;
                }

                $parts = is_string($key) ? json_decode($key, true) : null;
                if (! is_array($parts) || array_diff($primaryKeys, array_keys($parts)) !== []) {
                    throw new RuntimeException('La seleccion de clave compuesta no es valida.');
                }
                $parts = array_intersect_key($parts, array_flip($primaryKeys));
                foreach ($parts as $part) {
                    if (! is_scalar($part) || (string) $part === '' || strlen((string) $part) > 128) {
                        throw new RuntimeException('La clave compuesta contiene valores no validos.');
                    }
                }
                $canonical = json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $normalizedKeys[$canonical] = $parts;
            }
            $recordCount += count($normalizedKeys);
            if ($recordCount > self::MAX_SELECTED_RECORDS) {
                throw new RuntimeException('El paquete puede incluir como maximo ' . self::MAX_SELECTED_RECORDS . ' registros.');
            }

            $query = $db->table($table);
            if ($allowSensitiveOmission) {
                $query->select($columns);
            }
            if (count($primaryKeys) === 1) {
                $query->whereIn($primaryKeys[0], array_values($normalizedKeys));
            } else {
                $query->groupStart();
                foreach ($normalizedKeys as $parts) {
                    $query->orGroupStart();
                    foreach ($primaryKeys as $primaryKey) {
                        $query->where($primaryKey, $parts[$primaryKey]);
                    }
                    $query->groupEnd();
                }
                $query->groupEnd();
            }
            $rows = $query->get()->getResultArray();
            if (count($rows) !== count($normalizedKeys)) {
                throw new RuntimeException('Uno o mas registros seleccionados ya no existen en ' . $table . '.');
            }

            $tables[] = [
                'name' => $table,
                'primary_key' => $primaryKeys[0],
                'columns' => $columns,
                'file_columns' => $fileColumns,
                'rows' => $rows,
            ];
        }

        return [$this->orderTablesByDependencies($tables), $recordCount];
    }

    private function orderTablesByDependencies(array $tables): array
    {
        $db = db_connect();
        $bundles = [];
        foreach ($tables as $bundle) {
            $bundles[$bundle['name']] = $bundle;
        }
        $ordered = [];
        $visiting = [];
        $visited = [];
        $visit = function (string $table) use (&$visit, &$ordered, &$visiting, &$visited, $bundles, $db): void {
            if (isset($visited[$table]) || isset($visiting[$table])) {
                return;
            }
            $visiting[$table] = true;
            foreach ($db->getForeignKeyData($table) as $foreignKey) {
                $parent = (string) ($foreignKey->foreign_table_name ?? '');
                if ($parent !== '' && isset($bundles[$parent])) {
                    $visit($parent);
                }
            }
            unset($visiting[$table]);
            $visited[$table] = true;
            $ordered[] = $bundles[$table];
        };

        foreach (array_keys($bundles) as $table) {
            $visit($table);
        }

        return $ordered;
    }

    private function isFileColumn(string $column): bool
    {
        if (in_array(strtolower($column), ['route_path', 'ruta_path', 'route', 'url', 'href', 'tipo_archivo', 'tipo_documento', 'visible_plantilla', 'plantilla_html'], true)) {
            return false;
        }
        return preg_match('/(?:^|_)(?:ruta|path|filepath|filename|archivo|imagen|image|documento|document|adjunto|plantilla|icono)(?:_|$)/i', $column) === 1;
    }

    private function isSensitiveColumn(string $column): bool
    {
        return preg_match('/(?:password|passwd|secret|token|api[_-]?key|credential|private[_-]?key|twofa|otp)/i', $column) === 1;
    }

    private function previewValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        if (! mb_check_encoding($value, 'UTF-8')) {
            return '[contenido binario omitido]';
        }
        return mb_strlen($value) > 240 ? mb_substr($value, 0, 240) . '…' : $value;
    }

    private function resolveReferencedFile(string $moduleId, string $value): ?array
    {
        $path = str_replace('\\', '/', trim($value));
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }
        if (preg_match('#^[a-z]:/#i', $path) || str_starts_with($path, '/')) {
            if ($moduleId !== 'etcee') {
                return null;
            }
            $realPath = realpath(str_replace('/', DIRECTORY_SEPARATOR, $path));
            $allowedRoot = realpath(WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sni_kmz');
            if ($realPath === false || $allowedRoot === false || ! str_starts_with(strtolower($realPath), strtolower($allowedRoot . DIRECTORY_SEPARATOR)) || ! is_file($realPath)) {
                return null;
            }
            $relative = 'uploads/sni_kmz/' . str_replace(DIRECTORY_SEPARATOR, '/', substr($realPath, strlen($allowedRoot) + 1));
            return ['source' => $realPath, 'package_path' => 'writable/' . $relative];
        }
        if (str_starts_with($path, '//')) {
            return null;
        }
        if (str_starts_with($path, 'writable/')) {
            $path = substr($path, strlen('writable/'));
        } elseif (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }
        $segments = explode('/', $path);
        if (in_array('..', $segments, true) || in_array('.', $segments, true) || in_array('', $segments, true)) {
            return null;
        }

        foreach ($this->modulePublicRoots($moduleId) as $publicRoot) {
            if (str_starts_with($path, $publicRoot . '/')) {
                $source = $this->resolveFileWithinRoot(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $publicRoot), substr($path, strlen($publicRoot) + 1));
                return $source === null ? null : ['source' => $source, 'package_path' => 'public/' . $path];
            }
        }

        if (str_starts_with($path, 'uploads/')) {
            $allowed = match ($moduleId) {
                'ecoe' => ['uploads/ecoe/ts/', 'uploads/ecoe/formularios/'],
                'etcee' => ['uploads/sni_kmz/'],
                default => ['uploads/' . $moduleId . '/'],
            };
            foreach ($allowed as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $source = $this->resolveFileWithinRoot(WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, rtrim($prefix, '/')), substr($path, strlen($prefix)));
                    return $source === null ? null : ['source' => $source, 'package_path' => 'writable/' . $path];
                }
            }
            return null;
        }

        return null;
    }

    private function resolveFileWithinRoot(string $root, string $relativePath): ?string
    {
        $realRoot = realpath($root);
        if ($realRoot === false) {
            return null;
        }
        $candidatePath = rtrim($root, '\\/ ') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $candidate = realpath($candidatePath);
        if ($candidate === false) {
            return $candidatePath;
        }
        if (! is_file($candidate)) {
            return $candidatePath;
        }
        $prefix = rtrim($realRoot, '\\/') . DIRECTORY_SEPARATOR;
        if (! str_starts_with(strtolower($candidate), strtolower($prefix))) {
            return null;
        }
        return $candidate;
    }

    private function importPackageData(string $dataPath, string $moduleId): array
    {
        $raw = file_get_contents($dataPath);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($data) || ($data['format'] ?? '') !== 'inde-module-data' || (int) ($data['format_version'] ?? 0) !== 1 || ($data['module_id'] ?? '') !== $moduleId || ! is_array($data['tables'] ?? null)) {
            throw new RuntimeException('El archivo package-data/data.json no es valido para este modulo.');
        }
        $updateOnly = isset(self::ADMIN_DATA_MODULES[$moduleId]);
        $expectedMode = $updateOnly ? 'update_existing' : 'insert_missing';
        if (($data['data_mode'] ?? 'insert_missing') !== $expectedMode) {
            throw new RuntimeException('El modo de actualización de data.json no está permitido para este módulo.');
        }

        $allowedTables = $this->moduleTables($moduleId);
        $db = db_connect();
        $report = [];
        if (count($data['tables']) > count($allowedTables)) {
            throw new RuntimeException('El paquete contiene mas tablas de las permitidas para el modulo.');
        }
        $tableNames = [];
        $recordTotal = 0;
        foreach ($data['tables'] as $tableBundle) {
            $table = is_array($tableBundle) ? (string) ($tableBundle['name'] ?? '') : '';
            if (! in_array($table, $allowedTables, true) || ! $db->tableExists($table) || ! is_array($tableBundle['rows'] ?? null)) {
                throw new RuntimeException('Tabla de datos no permitida o inexistente: ' . $table);
            }
            if (isset($tableNames[$table])) {
                throw new RuntimeException('El paquete repite una tabla de datos: ' . $table);
            }
            $tableNames[$table] = true;
            $recordTotal += count($tableBundle['rows']);
            if ($recordTotal > self::MAX_SELECTED_RECORDS) {
                throw new RuntimeException('El paquete excede el maximo de ' . self::MAX_SELECTED_RECORDS . ' registros.');
            }
            foreach ($db->getFieldData($table) as $field) {
                $sensitive = $this->isSensitiveColumn((string) $field->name);
                if (($sensitive && $moduleId !== 'admin-usuarios') || preg_match('/(?:blob|binary)/i', (string) ($field->type ?? ''))) {
                    throw new RuntimeException('La tabla ' . $table . ' contiene columnas sensibles o binarias no importables.');
                }
            }
        }
        $db->transBegin();
        try {
            foreach ($this->orderTablesByDependencies($data['tables']) as $tableBundle) {
                $table = (string) ($tableBundle['name'] ?? '');
                $columnNames = $db->getFieldNames($table);
                $added = 0;
                $updated = 0;
                $omitted = 0;
                $omittedForeignKeys = [];
                foreach ($tableBundle['rows'] as $row) {
                    if (! is_array($row) || $row === [] || array_diff(array_keys($row), $columnNames) !== []) {
                        throw new RuntimeException('Registro incompatible con el esquema de ' . $table . '.');
                    }
                    if ($moduleId === 'admin-usuarios' && array_filter(array_keys($row), fn (string $column): bool => $this->isSensitiveColumn($column)) !== []) {
                        throw new RuntimeException('El paquete de usuarios incluye un campo secreto no permitido.');
                    }
                    foreach ($row as $value) {
                        if (! is_scalar($value) && $value !== null) {
                            throw new RuntimeException('Un valor de ' . $table . ' no es escalar ni null.');
                        }
                    }
                    if ($moduleId === 'etcee' && isset($row['ruta_archivo']) && is_string($row['ruta_archivo']) && str_starts_with($row['ruta_archivo'], 'uploads/sni_kmz/')) {
                        $row['ruta_archivo'] = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $row['ruta_archivo']);
                    }
                    $existing = $this->findExistingRecord($table, $row);
                    if ($updateOnly) {
                        if ($existing === null) {
                            $omitted++;
                            continue;
                        }
                        $primaryKeys = [];
                        foreach ($db->getIndexData($table) as $index) {
                            if (($index->type ?? '') === 'PRIMARY') {
                                $primaryKeys = (array) ($index->fields ?? []);
                                break;
                            }
                        }
                        $updates = array_diff_key($row, array_flip($primaryKeys));
                        [$updates, $rowOmittedForeignKeys] = $this->filterUnavailableForeignKeys($table, $updates);
                        $omittedForeignKeys = array_merge($omittedForeignKeys, $rowOmittedForeignKeys);
                        if ($updates === []) {
                            $omitted++;
                            continue;
                        }
                        $builder = $db->table($table);
                        foreach ($primaryKeys as $primaryKey) {
                            $builder->where($primaryKey, $existing[$primaryKey]);
                        }
                        if (! $builder->update($updates)) {
                            throw new RuntimeException('CI4 no pudo actualizar un registro existente en ' . $table . '.');
                        }
                        $updated++;
                        continue;
                    }
                    if ($existing !== null) {
                        $omitted++;
                        continue;
                    }
                    if (! $db->table($table)->insert($row)) {
                        throw new RuntimeException('CI4 no pudo insertar un registro en ' . $table . '.');
                    }
                    $added++;
                }
                $status = $updated > 0 || $added > 0 ? 'success' : 'omitted';
                $message = $updateOnly
                    ? $updated . ' registros actualizados; ' . $omitted . ' omitidos porque no existían en destino.'
                    : $added . ' registros insertados; ' . $omitted . ' omitidos por clave existente.';
                if ($omittedForeignKeys !== []) {
                    $message .= ' Se conservaron relaciones destino por referencias ausentes: ' . implode('; ', array_unique($omittedForeignKeys)) . '.';
                }
                $report[] = ['status' => $status, 'path' => $table, 'message' => $message];
            }
            if ($db->transStatus() === false) {
                throw new RuntimeException('La transaccion de datos fue rechazada por la base de datos.');
            }
            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }

        return $report;
    }

    private function findExistingRecord(string $table, array $row): ?array
    {
        $db = db_connect();
        $indexes = $db->getIndexData($table);
        usort($indexes, static fn ($left, $right): int => (($left->type ?? '') === 'PRIMARY' ? 1 : 0) <=> (($right->type ?? '') === 'PRIMARY' ? 1 : 0));

        foreach ($indexes as $index) {
            if (! in_array((string) ($index->type ?? ''), ['PRIMARY', 'UNIQUE'], true) || ! is_array($index->fields ?? null)) {
                continue;
            }
            $fields = $index->fields;
            if ($fields === [] || array_diff($fields, array_keys($row)) !== []) {
                continue;
            }
            $query = $db->table($table);
            $usable = true;
            foreach ($fields as $field) {
                if ($row[$field] === null) {
                    $usable = false;
                    break;
                }
                $query->where($field, $row[$field]);
            }
            if ($usable) {
                $existing = $query->get()->getFirstRow('array');
                if (is_array($existing)) {
                    return $existing;
                }
            }
        }
        return null;
    }

    private function filterUnavailableForeignKeys(string $table, array $updates): array
    {
        $db = db_connect();
        $omitted = [];

        foreach ($db->getForeignKeyData($table) as $foreignKey) {
            $localColumns = (array) ($foreignKey->column_name ?? []);
            $foreignColumns = (array) ($foreignKey->foreign_column_name ?? []);
            $foreignTable = (string) ($foreignKey->foreign_table_name ?? '');
            if ($localColumns === [] || count($localColumns) !== count($foreignColumns) || $foreignTable === '') {
                continue;
            }
            if (array_diff($localColumns, array_keys($updates)) !== []) {
                continue;
            }

            $parentQuery = $db->table($foreignTable);
            $hasNullReference = false;
            foreach ($localColumns as $index => $localColumn) {
                $value = $updates[$localColumn];
                if ($value === null) {
                    $hasNullReference = true;
                    break;
                }
                $parentQuery->where($foreignColumns[$index], $value);
            }

            if (! $hasNullReference && $parentQuery->countAllResults() === 0) {
                foreach ($localColumns as $localColumn) {
                    unset($updates[$localColumn]);
                }
                $omitted[] = implode(', ', $localColumns) . ' -> ' . $foreignTable;
            }
        }

        return [$updates, $omitted];
    }

    private function collectDirectory(string $root, string $packageRoot, array &$files): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($root) + 1);
            $segments = preg_split('#[\\\\/]#', $relative) ?: [];
            if (array_intersect($segments, ['.git', 'node_modules', 'vendor', 'writable', 'uploads', 'storage', 'cache', 'logs', 'tmp']) !== []) {
                continue;
            }
            if (preg_match('/^\.env(?:\.|$)/i', basename($file->getPathname())) || preg_match('/\.(?:log|bak|backup|zip)$/i', $file->getFilename())) {
                continue;
            }
            $packagePath = $packageRoot . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            $this->validatePackagePath($packagePath);
            $files[$packagePath] = $file->getPathname();
        }
    }

    private function readAndValidateManifest(ZipArchive $zip): array
    {
        if ($zip->numFiles < 2 || $zip->numFiles > self::MAX_ENTRIES || $zip->statName('manifest.json') === false) {
            throw new RuntimeException('El ZIP debe contener manifest.json y una cantidad valida de archivos.');
        }
        $stat = $zip->statName('manifest.json');
        if ((int) ($stat['size'] ?? 0) > 1_000_000) {
            throw new RuntimeException('El manifiesto excede el tamano permitido.');
        }
        $raw = $zip->getFromName('manifest.json');
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($manifest) || ($manifest['format'] ?? '') !== 'inde-module-package' || (int) ($manifest['format_version'] ?? 0) !== 1) {
            throw new RuntimeException('Formato de paquete o manifiesto no compatible.');
        }
        if (! is_array($manifest['module'] ?? null) || ! is_array($manifest['files'] ?? null) || ! is_array($manifest['module']['dependencies'] ?? null)) {
            throw new RuntimeException('El manifiesto no contiene metadata valida del modulo.');
        }
        if (! preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', (string) ($manifest['module']['version'] ?? ''))) {
            throw new RuntimeException('La version del modulo no es valida.');
        }

        return $manifest;
    }

    private function validateArchiveEntries(ZipArchive $zip, array $manifest, array $module): array
    {
        if (count($manifest['files']) > self::MAX_ENTRIES - 1) {
            throw new RuntimeException('El paquete excede el maximo de archivos permitido.');
        }
        $entries = [];
        $seen = ['manifest.json' => true];
        $totalBytes = 0;

        foreach ($manifest['files'] as $entry) {
            if (! is_array($entry) || ! is_string($entry['path'] ?? null) || ! is_string($entry['sha256'] ?? null)) {
                throw new RuntimeException('Entrada de archivo no valida en el manifiesto.');
            }
            $path = $entry['path'];
            $this->validatePackagePath($path);
            if (isset($seen[$path]) || ! preg_match('/^[a-f0-9]{64}$/', $entry['sha256'])) {
                throw new RuntimeException('Ruta duplicada o checksum no valido en el manifiesto.');
            }
            $seen[$path] = true;
            $size = filter_var($entry['size'] ?? null, FILTER_VALIDATE_INT);
            if ($size === false || $size < 0) {
                throw new RuntimeException('Tamano de archivo no valido: ' . $path);
            }
            $this->validateTargetPath($path, $module);
            $stat = $zip->statName($path);
            if ($stat === false || (int) ($stat['size'] ?? -1) !== $size) {
                throw new RuntimeException('El archivo declarado no coincide con el ZIP: ' . $path);
            }
            $totalBytes += $size;
            if ($totalBytes > self::MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('El contenido descomprimido excede el limite permitido.');
            }
            $entries[] = ['path' => $path, 'sha256' => $entry['sha256'], 'size' => $size];
        }

        if (count($seen) !== $zip->numFiles) {
            throw new RuntimeException('El ZIP contiene archivos no declarados en el manifiesto.');
        }

        return $entries;
    }

    private function validatePackagePath(string $path): void
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:/', $path)) {
            throw new RuntimeException('El paquete contiene una ruta no permitida.');
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('El paquete contiene una ruta insegura.');
            }
        }
    }

    private function validateTargetPath(string $path, array $module): void
    {
        if ($path === 'package-data/data.json') {
            return;
        }
        $packageName = $module['source_name'] ?? $module['name'];
        $modulePrefix = 'app/Modules/' . $packageName . '/';
        $assetPrefixes = array_map(static fn (string $root): string => 'public/' . $root . '/', $this->modulePublicRoots($module['id']));
        if (str_starts_with($path, $modulePrefix)) {
            return;
        }
        foreach ($assetPrefixes as $assetPrefix) {
            if (str_starts_with($path, $assetPrefix)) {
                return;
            }
        }
        $uploadPrefixes = match ($module['id']) {
            'ecoe' => ['writable/uploads/ecoe/ts/', 'writable/uploads/ecoe/formularios/'],
            'etcee' => ['writable/uploads/sni_kmz/'],
            default => ['writable/uploads/' . $module['id'] . '/'],
        };
        foreach ($uploadPrefixes as $uploadPrefix) {
            if (str_starts_with($path, $uploadPrefix)) {
                return;
            }
        }
        if (str_starts_with($path, 'app/Database/Migrations/')) {
            $filename = strtolower(basename($path));
            $migrationToken = preg_replace('/[^a-z0-9]/', '', $filename);
            $moduleToken = preg_replace('/[^a-z0-9]/', '', $module['id']);
            if (str_contains($migrationToken, $moduleToken) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{6}_[a-z0-9_-]+\.php$/', $filename)) {
                return;
            }
        }
        throw new RuntimeException('Ruta fuera del alcance permitido para el modulo: ' . $path);
    }

    private function destinationFor(string $packagePath): string
    {
        if (str_starts_with($packagePath, 'app/')) {
            return APPPATH . str_replace('/', DIRECTORY_SEPARATOR, substr($packagePath, 4));
        }
        if (str_starts_with($packagePath, 'public/')) {
            return FCPATH . str_replace('/', DIRECTORY_SEPARATOR, substr($packagePath, 7));
        }
        if (str_starts_with($packagePath, 'writable/uploads/')) {
            return WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, substr($packagePath, 9));
        }
        throw new RuntimeException('Destino de paquete no permitido.');
    }

    private function runPendingMigrations(array $entries): array
    {
        $migrationPaths = array_values(array_filter($entries, static fn (array $entry): bool => str_starts_with($entry['path'], 'app/Database/Migrations/')));
        if ($migrationPaths === []) {
            return [];
        }

        $runner = service('migrations');
        if (! $runner instanceof MigrationRunner) {
            throw new RuntimeException('El servicio de migraciones de CI4 no esta disponible.');
        }
        $defaultGroup = config(Database::class)->defaultGroup;
        $runner->setGroup($defaultGroup)->setNamespace('App\\Database\\Migrations');
        $history = $runner->getHistory($defaultGroup);
        $applied = [];
        foreach ($history as $item) {
            $applied[$runner->getObjectUid($item)] = true;
        }

        $availableByPath = [];
        foreach ($runner->findMigrations() as $migration) {
            $availableByPath[str_replace('\\', '/', (string) $migration->path)] = $migration;
        }

        $results = [];
        foreach ($migrationPaths as $entry) {
            $path = $this->destinationFor($entry['path']);
            $migration = $availableByPath[str_replace('\\', '/', $path)] ?? null;
            if ($migration === null) {
                throw new RuntimeException('CI4 no reconocio la migracion: ' . $entry['path']);
            }
            $uid = $runner->getObjectUid($migration);
            if (isset($applied[$uid])) {
                $results[] = ['status' => 'omitted', 'path' => $entry['path'], 'message' => 'Migracion ya registrada en el historial de CI4.'];
                continue;
            }
            if (! $runner->force($path, 'App\\Database\\Migrations', $defaultGroup)) {
                throw new RuntimeException('CI4 no pudo aplicar la migracion: ' . $entry['path']);
            }
            $applied[$uid] = true;
            $results[] = ['status' => 'success', 'path' => $entry['path'], 'message' => 'Migracion pendiente aplicada por CI4.'];
        }

        return $results;
    }

    private function resolveModule(string $moduleId): array
    {
        if (! $this->isSafeModuleId($moduleId)) {
            throw new RuntimeException('Identificador de modulo no valido.');
        }
        foreach ($this->listModules() as $module) {
            if (strtolower($module['id']) === strtolower($moduleId)) {
                if (isset(self::ADMIN_DATA_MODULES[strtolower($moduleId)])) {
                    return [
                        'id' => strtolower($moduleId),
                        'name' => strtolower($moduleId),
                        'label' => self::ADMIN_DATA_MODULES[strtolower($moduleId)]['label'],
                        'source_name' => 'Admin',
                        'table_count' => $module['table_count'],
                        'admin_update_only' => true,
                    ];
                }
                return array_merge($module, [
                    'label' => $module['name'],
                    'source_name' => $module['name'],
                    'admin_update_only' => false,
                ]);
            }
        }
        throw new RuntimeException('El modulo seleccionado no existe en este sistema.');
    }

    private function resolveModuleIdFromManifest(array $manifest): array
    {
        $id = (string) ($manifest['module']['id'] ?? '');
        $name = (string) ($manifest['module']['name'] ?? '');
        if (! $this->isSafeModuleId($id) || ! $this->isSafeModuleId($name) || strtolower($name) !== strtolower($id)) {
            throw new RuntimeException('El identificador y el nombre del modulo no coinciden.');
        }
        if (isset(self::ADMIN_DATA_MODULES[strtolower($id)])) {
            return $this->resolveModule(strtolower($id));
        }
        return ['id' => strtolower($id), 'name' => $name];
    }

    private function isSafeModuleId(string $moduleId): bool
    {
        return preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/', $moduleId) === 1;
    }

    private function modulePublicRoots(string $moduleId): array
    {
        $roots = ['assets/modules/' . $moduleId];
        if ($moduleId === 'etcee') {
            $roots[] = 'assets/img/sni';
        }
        if ($moduleId === 'ecoe') {
            $roots[] = 'uploads/consejos';
        }
        if ($moduleId === 'admin-consejos') {
            $roots[] = 'uploads/consejos';
        }
        if ($moduleId === 'admin-portal-publico') {
            $roots[] = 'uploads/portal-nav';
        }
        return $roots;
    }

    private function ensureParentDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException('No fue posible crear un directorio de destino.');
        }
    }

    private function restoreChanges(array $changes): array
    {
        $errors = [];
        foreach (array_reverse($changes) as $change) {
            if ($change['backup'] !== null) {
                if (! copy($change['backup'], $change['destination'])) {
                    $errors[] = $change['path'];
                }
            } elseif (is_file($change['destination']) && ! unlink($change['destination'])) {
                $errors[] = $change['path'];
            }
        }
        return $errors;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($directory);
    }
}