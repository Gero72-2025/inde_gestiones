<?php

namespace App\Modules\Ecoe\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Ecoe\Models\DistribuidoraModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controlador administrativo – CRUD Distribuidoras (ECOE)
 *
 * Rutas (declaradas en Config/Routes.php):
 *  GET  gerencias/ecoe/distribuidoras              → index()
 *  POST gerencias/ecoe/distribuidoras              → index() (CRUD actions)
 *  GET  gerencias/ecoe/distribuidoras/create       → create()
 *  POST gerencias/ecoe/distribuidoras/store        → store()
 *  GET  gerencias/ecoe/distribuidoras/edit/(:num)  → edit($1)
 *  POST gerencias/ecoe/distribuidoras/update/(:num)→ update($1)
 *  POST gerencias/ecoe/distribuidoras/delete/(:num)→ delete($1)
 */
class DistribuidoraAdminController extends AdminBaseController
{
    private const PERM = 'gerencia.ecoe.distribuidoras.access';
    private const PAGE_SIZE = 25;

    private DistribuidoraModel $distribuidoraModel;

    public function initController($request, $response, $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->distribuidoraModel = new DistribuidoraModel();
    }

    /**
     * Index – Listar distribuidoras con filtros
     */
    public function index(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (!$this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar distribuidoras.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'delete' => $this->deleteAction(),
                default  => $this->encryptedAdminResponse(['message' => 'Acción no reconocida.'], 400),
            };
        }

        // Filtros
        $filters = [
            'q' => trim((string) $this->request->getGet('q')),
        ];

        // Búsqueda
        $query = $this->distribuidoraModel;

        if ($filters['q'] !== '') {
            $query->like('nombre', $filters['q']);
        }

        $total = $query->countAllResults(false);
        $page = max(1, (int) $this->request->getGet('page'));
        $offset = ($page - 1) * self::PAGE_SIZE;

        $records = $query
            ->orderBy('nombre', 'ASC')
            ->limit(self::PAGE_SIZE, $offset)
            ->findAll();

        $pager = [
            'page' => $page,
            'total' => $total,
            'per_page' => self::PAGE_SIZE,
            'last_page' => ceil($total / self::PAGE_SIZE),
            'offset' => $offset,
        ];

        return $this->adminView('App\Modules\Ecoe\Views\distribuidora_index', [
            'records' => $records,
            'filters' => $filters,
            'pager' => $pager,
        ]);
    }

    /**
     * Create – Mostrar formulario de creación
     */
    public function create(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (!$this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar distribuidoras.');
        }

        return $this->adminView('App\Modules\Ecoe\Views\distribuidora_form', [
            'record' => null,
        ]);
    }

    /**
     * Edit – Mostrar formulario de edición
     */
    public function edit(int $id): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (!$this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar distribuidoras.');
        }

        $record = $this->distribuidoraModel->find($id);

        if (!$record) {
            return redirect()->to('gerencias/ecoe/distribuidoras')->with('error', 'Distribuidora no encontrada.');
        }

        return $this->adminView('App\Modules\Ecoe\Views\distribuidora_form', [
            'record' => $record,
        ]);
    }

    /**
     * Store – Guardar nueva distribuidora
     */
    public function store(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (!$this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar distribuidoras.');
        }

        $validation = $this->validate([
            'nombre' => 'required|max_length[120]|is_unique[ecoe_distribuidoras.nombre]',
            'status' => 'required|in_list[0,1]',
        ]);

        if (!$validation) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nombre' => trim((string) $this->request->getPost('nombre')),
            'status' => (int) $this->request->getPost('status'),
        ];

        if ($this->distribuidoraModel->insert($data)) {
            return redirect()->to('gerencias/ecoe/distribuidoras')->with('success', 'Distribuidora creada correctamente.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'No fue posible crear la distribuidora.');
    }

    /**
     * Update – Actualizar distribuidora
     */
    public function update(int $id): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (!$this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar distribuidoras.');
        }

        $record = $this->distribuidoraModel->find($id);

        if (!$record) {
            return redirect()->to('gerencias/ecoe/distribuidoras')->with('error', 'Distribuidora no encontrada.');
        }

        $validation = $this->validate([
            'nombre' => 'required|max_length[120]|is_unique[ecoe_distribuidoras.nombre,id,' . $id . ']',
            'status' => 'required|in_list[0,1]',
        ]);

        if (!$validation) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nombre' => trim((string) $this->request->getPost('nombre')),
            'status' => (int) $this->request->getPost('status'),
        ];

        if ($this->distribuidoraModel->update($id, $data)) {
            return redirect()->to('gerencias/ecoe/distribuidoras')->with('success', 'Distribuidora actualizada correctamente.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'No fue posible actualizar la distribuidora.');
    }

    /**
     * Delete – Eliminar distribuidora
     */
    private function deleteAction(): mixed
    {
        $recordId = max(0, (int) $this->request->getPost('id'));

        if ($recordId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'ID no válido.'], 422);
        }

        $record = $this->distribuidoraModel->find($recordId);

        if (!$record) {
            return $this->encryptedAdminResponse(['message' => 'Distribuidora no encontrada.'], 404);
        }

        // Verificar si tiene registros NIS asociados
        $db = db_connect();
        $count = $db->table('ecoe_nis_base')
            ->where('distribuidora_id', $recordId)
            ->countAllResults();

        if ($count > 0) {
            return $this->encryptedAdminResponse(['message' => 'No puedes eliminar una distribuidora que tiene registros NIS asociados. Elimina los registros primero.'], 422);
        }

        $this->distribuidoraModel->delete($recordId);

        return $this->encryptedAdminResponse(['message' => 'Distribuidora eliminada correctamente.']);
    }

    /**
     * Verificar permisos de acceso
     */
    private function canAccess(array $auth): bool
    {
        $isSuperAdmin = $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []));

        if ($isSuperAdmin) {
            return true;
        }

        return ((string) ($auth['gerencia_slug'] ?? '')) === 'ecoe'
            && $this->rbac->hasPermission((array) ($auth['permissions'] ?? []), self::PERM);
    }
}
