<?php

namespace App\Modules\Admin\Controllers;

use App\Models\LanguageModel;
use App\Models\TranslationModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class TranslationsController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        $languages = (new LanguageModel())->orderBy('id', 'ASC')->findAll();

        return $this->adminView('App\\Modules\\Admin\\Views\\idiomas', [
            'title' => 'Idiomas',
            'languages' => $languages,
        ]);
    }

    public function list()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $db = db_connect();
        $builder = $db->table('translations');

        $lang = trim((string) $this->request->getGet('lang'));
        $search = trim((string) $this->request->getGet('search'));
        $onlyAutodiscovered = (string) $this->request->getGet('autodiscovered') === '1';

        if ($lang !== '') {
            $builder->where('lang_code', $lang);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('translation_key', $search)
                ->orLike('translation_value', $search)
                ->groupEnd();
        }

        if ($onlyAutodiscovered) {
            $builder->where('is_autodiscovered', 1);
        }

        $rows = $builder->orderBy('lang_code', 'ASC')->orderBy('translation_key', 'ASC')->get()->getResultArray();

        return $this->encryptedAdminResponse(['translations' => $rows]);
    }

    public function languages()
    {
        if (! is_array($this->authProfile())) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        return $this->encryptedAdminResponse([
            'languages' => (new LanguageModel())->orderBy('id', 'ASC')->findAll(),
        ]);
    }

    public function save()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $id = (int) $this->request->getPost('id');
        $data = [
            'lang_code' => strtolower(trim((string) $this->request->getPost('lang_code'))),
            'translation_key' => trim((string) $this->request->getPost('translation_key')),
            'translation_value' => (string) $this->request->getPost('translation_value'),
        ];

        if ($data['lang_code'] === '' || $data['translation_key'] === '') {
            return $this->encryptedAdminResponse(['message' => 'Idioma y clave son obligatorios.'], 422);
        }

        $language = (new LanguageModel())->where('code', $data['lang_code'])->first();

        if (! is_array($language) || (int) $language['is_active'] !== 1) {
            return $this->encryptedAdminResponse(['message' => 'El idioma seleccionado no esta activo en el catalogo.'], 422);
        }

        $model = new TranslationModel();

        $duplicate = $model->where('lang_code', $data['lang_code'])
            ->where('translation_key', $data['translation_key'])
            ->first();

        if (is_array($duplicate) && (int) $duplicate['id'] !== $id) {
            return $this->encryptedAdminResponse(['message' => 'Ya existe una traduccion para ese idioma y clave.'], 422);
        }

        $sourceTextNormalized = '';

        if ($id > 0) {
            $existing = $model->find($id);

            if (! is_array($existing)) {
                return $this->encryptedAdminResponse(['message' => 'Traduccion no encontrada.'], 404);
            }

            $sourceTextNormalized = trim((string) ($existing['source_text_normalized'] ?? ''));

            if ($sourceTextNormalized === '' && trim((string) ($existing['source_text'] ?? '')) !== '') {
                $sourceTextNormalized = translation_normalize_text((string) $existing['source_text']);
            }

            $model->update($id, array_merge($data, ['is_autodiscovered' => 0]));
        } else {
            $model->insert(array_merge($data, ['is_autodiscovered' => 0]));
            $id = (int) $model->getInsertID();
        }

        $propagated = 0;

        if ($sourceTextNormalized !== '') {
            $propagated = $model->propagatePendingByNormalizedSourceText($data['lang_code'], $sourceTextNormalized, $data['translation_value'], $id);
        }

        translation_clear_cache($data['lang_code']);

        $message = 'Traduccion guardada correctamente.';

        if ($propagated > 0) {
            $message .= " Se reutilizo automaticamente en {$propagated} clave(s) pendiente(s) con el mismo texto origen.";
        }

        return $this->encryptedAdminResponse(['message' => $message]);
    }

    public function delete()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $id = (int) $this->request->getPost('id');
        $model = new TranslationModel();
        $translation = $model->find($id);

        if (! is_array($translation)) {
            return $this->encryptedAdminResponse(['message' => 'Traduccion no encontrada.'], 404);
        }

        $model->delete($id);
        translation_clear_cache((string) $translation['lang_code']);

        return $this->encryptedAdminResponse(['message' => 'Traduccion eliminada correctamente.']);
    }

    public function clearCache()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        translation_clear_cache();

        return $this->encryptedAdminResponse(['message' => 'Cache de traducciones limpiado correctamente.']);
    }

    public function saveLanguage()
    {
        if (! is_array($this->authProfile())) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $id = (int) $this->request->getPost('id');
        $data = [
            'code' => strtolower(trim((string) $this->request->getPost('code'))),
            'name' => trim((string) $this->request->getPost('name')),
            'is_active' => $this->request->getPost('is_active') === '0' ? 0 : 1,
            'is_visible' => $this->request->getPost('is_visible') === '0' ? 0 : 1,
        ];

        $model = new LanguageModel();

        if (! preg_match('/^[a-z][a-z0-9-]{1,9}$/', $data['code']) || $data['name'] === '') {
            return $this->encryptedAdminResponse(['message' => 'Codigo y nombre son obligatorios. El codigo debe usar letras minusculas, numeros o guiones.'], 422);
        }

        $duplicate = $model->where('code', $data['code'])->first();

        if (is_array($duplicate) && (int) $duplicate['id'] !== $id) {
            return $this->encryptedAdminResponse(['message' => 'Ya existe una lengua con ese codigo.'], 422);
        }

        if ($id > 0) {
            if (! is_array($model->find($id))) {
                return $this->encryptedAdminResponse(['message' => 'Lengua no encontrada.'], 404);
            }

            $model->update($id, $data);
        } else {
            $model->insert($data);
        }

        return $this->encryptedAdminResponse(['message' => 'Lengua guardada correctamente.']);
    }

    public function deleteLanguage()
    {
        if (! is_array($this->authProfile())) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $id = (int) $this->request->getPost('id');
        $model = new LanguageModel();
        $language = $model->find($id);

        if (! is_array($language)) {
            return $this->encryptedAdminResponse(['message' => 'Lengua no encontrada.'], 404);
        }

        if (db_connect()->table('translations')->where('lang_code', $language['code'])->countAllResults() > 0) {
            return $this->encryptedAdminResponse(['message' => 'No se puede eliminar una lengua con traducciones. Desactivala en su lugar.'], 422);
        }

        $model->delete($id);

        return $this->encryptedAdminResponse(['message' => 'Lengua eliminada correctamente.']);
    }

    public function export()
    {
        if (! is_array($this->authProfile())) {
            return redirect()->to('login');
        }

        $languages = $this->orderedLanguages();
        $model = new TranslationModel();
        $rows = $model->orderBy('id', 'ASC')->findAll();
        $grouped = [];

        foreach ($rows as $row) {
            $groupKey = trim((string) ($row['source_text_normalized'] ?? ''));
            $groupKey = $groupKey !== '' ? $groupKey : 'key:' . $row['translation_key'];

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'id' => (int) $row['id'],
                    'source_text' => (string) ($row['source_text'] ?? ''),
                    'source_text_normalized' => (string) ($row['source_text_normalized'] ?? ''),
                    'values' => [],
                ];
            }

            $grouped[$groupKey]['values'][$row['lang_code']] = (string) ($row['translation_value'] ?? '');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->spreadsheetHeaders($languages);
        $sheet->fromArray($headers, null, 'A1');
        $line = 2;

        foreach ($grouped as $row) {
            $values = [$row['id'], $row['source_text'], $row['source_text_normalized']];

            foreach ($languages as $language) {
                $values[] = $row['values'][$language['code']] ?? '';
            }

            $sheet->fromArray($values, null, 'A' . $line++);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $contents = (string) ob_get_clean();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="traducciones.xlsx"')
            ->setBody($contents);
    }

    public function import()
    {
        if (! is_array($this->authProfile())) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $file = $this->request->getFile('translation_file');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $this->encryptedAdminResponse(['message' => 'Archivo no recibido o no valido.'], 422);
        }

        $extension = strtolower((string) $file->getClientExtension());

        if (! in_array($extension, ['xlsx', 'xls'], true) || $file->getSize() > 20 * 1024 * 1024) {
            return $this->encryptedAdminResponse(['message' => 'Solo se permiten archivos Excel .xlsx o .xls de hasta 20 MB.'], 422);
        }

        $tempPath = WRITEPATH . 'tmp/translations_' . bin2hex(random_bytes(12)) . '.' . $extension;

        try {
            $file->move(dirname($tempPath), basename($tempPath));
            $spreadsheet = IOFactory::load($tempPath);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $languages = $this->orderedLanguages();
            $expectedHeaders = $this->spreadsheetHeaders($languages);
            $headers = array_map(static fn ($header): string => trim((string) $header), (array) ($rows[0] ?? []));

            if ($headers !== $expectedHeaders) {
                return $this->encryptedAdminResponse(['message' => 'La plantilla no coincide con la estructura vigente. Genera una nueva plantilla desde Exportar.'], 422);
            }

            $translationModel = new TranslationModel();
            $db = db_connect();
            $db->transBegin();
            $processed = 0;

            foreach (array_slice($rows, 1) as $rowNumber => $row) {
                $row = array_pad($row, count($expectedHeaders), null);
                if (trim(implode('', array_map(static fn ($value): string => (string) $value, $row))) === '') {
                    continue;
                }

                $id = (int) $row[0];
                $existing = $id > 0 ? $translationModel->find($id) : null;
                $sourceText = trim((string) $row[1]);
                $normalized = trim((string) $row[2]);

                if (! is_array($existing) && $normalized === '') {
                    throw new \RuntimeException('La fila ' . ($rowNumber + 2) . ' necesita id o source_text_normalized.');
                }

                $key = is_array($existing)
                    ? (string) $existing['translation_key']
                    : 'imported.' . substr(hash('sha256', $normalized), 0, 40);
                $processed++;

                foreach ($languages as $index => $language) {
                    $value = (string) ($row[$index + 3] ?? '');
                    $payload = [
                        'lang_code' => $language['code'],
                        'translation_key' => $key,
                        'source_text' => $sourceText !== '' ? $sourceText : null,
                        'source_text_normalized' => $normalized !== '' ? $normalized : null,
                        'translation_value' => $value,
                        'is_autodiscovered' => 0,
                    ];
                    $target = $translationModel->findByLangAndKey($language['code'], $key);

                    if (is_array($target)) {
                        $translationModel->update((int) $target['id'], $payload);
                    } else {
                        $translationModel->insert($payload);
                    }
                }
            }

            if (! $db->transStatus()) {
                throw new \RuntimeException('La transaccion de importacion no pudo completarse.');
            }

            $db->transCommit();
            translation_clear_cache();

            return $this->encryptedAdminResponse(['message' => "Importacion completada. {$processed} fila(s) procesada(s)."]); 
        } catch (Throwable $e) {
            if (isset($db)) {
                $db->transRollback();
            }

            return $this->encryptedAdminResponse(['message' => 'Error validando o importando el Excel: ' . $e->getMessage()], 422);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function orderedLanguages(): array
    {
        $model = new LanguageModel();
        $languages = $model
            ->where('is_active', 1)
            ->where('is_visible', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
        usort($languages, static function (array $left, array $right): int {
            $priority = ['es' => 0, 'en' => 1];
            return ($priority[$left['code']] ?? 2) <=> ($priority[$right['code']] ?? 2)
                ?: ((int) $left['id'] <=> (int) $right['id']);
        });

        return $languages;
    }

    /** @param array<int, array<string, mixed>> $languages */
    private function spreadsheetHeaders(array $languages): array
    {
        $headers = ['id', 'source_text', 'source_text_normalized'];

        foreach ($languages as $language) {
            $label = (string) $language['name'];

            if ($language['code'] === 'es') {
                $label = 'español';
            } elseif ($language['code'] === 'en') {
                $label = 'Ingles';
            }

            $headers[] = $label . ' (translation_value)';
        }

        return $headers;
    }
}
