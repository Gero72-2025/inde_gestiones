<?php

namespace App\Modules\Ecoe\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class FormularioEngineService
{
    private BaseConnection $db;
    private Forge $forge;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
        $this->forge = \Config\Database::forge($this->db);
    }

    public function normalizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? '';
        $value = preg_replace('/_+/', '_', $value) ?? '';

        return trim($value, '_');
    }

    public function dynamicTableName(string $slug): string
    {
        return 'dyn_ecoe_' . $this->normalizeSlug($slug);
    }

    public function archivedTableName(string $slug): string
    {
        return 'bk_' . $this->dynamicTableName($slug) . '_' . date('Ymd_His');
    }

    public function defaultTemplateHtml(string $titulo): string
    {
        return <<<HTML
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; color: #163247; font-size: 12px; }
    .sheet { border: 1px solid #d9e4eb; border-radius: 16px; padding: 24px; }
    .title { font-size: 20px; font-weight: 700; margin: 0 0 6px 0; }
    .meta { color: #587084; margin-bottom: 18px; }
    .code { display: inline-block; padding: 8px 12px; border-radius: 999px; background: #e9f6ef; color: #155c39; font-weight: 700; }
    table { width: 100%; border-collapse: collapse; margin-top: 18px; }
    td { padding: 8px 10px; border-bottom: 1px solid #e6edf2; vertical-align: top; }
    td.label { width: 36%; font-weight: 700; color: #24485f; }
    .instructions { margin-top: 18px; background: #f8fbfd; border: 1px solid #e2ebf2; padding: 14px 16px; border-radius: 14px; }
  </style>
</head>
<body>
  <div class="sheet">
    <div class="title">{{titulo}}</div>
    <div class="meta">Código de referencia: <span class="code">{{codigo_referencia}}</span></div>
    <div class="meta">Fecha de emisión: {{fecha_emision}}</div>
    <div>{{campos_html}}</div>
    <div class="instructions">{{instrucciones_html}}</div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    public function renderVisibleFieldsHtml(array $fields, array $record): string
    {
        $rows = [];

        foreach ($fields as $field) {
            if ((int) ($field['visible_pdf'] ?? 0) !== 1 && (int) ($field['visible_plantilla'] ?? 0) !== 1) {
                continue;
            }

            $slug = $this->normalizeSlug((string) ($field['slug'] ?? ''));

            if ($slug === '') {
                continue;
            }

            $label = $this->safeHtml((string) ($field['etiqueta'] ?? $field['nombre'] ?? $slug));
            $value = $record[$slug] ?? '';

            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }

            if ($value === null || $value === '') {
                $value = 'N/D';
            } elseif (is_string($value) && str_contains($slug, 'archivo')) {
                $value = basename($value);
            }

            $rows[] = '<tr><td class="label">' . $label . '</td><td>' . $this->safeHtml((string) $value) . '</td></tr>';
        }

        return $rows === []
            ? '<p>No hay campos visibles para esta plantilla.</p>'
            : '<table>' . implode('', $rows) . '</table>';
    }

    /**
     * @param array<string, mixed> $form
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $record
     */
    public function renderPdfHtml(array $form, array $fields, array $record, string $title, string $fallbackInstructions = ''): string
    {
        $template = trim((string) ($form['plantilla_html'] ?? ''));

        if ($template === '') {
            $template = $this->defaultTemplateHtml($title);
        }

        $replacements = [
            '{{titulo}}' => $this->safeHtml($title),
            '{{codigo_referencia}}' => $this->safeHtml((string) ($record['codigo_referencia'] ?? '')),
            '{{fecha_emision}}' => $this->safeHtml(date('d/m/Y H:i')),
            '{{campos_html}}' => $this->renderVisibleFieldsHtml($fields, $record),
            '{{instrucciones_html}}' => trim((string) ($form['instrucciones_html'] ?? '')) !== ''
                ? (string) $form['instrucciones_html']
                : $fallbackInstructions,
        ];

        foreach ($record as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $replacements['{{' . $this->normalizeSlug((string) $key) . '}}'] = $this->safeHtml((string) $value);
        }

        return strtr($template, $replacements);
    }

    /**
     * @param array<string, mixed> $field
     */
    public function storeUploadedFile(UploadedFile $file, string $formSlug, array $field): string
    {
        $validation = $this->validateUpload($file, $field);

        if (! ($validation['ok'] ?? false)) {
            throw new RuntimeException((string) ($validation['error'] ?? 'El archivo no es válido.'));
        }

        $targetDir = WRITEPATH . 'uploads/ecoe/formularios/' . $this->normalizeSlug($formSlug) . '/';

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0750, true);
        }

        $ext = strtolower((string) ($validation['ext'] ?? $file->getClientExtension() ?: 'bin'));
        $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
        $file->move($targetDir, $fileName, true);

        return 'uploads/ecoe/formularios/' . $this->normalizeSlug($formSlug) . '/' . $fileName;
    }

    /**
     * @param array<string, mixed> $record
     */
    public function generateReferenceCode(array $record, int $id, string $formCode = 'ECOE'): string
    {
        $dpiDigits = preg_replace('/\D+/', '', (string) ($record['dpi'] ?? $record['nit'] ?? ''));
        $suffix = str_pad(substr($dpiDigits, -5), 5, '0', STR_PAD_LEFT);
        $type = preg_replace('/[^A-Z0-9]+/i', '', strtoupper(trim($formCode))) ?: 'ECOE';
        $systemNumber = str_pad((string) max(1, $id), 7, '0', STR_PAD_LEFT);

        return $type . '-' . $suffix . '-' . $systemNumber;
    }

    private function safeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    public function createDynamicTable(string $slug, array $fields = []): void
    {
        $tableName = $this->dynamicTableName($slug);

        if ($this->db->tableExists($tableName)) {
            return;
        }

        $this->forge->addField($this->baseTableFields());

        foreach ($fields as $field) {
            $column = $this->normalizeSlug((string) ($field['slug'] ?? ''));

            if ($column === '' || isset($this->baseTableFields()[$column])) {
                continue;
            }

            $this->forge->addField([$column => $this->forgeFieldDefinition($field)]);
        }

        $this->forge->addKey('id', true);
        $this->forge->addKey('formulario_id', false, false, 'idx_dyn_formulario');
        $this->forge->addKey('codigo_referencia', false, false, 'idx_dyn_codigo_referencia');
        $this->forge->addKey('dpi', false, false, 'idx_dyn_dpi');
        $this->forge->addKey('nit', false, false, 'idx_dyn_nit');
        $this->forge->addKey('estado_tramite', false, false, 'idx_dyn_estado_tramite');
        $this->forge->addKey('created_at', false, false, 'idx_dyn_created_at');
        $this->forge->addUniqueKey('codigo_referencia', 'uq_dyn_codigo_referencia');
        $this->forge->addForeignKey('formulario_id', 'ecoe_formularios', 'id', 'CASCADE', 'CASCADE', 'fk_dyn_formulario');

        if ($this->db->tableExists('ecoe_eem_listado')) {
            $this->forge->addForeignKey('empresa_electrica_id', 'ecoe_eem_listado', 'id', 'SET NULL', 'SET NULL', 'fk_dyn_empresa_electrica');
        }

        $this->forge->createTable($tableName, true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    /**
     * @param array<string, mixed> $field
     */
    public function addFieldColumn(string $slug, array $field): void
    {
        $tableName = $this->dynamicTableName($slug);
        $column = $this->normalizeSlug((string) ($field['slug'] ?? ''));

        if ($column === '' || ! $this->db->tableExists($tableName) || $this->columnExists($tableName, $column)) {
            return;
        }

        $this->forge->addColumn($tableName, [$column => $this->forgeFieldDefinition($field)]);
    }

    /**
     * @param array<string, mixed> $field
     */
    public function renameFieldColumn(string $slug, string $oldColumn, string $newColumn, array $field): bool
    {
        $tableName = $this->dynamicTableName($slug);
        $old = $this->normalizeSlug($oldColumn);
        $new = $this->normalizeSlug($newColumn);

        if ($old === '' || $new === '' || $old === $new) {
            return false;
        }

        if (! $this->db->tableExists($tableName)) {
            throw new RuntimeException('La tabla dinámica no existe para este formulario.');
        }

        if (! $this->columnExists($tableName, $old)) {
            throw new RuntimeException('La columna origen no existe en la tabla dinámica.');
        }

        if ($this->columnExists($tableName, $new)) {
            throw new RuntimeException('Ya existe una columna con el nuevo slug en la tabla dinámica.');
        }

        $definition = $this->forgeFieldDefinition($field);
        $sqlType = $this->sqlTypeFromDefinition($definition);
        $sqlNull = ((bool) ($definition['null'] ?? true)) ? 'NULL' : 'NOT NULL';

        if ($sqlType === '') {
            throw new RuntimeException('No fue posible resolver el tipo SQL para renombrar la columna.');
        }

        $tableSql = $this->db->protectIdentifiers($tableName, true);
        $oldSql = $this->db->protectIdentifiers($old, true);
        $newSql = $this->db->protectIdentifiers($new, true);
        $sql = 'ALTER TABLE ' . $tableSql . ' CHANGE ' . $oldSql . ' ' . $newSql . ' ' . $sqlType . ' ' . $sqlNull;

        if (! $this->db->query($sql)) {
            throw new RuntimeException('No fue posible renombrar la columna física en la tabla dinámica.');
        }

        return true;
    }

    public function archiveDynamicTable(string $slug): ?string
    {
        $tableName = $this->dynamicTableName($slug);

        if (! $this->db->tableExists($tableName)) {
            return null;
        }

        $backupName = $this->archivedTableName($slug);

        if (! $this->db->query('RENAME TABLE ' . $this->db->protectIdentifiers($tableName, true) . ' TO ' . $this->db->protectIdentifiers($backupName, true))) {
            throw new RuntimeException('No fue posible archivar la tabla dinámica.');
        }

        return $backupName;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function forgeFieldDefinition(array $field): array
    {
        $tipo = strtolower((string) ($field['tipo'] ?? 'text'));

        return match ($tipo) {
            'textarea' => ['type' => 'LONGTEXT', 'null' => true],
            'number' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'file' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'date', 'time', 'email', 'text', 'select', 'radio', 'checkbox' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            default => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function baseTableFields(): array
    {
        return [
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'formulario_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'codigo_referencia' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'empresa_electrica_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'dpi' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'nit' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'estado_tramite' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'recibido',
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];
    }

    private function columnExists(string $tableName, string $column): bool
    {
        return $this->db->fieldExists($column, $tableName);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function sqlTypeFromDefinition(array $definition): string
    {
        $type = strtoupper((string) ($definition['type'] ?? ''));

        if ($type === '') {
            return '';
        }

        $constraint = $definition['constraint'] ?? null;

        if ($constraint === null || $constraint === '') {
            return $type;
        }

        return $type . '(' . $constraint . ')';
    }

    /**
     * @return array<string, string>
     */
    public function normalizePayload(array $input, array $fields): array
    {
        $payload = [];

        foreach ($fields as $field) {
            $column = $this->normalizeSlug((string) ($field['slug'] ?? ''));
            if ($column === '') {
                continue;
            }

            $payload[$column] = $input[$column] ?? null;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $field
     * @return array{ok: bool, error?: string, mime?: string, ext?: string, allowed_exts?: array<int, string>, allowed_mimes?: array<int, string>}
     */
    public function validateUpload(UploadedFile $file, array $field): array
    {
        if (! $file->isValid() || $file->hasMoved()) {
            return ['ok' => false, 'error' => 'El archivo no es válido.'];
        }

        $config = $this->fieldConfig($field);
        $allowedExts = array_map('strtolower', (array) ($config['allowed_exts'] ?? ['pdf', 'jpg', 'jpeg', 'png', 'webp']));
        $allowedMimes = array_map('strtolower', (array) ($config['allowed_mimes'] ?? [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ]));
        $maxMb = max((int) ($config['max_mb'] ?? 8), 1);

        $ext = strtolower((string) $file->getClientExtension());
        $mime = strtolower((string) ($file->getMimeType() ?? ''));

        if (! in_array($ext, $allowedExts, true)) {
            return ['ok' => false, 'error' => 'La extensión del archivo no está permitida.'];
        }

        if (! in_array($mime, $allowedMimes, true)) {
            return ['ok' => false, 'error' => 'El tipo MIME del archivo no está permitido.'];
        }

        if ((int) $file->getSize() > $maxMb * 1024 * 1024) {
            return ['ok' => false, 'error' => 'El archivo excede el tamaño máximo permitido.'];
        }

        return [
            'ok' => true,
            'mime' => $mime,
            'ext' => $ext,
            'allowed_exts' => $allowedExts,
            'allowed_mimes' => $allowedMimes,
        ];
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function fieldConfig(array $field): array
    {
        $decoded = json_decode((string) ($field['opciones_json'] ?? ''), true);

        return is_array($decoded) ? $decoded : [];
    }
}