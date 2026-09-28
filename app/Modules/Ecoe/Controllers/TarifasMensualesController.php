<?php

namespace App\Modules\Ecoe\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Ecoe\Models\DistribuidoraModel;
use App\Modules\Ecoe\Models\TarifaMensualModel;
use CodeIgniter\HTTP\RedirectResponse;

class TarifasMensualesController extends AdminBaseController
{
    private const PERM = 'gerencia.ecoe.tarifa_social.tarifas.access';

    private TarifaMensualModel $tarifaMensualModel;
    private DistribuidoraModel $distribuidoraModel;

    public function initController($request, $response, $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->tarifaMensualModel = new TarifaMensualModel();
        $this->distribuidoraModel = new DistribuidoraModel();
    }

    public function index(): mixed
    {
        $auth = $this->authProfile();
        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        $permissions = (array) ($auth['permissions'] ?? []);
        $isSuperAdmin = $this->rbac->isSuperAdminByPermissions($permissions);
        if (! $isSuperAdmin && (($auth['gerencia_slug'] ?? '') !== 'ecoe'
            || ! $this->rbac->hasPermission($permissions, self::PERM))) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar tarifas mensuales ECOE.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            return match ((string) $this->request->getPost('action')) {
                'guardar' => $this->guardar(),
                'eliminar' => $this->eliminar(),
                default => redirect()->back()->with('error', 'Acción de tarifa no reconocida.'),
            };
        }

        $editId = max(0, (int) $this->request->getGet('edit'));
        $record = $editId > 0 ? $this->tarifaMensualModel->find($editId) : null;
        if ($editId > 0 && $record === null) {
            return redirect()->to('gerencias/ecoe/tarifa-social/tarifas')
                ->with('error', 'La tarifa solicitada no existe.');
        }

        $distribuidoras = $this->distribuidoraModel->listActivas();
        $filterDistributorId = max(0, (int) $this->request->getGet('distribuidora_id'));
        if ($filterDistributorId > 0 && ! in_array($filterDistributorId, array_column($distribuidoras, 'id'), false)) {
            $filterDistributorId = 0;
        }

        return $this->adminView('App\\Modules\\Ecoe\\Views\\tarifas_mensuales', [
            'records' => $this->tarifaMensualModel->listOrdenados($filterDistributorId),
            'record' => $record,
            'distribuidoras' => $distribuidoras,
            'filterDistributorId' => $filterDistributorId,
        ]);
    }

    private function guardar(): mixed
    {
        if (! $this->validate([
            'tarifa_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'distribuidora_id' => 'required|is_natural_no_zero',
            'anio' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]',
            'mes' => 'required|integer|in_list[1,2,3,4,5,6,7,8,9,10,11,12]',
            'tarifa_plena' => 'required|decimal|greater_than[0]',
            'tarifa_social' => 'required|decimal|greater_than[0]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id = max(0, (int) $this->request->getPost('tarifa_id'));
        $distribuidoraId = (int) $this->request->getPost('distribuidora_id');
        $year = (int) $this->request->getPost('anio');
        $month = (int) $this->request->getPost('mes');
        $distribuidora = $this->distribuidoraModel->find($distribuidoraId);
        if (! $distribuidora || (int) ($distribuidora['status'] ?? 0) !== 1) {
            return redirect()->back()->withInput()
                ->with('error', 'Selecciona una distribuidora activa.');
        }

        if ($this->tarifaMensualModel->existsForPeriod($distribuidoraId, $year, $month, $id)) {
            return redirect()->back()->withInput()
                ->with('error', 'Ya existe una tarifa registrada para esa distribuidora, año y mes.');
        }

        $data = [
            'distribuidora_id' => $distribuidoraId,
            'anio' => $year,
            'mes' => $month,
            'tarifa_plena' => (string) $this->request->getPost('tarifa_plena'),
            'tarifa_social' => (string) $this->request->getPost('tarifa_social'),
        ];

        if ($id > 0) {
            if ($this->tarifaMensualModel->find($id) === null) {
                return redirect()->to('gerencias/ecoe/tarifa-social/tarifas')
                    ->with('error', 'La tarifa que intentas actualizar no existe.');
            }
            $saved = $this->tarifaMensualModel->update($id, $data);
        } else {
            $saved = $this->tarifaMensualModel->insert($data) !== false;
        }

        return $saved
            ? redirect()->to('gerencias/ecoe/tarifa-social/tarifas')->with('success', 'Tarifa mensual guardada correctamente.')
            : redirect()->back()->withInput()->with('error', 'No fue posible guardar la tarifa mensual.');
    }

    private function eliminar(): mixed
    {
        $id = max(0, (int) $this->request->getPost('tarifa_id'));
        if ($id === 0 || $this->tarifaMensualModel->find($id) === null) {
            return redirect()->to('gerencias/ecoe/tarifa-social/tarifas')
                ->with('error', 'La tarifa que intentas eliminar no existe.');
        }

        return $this->tarifaMensualModel->delete($id)
            ? redirect()->to('gerencias/ecoe/tarifa-social/tarifas')->with('success', 'Tarifa mensual eliminada.')
            : redirect()->back()->with('error', 'No fue posible eliminar la tarifa mensual.');
    }
}