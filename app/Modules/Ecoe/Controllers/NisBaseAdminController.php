<?php

namespace App\Modules\Ecoe\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Ecoe\Models\DistribuidoraModel;
use App\Modules\Ecoe\Models\NisBaseModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controlador administrativo – CRUD Base NIS (ECOE)
 *
 * Rutas (declaradas en Config/Routes.php):
 *  GET  gerencias/ecoe/nis-base              → index()
 *  POST gerencias/ecoe/nis-base              → index() (CRUD actions)
 *  GET  gerencias/ecoe/nis-base/create       → create()
 *  POST gerencias/ecoe/nis-base/store        → store()
 *  GET  gerencias/ecoe/nis-base/edit/(:num)  → edit($1)
 *  POST gerencias/ecoe/nis-base/update/(:num)→ update($1)
 *  POST gerencias/ecoe/nis-base/delete/(:num)→ delete($1)
 */
class NisBaseAdminController extends AdminBaseController
{
    private const PERM = 'gerencia.ecoe.nis_base.access';
    private const PAGE_SIZE = 25;

    private NisBaseModel $nisModel;
    private DistribuidoraModel $distribuidoraModel;

    public function initController($request, $response, $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->nisModel = new NisBaseModel();
        $this->distribuidoraModel = new DistribuidoraModel();
    }

