<?php

namespace App\Modules\Admin\Controllers;

use App\Modules\Ecoe\Models\TsConsejoModel;

class ConsejosController extends AdminBaseController
{
    private const IMAGE_DIRECTORY = 'uploads/consejos/';
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\consejos', [
            'title' => 'Consejos de ahorro',
        ]);
    }

    public function list()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $model = new TsConsejoModel();

        return $this->encryptedAdminResponse(['consejos' => $model->listOrdered()]);
    }

    public function save()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $id = (int) $this->request->getPost('id');
        $data = [
            'clave' => trim((string) $this->request->getPost('clave')),
            'texto' => trim((string) $this->request->getPost('texto')),
            'orden' => max((int) $this->request->getPost('orden'), 0),
            'activo' => (int) $this->request->getPost('activo') === 1 ? 1 : 0,
        ];

        if ($data['clave'] === '' || $data['texto'] === '') {
            return $this->encryptedAdminResponse(['message' => 'Clave y texto son obligatorios.'], 422);
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $data['clave'])) {
            return $this->encryptedAdminResponse(['message' => 'La clave solo puede contener letras, numeros, puntos, guiones y guiones bajos.'], 422);
        }

        $model = new TsConsejoModel();
        $duplicate = $model->where('clave', $data['clave'])->first();

        if (is_array($duplicate) && (int) $duplicate['id'] !== $id) {
            return $this->encryptedAdminResponse(['message' => 'Ya existe un consejo con esa clave.'], 422);
        }

        $existing = $id > 0 ? $model->find($id) : null;

        if ($id > 0 && ! is_array($existing)) {
            return $this->encryptedAdminResponse(['message' => 'Consejo no encontrado.'], 404);
        }

        $imageFile = $this->request->getFile('imagen_file');
        $hasNewImage = $imageFile !== null && $imageFile->getError() !== UPLOAD_ERR_NO_FILE;

        if ($id === 0 && ! $hasNewImage) {
            return $this->encryptedAdminResponse(['message' => 'La imagen es obligatoria al crear un consejo.'], 422);
        }

        $newImagePath = null;

        if ($hasNewImage) {
            $validation = $this->validateImage($imageFile);

            if (! $validation['ok']) {
                return $this->encryptedAdminResponse(['message' => $validation['error']], 422);
            }

            $directory = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::IMAGE_DIRECTORY);

            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                return $this->encryptedAdminResponse(['message' => 'No fue posible preparar el directorio de imagenes.'], 500);
            }

            $fileName = bin2hex(random_bytes(16)) . '.' . $validation['extension'];

            if (! $imageFile->move($directory, $fileName)) {
                return $this->encryptedAdminResponse(['message' => 'No fue posible guardar la imagen.'], 500);
            }

            $newImagePath = self::IMAGE_DIRECTORY . $fileName;
            $data['imagen'] = $newImagePath;
        }

        if ($id > 0) {
            $model->update($id, $data);

            if ($newImagePath !== null) {
                $this->deleteStoredImage((string) ($existing['imagen'] ?? ''));
            }

            $message = 'Consejo actualizado correctamente.';
        } else {
            $model->insert($data);
            $message = 'Consejo creado correctamente.';
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
        $model = new TsConsejoModel();

        if (! is_array($model->find($id))) {
            return $this->encryptedAdminResponse(['message' => 'Consejo no encontrado.'], 404);
        }

        $consejo = $model->find($id);
        $model->delete($id);
        $this->deleteStoredImage((string) ($consejo['imagen'] ?? ''));

        return $this->encryptedAdminResponse(['message' => 'Consejo eliminado correctamente.']);
    }

    private function validateImage($file): array
    {
        if (! $file->isValid() || $file->hasMoved()) {
            return ['ok' => false, 'error' => 'La imagen no es valida o ya fue procesada.'];
        }

        if ($file->getSize() > self::MAX_IMAGE_BYTES) {
            return ['ok' => false, 'error' => 'La imagen no puede superar los 5 MB.'];
        }

        $mime = strtolower((string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getTempName()));

        if (! isset(self::ALLOWED_IMAGE_MIMES[$mime]) || @getimagesize($file->getTempName()) === false) {
            return ['ok' => false, 'error' => 'Solo se aceptan imagenes JPG, PNG o WEBP validas.'];
        }

        return ['ok' => true, 'extension' => self::ALLOWED_IMAGE_MIMES[$mime]];
    }

    private function deleteStoredImage(string $relativePath): void
    {
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));

        if ($relativePath === '' || ! str_starts_with($relativePath, self::IMAGE_DIRECTORY)) {
            return;
        }

        $fullPath = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
