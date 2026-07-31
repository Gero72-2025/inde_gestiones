<?php

namespace App\Modules\Ecoe\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Ecoe\Models\FormularioCampoModel;
use App\Modules\Ecoe\Models\FormularioModel;
use App\Modules\Ecoe\Services\FormularioEngineService;
use CodeIgniter\HTTP\RedirectResponse;

class FormularioController extends AdminBaseController
{
    private const PERM = 'gerencia.ecoe.formularios.access';

    private FormularioModel $formularioModel;
    private FormularioCampoModel $campoModel;
    private FormularioEngineService $engine;

    public function initController($request, $response, $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->formularioModel = new FormularioModel();
        $this->campoModel = new FormularioCampoModel();
        $this->engine = new FormularioEngineService();
    }

    public function index(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para administrar formularios ECOE.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'save_form'   => $this->saveForm($auth),
                'save_field'  => $this->saveField($auth),
                'update_field'=> $this->updateField($auth),
                'delete_form' => $this->deleteForm($auth),
                'delete_field'=> $this->deleteField($auth),
                default       => $this->encryptedAdminResponse(['message' => 'Acción no reconocida.'], 400),
            };
        }

        $selectedFormId = max(0, (int) $this->request->getGet('form_id'));
        $selectedForm = $selectedFormId > 0 ? $this->formularioModel->find($selectedFormId) : null;
        $selectedFields = $selectedForm ? $this->campoModel->listByFormulario((int) $selectedForm['id'], false) : [];

        return $this->adminView('App\Modules\Ecoe\Views\formularios_admin', [
            'formularios' => $this->formularioModel->listAll(),
            'selectedForm' => $selectedForm,
            'selectedFields' => $selectedFields,
            'selectedFormId' => $selectedFormId,
            'moduloOptions' => [
                ['value' => 'gu', 'label' => 'Grandes Usuarios'],
                ['value' => 'eem', 'label' => 'EEM'],
            ],
            'fieldTypeOptions' => ['text', 'textarea', 'email', 'number', 'date', 'time', 'select', 'radio', 'checkbox', 'file'],
        ]);
    }

    private function canAccess(array $auth): bool
    {
        $isSuperAdmin = $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []));

        if ($isSuperAdmin) {
            return true;
        }

        return ((string) ($auth['gerencia_slug'] ?? '')) === 'ecoe'
            && $this->rbac->hasPermission((array) ($auth['permissions'] ?? []), self::PERM);
    }

    private function saveForm(array $auth): mixed
    {
        $id = (int) $this->request->getPost('id');
        $nombre = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 160);
        $codigo = strtoupper(mb_substr(trim((string) $this->request->getPost('codigo')), 0, 40));
        $slugRaw = mb_substr(trim((string) $this->request->getPost('slug')), 0, 120);
        $moduloAsignado = strtolower(trim((string) $this->request->getPost('modulo_asignado')));
        $descripcion = mb_substr(trim((string) $this->request->getPost('descripcion')), 0, 1000);
        $plantillaHtml = (string) $this->request->getPost('plantilla_html');
        $instruccionesHtml = (string) $this->request->getPost('instrucciones_html');
        $estado = ((int) $this->request->getPost('estado')) === 1 ? 1 : 0;

        if ($nombre === '' || $codigo === '' || $slugRaw === '' || ! in_array($moduloAsignado, ['gu', 'eem'], true)) {
            return $this->encryptedAdminResponse(['message' => 'Nombre, código, slug y módulo asignado son obligatorios.'], 422);
        }

        $slug = $this->engine->normalizeSlug($slugRaw);

        if ($slug === '') {
            return $this->encryptedAdminResponse(['message' => 'El slug no es válido.'], 422);
        }

        if ($id > 0) {
            $current = $this->formularioModel->find($id);

            if (! $current) {
                return $this->encryptedAdminResponse(['message' => 'Formulario no encontrado.'], 404);
            }

            if ((string) $current['slug'] !== $slug) {
                return $this->encryptedAdminResponse(['message' => 'El slug no puede modificarse después de crear la tabla dinámica.'], 422);
            }

            $this->formularioModel->update($id, [
                'nombre' => $nombre,
                'codigo' => $codigo,
                'modulo_asignado' => $moduloAsignado,
                'descripcion' => $descripcion,
                'plantilla_html' => $plantillaHtml,
                'instrucciones_html' => $instruccionesHtml,
                'estado' => $estado,
            ]);

            return $this->encryptedAdminResponse(['message' => 'Formulario actualizado correctamente.']);
        }

        $duplicate = $this->formularioModel->where('slug', $slug)
            ->orWhere('codigo', $codigo)
            ->first();

        if ($duplicate) {
            return $this->encryptedAdminResponse(['message' => 'El slug o código ya existe.'], 422);
        }

        $insertId = (int) $this->formularioModel->insert([
            'nombre' => $nombre,
            'codigo' => $codigo,
            'slug' => $slug,
            'modulo_asignado' => $moduloAsignado,
            'descripcion' => $descripcion,
            'plantilla_html' => $plantillaHtml !== '' ? $plantillaHtml : $this->engine->defaultTemplateHtml($nombre),
            'instrucciones_html' => $instruccionesHtml !== '' ? $instruccionesHtml : '<p>Formulario registrado correctamente.</p>',
            'estado' => $estado,
        ], true);

        if ($insertId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'No fue posible crear el formulario.'], 500);
        }

        try {
            $this->engine->createDynamicTable($slug);
        } catch (\Throwable $exception) {
            $this->formularioModel->delete($insertId);

            return $this->encryptedAdminResponse(['message' => 'No fue posible crear la tabla dinámica: ' . $exception->getMessage()], 500);
        }

        return $this->encryptedAdminResponse([
            'message' => 'Formulario creado correctamente.',
            'formulario_id' => $insertId,
        ]);
    }

    private function saveField(array $auth): mixed
    {
        $formularioId = (int) $this->request->getPost('formulario_id');
        $formulario = $this->formularioModel->find($formularioId);

        if (! $formulario) {
            return $this->encryptedAdminResponse(['message' => 'Selecciona un formulario válido.'], 422);
        }

        $nombre = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 160);
        $slugRaw = mb_substr(trim((string) $this->request->getPost('slug')), 0, 120);
        $tipo = strtolower(trim((string) $this->request->getPost('tipo')));
        $etiqueta = mb_substr(trim((string) $this->request->getPost('etiqueta')), 0, 160);
        $ayuda = mb_substr(trim((string) $this->request->getPost('ayuda')), 0, 2000);
        $opcionesJson = (string) $this->request->getPost('opciones_json');
        $obligatorio = ((int) $this->request->getPost('obligatorio')) === 1 ? 1 : 0;
        $visiblePdf = ((int) $this->request->getPost('visible_pdf')) === 1 ? 1 : 0;
        $visiblePlantilla = ((int) $this->request->getPost('visible_plantilla')) === 1 ? 1 : 0;
        $estado = ((int) $this->request->getPost('estado')) === 1 ? 1 : 0;
        $orden = max(0, (int) $this->request->getPost('orden'));

        $slug = $this->engine->normalizeSlug($slugRaw);

        if ($nombre === '' || $slug === '' || $tipo === '') {
            return $this->encryptedAdminResponse(['message' => 'Nombre, slug y tipo son obligatorios.'], 422);
        }

        if (! in_array($tipo, ['text', 'textarea', 'email', 'number', 'date', 'time', 'select', 'radio', 'checkbox', 'file'], true)) {
            return $this->encryptedAdminResponse(['message' => 'Tipo de campo no permitido.'], 422);
        }

        $duplicate = $this->campoModel->where('formulario_id', $formularioId)
            ->where('slug', $slug)
            ->first();

        if ($duplicate) {
            return $this->encryptedAdminResponse(['message' => 'Ya existe un campo con ese slug en el formulario.'], 422);
        }

        $payload = [
            'formulario_id' => $formularioId,
            'nombre' => $nombre,
            'slug' => $slug,
            'tipo' => $tipo,
            'etiqueta' => $etiqueta !== '' ? $etiqueta : $nombre,
            'ayuda' => $ayuda !== '' ? $ayuda : null,
            'opciones_json' => trim($opcionesJson) !== '' ? $opcionesJson : null,
            'obligatorio' => $obligatorio,
            'visible_pdf' => $visiblePdf,
            'visible_plantilla' => $visiblePlantilla,
            'estado' => $estado,
            'orden' => $orden,
        ];

        $campoId = (int) $this->campoModel->insert($payload, true);

        if ($campoId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'No fue posible guardar el campo.'], 500);
        }

        try {
            $this->engine->addFieldColumn((string) $formulario['slug'], $payload);
        } catch (\Throwable $exception) {
            $this->campoModel->delete($campoId);

            return $this->encryptedAdminResponse(['message' => 'No fue posible agregar la columna física: ' . $exception->getMessage()], 500);
        }

        return $this->encryptedAdminResponse([
            'message' => 'Campo agregado correctamente.',
            'campo_id' => $campoId,
        ]);
    }

    private function updateField(array $auth): mixed
    {
        $fieldId = (int) $this->request->getPost('field_id');
        $existing = $this->campoModel->find($fieldId);

        if (! $existing) {
            return $this->encryptedAdminResponse(['message' => 'Campo no encontrado.'], 404);
        }

        $formularioId = (int) ($existing['formulario_id'] ?? 0);
        $formulario = $this->formularioModel->find($formularioId);

        if (! $formulario) {
            return $this->encryptedAdminResponse(['message' => 'Formulario asociado no encontrado.'], 422);
        }

        $nombre = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 160);
        $slugRaw = mb_substr(trim((string) $this->request->getPost('slug')), 0, 120);
        $tipo = strtolower(trim((string) $this->request->getPost('tipo')));
        $etiqueta = mb_substr(trim((string) $this->request->getPost('etiqueta')), 0, 160);
        $ayuda = mb_substr(trim((string) $this->request->getPost('ayuda')), 0, 2000);
        $opcionesJson = (string) $this->request->getPost('opciones_json');
        $obligatorio = ((int) $this->request->getPost('obligatorio')) === 1 ? 1 : 0;
        $visiblePdf = ((int) $this->request->getPost('visible_pdf')) === 1 ? 1 : 0;
        $visiblePlantilla = ((int) $this->request->getPost('visible_plantilla')) === 1 ? 1 : 0;
        $estado = ((int) $this->request->getPost('estado')) === 1 ? 1 : 0;
        $orden = max(0, (int) $this->request->getPost('orden'));

        $slug = $this->engine->normalizeSlug($slugRaw);

        if ($nombre === '' || $slug === '' || $tipo === '') {
            return $this->encryptedAdminResponse(['message' => 'Nombre, slug y tipo son obligatorios.'], 422);
        }

        if (! in_array($tipo, ['text', 'textarea', 'email', 'number', 'date', 'time', 'select', 'radio', 'checkbox', 'file'], true)) {
            return $this->encryptedAdminResponse(['message' => 'Tipo de campo no permitido.'], 422);
        }

        $duplicate = $this->campoModel
            ->where('formulario_id', $formularioId)
            ->where('slug', $slug)
            ->where('id !=', $fieldId)
            ->first();

        if ($duplicate) {
            return $this->encryptedAdminResponse(['message' => 'Ya existe un campo con ese slug en el formulario.'], 422);
        }

        $payload = [
            'nombre' => $nombre,
            'slug' => $slug,
            'tipo' => $tipo,
            'etiqueta' => $etiqueta !== '' ? $etiqueta : $nombre,
            'ayuda' => $ayuda !== '' ? $ayuda : null,
            'opciones_json' => trim($opcionesJson) !== '' ? $opcionesJson : null,
            'obligatorio' => $obligatorio,
            'visible_pdf' => $visiblePdf,
            'visible_plantilla' => $visiblePlantilla,
            'estado' => $estado,
            'orden' => $orden,
        ];

        $oldSlug = (string) ($existing['slug'] ?? '');
        $slugChanged = $this->engine->normalizeSlug($oldSlug) !== $slug;
        $migrationFile = null;

        if ($slugChanged) {
            try {
                $this->engine->renameFieldColumn((string) $formulario['slug'], $oldSlug, $slug, $payload);
            } catch (\Throwable $exception) {
                return $this->encryptedAdminResponse(['message' => 'No fue posible renombrar la columna física: ' . $exception->getMessage()], 500);
            }

            $tableName = $this->engine->dynamicTableName((string) $formulario['slug']);
            $fieldDefinition = $this->engine->forgeFieldDefinition($payload);
            $migrationFile = $this->createRenameColumnMigration($tableName, $oldSlug, $slug, $fieldDefinition);
        }

        $this->campoModel->update($fieldId, $payload);

        $message = 'Campo actualizado correctamente.';
        if ($migrationFile !== null) {
            $message .= ' Migración generada: ' . $migrationFile;
        }

        return $this->encryptedAdminResponse([
            'message' => $message,
            'migration_file' => $migrationFile,
        ]);
    }

    private function deleteField(array $auth): mixed
    {
        $campoId = (int) $this->request->getPost('campo_id');
        $campo = $this->campoModel->find($campoId);

        if (! $campo) {
            return $this->encryptedAdminResponse(['message' => 'Campo no encontrado.'], 404);
        }

        $this->campoModel->update($campoId, ['estado' => 0]);

        return $this->encryptedAdminResponse(['message' => 'Campo ocultado correctamente.']);
    }

    private function deleteForm(array $auth): mixed
    {
        $formularioId = (int) $this->request->getPost('formulario_id');
        $formulario = $this->formularioModel->find($formularioId);

        if (! $formulario) {
            return $this->encryptedAdminResponse(['message' => 'Formulario no encontrado.'], 404);
        }

        try {
            $backupName = $this->engine->archiveDynamicTable((string) $formulario['slug']);
        } catch (\Throwable $exception) {
            return $this->encryptedAdminResponse(['message' => $exception->getMessage()], 500);
        }

        $this->formularioModel->update($formularioId, ['estado' => 0]);
        $this->campoModel->where('formulario_id', $formularioId)->set(['estado' => 0])->update();

        return $this->encryptedAdminResponse([
            'message' => 'Formulario desactivado correctamente.',
            'backup_table' => $backupName,
        ]);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function createRenameColumnMigration(string $tableName, string $oldColumn, string $newColumn, array $definition): ?string
    {
        $old = $this->engine->normalizeSlug($oldColumn);
        $new = $this->engine->normalizeSlug($newColumn);

        if ($old === '' || $new === '' || $old === $new) {
            return null;
        }

        $datePrefix = date('Y-m-d-His');
        $classBase = 'Rename' . $this->toStudly($tableName) . $this->toStudly($old) . 'To' . $this->toStudly($new);
        $className = preg_replace('/[^A-Za-z0-9]/', '', $classBase) ?: ('RenameDynamicColumn' . date('YmdHis'));
        $fileName = $datePrefix . '_' . $className . '.php';
        $path = APPPATH . 'Database/Migrations/' . $fileName;

        if (is_file($path)) {
            $fileName = $datePrefix . '_' . $className . '_' . substr(bin2hex(random_bytes(2)), 0, 4) . '.php';
            $path = APPPATH . 'Database/Migrations/' . $fileName;
        }

        $upDefinition = $definition;
        $upDefinition['name'] = $new;
        $downDefinition = $definition;
        $downDefinition['name'] = $old;

        $upDefinitionCode = $this->phpArray($upDefinition, 5);
        $downDefinitionCode = $this->phpArray($downDefinition, 5);

        $content = "<?php\n\n"
            . "namespace App\\Database\\Migrations;\n\n"
            . "use CodeIgniter\\Database\\Migration;\n\n"
            . "class {$className} extends Migration\n"
            . "{\n"
            . "    public function up(): void\n"
            . "    {\n"
            . "        if (! \$this->db->tableExists('{$tableName}')) {\n"
            . "            return;\n"
            . "        }\n\n"
            . "        if (! \$this->db->fieldExists('{$old}', '{$tableName}') || \$this->db->fieldExists('{$new}', '{$tableName}')) {\n"
            . "            return;\n"
            . "        }\n\n"
            . "        \$this->forge->modifyColumn('{$tableName}', [\n"
            . "            '{$old}' => {$upDefinitionCode},\n"
            . "        ]);\n"
            . "    }\n\n"
            . "    public function down(): void\n"
            . "    {\n"
            . "        if (! \$this->db->tableExists('{$tableName}')) {\n"
            . "            return;\n"
            . "        }\n\n"
            . "        if (! \$this->db->fieldExists('{$new}', '{$tableName}') || \$this->db->fieldExists('{$old}', '{$tableName}')) {\n"
            . "            return;\n"
            . "        }\n\n"
            . "        \$this->forge->modifyColumn('{$tableName}', [\n"
            . "            '{$new}' => {$downDefinitionCode},\n"
            . "        ]);\n"
            . "    }\n"
            . "}\n";

        if (file_put_contents($path, $content) === false) {
            return null;
        }

        return $fileName;
    }

    /**
     * @param array<string, mixed> $value
     */
    private function phpArray(array $value, int $indentLevel): string
    {
        $indent = str_repeat('    ', $indentLevel);
        $innerIndent = $indent . '    ';
        $rows = [];

        foreach ($value as $key => $item) {
            $rows[] = $innerIndent . var_export((string) $key, true) . ' => ' . var_export($item, true) . ',';
        }

        if ($rows === []) {
            return '[]';
        }

        return "[\n" . implode("\n", $rows) . "\n" . $indent . "]";
    }

    private function toStudly(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? '';
        $parts = preg_split('/\s+/', trim($clean)) ?: [];
        $studly = '';

        foreach ($parts as $part) {
            $studly .= ucfirst(strtolower($part));
        }

        return $studly;
    }
}