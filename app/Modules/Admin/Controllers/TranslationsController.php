<?php

namespace App\Modules\Admin\Controllers;

use App\Models\TranslationModel;

class TranslationsController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\idiomas', [
            'title' => 'Idiomas',
            'supportedLocales' => config('App')->supportedLocales,
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

    public function save()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $id = (int) $this->request->getPost('id');
        $supported = config('App')->supportedLocales;

        $data = [
            'lang_code' => strtolower(trim((string) $this->request->getPost('lang_code'))),
            'translation_key' => trim((string) $this->request->getPost('translation_key')),
            'translation_value' => (string) $this->request->getPost('translation_value'),
        ];

        if ($data['lang_code'] === '' || $data['translation_key'] === '') {
            return $this->encryptedAdminResponse(['message' => 'Idioma y clave son obligatorios.'], 422);
        }

        if (! in_array($data['lang_code'], $supported, true)) {
            return $this->encryptedAdminResponse(['message' => 'El idioma seleccionado no esta soportado.'], 422);
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
}