    /**
     * Index – Listar registros NIS con filtros
     */
    public function index(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (!$this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar la base NIS.');
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
            'q'           => trim((string) $this->request->getGet('q')),
            'distribuidor' => max(0, (int) $this->request->getGet('distribuidor')),
        ];

        // Búsqueda con join a distribuidoras
        $db = db_connect();
        $query = $db->table('ecoe_nis_base as nis')
            ->select('nis.*, dist.nombre as distribuidora_nombre')
            ->join('ecoe_distribuidoras dist', 'dist.id = nis.distribuidora_id', 'left');

        if ($filters['q'] !== '') {
            $query->groupStart()
                ->like('nis.id_usuario', $filters['q'])
                ->orLike('nis.nombre_usuario', $filters['q'])
                ->orLike('nis.direccion', $filters['q'])
                ->groupEnd();
        }

        if ($filters['distribuidor'] > 0) {
            $query->where('nis.distribuidora_id', $filters['distribuidor']);
        }

        $countQuery = clone $query;
        $total = $countQuery->countAllResults();
        
        $page = max(1, (int) $this->request->getGet('page'));
        $offset = ($page - 1) * self::PAGE_SIZE;

        $records = $query
            ->orderBy('nis.id', 'DESC')
            ->limit(self::PAGE_SIZE, $offset)
            ->get()
            ->getResultArray();

        $pager = [
            'page' => $page,
            'total' => $total,
            'per_page' => self::PAGE_SIZE,
            'last_page' => ceil($total / self::PAGE_SIZE),
            'offset' => $offset,
        ];

        return $this->adminView('App\Modules\Ecoe\Views\nis_base_index', [
            'records' => $records,
            'distribuidoras' => $this->distribuidoraModel->findAll(),
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
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar la base NIS.');
        }

        return $this->adminView('App\Modules\Ecoe\Views\nis_base_form', [
            'record' => null,
            'distribuidoras' => $this->distribuidoraModel->findAll(),
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
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar la base NIS.');
        }

        $record = $this->nisModel->find($id);

        if (!$record) {
            return redirect()->to('gerencias/ecoe/nis-base')->with('error', 'Registro no encontrado.');
        }

        return $this->adminView('App\Modules\Ecoe\Views\nis_base_form', [
            'record' => $record,
            'distribuidoras' => $this->distribuidoraModel->findAll(),
        ]);
    }

    /**
     * Store – Guardar nuevo registro
     */
    public function store(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (!$this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar la base NIS.');
        }

        $validation = $this->validate([
            'id_usuario'       => 'required|max_length[50]',
            'nombre_usuario'   => 'required|max_length[180]',
            'departamento'     => 'max_length[100]',
            'municipio'        => 'max_length[100]',
            'aldea'            => 'max_length[100]',
            'direccion'        => 'max_length[255]',
            'activ_economica'  => 'max_length[100]',
            'revision'         => 'max_length[50]',
            'mes'              => 'max_length[20]',
            'consumo_kwh'      => 'numeric',
            'distribuidora_id' => 'required|integer|greater_than[0]',
        ]);

        if (!$validation) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'id_usuario'       => trim((string) $this->request->getPost('id_usuario')),
            'nombre_usuario'   => trim((string) $this->request->getPost('nombre_usuario')),
            'departamento'     => trim((string) $this->request->getPost('departamento')),
            'municipio'        => trim((string) $this->request->getPost('municipio')),
            'aldea'            => trim((string) $this->request->getPost('aldea')),
            'direccion'        => trim((string) $this->request->getPost('direccion')),
            'activ_economica'  => trim((string) $this->request->getPost('activ_economica')),
            'revision'         => trim((string) $this->request->getPost('revision')),
            'mes'              => trim((string) $this->request->getPost('mes')),
            'consumo_kwh'      => (float) $this->request->getPost('consumo_kwh'),
            'distribuidora_id' => (int) $this->request->getPost('distribuidora_id'),
        ];

        // Verificar si ya existe con el mismo id_usuario
        $existing = $this->nisModel
            ->where('id_usuario', $data['id_usuario'])
            ->where('distribuidora_id', $data['distribuidora_id'])
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Ya existe un usuario NIS con este ID en la distribuidora seleccionada.');
        }

        if ($this->nisModel->insert($data)) {
            return redirect()->to('gerencias/ecoe/nis-base')->with('success', 'Registro creado correctamente.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'No fue posible crear el registro.');
    }

    /**
     * Update – Actualizar registro existente
     */
    public function update(int $id): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (!$this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar la base NIS.');
        }

        $record = $this->nisModel->find($id);

        if (!$record) {
            return redirect()->to('gerencias/ecoe/nis-base')->with('error', 'Registro no encontrado.');
        }

        $validation = $this->validate([
            'id_usuario'       => 'required|max_length[50]',
            'nombre_usuario'   => 'required|max_length[180]',
            'departamento'     => 'max_length[100]',
            'municipio'        => 'max_length[100]',
            'aldea'            => 'max_length[100]',
            'direccion'        => 'max_length[255]',
            'activ_economica'  => 'max_length[100]',
            'revision'         => 'max_length[50]',
            'mes'              => 'max_length[20]',
            'consumo_kwh'      => 'numeric',
            'distribuidora_id' => 'required|integer|greater_than[0]',
        ]);

        if (!$validation) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'id_usuario'       => trim((string) $this->request->getPost('id_usuario')),
            'nombre_usuario'   => trim((string) $this->request->getPost('nombre_usuario')),
            'departamento'     => trim((string) $this->request->getPost('departamento')),
            'municipio'        => trim((string) $this->request->getPost('municipio')),
            'aldea'            => trim((string) $this->request->getPost('aldea')),
            'direccion'        => trim((string) $this->request->getPost('direccion')),
            'activ_economica'  => trim((string) $this->request->getPost('activ_economica')),
            'revision'         => trim((string) $this->request->getPost('revision')),
            'mes'              => trim((string) $this->request->getPost('mes')),
            'consumo_kwh'      => (float) $this->request->getPost('consumo_kwh'),
            'distribuidora_id' => (int) $this->request->getPost('distribuidora_id'),
        ];

        if ($this->nisModel->update($id, $data)) {
            return redirect()->to('gerencias/ecoe/nis-base')->with('success', 'Registro actualizado correctamente.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'No fue posible actualizar el registro.');
    }

    /**
     * Delete – Eliminar registro
     */
    private function deleteAction(): mixed
    {
        $recordId = max(0, (int) $this->request->getPost('id'));

        if ($recordId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'ID no válido.'], 422);
        }

        $record = $this->nisModel->find($recordId);

        if (!$record) {
            return $this->encryptedAdminResponse(['message' => 'Registro no encontrado.'], 404);
        }

        $this->nisModel->delete($recordId);

        return $this->encryptedAdminResponse(['message' => 'Registro eliminado correctamente.']);
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
