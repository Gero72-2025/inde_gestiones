<?php

namespace App\Modules\Ecoe\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Ecoe\Models\EemListadoModel;
use App\Modules\Ecoe\Models\FormularioCampoModel;
use App\Modules\Ecoe\Models\FormularioModel;
use App\Modules\Ecoe\Services\FormularioEngineService;
use CodeIgniter\HTTP\RedirectResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

class EemController extends AdminBaseController
{
    private const PERM = 'gerencia.ecoe.eem.access';

    private \CodeIgniter\Database\BaseConnection $db;
    private FormularioModel $formularioModel;
    private FormularioCampoModel $campoModel;
    private EemListadoModel $eemModel;
    private FormularioEngineService $engine;

    public function initController($request, $response, $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->db = db_connect();
        $this->formularioModel = new FormularioModel();
        $this->campoModel = new FormularioCampoModel();
        $this->eemModel = new EemListadoModel();
        $this->engine = new FormularioEngineService();
    }

    public function index(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar EEM ECOE.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'detalle'        => $this->detalle(),
                'save_empresa'   => $this->saveEmpresa(),
                'delete_empresa' => $this->deleteEmpresa(),
                default          => $this->encryptedAdminResponse(['message' => 'Acción no reconocida.'], 400),
            };
        }

        return $this->adminView('App\Modules\Ecoe\Views\eem_admin', [
            'records' => $this->listRecords(),
            'formularios' => $this->formularioModel->listActivosByModulo('eem'),
            'empresas' => $this->eemModel->listActivas(),
            'empresasAdmin' => $this->eemModel->listAll(),
        ]);
    }

    public function submitNuevaConexion(): mixed
    {
        return $this->handleSubmission('EEM1', 'Nueva Conexión', 'La constancia confirma la recepción de tu solicitud de nueva conexión.');
    }

    public function submitGestionarExpediente(): mixed
    {
        return $this->handleSubmission('EEM2', 'Gestionar Expediente', 'La constancia confirma la recepción de tu expediente.');
    }

    public function submitCapacitacionTecnica(): mixed
    {
        return $this->handleSubmission('EEM3', 'Capacitación Técnica', 'La constancia confirma la recepción de tu solicitud de capacitación.');
    }

    public function consultarEstado(): mixed
    {
        $term = trim((string) ($this->request->getGet('term') ?? $this->request->getPost('term') ?? ''));

        if ($term === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Debes escribir un código de referencia o DPI.',
            ]);
        }

        $records = [];

        foreach (['EEM1', 'EEM2', 'EEM3'] as $codigo) {
            $formulario = $this->formularioModel->findByCodigo($codigo);

            if (! $formulario) {
                continue;
            }

            $tableName = $this->engine->dynamicTableName((string) $formulario['slug']);

            if (! $this->db->tableExists($tableName)) {
                continue;
            }

            $row = $this->db->table($tableName)
                ->groupStart()
                ->where('codigo_referencia', $term)
                ->orWhere('dpi', $term)
                ->groupEnd()
                ->orderBy('created_at', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            if ($row) {
                $row['formulario_codigo'] = $formulario['codigo'];
                $row['formulario_nombre'] = $formulario['nombre'];
                $records[] = $row;
            }
        }

        return $this->response->setJSON([
            'ok' => true,
            'records' => $records,
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

    private function detalle(): mixed
    {
        $codigo = strtoupper(trim((string) $this->request->getPost('formulario_codigo')));
        $registroId = max(0, (int) $this->request->getPost('registro_id'));

        $formulario = $this->formularioModel->findByCodigo($codigo);

        if (! $formulario) {
            return $this->encryptedAdminResponse(['message' => 'Formulario no encontrado.'], 404);
        }

        $registro = $this->findSubmissionById((string) $formulario['slug'], $registroId);

        if (! $registro) {
            return $this->encryptedAdminResponse(['message' => 'Registro no encontrado.'], 404);
        }

        return $this->encryptedAdminResponse([
            'formulario' => $formulario,
            'campos' => $this->campoModel->listByFormulario((int) $formulario['id']),
            'registro' => $registro,
        ]);
    }

    private function saveEmpresa(): mixed
    {
        $empresaId = max(0, (int) $this->request->getPost('empresa_id'));
        $nombre = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 180);
        $descripcion = mb_substr(trim((string) $this->request->getPost('descripcion')), 0, 2000);
        $estado = ((int) $this->request->getPost('estado')) === 1 ? 1 : 0;

        if ($nombre === '') {
            return $this->encryptedAdminResponse(['message' => 'El nombre de la empresa es obligatorio.'], 422);
        }

        $duplicate = $this->eemModel
            ->where('nombre', $nombre)
            ->where('id !=', $empresaId)
            ->first();

        if ($duplicate) {
            return $this->encryptedAdminResponse(['message' => 'Ya existe una empresa con ese nombre.'], 422);
        }

        $payload = [
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'estado' => $estado,
        ];

        if ($empresaId > 0) {
            $empresa = $this->eemModel->find($empresaId);

            if (! $empresa) {
                return $this->encryptedAdminResponse(['message' => 'Empresa no encontrada.'], 404);
            }

            $this->eemModel->update($empresaId, $payload);

            return $this->encryptedAdminResponse(['message' => 'Empresa actualizada correctamente.']);
        }

        $newId = (int) $this->eemModel->insert($payload, true);

        if ($newId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'No fue posible crear la empresa.'], 500);
        }

        return $this->encryptedAdminResponse([
            'message' => 'Empresa creada correctamente.',
            'empresa_id' => $newId,
        ]);
    }

    private function deleteEmpresa(): mixed
    {
        $empresaId = max(0, (int) $this->request->getPost('empresa_id'));

        if ($empresaId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'Empresa no válida.'], 422);
        }

        $empresa = $this->eemModel->find($empresaId);

        if (! $empresa) {
            return $this->encryptedAdminResponse(['message' => 'Empresa no encontrada.'], 404);
        }

        $this->eemModel->delete($empresaId);

        return $this->encryptedAdminResponse(['message' => 'Empresa eliminada correctamente.']);
    }

    private function handleSubmission(string $codigo, string $titulo, string $fallbackInstructions): mixed
    {
        $formulario = $this->formularioModel->findByCodigo($codigo);

        if (! $formulario || (int) ($formulario['estado'] ?? 0) !== 1) {
            return $this->response->setStatusCode(404)->setBody('Formulario no disponible.');
        }

        $fields = $this->campoModel->getActiveVisibleFields((int) $formulario['id']);
        $data = [];
        $payload = [];

        $empresaId = max(0, (int) $this->request->getPost('empresa_electrica_id'));
        $empresa = $empresaId > 0 ? $this->eemModel->find($empresaId) : null;

        if ($empresaId > 0 && ! $empresa) {
            return $this->response->setStatusCode(422)->setBody('Debes seleccionar una empresa eléctrica válida.');
        }

        foreach ($fields as $field) {
            $slug = $this->engine->normalizeSlug((string) ($field['slug'] ?? ''));

            if ($slug === '') {
                continue;
            }

            if ((string) ($field['tipo'] ?? '') === 'file') {
                $uploadedFile = $this->request->getFile($slug);

                if ($uploadedFile && $uploadedFile->getError() !== UPLOAD_ERR_NO_FILE) {
                    $data[$slug] = $this->engine->storeUploadedFile($uploadedFile, (string) $formulario['slug'], $field);
                } elseif ((int) ($field['obligatorio'] ?? 0) === 1) {
                    return $this->response->setStatusCode(422)->setBody('Debes adjuntar el archivo requerido para continuar.');
                } else {
                    $data[$slug] = null;
                }

                $payload[$slug] = $data[$slug] ?? null;
                continue;
            }

            $value = trim((string) $this->request->getPost($slug));

            if ($slug === 'empresa_nombre' && $empresa) {
                $value = (string) $empresa['nombre'];
            }

            if ($value === '' && (int) ($field['obligatorio'] ?? 0) === 1) {
                return $this->response->setStatusCode(422)->setBody('Completa el campo ' . (string) ($field['etiqueta'] ?? $slug) . '.');
            }

            $data[$slug] = $value !== '' ? $value : null;
            $payload[$slug] = $data[$slug];
        }

        $tableName = $this->engine->dynamicTableName((string) $formulario['slug']);

        $insertPayload = array_merge($data, [
            'formulario_id' => (int) $formulario['id'],
            'empresa_electrica_id' => $empresaId > 0 ? $empresaId : null,
            'codigo_referencia' => null,
            'estado_tramite' => 'recibido',
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->table($tableName)->insert($insertPayload);
        $registroId = (int) $this->db->insertID();

        if ($registroId <= 0) {
            return $this->response->setStatusCode(500)->setBody('No fue posible registrar la solicitud.');
        }

        $codigoReferencia = $this->engine->generateReferenceCode($payload, $registroId, $codigo);

        $this->db->table($tableName)
            ->where('id', $registroId)
            ->update([
                'codigo_referencia' => $codigoReferencia,
                'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

        $registro = $this->findSubmissionById((string) $formulario['slug'], $registroId) ?? array_merge($insertPayload, [
            'id' => $registroId,
            'codigo_referencia' => $codigoReferencia,
        ]);

        $registro['codigo_referencia'] = $codigoReferencia;

        return $this->streamPdfResponse(
            $this->engine->renderPdfHtml($formulario, $fields, $registro, $titulo, $fallbackInstructions),
            'eem_' . $codigoReferencia . '.pdf'
        );
    }

    private function listRecords(): array
    {
        $records = [];

        foreach (['EEM1', 'EEM2', 'EEM3'] as $codigo) {
            $formulario = $this->formularioModel->findByCodigo($codigo);

            if (! $formulario) {
                continue;
            }

            $tableName = $this->engine->dynamicTableName((string) $formulario['slug']);

            if (! $this->db->tableExists($tableName)) {
                continue;
            }

            $rows = $this->db->table($tableName)
                ->orderBy('created_at', 'DESC')
                ->limit(50)
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $row['formulario_codigo'] = $formulario['codigo'];
                $row['formulario_nombre'] = $formulario['nombre'];
                $records[] = $row;
            }
        }

        usort($records, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return array_slice($records, 0, 100);
    }

    private function findSubmissionById(string $slug, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $tableName = $this->engine->dynamicTableName($slug);

        if (! $this->db->tableExists($tableName)) {
            return null;
        }

        $row = $this->db->table($tableName)->where('id', $id)->get()->getRowArray();

        return $row ?: null;
    }

    private function streamPdfResponse(string $html, string $fileName): mixed
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($dompdf->output());
    }
}