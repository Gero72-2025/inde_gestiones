<?php

namespace App\Modules\Etcee\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Etcee\Models\EstadosMantenimientoModel;
use CodeIgniter\HTTP\RedirectResponse;

class EstadosMantenimientoController extends AdminBaseController
{
    private EstadosMantenimientoModel $estadosModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->estadosModel = new EstadosMantenimientoModel();
    }

    public function index()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccessEtcee($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar estados ETCEE.');
        }

        $editingId = max((int) $this->request->getGet('edit'), 0);

        return $this->adminView('App\\Modules\\Etcee\\Views\\estados_mantenimiento', [
            'estados' => $this->estadosModel->listAll(),
            'editing' => $editingId > 0 ? $this->estadosModel->find($editingId) : null,
        ]);
    }

    public function save()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccessEtcee($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar estados ETCEE.');
        }

        $id = max((int) $this->request->getPost('id'), 0);
        $name = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 100);
        $key = strtoupper(trim((string) $this->request->getPost('clave')));
        $color = strtoupper(trim((string) $this->request->getPost('color')));

        if ($name === '' || ! preg_match('/^[A-Z]{1,2}$/', $key) || ! preg_match('/^#[0-9A-F]{6}$/', $color)) {
            return redirect()->back()->withInput()->with('error', 'Completa un nombre, una clave de 1 o 2 letras y un color hexadecimal valido.');
        }

        if ($this->estadosModel->nameExists($name, $id > 0 ? $id : null)) {
            return redirect()->back()->withInput()->with('error', 'Ya existe un estado con ese nombre.');
        }

        if ($this->estadosModel->keyExists($key, $id > 0 ? $id : null)) {
            return redirect()->back()->withInput()->with('error', 'Ya existe un estado con esa clave.');
        }

        $data = [
            'nombre' => $name,
            'clave' => $key,
            'color' => $color,
            'activo' => $this->request->getPost('activo') ? 1 : 0,
        ];

        try {
            if ($id > 0) {
                if ($this->estadosModel->find($id) === null) {
                    return redirect()->to('admin/etcee/estados-mantenimiento')->with('error', 'El estado solicitado no existe.');
                }
                $this->estadosModel->update($id, $data);
            } else {
                $this->estadosModel->insert($data);
            }
        } catch (\Throwable $exception) {
            return redirect()->back()->withInput()->with('error', 'No fue posible guardar el estado.');
        }

        return redirect()->to('admin/etcee/estados-mantenimiento')->with('success', 'Estado guardado correctamente.');
    }

    public function delete(int $id)
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccessEtcee($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar estados ETCEE.');
        }

        if ($this->estadosModel->find($id) === null) {
            return redirect()->to('admin/etcee/estados-mantenimiento')->with('error', 'El estado solicitado no existe.');
        }

        if ($this->estadosModel->countLinkedEvents($id) > 0) {
            return redirect()->to('admin/etcee/estados-mantenimiento')->with('error', 'No puedes eliminar un estado asociado a mantenimientos. Desactivalo para conservar el historial.');
        }

        $this->estadosModel->delete($id);

        return redirect()->to('admin/etcee/estados-mantenimiento')->with('success', 'Estado eliminado correctamente.');
    }

    private function canAccessEtcee(array $auth): bool
    {
        $permissions = array_map('strval', (array) ($auth['permissions'] ?? []));

        return $this->rbac->isSuperAdminByPermissions($permissions)
            || $this->rbac->hasPermission($permissions, 'gerencia.etcee.modulo.access')
            || $this->rbac->hasPermission($permissions, 'gerencia.etcee.dashboard.access');
    }
}