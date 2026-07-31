<?php

namespace App\Modules\Ecoe\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Ecoe\Models\FormularioCampoModel;
use App\Modules\Ecoe\Models\FormularioModel;
use App\Modules\Ecoe\Services\FormularioEngineService;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RedirectResponse;
use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class GuController extends AdminBaseController
{
    private const PERM = 'gerencia.ecoe.gu.access';
    private const GU1_TEMPLATE_RELATIVE_PATH = 'uploads/ecoe/plantillas/gu1/plantilla_gu1.docx';
    private const GU1_CONTINUACION_RELATIVE_DIR = 'uploads/ecoe/formularios/gu1_continuacion/';

    private \CodeIgniter\Database\BaseConnection $db;
    private FormularioModel $formularioModel;
    private FormularioCampoModel $campoModel;
    private FormularioEngineService $engine;

    public function initController($request, $response, $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->db = db_connect();
        $this->formularioModel = new FormularioModel();
        $this->campoModel = new FormularioCampoModel();
        $this->engine = new FormularioEngineService();
    }

    public function index(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar Grandes Usuarios ECOE.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'detalle' => $this->detalle(),
                default   => $this->encryptedAdminResponse(['message' => 'Acción no reconocida.'], 400),
            };
        }

        return $this->adminView('App\Modules\Ecoe\Views\gu_admin', [
            'records' => $this->listRecords(),
            'formularios' => $this->formularioModel->listActivosByModulo('gu'),
        ]);
    }

    public function submitOferta(): mixed
    {
        return $this->handleSubmission('GU1', 'Oferta de Suministro', 'Su oferta fue recibida correctamente.');
    }

    public function submitQuejas(): mixed
    {
        return $this->handleSubmission('GU2', 'Quejas / Comentarios', 'Su comentario fue recibido correctamente.');
    }

    public function downloadGu1Template(): mixed
    {
        $templatePath = WRITEPATH . self::GU1_TEMPLATE_RELATIVE_PATH;

        if (! is_file($templatePath)) {
            return $this->response
                ->setStatusCode(404)
                ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
                ->setBody(
                    "No se encontro la plantilla de Word para GU1.\n" .
                    "Subela en: writable/" . self::GU1_TEMPLATE_RELATIVE_PATH
                );
        }

        return $this->response
            ->download($templatePath, null)
            ->setFileName('Plantilla_GU1.docx');
    }

    public function validarOfertaReferencia(): ResponseInterface
    {
        $codigoReferencia = strtoupper(trim((string) $this->request->getPost('codigo_referencia')));

        if ($codigoReferencia === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Debes ingresar el codigo de referencia.',
                'csrf' => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        $registro = $this->findSubmissionByReference('GU1', $codigoReferencia);

        if (! $registro) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => true,
                'found' => false,
                'message' => 'No se encontro la referencia ingresada para GU1.',
                'csrf' => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'found' => true,
            'message' => 'Referencia encontrada. Ya puedes adjuntar la plantilla completa.',
            'data' => [
                'codigo_referencia' => (string) ($registro['codigo_referencia'] ?? $codigoReferencia),
                'estado_tramite' => (string) ($registro['estado_tramite'] ?? 'recibido'),
            ],
            'csrf' => [
                'name' => csrf_token(),
                'hash' => csrf_hash(),
            ],
        ]);
    }

    public function continuarOferta(): ResponseInterface
    {
        $codigoReferencia = strtoupper(trim((string) $this->request->getPost('codigo_referencia')));

        if ($codigoReferencia === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Debes ingresar el codigo de referencia para continuar.',
                'csrf' => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        $formulario = $this->formularioModel->findByCodigo('GU1');
        if (! $formulario) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'El formulario GU1 no esta disponible.',
                'csrf' => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        $registro = $this->findSubmissionByReference('GU1', $codigoReferencia);
        if (! $registro) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'No se encontro la referencia para GU1.',
                'csrf' => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        $uploadedFile = $this->request->getFile('archivo_completado_gu1');
        if (! $uploadedFile || $uploadedFile->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Adjunta el archivo completado para continuar.',
                'csrf' => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        $validation = $this->validateContinuationFile($uploadedFile);
        if (! $validation['ok']) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => (string) ($validation['error'] ?? 'El archivo no es valido.'),
                'csrf' => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        try {
            $storedPath = $this->storeContinuationFile($uploadedFile, $codigoReferencia);
        } catch (RuntimeException $exception) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => $exception->getMessage(),
                'csrf' => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        }

        $payload = json_decode((string) ($registro['payload_json'] ?? ''), true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $payload['gu1_continuacion'] = [
            'archivo_path' => $storedPath,
            'archivo_nombre_original' => (string) $uploadedFile->getClientName(),
            'fecha_carga' => date('Y-m-d H:i:s'),
        ];

        $tableName = $this->engine->dynamicTableName((string) $formulario['slug']);

        $this->db->table($tableName)
            ->where('id', (int) ($registro['id'] ?? 0))
            ->update([
                'estado_tramite' => 'continuacion_recibida',
                'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Archivo de continuacion recibido correctamente para la referencia ' . $codigoReferencia . '.',
            'data' => [
                'codigo_referencia' => $codigoReferencia,
                'archivo_path' => $storedPath,
            ],
            'csrf' => [
                'name' => csrf_token(),
                'hash' => csrf_hash(),
            ],
        ]);
    }

    public function descargarContinuacion(string $codigoReferencia): mixed
    {
        $auth = $this->authProfile();
        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para descargar archivos de Grandes Usuarios ECOE.');
        }

        $codigoReferencia = strtoupper(trim($codigoReferencia));
        $registro = $this->findSubmissionByReference('GU1', $codigoReferencia);
        if (! $registro) {
            return redirect()->to('gerencias/ecoe/grandes-usuarios')->with('error', 'No se encontro la referencia solicitada.');
        }

        $payload = json_decode((string) ($registro['payload_json'] ?? ''), true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $continuacion = (array) ($payload['gu1_continuacion'] ?? []);
        $relativePath = trim((string) ($continuacion['archivo_path'] ?? ''));

        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return redirect()->to('gerencias/ecoe/grandes-usuarios')->with('error', 'No hay archivo de continuacion disponible para esta referencia.');
        }

        $absolutePath = WRITEPATH . ltrim(str_replace('\\', '/', $relativePath), '/');
        $realPath = realpath($absolutePath);
        $realWritable = realpath(WRITEPATH);

        if ($realPath === false || $realWritable === false || ! str_starts_with($realPath, $realWritable) || ! is_file($realPath)) {
            return redirect()->to('gerencias/ecoe/grandes-usuarios')->with('error', 'El archivo de continuacion no existe en el servidor.');
        }

        $downloadName = trim((string) ($continuacion['archivo_nombre_original'] ?? ''));
        if ($downloadName === '') {
            $downloadName = basename($realPath);
        }

        return $this->response->download($realPath, null)->setFileName($downloadName);
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

    private function handleSubmission(string $codigo, string $titulo, string $fallbackInstructions): mixed
    {
        $formulario = $this->formularioModel->findByCodigo($codigo);

        if (! $formulario || (int) ($formulario['estado'] ?? 0) !== 1) {
            return $this->response->setStatusCode(404)->setBody('Formulario no disponible.');
        }

        $fields = $this->campoModel->getActiveVisibleFields((int) $formulario['id']);
        $data = [];
        $payload = [];

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

            if ($value === '' && (int) ($field['obligatorio'] ?? 0) === 1) {
                return $this->response->setStatusCode(422)->setBody('Completa el campo ' . (string) ($field['etiqueta'] ?? $slug) . '.');
            }

            $data[$slug] = $value !== '' ? $value : null;
            $payload[$slug] = $data[$slug];
        }

        $tableName = $this->engine->dynamicTableName((string) $formulario['slug']);

        $insertPayload = array_merge($data, [
            'formulario_id' => (int) $formulario['id'],
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
            'gu_' . $codigoReferencia . '.pdf'
        );
    }

    private function listRecords(): array
    {
        $records = [];

        foreach (['GU1', 'GU2'] as $codigo) {
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
                $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
                if (! is_array($payload)) {
                    $payload = [];
                }

                $continuacion = (array) ($payload['gu1_continuacion'] ?? []);
                $row['continuacion_archivo_path'] = (string) ($continuacion['archivo_path'] ?? '');
                $row['continuacion_archivo_nombre'] = (string) ($continuacion['archivo_nombre_original'] ?? '');
                $row['continuacion_fecha_carga'] = (string) ($continuacion['fecha_carga'] ?? '');
                $row['tiene_continuacion'] = $row['continuacion_archivo_path'] !== '';

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

    private function findSubmissionByReference(string $codigoFormulario, string $codigoReferencia): ?array
    {
        if ($codigoReferencia === '') {
            return null;
        }

        $formulario = $this->formularioModel->findByCodigo($codigoFormulario);
        if (! $formulario) {
            return null;
        }

        $tableName = $this->engine->dynamicTableName((string) $formulario['slug']);
        if (! $this->db->tableExists($tableName)) {
            return null;
        }

        $row = $this->db->table($tableName)
            ->where('codigo_referencia', $codigoReferencia)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * @return array{ok: bool, error?: string, ext?: string}
     */
    private function validateContinuationFile(UploadedFile $file): array
    {
        if (! $file->isValid() || $file->hasMoved()) {
            return ['ok' => false, 'error' => 'El archivo no es valido.'];
        }

        $allowedExts = ['doc', 'docx', 'pdf'];
        $allowedMimes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/pdf',
        ];

        $ext = strtolower((string) $file->getClientExtension());
        if (! in_array($ext, $allowedExts, true)) {
            return ['ok' => false, 'error' => 'Formato no permitido. Usa DOC, DOCX o PDF.'];
        }

        $mime = strtolower((string) $file->getClientMimeType());
        if ($mime !== '' && ! in_array($mime, $allowedMimes, true)) {
            return ['ok' => false, 'error' => 'Tipo de archivo no permitido.'];
        }

        if ((int) $file->getSize() > 15 * 1024 * 1024) {
            return ['ok' => false, 'error' => 'El archivo excede el limite de 15MB.'];
        }

        return ['ok' => true, 'ext' => $ext];
    }

    private function storeContinuationFile(UploadedFile $file, string $codigoReferencia): string
    {
        $targetDir = WRITEPATH . self::GU1_CONTINUACION_RELATIVE_DIR;
        if (! is_dir($targetDir) && ! mkdir($targetDir, 0750, true) && ! is_dir($targetDir)) {
            throw new RuntimeException('No fue posible preparar la carpeta de continuacion de GU1.');
        }

        $safeReference = preg_replace('/[^A-Z0-9]+/i', '', strtoupper($codigoReferencia)) ?: 'GU1';
        $ext = strtolower((string) $file->getClientExtension() ?: 'bin');
        $fileName = $safeReference . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

        $file->move($targetDir, $fileName, true);

        return self::GU1_CONTINUACION_RELATIVE_DIR . $fileName;
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