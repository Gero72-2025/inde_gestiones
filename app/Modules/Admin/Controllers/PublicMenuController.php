<?php

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\PublicMenuItemModel;

class PublicMenuController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\public_menu');
    }

    public function list()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->tableExists()) {
            return $this->encryptedAdminResponse([
                'items' => [],
                'message' => 'La tabla public_menu_items no existe. Ejecuta migrations.',
            ]);
        }

        $model = new PublicMenuItemModel();
        $items = $model
            ->orderBy('is_dropdown', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->encryptedAdminResponse(['items' => $items]);
    }

    public function store()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->tableExists()) {
            return $this->encryptedAdminResponse(['message' => 'La tabla public_menu_items no existe. Ejecuta migrations.'], 422);
        }

        $model = new PublicMenuItemModel();
        $payload = $this->payloadFromRequest();

        if ($payload['title'] === '') {
            return $this->encryptedAdminResponse(['message' => 'Titulo es obligatorio.'], 422);
        }

        if ((int) $payload['is_dropdown'] === 0 && $payload['route_path'] === '') {
            return $this->encryptedAdminResponse(['message' => 'La ruta es obligatoria cuando no es desplegable.'], 422);
        }

        if ($payload['parent_id'] !== null && ! $this->isValidParentId($model, (int) $payload['parent_id'])) {
            return $this->encryptedAdminResponse(['message' => 'El padre seleccionado no existe o no es desplegable.'], 422);
        }

        if (($payload['_error'] ?? '') !== '') {
            return $this->encryptedAdminResponse(['message' => $payload['_error']], 422);
        }

        unset($payload['_error']);

        $model->insert($payload);

        return $this->encryptedAdminResponse([
            'message' => 'Ruta publica creada correctamente.',
            'id' => $model->getInsertID(),
        ]);
    }

    public function update(int $id)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->tableExists()) {
            return $this->encryptedAdminResponse(['message' => 'La tabla public_menu_items no existe. Ejecuta migrations.'], 422);
        }

        $model = new PublicMenuItemModel();
        $item = $model->find($id);

        if (! is_array($item)) {
            return $this->encryptedAdminResponse(['message' => 'Registro no encontrado.'], 404);
        }

        $payload = $this->payloadFromRequest($item);

        if ($payload['title'] === '') {
            return $this->encryptedAdminResponse(['message' => 'Titulo es obligatorio.'], 422);
        }

        if ((int) $payload['is_dropdown'] === 0 && $payload['route_path'] === '') {
            return $this->encryptedAdminResponse(['message' => 'La ruta es obligatoria cuando no es desplegable.'], 422);
        }

        if ($payload['parent_id'] !== null && (int) $payload['parent_id'] === $id) {
            return $this->encryptedAdminResponse(['message' => 'Un item no puede ser padre de si mismo.'], 422);
        }

        if ($payload['parent_id'] !== null && ! $this->isValidParentId($model, (int) $payload['parent_id'])) {
            return $this->encryptedAdminResponse(['message' => 'El padre seleccionado no existe o no es desplegable.'], 422);
        }

        if (($payload['_error'] ?? '') !== '') {
            return $this->encryptedAdminResponse(['message' => $payload['_error']], 422);
        }

        unset($payload['_error']);

        $model->update($id, $payload);

        return $this->encryptedAdminResponse(['message' => 'Ruta publica actualizada.']);
    }

    public function delete(int $id)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->tableExists()) {
            return $this->encryptedAdminResponse(['message' => 'La tabla public_menu_items no existe. Ejecuta migrations.'], 422);
        }

        $model = new PublicMenuItemModel();
        $item = $model->find($id);

        if (! is_array($item)) {
            return $this->encryptedAdminResponse(['message' => 'Registro no encontrado.'], 404);
        }

        $model->delete($id);

        return $this->encryptedAdminResponse(['message' => 'Ruta publica eliminada.']);
    }

    private function payloadFromRequest(?array $existing = null): array
    {
        $isDropdown = $this->request->getPost('is_dropdown') ? 1 : 0;
        $parentId = max((int) $this->request->getPost('parent_id'), 0);

        if ($isDropdown === 1) {
            $parentId = 0;
        }

        $route = trim((string) $this->request->getPost('route_path'));
        $route = preg_replace('/\\s+/', '', $route) ?? '';
        $route = ltrim($route, '/');
        $route = preg_replace('/[^a-zA-Z0-9_\/-]/', '', $route) ?? '';

        if ($isDropdown === 1) {
            $route = '';
        }

        [$backgroundImagePath, $backgroundError] = $this->resolveBackgroundImagePath($isDropdown, $existing);

        return [
            'title' => trim(strip_tags((string) $this->request->getPost('title'))),
            'title_en' => trim(strip_tags((string) $this->request->getPost('title_en'))),
            'title_quc' => trim(strip_tags((string) $this->request->getPost('title_quc'))),
            'title_qeq' => trim(strip_tags((string) $this->request->getPost('title_qeq'))),
            'title_cak' => trim(strip_tags((string) $this->request->getPost('title_cak'))),
            'description' => trim(strip_tags((string) $this->request->getPost('description'))),
            'description_en' => trim(strip_tags((string) $this->request->getPost('description_en'))),
            'description_quc' => trim(strip_tags((string) $this->request->getPost('description_quc'))),
            'description_qeq' => trim(strip_tags((string) $this->request->getPost('description_qeq'))),
            'description_cak' => trim(strip_tags((string) $this->request->getPost('description_cak'))),
            'route_path' => $route,
            'icon_class' => trim(strip_tags((string) $this->request->getPost('icon_class'))) ?: 'bi-grid',
            'background_image_path' => $backgroundImagePath,
            'parent_id' => $parentId > 0 ? $parentId : null,
            'is_dropdown' => $isDropdown,
            'sort_order' => max((int) $this->request->getPost('sort_order'), 0),
            'is_published' => $this->request->getPost('is_published') ? 1 : 0,
            '_error' => $backgroundError,
        ];
    }

    private function resolveBackgroundImagePath(int $isDropdown, ?array $existing): array
    {
        if ($isDropdown !== 1) {
            return [null, null];
        }

        $currentPath = trim(strip_tags((string) $this->request->getPost('current_background_image_path')));

        if ($currentPath === '' && is_array($existing)) {
            $currentPath = trim((string) ($existing['background_image_path'] ?? ''));
        }

        if ($this->request->getPost('clear_background_image')) {
            $currentPath = '';
        }

        $file = $this->request->getFile('background_image_file');

        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            $ext = strtolower((string) $file->getClientExtension());
            if ($ext !== 'png') {
                return [null, 'Solo se permiten imagenes PNG para el fondo del padre.'];
            }

            $targetDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portal-nav';
            if (! is_dir($targetDir) && ! @mkdir($targetDir, 0775, true) && ! is_dir($targetDir)) {
                return [null, 'No fue posible crear el directorio de carga para fondos del portal.'];
            }

            $filename = 'nav-bg-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.png';
            $file->move($targetDir, $filename, true);

            return ['uploads/portal-nav/' . $filename, null];
        }

        return [$currentPath !== '' ? $currentPath : null, null];
    }

    private function isValidParentId(PublicMenuItemModel $model, int $id): bool
    {
        $parent = $model->find($id);

        if (! is_array($parent)) {
            return false;
        }

        return (int) ($parent['is_dropdown'] ?? 0) === 1;
    }

    private function tableExists(): bool
    {
        return db_connect()->tableExists('public_menu_items');
    }
}