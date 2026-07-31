<?php

namespace App\Modules\Etcee\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Etcee\Models\CortesModel;
use CodeIgniter\HTTP\RedirectResponse;
use InvalidArgumentException;

class CortesController extends AdminBaseController
{
    private CortesModel $cortesModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->cortesModel = new CortesModel();
    }

    public function index()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccessEtcee($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar el calendario ETCEE.');
        }

        return $this->adminView('App\\Modules\\Etcee\\Views\\cortes_admin', [
            'departamentos' => $this->cortesModel->catalogoDepartamentos(),
            'municipios' => $this->cortesModel->catalogoMunicipios(),
        ]);
    }

    public function catalogos()
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        return $this->encryptedAdminResponse([
            'departamentos' => $this->cortesModel->catalogoDepartamentos(),
            'municipios' => $this->cortesModel->catalogoMunicipios(),
        ]);
    }

    public function list()
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        try {
            $payload = $this->decryptAjaxEnvelopeFromRequest(true);
        } catch (InvalidArgumentException $exception) {
            return $this->encryptedAdminResponse(['message' => $exception->getMessage()], 400);
        }

        $departamentoId = (int) ($payload['departamento_id'] ?? 0);
        $municipioId = (int) ($payload['municipio_id'] ?? 0);

        return $this->encryptedAdminResponse([
            'events' => $this->cortesModel->listCalendar($departamentoId > 0 ? $departamentoId : null, $municipioId > 0 ? $municipioId : null),
        ]);
    }

    public function store()
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        try {
            $payload = $this->decryptAjaxEnvelopeFromRequest(true);
        } catch (InvalidArgumentException $exception) {
            return $this->encryptedAdminResponse(['message' => $exception->getMessage()], 400);
        }

        $validation = $this->validatePayload($payload);

        if ($validation !== null) {
            return $validation;
        }

        $locations = $this->cortesModel->resolveLocations((array) ($payload['department_ids'] ?? []), (array) ($payload['municipality_ids'] ?? []));

        if ($locations === []) {
            return $this->encryptedAdminResponse(['message' => 'Debes seleccionar al menos una ubicacion valida.'], 422);
        }

        try {
            $eventId = $this->cortesModel->saveEvent(null, [
                'titulo' => trim((string) $payload['titulo']),
                'descripcion' => trim((string) ($payload['descripcion'] ?? '')),
                'fecha_inicio' => (string) $payload['fecha_inicio'],
                'fecha_fin' => (string) $payload['fecha_fin'],
                'estado' => $this->normalizeEstado((string) ($payload['estado'] ?? 'programado')),
                'color' => trim((string) ($payload['color'] ?? '#1f6feb')) ?: '#1f6feb',
                'created_by' => (int) (($this->session->get('auth')['user_id'] ?? 0)),
                'updated_by' => (int) (($this->session->get('auth')['user_id'] ?? 0)),
            ], $locations);
        } catch (\Throwable $exception) {
            return $this->encryptedAdminResponse(['message' => 'No fue posible crear el corte.'], 500);
        }

        return $this->encryptedAdminResponse([
            'message' => 'Corte creado correctamente.',
            'event' => $this->cortesModel->findEvent($eventId),
        ]);
    }

    public function update(int $id)
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        if ($this->cortesModel->find($id) === null) {
            return $this->encryptedAdminResponse(['message' => 'El corte solicitado no existe.'], 404);
        }

        try {
            $payload = $this->decryptAjaxEnvelopeFromRequest(true);
        } catch (InvalidArgumentException $exception) {
            return $this->encryptedAdminResponse(['message' => $exception->getMessage()], 400);
        }

        $validation = $this->validatePayload($payload);

        if ($validation !== null) {
            return $validation;
        }

        $locations = $this->cortesModel->resolveLocations((array) ($payload['department_ids'] ?? []), (array) ($payload['municipality_ids'] ?? []));

        if ($locations === []) {
            return $this->encryptedAdminResponse(['message' => 'Debes seleccionar al menos una ubicacion valida.'], 422);
        }

        try {
            $this->cortesModel->saveEvent($id, [
                'titulo' => trim((string) $payload['titulo']),
                'descripcion' => trim((string) ($payload['descripcion'] ?? '')),
                'fecha_inicio' => (string) $payload['fecha_inicio'],
                'fecha_fin' => (string) $payload['fecha_fin'],
                'estado' => $this->normalizeEstado((string) ($payload['estado'] ?? 'programado')),
                'color' => trim((string) ($payload['color'] ?? '#1f6feb')) ?: '#1f6feb',
                'updated_by' => (int) (($this->session->get('auth')['user_id'] ?? 0)),
            ], $locations);
        } catch (\Throwable $exception) {
            return $this->encryptedAdminResponse(['message' => 'No fue posible actualizar el corte.'], 500);
        }

        return $this->encryptedAdminResponse([
            'message' => 'Corte actualizado correctamente.',
            'event' => $this->cortesModel->findEvent($id),
        ]);
    }

    public function delete(int $id)
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        if ($this->cortesModel->find($id) === null) {
            return $this->encryptedAdminResponse(['message' => 'El corte solicitado no existe.'], 404);
        }

        try {
            $this->cortesModel->deleteEvent($id);
        } catch (\Throwable $exception) {
            return $this->encryptedAdminResponse(['message' => 'No fue posible eliminar el corte.'], 500);
        }

        return $this->encryptedAdminResponse(['message' => 'Corte eliminado correctamente.']);
    }

    private function requireApiAuth()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->canAccessEtcee($auth)) {
            return $this->encryptedAdminResponse(['message' => 'No tienes permisos para gestionar cortes ETCEE.'], 403);
        }

        return null;
    }

    private function canAccessEtcee(array $auth): bool
    {
        $permissions = array_map('strval', (array) ($auth['permissions'] ?? []));

        return $this->rbac->isSuperAdminByPermissions($permissions)
            || $this->rbac->hasPermission($permissions, 'gerencia.etcee.modulo.access')
            || $this->rbac->hasPermission($permissions, 'gerencia.etcee.dashboard.access');
    }

    private function validatePayload(array $payload)
    {
        $titulo = trim((string) ($payload['titulo'] ?? ''));
        $fechaInicio = trim((string) ($payload['fecha_inicio'] ?? ''));
        $fechaFin = trim((string) ($payload['fecha_fin'] ?? ''));

        if ($titulo === '' || $fechaInicio === '' || $fechaFin === '') {
            return $this->encryptedAdminResponse(['message' => 'Titulo, fecha de inicio y fecha de fin son obligatorios.'], 422);
        }

        $startTs = strtotime($fechaInicio);
        $endTs = strtotime($fechaFin);

        if ($startTs === false || $endTs === false) {
            return $this->encryptedAdminResponse(['message' => 'Formato de fecha invalido.'], 422);
        }

        if ($endTs <= $startTs) {
            return $this->encryptedAdminResponse(['message' => 'La fecha fin debe ser mayor a la fecha inicio.'], 422);
        }

        return null;
    }

    private function normalizeEstado(string $estado): string
    {
        $estado = strtolower(trim($estado));
        $allowed = ['programado', 'activo', 'finalizado', 'cancelado'];

        return in_array($estado, $allowed, true) ? $estado : 'programado';
    }
}
