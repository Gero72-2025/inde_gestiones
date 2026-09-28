<?php

namespace App\Modules\Ecoe\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Admin\Models\UploadLogModel;
use App\Modules\Ecoe\Models\DistribuidoraModel;
use App\Modules\Ecoe\Models\NisBaseModel;
use App\Modules\Ecoe\Models\TsAdjuntoModel;
use App\Modules\Ecoe\Models\TsBitacoraModel;
use App\Modules\Ecoe\Models\TsEstadoModel;
use App\Modules\Ecoe\Models\TsPdfTemplateModel;
use App\Modules\Ecoe\Models\TsTicketModel;
use App\Modules\Ecoe\Models\TarifaMensualModel;
use CodeIgniter\HTTP\RedirectResponse;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Controlador administrativo – Módulo Tarifa Social (ECOE)
 *
 * Rutas (declaradas en Config/Routes.php):
 *  GET  gerencias/ecoe/tarifa-social              → index()
 *  POST gerencias/ecoe/tarifa-social              → index() (acciones AJAX)
 *  GET  gerencias/ecoe/tarifa-social/estados      → estadosIndex()
 *  POST gerencias/ecoe/tarifa-social/estados      → estadosIndex() (CRUD estados)
 *  GET  gerencias/ecoe/tarifa-social/nis          → nisIndex()
 *  POST gerencias/ecoe/tarifa-social/nis          → nisIndex() (carga XLSX)
 *  GET  gerencias/ecoe/tarifa-social/adjunto/{id} → descargarAdjunto()
 */
class TarifaSocialController extends AdminBaseController
{
    // ─── Permisos requeridos ───────────────────────────────────────────────────
    private const PERM = 'gerencia.ecoe.tarifa_social.access';

    // ─── Directorio de almacenamiento (relativo a WRITEPATH) ──────────────────
    private const UPLOAD_DIR = 'uploads/ecoe/ts/';

    // ─── MIME types permitidos para adjuntos (solo imagenes) ─────────────────
    private const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    // ─── Extensiones permitidas para adjuntos (solo imagenes) ────────────────
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp'];

    // ─── Tamaño máximo por archivo: 5 MB ──────────────────────────────────────
    private const MAX_FILE_BYTES = 5 * 1024 * 1024;
    private const ECOE_QUERY_TIMEOUT_SECONDS = 8;
    private const ECOE_MAX_RESULT_ROWS = 120;
    private const ECOE_RATE_LIMIT_REQUESTS = 10;
    private const ECOE_RATE_LIMIT_WINDOW_SECONDS = 60;

    /** @var array<string, string> */
    private const MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private DistribuidoraModel $distribuidoraModel;
    private NisBaseModel       $nisModel;
    private TsEstadoModel      $estadoModel;
    private TsTicketModel      $ticketModel;
    private TsPdfTemplateModel $templateModel;
    private TsAdjuntoModel     $adjuntoModel;
    private TsBitacoraModel    $bitacoraModel;
    private UploadLogModel     $uploadLogModel;
    private TarifaMensualModel $tarifaMensualModel;

    public function initController($request, $response, $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->distribuidoraModel = new DistribuidoraModel();
        $this->nisModel           = new NisBaseModel();
        $this->estadoModel        = new TsEstadoModel();
        $this->ticketModel        = new TsTicketModel();
        $this->templateModel      = new TsPdfTemplateModel();
        $this->adjuntoModel       = new TsAdjuntoModel();
        $this->bitacoraModel      = new TsBitacoraModel();
        $this->uploadLogModel     = new UploadLogModel();
        $this->tarifaMensualModel = new TarifaMensualModel();
    }

    /**
     * Valida que el archivo sea una imagen real y no contenga carga de script.
     *
     * @return array{ok: bool, mime?: string, ext?: string, error?: string}
     */
    private function validateImageUpload($uploadedFile): array
    {
        $tmpPath = $uploadedFile->getTempName();

        if (! is_file($tmpPath)) {
            return ['ok' => false, 'error' => 'No fue posible inspeccionar el archivo temporal.'];
        }

        $clientExt = strtolower((string) $uploadedFile->getClientExtension());

        if (! in_array($clientExt, self::ALLOWED_EXT, true)) {
            return ['ok' => false, 'error' => 'Extension no permitida. Solo se aceptan JPG, PNG o WEBP.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = strtolower((string) $finfo->file($tmpPath));

        if (! in_array($mime, self::ALLOWED_MIME, true)) {
            return ['ok' => false, 'error' => 'Tipo MIME no permitido. Solo se aceptan imagenes JPG, PNG o WEBP.'];
        }

        if (! isset(self::MIME_TO_EXT[$mime])) {
            return ['ok' => false, 'error' => 'No fue posible determinar un formato de salida seguro para la imagen.'];
        }

        $imageInfo = @getimagesize($tmpPath);

        if ($imageInfo === false) {
            return ['ok' => false, 'error' => 'El archivo no corresponde a una imagen valida.'];
        }

        $head = file_get_contents($tmpPath, false, null, 0, 4096);
        $size = filesize($tmpPath);
        $tail = '';

        if (is_int($size) && $size > 4096) {
            $offset = max($size - 4096, 0);
            $tail = (string) file_get_contents($tmpPath, false, null, $offset, 4096);
        }

        if (($head !== false && preg_match('/<\?(php|=)|<script\b/i', $head) === 1)
            || ($tail !== '' && preg_match('/<\?(php|=)|<script\b/i', $tail) === 1)
        ) {
            return ['ok' => false, 'error' => 'Se detecto contenido potencialmente malicioso en el archivo.'];
        }

        return ['ok' => true, 'mime' => $mime, 'ext' => self::MIME_TO_EXT[$mime]];
    }

    private function sanitizeAndStoreImage(string $tmpPath, string $targetPath, string $mime): bool
    {
        if (! function_exists('imagecreatefromstring')) {
            return false;
        }

        $raw = file_get_contents($tmpPath);

        if ($raw === false) {
            return false;
        }

        $image = @imagecreatefromstring($raw);

        if ($image === false) {
            return false;
        }

        $saved = false;

        switch ($mime) {
            case 'image/jpeg':
                $saved = imagejpeg($image, $targetPath, 85);
                break;

            case 'image/png':
                imagealphablending($image, false);
                imagesavealpha($image, true);
                $saved = imagepng($image, $targetPath, 6);
                break;

            case 'image/webp':
                if (function_exists('imagewebp')) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    $saved = imagewebp($image, $targetPath, 85);
                }

                break;
        }

        imagedestroy($image);

        if ($saved) {
            @chmod($targetPath, 0640);
        }

        return (bool) $saved;
    }

    // =========================================================================
    // Helpers de acceso
    // =========================================================================

    private function canAccess(array $auth): bool
    {
        $isSuperAdmin = $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []));

        if ($isSuperAdmin) {
            return true;
        }

        $gerencia = (string) ($auth['gerencia_slug'] ?? '');

        return $gerencia === 'ecoe'
            && $this->rbac->hasPermission((array) ($auth['permissions'] ?? []), self::PERM);
    }

    // =========================================================================
    // INDEX – Gestión de tickets
    // =========================================================================

    public function index(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return redirect()->to('admin')
                ->with('error', 'No tienes permisos para acceder a Tarifa Social ECOE.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'cambiar_estado' => $this->handleCambiarEstado($auth),
                'delete'         => $this->handleDeleteTicket(),
                default          => $this->encryptedAdminResponse(['message' => 'Acción no reconocida.'], 400),
            };
        }

        $filters = [
            'q'         => mb_substr(trim((string) $this->request->getGet('q')), 0, 120),
            'estado_id' => max((int) $this->request->getGet('estado_id'), 0),
        ];

        return $this->adminView('App\\Modules\\Ecoe\\Views\\ts_index', [
            'tickets'  => $this->ticketModel->listConEstado($filters),
            'estados'  => $this->estadoModel->listOrdenados(),
            'filters'  => $filters,
        ]);
    }

    // =========================================================================
    // Estados – CRUD de catálogo de estados de gestión
    // =========================================================================

    public function estadosIndex(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return redirect()->to('admin')
                ->with('error', 'No tienes permisos para administrar estados de Tarifa Social ECOE.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'guardar'  => $this->handleGuardarEstado(),
                'eliminar' => $this->handleEliminarEstado(),
                default    => redirect()->back()->with('error', 'Accion de estado no reconocida.'),
            };
        }

        $editId = max(0, (int) $this->request->getGet('edit_id'));
        $selectedEstado = $editId > 0 ? $this->estadoModel->find($editId) : null;

        return $this->adminView('App\\Modules\\Ecoe\\Views\\ts_estados', [
            'estados'       => $this->estadoModel->listOrdenados(),
            'selectedEstado'=> $selectedEstado,
            'editId'        => $editId,
        ]);
    }

    private function handleGuardarEstado(): RedirectResponse
    {
        $id = max(0, (int) $this->request->getPost('estado_id'));
        $nombre = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 60);
        $ordenPaso = max(0, (int) $this->request->getPost('orden_paso'));
        $descripcion = mb_substr(trim((string) $this->request->getPost('descripcion')), 0, 255);

        if ($nombre === '' || $ordenPaso <= 0) {
            return redirect()->back()->withInput()->with('error', 'Nombre y orden de paso son obligatorios.');
        }

        $duplicateNombre = $this->estadoModel
            ->where('nombre', $nombre)
            ->where('id !=', $id > 0 ? $id : -1)
            ->first();

        if ($duplicateNombre) {
            return redirect()->back()->withInput()->with('error', 'Ya existe un estado con ese nombre.');
        }

        $duplicateOrden = $this->estadoModel
            ->where('orden_paso', $ordenPaso)
            ->where('id !=', $id > 0 ? $id : -1)
            ->first();

        if ($duplicateOrden) {
            return redirect()->back()->withInput()->with('error', 'Ya existe un estado con ese orden de paso.');
        }

        if ($id > 0) {
            $current = $this->estadoModel->find($id);

            if (! $current) {
                return redirect()->back()->with('error', 'Estado no encontrado.');
            }

            $isLeavingOrdenUno = (int) ($current['orden_paso'] ?? 0) === 1 && $ordenPaso !== 1;

            if ($isLeavingOrdenUno && ! $this->hasAnotherEstadoOrdenUno($id)) {
                return redirect()->back()->withInput()->with('error', 'Debe existir al menos un estado con orden de paso 1.');
            }

            $this->estadoModel->update($id, [
                'nombre' => $nombre,
                'orden_paso' => $ordenPaso,
                'descripcion' => $descripcion,
            ]);

            return redirect()->to(site_url('gerencias/ecoe/tarifa-social/estados'))->with('success', 'Estado actualizado correctamente.');
        }

        $insertId = (int) $this->estadoModel->insert([
            'nombre' => $nombre,
            'orden_paso' => $ordenPaso,
            'descripcion' => $descripcion,
        ], true);

        if ($insertId <= 0) {
            return redirect()->back()->withInput()->with('error', 'No fue posible crear el estado.');
        }

        return redirect()->to(site_url('gerencias/ecoe/tarifa-social/estados'))->with('success', 'Estado creado correctamente.');
    }

    private function handleEliminarEstado(): RedirectResponse
    {
        $id = max(0, (int) $this->request->getPost('estado_id'));

        if ($id <= 0) {
            return redirect()->back()->with('error', 'ID de estado no valido.');
        }

        $estado = $this->estadoModel->find($id);

        if (! $estado) {
            return redirect()->back()->with('error', 'Estado no encontrado.');
        }

        $totalEstados = $this->estadoModel->countAllResults();

        if ($totalEstados <= 1) {
            return redirect()->back()->with('error', 'No puedes eliminar el unico estado registrado.');
        }

        $ticketsEnUso = $this->ticketModel->where('estado_id', $id)->countAllResults();
        $bitacoraEnUso = $this->bitacoraModel->where('estado_id', $id)->countAllResults();

        if ($ticketsEnUso > 0 || $bitacoraEnUso > 0) {
            return redirect()->back()->with('error', 'No puedes eliminar un estado en uso por tickets o bitacora.');
        }

        $isOrdenUno = (int) ($estado['orden_paso'] ?? 0) === 1;

        if ($isOrdenUno && ! $this->hasAnotherEstadoOrdenUno($id)) {
            return redirect()->back()->with('error', 'Debe existir al menos un estado con orden de paso 1.');
        }

        try {
            $this->estadoModel->delete($id);
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'No fue posible eliminar el estado: ' . $e->getMessage());
        }

        return redirect()->to(site_url('gerencias/ecoe/tarifa-social/estados'))->with('success', 'Estado eliminado correctamente.');
    }

    private function hasAnotherEstadoOrdenUno(int $excludeId = 0): bool
    {
        $builder = $this->estadoModel->where('orden_paso', 1);

        if ($excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    // ─── Cambiar estado de un ticket ──────────────────────────────────────────

    private function handleCambiarEstado(array $auth): mixed
    {
        $ticketId = (int) $this->request->getPost('ticket_id');
        $estadoId = (int) $this->request->getPost('estado_id');
        $descripcionExtra = mb_substr(trim((string) $this->request->getPost('descripcion')), 0, 255);

        if ($ticketId <= 0 || $estadoId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'Datos incompletos.'], 422);
        }

        $ticket = $this->ticketModel->find($ticketId);

        if (! $ticket) {
            return $this->encryptedAdminResponse(['message' => 'Ticket no encontrado.'], 404);
        }

        $estado = $this->estadoModel->find($estadoId);

        if (! $estado) {
            return $this->encryptedAdminResponse(['message' => 'Estado no válido.'], 422);
        }

        $oldEstadoId = (int) ($ticket['estado_id'] ?? 0);
        $oldEstado   = $oldEstadoId > 0 ? $this->estadoModel->find($oldEstadoId) : null;

        if ($oldEstadoId === $estadoId) {
            return $this->encryptedAdminResponse([
                'message'       => 'La solicitud ya se encuentra en ese estado.',
                'estado_nombre' => esc($estado['nombre']),
            ]);
        }

        $this->ticketModel->update($ticketId, ['estado_id' => $estadoId]);

        $descripcion = 'Cambio de etapa';
        if ($oldEstado && ! empty($oldEstado['nombre'])) {
            $descripcion = 'Cambio de etapa: ' . (string) $oldEstado['nombre'] . ' -> ' . (string) $estado['nombre'];
        }

        if ($descripcionExtra !== '') {
            $descripcion = mb_substr($descripcion . '. Detalle: ' . $descripcionExtra, 0, 255);
        }

        $this->registrarBitacoraEstado(
            $ticketId,
            $estadoId,
            (string) ($estado['nombre'] ?? ''),
            $descripcion,
            (int) ($auth['user_id'] ?? 0),
        );

        return $this->encryptedAdminResponse([
            'message'       => 'Estado actualizado correctamente.',
            'estado_nombre' => esc($estado['nombre']),
        ]);
    }

    // ─── Eliminar ticket ──────────────────────────────────────────────────────

    private function handleDeleteTicket(): mixed
    {
        $ticketId = (int) $this->request->getPost('ticket_id');

        if ($ticketId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'ID no válido.'], 422);
        }

        $ticket = $this->ticketModel->find($ticketId);

        if (! $ticket) {
            return $this->encryptedAdminResponse(['message' => 'Ticket no encontrado.'], 404);
        }

        // Eliminar archivos físicos
        foreach ($this->adjuntoModel->porTicket($ticketId) as $adj) {
            $ruta = WRITEPATH . ltrim((string) $adj['ruta_archivo'], '/');

            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }

        $this->ticketModel->delete($ticketId);

        return $this->encryptedAdminResponse(['message' => 'Solicitud eliminada.']);
    }

    // =========================================================================
    // NIS – Gestión de base de datos y carga masiva XLSX
    // =========================================================================

    public function nisIndex(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return redirect()->to('admin')
                ->with('error', 'No tienes permisos para gestionar la base NIS.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            return $this->handleNisUpload($auth);
        }

        return $this->adminView('App\\Modules\\Ecoe\\Views\\ts_nis', [
            'distribuidoras' => $this->distribuidoraModel->listActivas(),
            'logs'           => $this->uploadLogModel
                ->where('gerencia_id', (int) ($auth['gerencia_id'] ?? 0))
                ->orderBy('fecha_creacion', 'DESC')
                ->limit(30)
                ->findAll(),
        ]);
    }

    // =========================================================================
    // Plantillas PDF – administración desde módulo ECOE
    // =========================================================================

    public function plantillasIndex(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return redirect()->to('admin')
                ->with('error', 'No tienes permisos para administrar plantillas PDF de Tarifa Social.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'guardar'      => $this->handleGuardarPlantilla(),
                'eliminar'     => $this->handleEliminarPlantilla(),
                'set_default'  => $this->handleSetDefaultPlantilla(),
                'preview'      => $this->handlePreviewPlantillaDraft(),
                default        => redirect()->back()->with('error', 'Acción de plantilla no reconocida.'),
            };
        }

        return $this->adminView('App\\Modules\\Ecoe\\Views\\ts_pdf_templates', [
            'plantillas'     => $this->templateModel->listAll(),
            'templateEditId' => (int) $this->request->getGet('edit_id'),
        ]);
    }

    private function handleGuardarPlantilla(): RedirectResponse
    {
        $id          = (int) $this->request->getPost('template_id');
        $nombre      = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 120);
        $slugRaw     = mb_substr(trim((string) $this->request->getPost('slug')), 0, 120);
        $slug        = strtolower(preg_replace('/[^a-z0-9\-]+/', '-', $slugRaw) ?? '');
        $slug        = trim($slug, '-');
        $descripcion = mb_substr(trim((string) $this->request->getPost('descripcion')), 0, 255);
        $html        = (string) $this->request->getPost('html_template');
        $instruccion = (string) $this->request->getPost('instrucciones_html');
        $isDefault   = ((int) $this->request->getPost('is_default')) === 1 ? 1 : 0;
        $isActive    = ((int) $this->request->getPost('is_active')) === 1 ? 1 : 0;

        if ($nombre === '' || $slug === '' || trim($html) === '' || trim($instruccion) === '') {
            return redirect()->back()->withInput()->with('error', 'Nombre, slug, plantilla HTML e instrucciones son obligatorios.');
        }

        $payload = [
            'nombre'             => $nombre,
            'slug'               => $slug,
            'descripcion'        => $descripcion,
            'html_template'      => $html,
            'instrucciones_html' => $instruccion,
            'is_default'         => $isDefault,
            'is_active'          => $isActive,
        ];

        $duplicate = $this->templateModel
            ->where('slug', $slug)
            ->where('id !=', $id > 0 ? $id : -1)
            ->first();

        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', 'El slug ya existe. Usa uno diferente.');
        }

        if ($id > 0) {
            $current = $this->templateModel->find($id);

            if (! $current) {
                return redirect()->back()->with('error', 'Plantilla no encontrada.');
            }

            $this->templateModel->update($id, $payload);
            $savedId = $id;
        } else {
            $savedId = (int) $this->templateModel->insert($payload, true);
        }

        if ($savedId <= 0) {
            return redirect()->back()->withInput()->with('error', 'No fue posible guardar la plantilla.');
        }

        if ($isDefault === 1) {
            $this->templateModel->setAsDefault($savedId);
        }

        return redirect()->to(site_url('gerencias/ecoe/tarifa-social/plantillas'))->with('success', 'Plantilla guardada correctamente.');
    }

    private function handleEliminarPlantilla(): RedirectResponse
    {
        $id = (int) $this->request->getPost('template_id');

        if ($id <= 0) {
            return redirect()->back()->with('error', 'ID de plantilla no válido.');
        }

        $template = $this->templateModel->find($id);

        if (! $template) {
            return redirect()->back()->with('error', 'Plantilla no encontrada.');
        }

        if ((int) ($template['is_default'] ?? 0) === 1) {
            return redirect()->back()->with('error', 'No puedes eliminar la plantilla marcada como predeterminada.');
        }

        $this->templateModel->delete($id);

        return redirect()->to(site_url('gerencias/ecoe/tarifa-social/plantillas'))->with('success', 'Plantilla eliminada.');
    }

    private function handleSetDefaultPlantilla(): RedirectResponse
    {
        $id = (int) $this->request->getPost('template_id');

        if ($id <= 0) {
            return redirect()->back()->with('error', 'ID de plantilla no válido.');
        }

        $template = $this->templateModel->find($id);

        if (! $template) {
            return redirect()->back()->with('error', 'Plantilla no encontrada.');
        }

        $this->templateModel->setAsDefault($id);

        return redirect()->to(site_url('gerencias/ecoe/tarifa-social/plantillas'))->with('success', 'Plantilla predeterminada actualizada.');
    }

    public function previewPlantillaPdf(int $id = 0): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return $this->response->setStatusCode(403)->setBody('Acceso denegado.');
        }

        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setBody('Plantilla no válida.');
        }

        $template = $this->templateModel->find($id);

        if (! $template) {
            return $this->response->setStatusCode(404)->setBody('Plantilla no encontrada.');
        }

        return $this->renderPreviewPdfResponse($template);
    }

    private function handlePreviewPlantillaDraft(): mixed
    {
        $nombre      = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 120);
        $descripcion = mb_substr(trim((string) $this->request->getPost('descripcion')), 0, 255);
        $html        = (string) $this->request->getPost('html_template');
        $instruccion = (string) $this->request->getPost('instrucciones_html');

        if ($nombre === '' || trim($html) === '' || trim($instruccion) === '') {
            return redirect()->back()->withInput()->with('error', 'Para vista previa se requiere nombre, HTML de plantilla e instrucciones.');
        }

        $template = [
            'nombre'             => $nombre,
            'descripcion'        => $descripcion,
            'html_template'      => $html,
            'instrucciones_html' => $instruccion,
        ];

        return $this->renderPreviewPdfResponse($template, 'preview-borrador-tarifa-social.pdf');
    }

    private function renderPreviewPdfResponse(array $template, ?string $fileName = null): mixed
    {
        $sampleTicket = $this->getTicketDataForPreview();
        $sampleBitacora = $this->getBitacoraForPreview((int) ($sampleTicket['id'] ?? 0));
        $html = $this->renderTicketPdfHtml($template, $sampleTicket, $sampleBitacora);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $downloadName = $fileName ?: ('preview-' . preg_replace('/[^a-z0-9\-]+/i', '-', (string) ($template['nombre'] ?? 'plantilla')) . '.pdf');

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $downloadName . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($dompdf->output());
    }

    // ─── Carga masiva XLSX ────────────────────────────────────────────────────

    private function handleNisUpload(array $auth): mixed
    {
        $distribuidoraId = (int) $this->request->getPost('distribuidora_id');

        if ($distribuidoraId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'Selecciona una distribuidora.'], 422);
        }

        $distribuidora = $this->distribuidoraModel->find($distribuidoraId);

        if (! $distribuidora) {
            return $this->encryptedAdminResponse(['message' => 'Distribuidora no encontrada.'], 422);
        }

        $file = $this->request->getFile('xlsx_file');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $this->encryptedAdminResponse(['message' => 'Archivo no recibido o no válido.'], 422);
        }

        // Validar extensión y MIME de manera estricta
        $ext  = strtolower($file->getClientExtension());
        $mime = strtolower($file->getMimeType() ?? '');

        $allowedXlsxMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/octet-stream',
        ];

        if (! in_array($ext, ['xlsx', 'xls'], true) || ! in_array($mime, $allowedXlsxMimes, true)) {
            return $this->encryptedAdminResponse(['message' => 'Solo se permiten archivos .xlsx o .xls.'], 422);
        }

        if ($file->getSize() > 20 * 1024 * 1024) {
            return $this->encryptedAdminResponse(['message' => 'El archivo excede el tamaño máximo (20 MB).'], 422);
        }

        // Mover a directorio temporal seguro
        $tmpDir  = WRITEPATH . 'uploads/ecoe/nis_tmp/';

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0750, true);
        }

        $tmpName = bin2hex(random_bytes(16)) . '.' . $ext;
        $file->move($tmpDir, $tmpName);
        $tmpPath = $tmpDir . $tmpName;

        try {
            $rows   = $this->parseXlsxNis($tmpPath);
            $result = $this->nisModel->upsertBatch($rows, $distribuidoraId);

            // Registrar en upload_logs
            $this->uploadLogModel->insert([
                'gerencia_id'          => (int) ($auth['gerencia_id'] ?? 0),
                'usuario_id'           => (int) ($auth['user_id'] ?? 0),
                'nombre_archivo'       => $file->getClientName(),
                'registros_procesados' => $result['inserted'] + $result['updated'],
                'fecha_creacion'       => date('Y-m-d H:i:s'),
            ]);

            return $this->encryptedAdminResponse([
                'message'  => 'Carga completada.',
                'inserted' => $result['inserted'],
                'updated'  => $result['updated'],
                'errors'   => $result['errors'],
            ]);
        } catch (Throwable $e) {
            return $this->encryptedAdminResponse(['message' => 'Error procesando XLSX: ' . $e->getMessage()], 500);
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Detecta automáticamente las columnas del XLSX y mapea:
     *   - Columna que contenga "correlativo", "nis", "usuario" → id_usuario
     *   - Columna que contenga "nombre" → nombre_usuario
     *   - Columna que contenga "departamento" → departamento
     *   - Columna que contenga "municipio" → municipio
     *   - Columna que contenga "aldea" → aldea
     *   - Columna que contenga "direccion" → direccion
     *   - Columna que contenga "activ" → activ_economica
     *   - Columna que contenga "revision" → revision
     *   - Columna que contenga "mes" → mes
     *   - Columna que contenga "consumo" o "kwh" → consumo_kwh
     *
     * @return array<int, array{id_usuario: string, nombre_usuario: string, departamento: string, municipio: string, aldea: string, direccion: string, activ_economica: string, revision: string, mes: string, consumo_kwh: float}>
     */
    private function parseXlsxNis(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $data        = $sheet->toArray(null, true, true, false);

        if (empty($data)) {
            return [];
        }

        // Primera fila como encabezados
        $headers = array_map(
            static fn ($h) => mb_strtolower(trim((string) $h)),
            (array) array_shift($data),
        );

        // Mapeo de columnas por coincidencia parcial
        $colUsuario     = null;
        $colNombre      = null;
        $colDepartamento = null;
        $colMunicipio   = null;
        $colAldea       = null;
        $colDireccion   = null;
        $colActiv       = null;
        $colRevision    = null;
        $colMes         = null;
        $colConsumo     = null;

        foreach ($headers as $idx => $header) {
            if ($colUsuario === null && (
                str_contains($header, 'correlativo')
                || str_contains($header, 'nis')
                || str_contains($header, 'usuario')
                || str_contains($header, 'id_usuario')
            )) {
                $colUsuario = $idx;
            }

            if ($colNombre === null && str_contains($header, 'nombre')) {
                $colNombre = $idx;
            }

            if ($colDepartamento === null && str_contains($header, 'departamento')) {
                $colDepartamento = $idx;
            }

            if ($colMunicipio === null && str_contains($header, 'municipio')) {
                $colMunicipio = $idx;
            }

            if ($colAldea === null && str_contains($header, 'aldea')) {
                $colAldea = $idx;
            }

            if ($colDireccion === null && str_contains($header, 'direccion')) {
                $colDireccion = $idx;
            }

            if ($colActiv === null && str_contains($header, 'activ')) {
                $colActiv = $idx;
            }

            if ($colRevision === null && str_contains($header, 'revision')) {
                $colRevision = $idx;
            }

            if ($colMes === null && str_contains($header, 'mes')) {
                $colMes = $idx;
            }

            if ($colConsumo === null && (
                str_contains($header, 'consumo')
                || str_contains($header, 'kwh')
            )) {
                $colConsumo = $idx;
            }
        }

        if ($colUsuario === null) {
            throw new \RuntimeException(
                'No se encontró columna de correlativo/NIS en el XLSX. '
                . 'Asegúrate de que una columna se llame "correlativo", "nis" o "id_usuario".'
            );
        }

        $rows = [];

        foreach ($data as $row) {
            $idUsuario = $this->normalizeNisIdentifier($row[$colUsuario] ?? null);

            if ($idUsuario === '') {
                continue;
            }

            $rows[] = [
                'id_usuario'       => $idUsuario,
                'nombre_usuario'   => $colNombre !== null
                    ? $this->normalizeTextCell($row[$colNombre] ?? null, 160)
                    : '',
                'departamento'     => $colDepartamento !== null
                    ? $this->normalizeTextCell($row[$colDepartamento] ?? null, 80)
                    : '',
                'municipio'        => $colMunicipio !== null
                    ? $this->normalizeTextCell($row[$colMunicipio] ?? null, 80)
                    : '',
                'aldea'            => $colAldea !== null
                    ? $this->normalizeTextCell($row[$colAldea] ?? null, 120)
                    : '',
                'direccion'        => $colDireccion !== null
                    ? $this->normalizeTextCell($row[$colDireccion] ?? null, 255)
                    : '',
                'activ_economica'  => $colActiv !== null
                    ? $this->normalizeTextCell($row[$colActiv] ?? null, 80)
                    : '',
                'revision'         => $colRevision !== null
                    ? $this->normalizeTextCell($row[$colRevision] ?? null, 80)
                    : '',
                'mes'              => $colMes !== null
                    ? $this->normalizeTextCell($row[$colMes] ?? null, 20)
                    : '',
                'consumo_kwh'      => $colConsumo !== null
                    ? $this->normalizeDecimalCell($row[$colConsumo] ?? null)
                    : 0.0,
            ];
        }

        return $rows;
    }

    private function normalizeNisIdentifier(mixed $value): string
    {
        $raw = trim((string) ($value ?? ''));

        if ($raw === '') {
            return '';
        }

        // Quita espacios y normaliza valores numéricos que Excel suele exportar como 123.0
        $raw = preg_replace('/\s+/', '', $raw) ?? $raw;

        if (preg_match('/^\d+\.0+$/', $raw) === 1) {
            $raw = preg_replace('/\.0+$/', '', $raw) ?? $raw;
        }

        return mb_substr($raw, 0, 30);
    }

    private function normalizeTextCell(mixed $value, int $maxLength): string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return '';
        }

        return mb_substr($text, 0, max(1, $maxLength));
    }

    private function normalizeDecimalCell(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return 0.0;
        }

        // Soporta formatos como "1,234.50", "1234,50" y descarta texto no numérico.
        $normalized = str_replace(' ', '', $text);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace(',', '', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! is_numeric($normalized)) {
            return 0.0;
        }

        return (float) $normalized;
    }

    // =========================================================================
    // Descarga segura de adjuntos (solo admins autenticados)
    // =========================================================================

    public function descargarAdjunto(int $id = 0): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return $this->response->setStatusCode(403)->setBody('Acceso denegado.');
        }

        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setBody('ID no válido.');
        }

        $adjunto = $this->adjuntoModel->find($id);

        if (! $adjunto) {
            return $this->response->setStatusCode(404)->setBody('Adjunto no encontrado.');
        }

        $ruta = WRITEPATH . ltrim((string) $adjunto['ruta_archivo'], '/');

        if (! is_file($ruta)) {
            return $this->response->setStatusCode(404)->setBody('Archivo no encontrado en disco.');
        }

        $ext  = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf'             => 'application/pdf',
            'jpg', 'jpeg'     => 'image/jpeg',
            'png'             => 'image/png',
            'webp'            => 'image/webp',
            default           => 'application/octet-stream',
        };

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($ruta) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($ruta));
    }

    // =========================================================================
    // Endpoint interno: JSON de adjuntos de un ticket (uso del panel admin)
    // =========================================================================

    public function adjuntosJson(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'No autenticado.']);
        }

        if (! $this->canAccess($auth)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Sin permiso.']);
        }

        $ticketId = (int) $this->request->getGet('ticket_id');

        if ($ticketId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'ID no válido.'], 422);
        }

        $adjuntos = $this->adjuntoModel->porTicket($ticketId);

        $mapped = array_map(function (array $adj) {
            return [
                'id'           => (int) $adj['id'],
                'tipo_archivo' => $adj['tipo_archivo'],
                'download_url' => site_url('gerencias/ecoe/tarifa-social/adjunto/' . $adj['id']),
                'preview_url'  => site_url('gerencias/ecoe/tarifa-social/preview/' . $adj['id']), // Added preview URL
            ];
        }, $adjuntos);

        return $this->encryptedAdminResponse(['adjuntos' => $mapped]);
    }

    // =========================================================================
    // Endpoint interno: JSON de bitácora por ticket (uso del panel admin)
    // =========================================================================

    public function bitacoraJson(): mixed
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'No autenticado.']);
        }

        if (! $this->canAccess($auth)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Sin permiso.']);
        }

        $ticketId = (int) $this->request->getGet('ticket_id');

        if ($ticketId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'ID no válido.'], 422);
        }

        $rows = $this->bitacoraModel->listByTicket($ticketId);

        $mapped = array_map(static function (array $row): array {
            return [
                'id'             => (int) ($row['id'] ?? 0),
                'estado_nombre'  => (string) ($row['estado_nombre'] ?? ''),
                'descripcion'    => (string) ($row['descripcion'] ?? ''),
                'fecha_registro' => (string) ($row['fecha_registro'] ?? ''),
            ];
        }, $rows);

        return $this->encryptedAdminResponse(['bitacora' => $mapped]);
    }

    // =========================================================================
    // Endpoints AJAX públicos (consulta NIS y creación de ticket)
    // Estos son llamados por el portal público; no requieren sesión de admin.
    // Se protegen con CSRF y respuesta cifrada.
    // =========================================================================

    private function obtenerValorHistorial(array $row, array $candidatos): mixed
    {
        $rowLowerKeys = array_change_key_case($row, CASE_LOWER);

        foreach ($candidatos as $candidato) {
            $candidatoLower = strtolower($candidato);
            if (array_key_exists($candidatoLower, $rowLowerKeys)) {
                return $rowLowerKeys[$candidatoLower];
            }
        }

        return null;
    }

    private function convertirMesHistorial($valor): string
    {
        if ($valor === null || $valor === '') {
            return 'Sin fecha';
        }

        if (is_numeric($valor)) {
            return (string) $valor;
        }

        $texto = trim((string) $valor);

        if (preg_match('/^\d{4}-\d{2}$/', $texto)) {
            $fecha = date_create_from_format('Y-m', $texto);
            if ($fecha instanceof \DateTimeInterface) {
                return mb_convert_case(date_format($fecha, 'M Y'), MB_CASE_TITLE, 'UTF-8');
            }
        }

        if (preg_match('/^\d{4}\/\d{2}$/', $texto)) {
            $fecha = date_create_from_format('Y/m', $texto);
            if ($fecha instanceof \DateTimeInterface) {
                return mb_convert_case(date_format($fecha, 'M Y'), MB_CASE_TITLE, 'UTF-8');
            }
        }

        if (preg_match('/^\d{4}$/', $texto)) {
            return $texto;
        }

        return $texto;
    }

    private function normalizarHistorialEcoe(array $rows, string $correlativo): array
    {
        $periodos = [];
        $usuario = [
            'nombre_usuario' => '',
            'departamento'   => '',
            'municipio'      => '',
            'aldea'          => '',
            'direccion'      => '',
        ];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $mesValor = $this->obtenerValorHistorial($row, ['mes_operacion', 'mes', 'periodo', 'mes_periodo', 'fecha']);
            $mes = $this->convertirMesHistorial($mesValor);
            $mesNumero = null;

            if (is_numeric($mesValor)) {
                $mesNumero = (int) $mesValor;
            } elseif (is_string($mesValor) && preg_match('/^(\d{1,2})$/', trim($mesValor), $matches)) {
                $mesNumero = (int) $matches[1];
            }

            $consumo = (float) ($this->obtenerValorHistorial($row, ['consumo_kwh', 'csmo_energia_total', 'consumo', 'kwh']) ?? 0);
            $sinAporte = (float) ($this->obtenerValorHistorial($row, ['factura_sin_aporte', 'sin_aporte', 'monto_sin_aporte', 'total_sin_aporte']) ?? 0);
            $conAporte = (float) ($this->obtenerValorHistorial($row, ['factura_con_aporte', 'con_aporte', 'monto_con_aporte', 'total_con_aporte']) ?? 0);

            if ($mes === 'Sin fecha') {
                continue;
            }

            $periodos[] = [
                'mes'                 => $mes,
                'mes_operacion'       => $mesNumero,
                'consumo_kwh'         => $consumo,
                'factura_sin_aporte' => $sinAporte,
                'factura_con_aporte' => $conAporte,
            ];

        }

        if ($rows !== []) {
            $primerFila = $rows[0];
            if (is_array($primerFila)) {
                $usuario['nombre_usuario'] = (string) ($this->obtenerValorHistorial($primerFila, ['nombre_usuario', 'nombre', 'usuario']) ?? '');
                $usuario['departamento']   = (string) ($this->obtenerValorHistorial($primerFila, ['departamento', 'depto', 'departamento_residencia']) ?? '');
                $usuario['municipio']      = (string) ($this->obtenerValorHistorial($primerFila, ['municipio', 'municipio_residencia']) ?? '');
                $usuario['aldea']          = (string) ($this->obtenerValorHistorial($primerFila, ['aldea', 'comunidad', 'localidad']) ?? '');
                $usuario['direccion']      = (string) ($this->obtenerValorHistorial($primerFila, ['direccion', 'direccion_completa', 'direccion_residencia']) ?? '');
            }
        }

        $ahorroTotal = 0.0;
        foreach ($periodos as $periodo) {
            $ahorroTotal += max(0.0, (float) ($periodo['factura_sin_aporte'] ?? 0.0) - (float) ($periodo['factura_con_aporte'] ?? 0.0));
        }

        return [
            'correlativo' => $correlativo,
            'usuario'     => $usuario,
            'periodos'    => $periodos,
            'resumen'     => [
                'ahorro_total' => $ahorroTotal,
            ],
        ];
    }

    private function construirHtmlPdfSinDatos(array $historial, string $anio): string
    {
        $usuario = $historial['usuario'] ?? [];
        $correlativo = $historial['correlativo'] ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<style>
body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2a37; margin: 0; padding: 0; background: #f5f7fb; }
.page { padding: 24px; }
.header { background: linear-gradient(135deg, #003366 0%, #0a6e3d 100%); padding: 20px 24px; border-radius: 16px; margin-bottom: 16px; }
.title { font-size: 22px; font-weight: 700; margin: 0; color: #000000; }
.subtitle { font-size: 11px; margin-top: 4px; color: #4a5a6a; opacity: 1; }
.card { border: 1px solid #dce4eb; border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; background: #ffffff; }
.grid { width: 100%; border-collapse: collapse; }
.grid td { padding: 4px 0; vertical-align: top; }
.label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #687382; margin-bottom: 2px; }
.value { font-size: 12px; font-weight: 700; color: #152235; }
.alert { background: #fff3cd; border: 1px solid #ffc107; border-radius: 12px; padding: 16px; margin-top: 16px; font-size: 12px; color: #856404; }
</style>
</head>
<body>
<div class="page">
  <div class="header">
    <div class="title">Historial de ayuda social · Tarifa Social</div>
    <div class="subtitle">Generado por la Empresa de Comercialización de Energía Eléctrica del INDE (ECOE) · Año {$anio}</div>
  </div>

  <div class="card">
    <table class="grid">
      <tr>
        <td><div class="label">Correlativo</div><div class="value">{$this->escaparHtml((string) $correlativo)}</div></td>
        <td><div class="label">Usuario</div><div class="value">{$this->escaparHtml((string) ($usuario['nombre_usuario'] ?? ''))}</div></td>
        <td><div class="label">Departamento</div><div class="value">{$this->escaparHtml((string) ($usuario['departamento'] ?? ''))}</div></td>
      </tr>
      <tr>
        <td><div class="label">Municipio</div><div class="value">{$this->escaparHtml((string) ($usuario['municipio'] ?? ''))}</div></td>
        <td><div class="label">Aldea</div><div class="value">{$this->escaparHtml((string) ($usuario['aldea'] ?? ''))}</div></td>
        <td><div class="label">Dirección</div><div class="value">{$this->escaparHtml((string) ($usuario['direccion'] ?? ''))}</div></td>
      </tr>
    </table>
  </div>

  <div class="alert">
    <strong>Información no disponible</strong><br/>
    No hay datos de historial disponibles para este correlativo en el año {$anio}. Esto puede ocurrir si:
    <ul>
      <li>El usuario no cuenta con registros en el período solicitado.</li>
      <li>El correlativo aún no ha iniciado su historial de facturación.</li>
      <li>Los datos están siendo procesados en la base de datos.</li>
    </ul>
    Por favor, intenta con otro año o contacta al equipo técnico.
  </div>
</div>
</body>
</html>
HTML;
    }

    private function construirHtmlPdfHistorial(array $historial, ?string $anio = null): string
    {
        $usuario = $historial['usuario'] ?? [];
        $periodos = $historial['periodos'] ?? [];
        $resumen = $historial['resumen'] ?? [];
        $ahorroTotal = (float) ($resumen['ahorro_total'] ?? 0.0);
        $anio ??= (string) date('Y');

        if ($periodos === []) {
            return $this->construirHtmlPdfSinDatos($historial, $anio);
        }

        $meses_nombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        usort($periodos, function ($a, $b) {
            $mesA = (int) ($a['mes_operacion'] ?? 0);
            $mesB = (int) ($b['mes_operacion'] ?? 0);
            return $mesA - $mesB;
        });

        $consumos = [];
        $sinAporte = [];
        $conAporte = [];
        $labels = [];

        foreach ($periodos as $periodo) {
            $mesNum = (int) ($periodo['mes_operacion'] ?? 0);
            $mesNombre = $meses_nombres[$mesNum] ?? 'Mes ' . $mesNum;

            $labels[] = $mesNombre;
            $consumos[] = (float) ($periodo['consumo_kwh'] ?? 0.0);
            $sinAporte[] = (float) ($periodo['factura_sin_aporte'] ?? 0.0);
            $conAporte[] = (float) ($periodo['factura_con_aporte'] ?? 0.0);
        }

        // Validar que los arrays se construyeron correctamente
        if (empty($labels) || empty($consumos) || empty($sinAporte) || empty($conAporte) ||
            count($labels) === 0 || count($consumos) === 0 || count($sinAporte) === 0 || count($conAporte) === 0) {
            return $this->construirHtmlPdfSinDatos($historial, $anio);
        }
        
        // Calcular totales y promedios
        $totalConsumo = array_sum($consumos);
        $consumoPromedio = count($periodos) > 0 ? $totalConsumo / count($periodos) : 0.0;
        $mesesAnalizados = count($periodos);

        $lineChartHtml = $this->buildHistoryLineChartHtml($labels, $consumos);
        $barsChartHtml = $this->buildHistoryBarsChartHtml($labels, $sinAporte, $conAporte);

        $detalleRows = '';
        foreach ($periodos as $periodo) {
            $mesNum = (int) ($periodo['mes_operacion'] ?? 0);
            $mesNombre = $meses_nombres[$mesNum] ?? 'Mes ' . $mesNum;
            $mesNombre = $this->escaparHtml($mesNombre);
            
            $consumo = $this->formatearMonto((float) ($periodo['consumo_kwh'] ?? 0.0));
            $sin = $this->formatearMonto((float) ($periodo['factura_sin_aporte'] ?? 0.0));
            $con = $this->formatearMonto((float) ($periodo['factura_con_aporte'] ?? 0.0));
            $ahorro = $this->formatearMonto(max(0.0, (float) ($periodo['factura_sin_aporte'] ?? 0.0) - (float) ($periodo['factura_con_aporte'] ?? 0.0)));

            $detalleRows .= "<tr><td style='padding:7px 6px; border-bottom:1px solid #e8edf3; font-size:10px; color:#203447; text-align:left;'>$mesNombre</td><td style='padding:7px 6px; border-bottom:1px solid #e8edf3; font-size:10px; color:#203447; text-align:left;'>$consumo kWh</td><td style='padding:7px 6px; border-bottom:1px solid #e8edf3; font-size:10px; color:#203447; text-align:left;'>Q $sin</td><td style='padding:7px 6px; border-bottom:1px solid #e8edf3; font-size:10px; color:#203447; text-align:left;'>Q $con</td><td style='padding:7px 6px; border-bottom:1px solid #e8edf3; font-size:10px; color:#008f39; font-weight:700; text-align:left;'>Q $ahorro</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<style>
body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2a37; margin: 0; padding: 0; background: #f5f7fb; }
.page { padding: 24px; }
.header { background: linear-gradient(135deg, #003366 0%, #0a6e3d 100%); padding: 20px 24px; border-radius: 16px; margin-bottom: 16px; }
.title { font-size: 22px; font-weight: 700; margin: 0; color: #000000; }
.subtitle { font-size: 11px; margin-top: 4px; color: #4a5a6a; opacity: 1; }
.card { border: 1px solid #dce4eb; border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; background: #ffffff; }
.grid { width: 100%; border-collapse: collapse; }
.grid td { padding: 4px 0; vertical-align: top; }
.label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #687382; margin-bottom: 2px; }
.value { font-size: 12px; font-weight: 700; color: #152235; }
.summary { background: linear-gradient(135deg, #f8fbff 0%, #f3f9f5 100%); border: 1px solid #dce4eb; border-radius: 12px; padding: 12px; margin-bottom: 12px; }
.summary-table { width: 100%; border-collapse: collapse; }
.summary-table td { padding: 6px 0; }
.summary-value { font-size: 16px; font-weight: 700; color: #008f39; }
.summary-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #687382; }
.chart-box { border: 1px solid #dce4eb; border-radius: 12px; padding: 12px; margin-bottom: 12px; background: #fff; }
.chart-title { font-size: 13px; font-weight: 700; color: #003366; margin-bottom: 8px; }
.chart-caption { font-size: 9px; color: #687382; margin-top: 6px; }
.analysis { border-left: 6px solid #008f39; background: #f3f9f5; padding: 12px; border-radius: 8px; font-size: 11px; color: #203447; }
.page-break { page-break-before: always; }
.table { width: 100%; border-collapse: collapse; margin-top: 6px; }
.table th { font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #687382; text-align: left; padding: 7px 6px; border-bottom: 2px solid #dce4eb; }
.table td { font-size: 10px; color: #203447; }
.badge { display: inline-block; padding: 3px 8px; border-radius: 999px; color: #ffffff; background: #008f39; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
</style>
</head>
<body>
<div class="page">
  <div class="header">
    <div class="title">Historial de ayuda social · Tarifa Social</div>
    <div class="subtitle">Generado por la Empresa de Comercialización de Energía Eléctrica del INDE (ECOE) · Año {$anio}</div>
  </div>

  <div class="card">
    <table class="grid">
      <tr>
        <td><div class="label">Correlativo</div><div class="value">{$this->escaparHtml((string) ($historial['correlativo'] ?? ''))}</div></td>
        <td><div class="label">Usuario</div><div class="value">{$this->escaparHtml((string) ($usuario['nombre_usuario'] ?? ''))}</div></td>
        <td><div class="label">Departamento</div><div class="value">{$this->escaparHtml((string) ($usuario['departamento'] ?? ''))}</div></td>
      </tr>
      <tr>
        <td><div class="label">Municipio</div><div class="value">{$this->escaparHtml((string) ($usuario['municipio'] ?? ''))}</div></td>
        <td><div class="label">Aldea</div><div class="value">{$this->escaparHtml((string) ($usuario['aldea'] ?? ''))}</div></td>
        <td><div class="label">Dirección</div><div class="value">{$this->escaparHtml((string) ($usuario['direccion'] ?? ''))}</div></td>
      </tr>
    </table>
  </div>

  <div class="summary">
    <table class="summary-table">
      <tr>
        <td style="width:33%"><div class="summary-label">Ahorro acumulado</div><div class="summary-value">Q {$this->formatearMonto($ahorroTotal)}</div></td>
        <td style="width:33%"><div class="summary-label">Consumo promedio</div><div class="summary-value">{$this->formatearMonto($consumoPromedio)} kWh</div></td>
        <td style="width:34%"><div class="summary-label">Meses analizados</div><div class="summary-value">{$mesesAnalizados}</div></td>
      </tr>
    </table>
  </div>

  <div class="chart-box">
    <div class="chart-title">Consumo mensual</div>
    {$lineChartHtml}
    <div class="chart-caption">La línea azul muestra la evolución del consumo por mes y permite identificar patrones de uso durante el año.</div>
  </div>

  <div class="chart-box">
    <div class="chart-title">Comparativo factura sin aporte vs con aporte</div>
    {$barsChartHtml}
    <div class="chart-caption">La barra gris representa el costo sin aporte y la barra verde el costo con aporte, evidenciando el beneficio del programa.</div>
  </div>

  <div class="page-break"></div>

  <div class="chart-box">
    <div class="chart-title">Detalle mensual <span class="badge">Resumen</span></div>
    <table class="table">
      <thead>
        <tr>
          <th>Mes</th>
          <th>Consumo</th>
          <th>Sin aporte</th>
          <th>Con aporte</th>
          <th>Ahorro</th>
        </tr>
      </thead>
      <tbody>
        {$detalleRows}
      </tbody>
    </table>
  </div>

  <div class="analysis">
    <strong>Análisis del ahorro anual:</strong><br/>
    Durante el año {$anio}, el ahorro acumulado estimado por la ayuda social de la Tarifa Social asciende a <strong>Q {$this->formatearMonto($ahorroTotal)}</strong>.
    Este valor representa la diferencia entre la factura sin aporte y la factura con aporte en los meses analizados.
  </div>
</div>
</body>
</html>
HTML;
    }

    private function generarGridLineas($marginTop, $marginBottom, $svgHeight, $marginLeft, $chartWidth, $isBar = false): string
    {
        $lines = '';
        for ($i = 1; $i < 4; $i++) {
            $y = $marginTop + (($svgHeight - $marginTop - $marginBottom) / 4) * $i;
            $lines .= '<line x1="' . round($marginLeft, 1) . '" y1="' . round($y, 1) . '" x2="' . round($marginLeft + $chartWidth, 1) . '" y2="' . round($y, 1) . '" stroke="#e0e0e0" stroke-width="1" stroke-dasharray="2,2"/>';
        }
        return $lines;
    }

    private function buildHistoryLineChartHtml(array $labels, array $consumos): string
    {
        // Validación extremadamente robusta
        if (!is_array($labels) || !is_array($consumos)) {
            return '<div style="font-size:10px; color:#687382;">Sin datos para graficar.</div>';
        }

        $numLabels = count($labels);
        $numConsumos = count($consumos);

        if ($numLabels === 0 || $numConsumos === 0) {
            return '<div style="font-size:10px; color:#687382;">Sin datos para graficar.</div>';
        }

        // Asegurar que ambas arrays tengan la misma cantidad de elementos
        $numMeses = min($numLabels, $numConsumos);
        
        if ($numMeses <= 0) {
            return '<div style="font-size:10px; color:#687382;">Sin datos para graficar.</div>';
        }

        $maxConsumo = max($consumos);
        if ($maxConsumo === 0) {
            $maxConsumo = 1;
        }

        $alturaTotal = 150;

        $html = '<table cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; margin:10px 0; border-left:2px solid #333; border-bottom:2px solid #333;">';

        // Fila 1: Valores de consumo y puntos
        $html .= '<tr>';
        foreach ($consumos as $idx => $valor) {
            $porcentaje = $valor / $maxConsumo;
            $alturaValor = $porcentaje * $alturaTotal;
            $espacioArriba = $alturaTotal - $alturaValor;

            $anchoPorcentaje = ($numMeses > 0) ? (100 / $numMeses) : 0;
            $html .= '<td style="width:' . $anchoPorcentaje . '%; height:' . $alturaTotal . 'px; text-align:center; vertical-align:bottom; padding-bottom:4px; background:linear-gradient(to bottom, #f9f9f9 0%, #fff 100%); border-right:1px solid #eee; position:relative;">';

            // Valor numérico arriba del punto
            $html .= '<div style="font-size:8px; color:#666; position:absolute; top:0; left:0; right:0; padding-top:2px; line-height:10px;">' . (int) $valor . ' kWh</div>';

            // Punto azul
            $html .= '<div style="width:6px; height:6px; background:#003366; border-radius:50%; margin:0 auto; margin-bottom:' . ($espacioArriba + 2) . 'px; position:relative; z-index:10;"></div>';

            $html .= '</td>';
        }
        $html .= '</tr>';

        // Fila 2: Etiquetas de meses
        $html .= '<tr>';
        foreach ($labels as $label) {
            $anchoPorcentaje = ($numMeses > 0) ? (100 / $numMeses) : 0;
            $html .= '<td style="width:' . $anchoPorcentaje . '%; text-align:center; padding-top:4px; font-size:8px; color:#666; border-right:1px solid #eee;">' . $this->escaparHtml((string) $label) . '</td>';
        }
        $html .= '</tr>';

        $html .= '</table>';

        return $html;
    }

    private function buildHistoryBarsChartHtml(array $labels, array $sinAporte, array $conAporte): string
    {
        // Validación extremadamente robusta
        if (!is_array($labels) || !is_array($sinAporte) || !is_array($conAporte)) {
            return '<div style="font-size:10px; color:#687382;">Sin datos para graficar.</div>';
        }

        $numLabels = count($labels);
        $numSinAporte = count($sinAporte);
        $numConAporte = count($conAporte);

        if ($numLabels === 0 || $numSinAporte === 0 || $numConAporte === 0) {
            return '<div style="font-size:10px; color:#687382;">Sin datos para graficar.</div>';
        }

        // Asegurar que todas las arrays tengan la misma cantidad de elementos
        $numMeses = min($numLabels, $numSinAporte, $numConAporte);

        if ($numMeses <= 0) {
            return '<div style="font-size:10px; color:#687382;">Sin datos para graficar.</div>';
        }

        $maxBar = max(1.0, max(array_merge($sinAporte, $conAporte)));

        $alturaMaxBarra = 120;

        $html = '<table cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; margin:10px 0; border-left:2px solid #333; border-bottom:2px solid #333;">';

        // Fila 1: Barras con montos
        $html .= '<tr>';
        foreach ($labels as $idx => $label) {
            $sin = (float) ($sinAporte[$idx] ?? 0.0);
            $con = (float) ($conAporte[$idx] ?? 0.0);

            $altoSin = ($sin / $maxBar) * $alturaMaxBarra;
            $altoCon = ($con / $maxBar) * $alturaMaxBarra;

            $anchoPorcentaje = ($numMeses > 0) ? (100 / $numMeses) : 0;
            $html .= '<td style="width:' . $anchoPorcentaje . '%; height:' . ($alturaMaxBarra + 40) . 'px; text-align:center; vertical-align:bottom; padding:8px 2px 4px 2px; background:linear-gradient(to bottom, #f9f9f9 0%, #fff 100%); border-right:1px solid #eee;">';

            // Montos arriba
            $html .= '<div style="font-size:7px; color:#333; margin-bottom:4px; height:12px;">';
            $html .= '<div style="font-size:6px; color:#666;">Q ' . (int) round($sin) . '</div>';
            $html .= '<div style="font-size:6px; color:#666;">Q ' . (int) round($con) . '</div>';
            $html .= '</div>';

            // Barras con esquinas redondeadas
            $html .= '<div style="display:inline-block; width:18px; height:' . (int) $altoSin . 'px; background:#8a8a8a; margin-right:2px; vertical-align:bottom; border-top-left-radius:4px; border-top-right-radius:4px;"></div>';
            $html .= '<div style="display:inline-block; width:18px; height:' . (int) $altoCon . 'px; background:#008f39; vertical-align:bottom; border-top-left-radius:4px; border-top-right-radius:4px;"></div>';

            $html .= '</td>';
        }
        $html .= '</tr>';

        // Fila 2: Etiquetas de meses
        $html .= '<tr>';
        foreach ($labels as $label) {
            $anchoPorcentaje = ($numMeses > 0) ? (100 / $numMeses) : 0;
            $html .= '<td style="width:' . $anchoPorcentaje . '%; text-align:center; padding-top:4px; font-size:8px; color:#666; border-right:1px solid #eee;">' . $this->escaparHtml((string) $label) . '</td>';
        }
        $html .= '</tr>';

        $html .= '</table>';

        // Leyenda
        $html .= '<div style="margin-top:8px; font-size:8px;">';
        $html .= '<span style="margin-right:16px;"><span style="display:inline-block; width:10px; height:10px; background:#8a8a8a; margin-right:3px; vertical-align:middle; border-top-left-radius:2px; border-top-right-radius:2px;"></span>Sin aporte</span>';
        $html .= '<span><span style="display:inline-block; width:10px; height:10px; background:#008f39; margin-right:3px; vertical-align:middle; border-top-left-radius:2px; border-top-right-radius:2px;"></span>Con aporte</span>';
        $html .= '</div>';

        return $html;
    }

    private function escaparHtml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function formatearMonto(float $valor): string
    {
        return number_format($valor, 2, '.', ',');
    }

    private function normalizarCorrelativo(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $correlativo = trim((string) $value);
        if (strlen($correlativo) > 30 || preg_match('/\A[0-9]{1,30}\z/', $correlativo) !== 1) {
            return null;
        }

        return $correlativo;
    }

    private function normalizarDistribuidoraId(mixed $value): ?int
    {
        if (! is_scalar($value)) {
            return null;
        }

        $rawId = trim((string) $value);
        if (preg_match('/\A[1-9][0-9]{0,8}\z/', $rawId) !== 1) {
            return null;
        }

        return (int) $rawId;
    }

    private function permiteConsultaPublica(string $bucket): bool
    {
        $ipAddress = (string) $this->request->getIPAddress();
        $key = 'ecoe-ts-' . $bucket . '-' . substr(hash('sha256', $ipAddress), 0, 32);

        return \Config\Services::throttler()->check(
            $key,
            self::ECOE_RATE_LIMIT_REQUESTS,
            self::ECOE_RATE_LIMIT_WINDOW_SECONDS
        );
    }

    private function conectarEcoe(string $database): mixed
    {
        if (! function_exists('sqlsrv_connect')) {
            throw new \RuntimeException('La extensión SQLSRV no está disponible.');
        }

        $config = config('Database')->ecoe;
        $hostname = trim((string) ($config['hostname'] ?? ''));
        $port = (int) ($config['port'] ?? 1433);
        if ($hostname === '' || $database === '') {
            throw new \RuntimeException('La conexión SQLSRV ECOE no está configurada.');
        }

        $connectionOptions = [
            'Database' => $database,
            'ConnectionPooling' => false,
            'CharacterSet' => 'UTF-8',
            'LoginTimeout' => self::ECOE_QUERY_TIMEOUT_SECONDS,
            'ReturnDatesAsStrings' => true,
            'Encrypt' => ! empty($config['encrypt']),
        ];
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        if ($username !== '' || $password !== '') {
            $connectionOptions['UID'] = $username;
            $connectionOptions['PWD'] = $password;
        }

        $serverName = $hostname . (str_contains($hostname, ',') ? '' : ', ' . $port);
        $connection = sqlsrv_connect($serverName, $connectionOptions);
        if ($connection === false) {
            throw new \RuntimeException($this->erroresSqlsrv());
        }

        return $connection;
    }

    private function erroresSqlsrv(): string
    {
        if (! function_exists('sqlsrv_errors')) {
            return 'Error SQLSRV no disponible.';
        }

        $errors = sqlsrv_errors();
        if (! is_array($errors)) {
            return 'Error SQLSRV sin detalle.';
        }

        return implode('; ', array_map(
            static fn (array $error): string => trim((string) ($error['message'] ?? 'Error SQLSRV')),
            $errors
        ));
    }

    /** @return array<int, array<string, mixed>> */
    private function ejecutarConsultaPreparadaEcoe(mixed $connection, string $sql, array $bindings, int $maxRows = self::ECOE_MAX_RESULT_ROWS): array
    {
        if (! function_exists('sqlsrv_prepare') || ! function_exists('sqlsrv_execute') || ! function_exists('sqlsrv_fetch_array')) {
            throw new \RuntimeException('La extensión SQLSRV no está disponible.');
        }

        if ($maxRows < 1) {
            throw new \InvalidArgumentException('El límite de filas SQL debe ser positivo.');
        }

        $statement = sqlsrv_prepare($connection, $sql, $bindings, [
            'QueryTimeout' => self::ECOE_QUERY_TIMEOUT_SECONDS,
            'Scrollable' => SQLSRV_CURSOR_FORWARD,
        ]);

        if ($statement === false) {
            throw new \RuntimeException($this->erroresSqlsrv());
        }

        try {
            if (! sqlsrv_execute($statement)) {
                throw new \RuntimeException($this->erroresSqlsrv());
            }

            $rows = [];
            while (($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) !== null) {
                if ($row === false) {
                    throw new \RuntimeException($this->erroresSqlsrv());
                }

                if (count($rows) >= $maxRows) {
                    throw new \RuntimeException('La consulta excedió el límite de filas permitido.');
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            sqlsrv_free_stmt($statement);
        }
    }

    private function consultarAniosHistorialEcoe(string $correlativo): array
    {
        $correlativo = $this->normalizarCorrelativo($correlativo) ?? '';
        if ($correlativo === '') {
            return ['ok' => false, 'anios' => [], 'error' => 'El correlativo no tiene un formato válido.'];
        }

        try {
            $connection = $this->conectarEcoe((string) env('database.ecoe.deocsa', 'FAC DEOCSA'));
            try {
                $rows = $this->ejecutarConsultaPreparadaEcoe(
                    $connection,
                    'EXEC sp_BuscarAniosConHistorialDeocsa @id_usuario = ?',
                    [$correlativo],
                    100
                );
            } finally {
                if (is_resource($connection)) {
                    sqlsrv_close($connection);
                }
            }

            $anios = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $anioValor = $this->obtenerValorHistorial($row, ['anio', 'year', 'ano', 'Anio', 'Year']);
                if ($anioValor === null || $anioValor === '') {
                    continue;
                }

                $anioTexto = trim((string) $anioValor);
                if (preg_match('/^\d{4}$/', $anioTexto)) {
                    $anios[] = $anioTexto;
                }
            }

            sort($anios, SORT_NUMERIC);
            $anios = array_values(array_unique($anios));

            return [
                'ok' => true,
                'anios' => $anios,
            ];
        } catch (Throwable $e) {
            log_message('error', 'ECOE años de historial fallido para referencia {referencia}: {message}', [
                'referencia' => substr(hash('sha256', $correlativo), 0, 12),
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'anios' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    private function consultarHistorialEcoe(string $correlativo, ?string $anio = null): array
    {
        $correlativo = $this->normalizarCorrelativo($correlativo) ?? '';
        $anio ??= (string) date('Y');
        if ($correlativo === '' || preg_match('/\A(?:19|20)[0-9]{2}\z/', $anio) !== 1) {
            return [
                'ok' => false,
                'anio' => $anio,
                'encontrado' => false,
                'periodos' => [],
                'mensaje' => 'El correlativo o año no tiene un formato válido.',
            ];
        }

        try {
            $connection = $this->conectarEcoe((string) env('database.ecoe.deocsa', 'FAC DEOCSA'));
            try {
                $rows = $this->ejecutarConsultaPreparadaEcoe(
                    $connection,
                    'EXEC sp_ObtenerHistorialPorAnioDeocsa @id_usuario = ?, @anio = ?',
                    [$correlativo, $anio],
                    self::ECOE_MAX_RESULT_ROWS
                );
            } finally {
                if (is_resource($connection)) {
                    sqlsrv_close($connection);
                }
            }

            $historial = $this->normalizarHistorialEcoe($rows, $correlativo);
            $periodos = $historial['periodos'] ?? [];

            return [
                'ok'                   => true,
                'anio'                 => $anio,
                'encontrado'           => $periodos !== [],
                'periodos'             => $periodos,
                'usuario'              => $historial['usuario'] ?? [],
                'resumen'              => $historial['resumen'] ?? [],
                'mensaje'              => 'Consulta de historial ejecutada correctamente.',
                'pdf_download_url'    => $periodos !== [] ? site_url('api/ecoe/ts/historial-pdf?correlativo=' . rawurlencode($correlativo) . '&anio=' . rawurlencode($anio)) : null,
            ];
        } catch (Throwable $e) {
            log_message('error', 'ECOE historial externo fallido para referencia {referencia}: {message}', [
                'referencia' => substr(hash('sha256', $correlativo), 0, 12),
                'message'    => $e->getMessage(),
            ]);

            return [
                'ok'      => false,
                'anio'    => $anio,
                'encontrado' => false,
                'rows'    => [],
                'mensaje' => 'No fue posible consultar el historial externo de ECOE.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Resuelve la base externa desde un catalogo interno. Nunca se usa el
     * nombre recibido del navegador como identificador SQL.
     */
    private function resolverBaseEcoe(string $distribuidora): ?string
    {
        $mapa = [
            'DEOCSA' => (string) env('database.ecoe.deocsa', 'FAC DEOCSA'),
            'DEORSA' => (string) env('database.ecoe.deorsa', 'FAC DEORSA'),
            'EEGSA'  => (string) env('database.ecoe.eegsa', 'FAC EEGSA'),
        ];

        $nombre = strtoupper(trim($distribuidora));
        $base = $mapa[$nombre] ?? null;

        return $base !== null && preg_match('/^[A-Za-z0-9 _-]{1,128}$/', $base) === 1
            ? $base
            : null;
    }

    /** @return array<string, array<int, string>> */
    private function obtenerColumnasTablasEcoe(mixed $connection, array $particiones): array
    {
        if ($particiones === []) {
            return [];
        }

        $filters = [];
        $bindings = [];
        foreach ($particiones as $particion) {
            $filters[] = '(s.name = ? AND t.name = ?)';
            $bindings[] = (string) $particion['esquema'];
            $bindings[] = (string) $particion['tabla'];
        }

        $sql = 'SELECT s.name AS schema_name, t.name AS table_name, c.name AS column_name
             FROM sys.tables AS t
             INNER JOIN sys.schemas AS s ON s.schema_id = t.schema_id
             INNER JOIN sys.columns AS c ON c.object_id = t.object_id
             WHERE ' . implode(' OR ', $filters) . '
             ORDER BY s.name, t.name, c.column_id';
        $rows = $this->ejecutarConsultaPreparadaEcoe($connection, $sql, $bindings, max(1000, count($particiones) * 500));

        $columnsByTable = [];
        foreach ($rows as $row) {
            $key = (string) ($row['schema_name'] ?? '') . "\0" . (string) ($row['table_name'] ?? '');
            $column = (string) ($row['column_name'] ?? '');
            if ($column !== '') {
                $columnsByTable[$key][] = $column;
            }
        }

        return $columnsByTable;
    }

    private function encontrarColumnaEcoe(array $columnas, array $candidatas): ?string
    {
        $porNombre = [];
        foreach ($columnas as $columna) {
            $porNombre[strtolower($columna)] = $columna;
        }

        foreach ($candidatas as $candidata) {
            if (isset($porNombre[strtolower($candidata)])) {
                return $porNombre[strtolower($candidata)];
            }
        }

        return null;
    }

    private function identificadorSqlEcoe(string $identificador): string
    {
        return '[' . str_replace(']', ']]', $identificador) . ']';
    }

    private function expresionAgregadaEcoe(array $columnas, array $candidatas, string $alias, string $aggregate = 'SUM'): string
    {
        if (! in_array($aggregate, ['SUM', 'MAX'], true)) {
            throw new \InvalidArgumentException('Agregado SQL no permitido.');
        }

        $columna = $this->encontrarColumnaEcoe($columnas, $candidatas);
        if ($columna === null) {
            return 'CAST(0 AS decimal(38,4)) AS ' . $this->identificadorSqlEcoe($alias);
        }

        $expression = 'TRY_CONVERT(decimal(19,4), ' . $this->identificadorSqlEcoe($columna) . ')';

        return $aggregate . '(' . $expression . ') AS ' . $this->identificadorSqlEcoe($alias);
    }

    /** @return array<int, array{esquema: string, tabla: string, anio: int, mes: int}> */
    private function obtenerParticionesActivasEcoe($db, array $periodosSolicitados = []): array
    {
        $rows = $this->ejecutarConsultaPreparadaEcoe(
            $db,
            'SELECT TABLE_SCHEMA AS schema_name, TABLE_NAME AS table_name
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_TYPE = ? AND TABLE_NAME LIKE ?
             ORDER BY TABLE_SCHEMA, TABLE_NAME',
            ['BASE TABLE', '%20[0-9][0-9]%'],
            1000
        );

        $particiones = [];
        $periodosPermitidos = [];
        foreach ($periodosSolicitados as $periodo) {
            $periodosPermitidos[sprintf('%04d-%02d', (int) ($periodo['anio'] ?? 0), (int) ($periodo['mes'] ?? 0))] = true;
        }
        $tablasDisponibles = [];
        $nomenclaturas = [];
        foreach ($rows as $row) {
            $esquema = (string) ($row['schema_name'] ?? '');
            $tabla = (string) ($row['table_name'] ?? '');
            if ($esquema !== '' && $tabla !== '') {
                $tablasDisponibles[] = '[' . $esquema . '].[' . $tabla . ']';
            }

            // Detecta cualquier nomenclatura que contenga año y mes:
            // DC 2026 07, DR_2026_07, FACTURA-2026-07, etc.
            if (preg_match('/(?<!\d)(20\d{2})\D+(0[1-9]|1[0-2])(?!\d)/', $tabla, $matches) !== 1) {
                continue;
            }

            $periodoKey = $matches[1] . '-' . $matches[2];
            if ($periodosSolicitados !== [] && ! isset($periodosPermitidos[$periodoKey])) {
                continue;
            }

            $nomenclatura = strtoupper((string) preg_replace('/\s*20\d{2}.*$/i', '', $tabla));
            $nomenclaturas[$nomenclatura !== '' ? $nomenclatura : '(sin prefijo)'] = true;

            $particiones[] = [
                'esquema' => $esquema,
                'tabla' => $tabla,
                'anio' => (int) $matches[1],
                'mes' => (int) $matches[2],
            ];
        }

        usort($particiones, static function (array $left, array $right): int {
            return [$right['anio'], $right['mes']] <=> [$left['anio'], $left['mes']];
        });

        if ($periodosSolicitados === []) {
            $particiones = array_slice($particiones, 0, 3);
        }
        usort($particiones, static function (array $left, array $right): int {
            return [$left['anio'], $left['mes']] <=> [$right['anio'], $right['mes']];
        });

        log_message('debug', 'ECOE TS: catalogo INFORMATION_SCHEMA.TABLES detectado. tablas_disponibles={tablas}.', [
            'tablas' => implode(', ', $tablasDisponibles) ?: '(ninguna)',
        ]);
        log_message('debug', 'ECOE TS: nomenclaturas mensuales detectadas. nomenclaturas={nomenclaturas}, tablas_mensuales_validas={cantidad}.', [
            'nomenclaturas' => implode(', ', array_keys($nomenclaturas)) ?: '(ninguna)',
            'cantidad' => count($particiones),
        ]);

        return $particiones;
    }

    /**
     * Lee las particiones mensuales disponibles y devuelve el contrato que
     * consume la vista de graficas. El filtro por ID queda parametrizado para
     * permitir que SQL Server use el indice de cada particion.
     *
    * @return array{meses: array<int, array<string, mixed>>, precio_kwh_social: float, hay_registros: bool}
     */
    private function consultarMesesEcoe(string $distribuidora, string $correlativo, int $anio, array $periodosSolicitados = []): array
    {
        $base = $this->resolverBaseEcoe($distribuidora);
        if ($base === null) {
            log_message('error', 'ECOE TS: no se pudo resolver la base para distribuidora={distribuidora}.', [
                'distribuidora' => $distribuidora,
            ]);
            throw new \RuntimeException('La distribuidora no tiene una base externa configurada.');
        }

        $periodosCache = $periodosSolicitados;
        usort($periodosCache, static fn (array $left, array $right): int =>
            [(int) ($left['anio'] ?? 0), (int) ($left['mes'] ?? 0)] <=> [(int) ($right['anio'] ?? 0), (int) ($right['mes'] ?? 0)]
        );
        $cacheKey = 'ecoe_ts_months_' . hash('sha256', json_encode([
            strtoupper($base),
            $correlativo,
            $anio,
            $periodosCache,
        ], JSON_UNESCAPED_SLASHES));
        $cache = null;
        try {
            $cache = \Config\Services::cache();
            $cached = $cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (Throwable $e) {
            log_message('warning', 'ECOE TS: lectura de caché omitida: {message}.', ['message' => $e->getMessage()]);
        }

        $config = config('Database')->ecoe;

        log_message('debug', 'ECOE TS: iniciando consulta externa. base={base}, distribuidora={distribuidora}, referencia={referencia}, anio={anio}.', [
            'base' => $base,
            'distribuidora' => $distribuidora,
            'referencia' => substr(hash('sha256', $correlativo), 0, 12),
            'anio' => $anio,
        ]);

        $db = null;
        try {
            $db = $this->conectarEcoe($base);
            $catalogoRows = $this->ejecutarConsultaPreparadaEcoe($db, 'SELECT DB_NAME() AS database_name', [], 1);
            $catalogo = $catalogoRows[0] ?? [];
            $baseActiva = (string) ($catalogo['database_name'] ?? '');
            log_message('debug', 'ECOE TS: contexto SQL Server confirmado. base_solicitada={solicitada}, base_activa={activa}.', [
                'solicitada' => $base,
                'activa' => $baseActiva,
            ]);

            if (strcasecmp($baseActiva, $base) !== 0) {
                throw new \RuntimeException('SQL Server no cambio al catalogo solicitado: ' . $base . '.');
            }
        } catch (Throwable $e) {
            if (is_resource($db)) {
                sqlsrv_close($db);
            }

            log_message('critical', 'ECOE TS: error de conexion SQL Server. base={base}, driver={driver}, host={host}, mensaje={message}.', [
                'base' => $base,
                'driver' => (string) ($config['DBDriver'] ?? ''),
                'host' => (string) ($config['hostname'] ?? ''),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        $particiones = $this->obtenerParticionesActivasEcoe($db, $periodosSolicitados);

        log_message('debug', 'ECOE TS: particiones candidatas. base={base}, tablas={tablas}.', [
            'base' => $base,
            'tablas' => implode(', ', array_map(static fn (array $particion): string => '[' . $particion['esquema'] . '].[' . $particion['tabla'] . ']', $particiones)),
        ]);

        if ($particiones === []) {
            log_message('warning', 'ECOE TS: no existen particiones mensuales activas en la base. base={base}, anio_solicitado={anio}.', [
                'base' => $base,
                'anio' => $anio,
            ]);
            if ($periodosSolicitados === []) {
                if (is_resource($db)) {
                    sqlsrv_close($db);
                }
                throw new \RuntimeException('La base externa no contiene tablas mensuales compatibles.');
            }
        }

        $selects = [];
        $bindings = [];
        $meses = [];
        $hayRegistrosExternos = false;
        $nombresMes = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];

        foreach ($periodosSolicitados as $periodo) {
            $periodYear = (int) ($periodo['anio'] ?? 0);
            $periodMonth = (int) ($periodo['mes'] ?? 0);
            if ($periodYear < 2000 || $periodMonth < 1 || $periodMonth > 12) {
                continue;
            }

            $periodKey = sprintf('%04d-%02d', $periodYear, $periodMonth);
            $meses[$periodKey] = [
                '_periodo_key' => $periodKey,
                '_mes_num' => $periodMonth,
                '_tabla_disponible' => false,
                '_registro_encontrado' => false,
                'mes' => $nombresMes[$periodMonth],
                'consumo_kwh' => 0.0,
                'costo_tarifa_plena' => 0.0,
                'costo_tarifa_social' => 0.0,
                'costo_tarifa_no_social' => 0.0,
                'aporte_inde' => 0.0,
                'beneficio_tarifa_social' => 0.0,
                'ahorro' => 0.0,
                '_precio' => 0.0,
            ];
        }

        $columnasPorTabla = $this->obtenerColumnasTablasEcoe($db, $particiones);
        foreach ($particiones as $particion) {
            $partitionKey = $particion['esquema'] . "\0" . $particion['tabla'];
            $columnas = $columnasPorTabla[$partitionKey] ?? [];

            if ($columnas === []) {
                log_message('warning', 'ECOE TS: particion inexistente o sin columnas. base={base}, tabla={tabla}.', [
                    'base' => $base,
                    'tabla' => '[' . $particion['esquema'] . '].[' . $particion['tabla'] . ']',
                ]);
                continue;
            }

            $idColumna = $this->encontrarColumnaEcoe($columnas, ['id_usuario', 'nis', 'correlativo', 'id']);
            log_message('debug', 'ECOE TS: columnas inspeccionadas. base={base}, tabla={tabla}, columnas={columnas}, columna_busqueda={columna}, referencia={referencia}.', [
                'base' => $base,
                'tabla' => '[' . $particion['esquema'] . '].[' . $particion['tabla'] . ']',
                'columnas' => implode(', ', $columnas),
                'columna' => $idColumna ?? '(ninguna)',
                'referencia' => substr(hash('sha256', $correlativo), 0, 12),
            ]);

            $mapeoColumnas = [
                'consumo_kwh' => $this->encontrarColumnaEcoe($columnas, ['consumo_kwh', 'csmo_energia_total', 'consumo_energia_total', 'consumo', 'kwh']),
                'costo_tarifa_plena' => $this->encontrarColumnaEcoe($columnas, ['costo_tarifa_plena', 'factura_sin_aporte', 'imp_tns', 'importe_tns', 'sin_aporte', 'monto_sin_aporte', 'total_sin_aporte']),
                'costo_tarifa_social' => $this->encontrarColumnaEcoe($columnas, ['costo_tarifa_social', 'factura_con_aporte', 'imp_ts', 'importe_ts', 'con_aporte', 'monto_con_aporte', 'total_con_aporte']),
                'costo_tarifa_no_social' => $this->encontrarColumnaEcoe($columnas, ['costo_tarifa_no_social', 'factura_sin_aporte', 'imp_tns', 'importe_tns', 'sin_aporte', 'monto_sin_aporte']),
                'aporte_inde' => $this->encontrarColumnaEcoe($columnas, ['aporte_inde', 'aporte a tarifa social Inde', 'aporte_solidaridad_inde', 'aporte_social', 'aporte']),
                'beneficio_tarifa_social' => $this->encontrarColumnaEcoe($columnas, ['beneficio_tarifa_social', 'ahorro_tarifa_social']),
                'precio_kwh_social' => $this->encontrarColumnaEcoe($columnas, ['precio_kwh_social', 'precio_social', 'tarifa_social']),
            ];
            log_message('debug', 'ECOE TS: mapeo fisico a JSON. base={base}, tabla={tabla}, mapeo={mapeo}.', [
                'base' => $base,
                'tabla' => '[' . $particion['esquema'] . '].[' . $particion['tabla'] . ']',
                'mapeo' => json_encode($mapeoColumnas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            if ($idColumna === null) {
                log_message('warning', 'ECOE: la tabla {tabla} no tiene columna de usuario.', ['tabla' => $particion['tabla']]);
                continue;
            }

            $tablaSql = $this->identificadorSqlEcoe($base) . '.'
                . $this->identificadorSqlEcoe($particion['esquema']) . '.'
                . $this->identificadorSqlEcoe($particion['tabla']);
            $aggregateSql = 'SELECT COUNT_BIG(*) AS [registros_encontrados], '
                . $this->expresionAgregadaEcoe($columnas, ['consumo_kwh', 'csmo_energia_total', 'consumo_energia_total', 'consumo', 'kwh'], 'consumo_kwh') . ', '
                . $this->expresionAgregadaEcoe($columnas, ['costo_tarifa_plena', 'factura_sin_aporte', 'imp_tns', 'importe_tns', 'sin_aporte', 'monto_sin_aporte', 'total_sin_aporte'], 'costo_tarifa_plena') . ', '
                . $this->expresionAgregadaEcoe($columnas, ['costo_tarifa_social', 'factura_con_aporte', 'imp_ts', 'importe_ts', 'con_aporte', 'monto_con_aporte', 'total_con_aporte'], 'costo_tarifa_social') . ', '
                . $this->expresionAgregadaEcoe($columnas, ['costo_tarifa_no_social', 'factura_sin_aporte', 'imp_tns', 'importe_tns', 'sin_aporte', 'monto_sin_aporte'], 'costo_tarifa_no_social') . ', '
                . $this->expresionAgregadaEcoe($columnas, ['aporte_inde', 'aporte a tarifa social Inde', 'aporte_solidaridad_inde', 'aporte_social', 'aporte'], 'aporte_inde') . ', '
                . $this->expresionAgregadaEcoe($columnas, ['beneficio_tarifa_social', 'ahorro_tarifa_social'], 'beneficio_tarifa_social') . ', '
                . $this->expresionAgregadaEcoe($columnas, ['precio_kwh_social', 'precio_social', 'tarifa_social'], 'precio_kwh_social', 'MAX') . '
                FROM ' . $tablaSql . ' WHERE ' . $this->identificadorSqlEcoe($idColumna) . ' = ?';
            $selects[] = 'SELECT ? AS anio_num, ? AS mes_num, [aggregate_rows].* FROM (' . $aggregateSql . ') AS [aggregate_rows]';
            $bindings[] = $particion['anio'];
            $bindings[] = $particion['mes'];
            $bindings[] = $correlativo;

            log_message('debug', 'ECOE TS: SELECT agregado preparado. base={base}, tabla={tabla}, columna_busqueda={columna}, sql={sql}, binding_count={binding_count}.', [
                'base' => $base,
                'tabla' => '[' . $particion['esquema'] . '].[' . $particion['tabla'] . ']',
                'columna' => $idColumna,
                'sql' => end($selects),
                'binding_count' => 3,
            ]);
            $periodKey = sprintf('%04d-%02d', $particion['anio'], $particion['mes']);
            if (! isset($meses[$periodKey])) {
                $meses[$periodKey] = [
                    '_periodo_key' => $periodKey,
                    '_mes_num' => $particion['mes'],
                    '_registro_encontrado' => false,
                    'mes' => $nombresMes[$particion['mes']],
                    'consumo_kwh' => 0.0,
                    'costo_tarifa_plena' => 0.0,
                    'costo_tarifa_social' => 0.0,
                    'costo_tarifa_no_social' => 0.0,
                    'aporte_inde' => 0.0,
                    'beneficio_tarifa_social' => 0.0,
                    'ahorro' => 0.0,
                    '_precio' => 0.0,
                ];
            }
            $meses[$periodKey]['_tabla_disponible'] = true;
        }

        if ($selects !== []) {
            $sql = implode(' UNION ALL ', $selects);
            log_message('debug', 'ECOE TS: UNION ALL agregado. base={base}, tablas_consultadas={cantidad}, binding_count={binding_count}, sql={sql}.', [
                'base' => $base,
                'cantidad' => count($selects),
                'binding_count' => count($bindings),
                'sql' => $sql,
            ]);

            try {
                $filas = $this->ejecutarConsultaPreparadaEcoe($db, $sql, $bindings, count($selects));
            } catch (Throwable $e) {
                log_message('error', 'ECOE TS: error ejecutando UNION ALL agregado. base={base}, tablas={tablas}, mensaje={message}.', [
                    'base' => $base,
                    'tablas' => count($selects),
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }

            log_message('debug', 'ECOE TS: consulta completada. base={base}, filas={filas}, resultado_por_mes={resultado}.', [
                'base' => $base,
                'filas' => count($filas),
                'resultado' => json_encode($filas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            foreach ($filas as $row) {
                $periodKey = sprintf('%04d-%02d', (int) ($row['anio_num'] ?? 0), (int) ($row['mes_num'] ?? 0));
                if (! isset($meses[$periodKey])) {
                    continue;
                }

                if ((int) ($row['registros_encontrados'] ?? 0) <= 0) {
                    continue;
                }

                $hayRegistrosExternos = true;
                $meses[$periodKey]['_registro_encontrado'] = true;
                foreach (['consumo_kwh', 'costo_tarifa_plena', 'costo_tarifa_social', 'costo_tarifa_no_social', 'aporte_inde', 'beneficio_tarifa_social'] as $campo) {
                    $meses[$periodKey][$campo] += (float) ($row[$campo] ?? 0);
                }
                $meses[$periodKey]['_precio'] = max($meses[$periodKey]['_precio'], (float) ($row['precio_kwh_social'] ?? 0));
            }
        }

        foreach ($meses as &$mes) {
            if ($mes['costo_tarifa_plena'] == 0.0 && $mes['costo_tarifa_no_social'] != 0.0) {
                $mes['costo_tarifa_plena'] = $mes['costo_tarifa_no_social'];
            }

            if ($mes['costo_tarifa_no_social'] == 0.0) {
                $mes['costo_tarifa_no_social'] = $mes['costo_tarifa_plena'];
            }

            // La diferencia entre tarifa plena y tarifa social es la fuente
            // de verdad del beneficio, aunque exista una columna adicional.
            $mes['beneficio_tarifa_social'] = max(
                0.0,
                (float) $mes['costo_tarifa_plena'] - (float) $mes['costo_tarifa_social']
            );

            if ($mes['_precio'] == 0.0 && $mes['consumo_kwh'] > 0.0 && $mes['costo_tarifa_social'] > 0.0) {
                $mes['_precio'] = $mes['costo_tarifa_social'] / $mes['consumo_kwh'];
            }
            $mes['ahorro'] = $mes['beneficio_tarifa_social'] + abs($mes['aporte_inde']);
            unset($mes['_precio']);
        }
        unset($mes);

        $precioKwhSocial = (float) max(array_map(
            static fn (array $mes): float => $mes['consumo_kwh'] > 0.0
                ? (float) $mes['costo_tarifa_social'] / (float) $mes['consumo_kwh']
                : 0.0,
            $meses ?: [['consumo_kwh' => 0.0, 'costo_tarifa_social' => 0.0]]
        ));

        $result = [
            'meses' => array_values($meses),
            'precio_kwh_social' => $precioKwhSocial,
            'hay_registros' => $hayRegistrosExternos,
        ];
        if ($cache !== null) {
            try {
                $cache->save($cacheKey, $result, 20);
            } catch (Throwable $e) {
                log_message('warning', 'ECOE TS: escritura de caché omitida: {message}.', ['message' => $e->getMessage()]);
            }
        }
        if (is_resource($db)) {
            sqlsrv_close($db);
        }

        return $result;
    }

    /** @return array<string, array{tabla_disponible: bool, registro_encontrado: bool}> */
    private function consultarDisponibilidadMesesEcoe(string $distribuidora, string $correlativo, array $periodos): array
    {
        $base = $this->resolverBaseEcoe($distribuidora);
        if ($base === null) {
            throw new \RuntimeException('La distribuidora no tiene una base externa configurada.');
        }

        $periodos = array_values(array_filter($periodos, static fn (array $period): bool =>
            (int) ($period['anio'] ?? 0) >= 2000
            && (int) ($period['mes'] ?? 0) >= 1
            && (int) ($period['mes'] ?? 0) <= 12
        ));
        $cacheKey = 'ecoe_ts_availability_' . hash('sha256', json_encode([
            strtoupper($base),
            $correlativo,
            $periodos,
        ], JSON_UNESCAPED_SLASHES));

        $cache = null;
        try {
            $cache = \Config\Services::cache();
            $cached = $cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (Throwable $e) {
            log_message('warning', 'ECOE TS: lectura de caché de disponibilidad omitida: {message}.', ['message' => $e->getMessage()]);
        }

        $periodStatus = [];
        foreach ($periodos as $period) {
            $periodKey = sprintf('%04d-%02d', (int) $period['anio'], (int) $period['mes']);
            $periodStatus[$periodKey] = ['tabla_disponible' => false, 'registro_encontrado' => false];
        }

        $connection = $this->conectarEcoe($base);
        try {
            $particiones = $this->obtenerParticionesActivasEcoe($connection, $periodos);
            $columnsByTable = $this->obtenerColumnasTablasEcoe($connection, $particiones);
            $queries = [];
            $bindings = [];

            foreach ($particiones as $partition) {
                $partitionKey = $partition['esquema'] . "\0" . $partition['tabla'];
                $columns = $columnsByTable[$partitionKey] ?? [];
                $idColumn = $this->encontrarColumnaEcoe($columns, ['id_usuario', 'nis', 'correlativo', 'id']);
                if ($idColumn === null) {
                    continue;
                }

                $periodKey = sprintf('%04d-%02d', $partition['anio'], $partition['mes']);
                $periodStatus[$periodKey]['tabla_disponible'] = true;

                $table = $this->identificadorSqlEcoe($base) . '.'
                    . $this->identificadorSqlEcoe($partition['esquema']) . '.'
                    . $this->identificadorSqlEcoe($partition['tabla']);
                $queries[] = 'SELECT ? AS anio_num, ? AS mes_num WHERE EXISTS '
                    . '(SELECT TOP (1) 1 FROM ' . $table . ' WHERE '
                    . $this->identificadorSqlEcoe($idColumn) . ' = ?)';
                $bindings[] = $partition['anio'];
                $bindings[] = $partition['mes'];
                $bindings[] = $correlativo;
            }

            if ($queries !== []) {
                $rows = $this->ejecutarConsultaPreparadaEcoe($connection, implode(' UNION ALL ', $queries), $bindings, count($queries));
                foreach ($rows as $row) {
                    $periodKey = sprintf('%04d-%02d', (int) ($row['anio_num'] ?? 0), (int) ($row['mes_num'] ?? 0));
                    if (isset($periodStatus[$periodKey])) {
                        $periodStatus[$periodKey]['registro_encontrado'] = true;
                    }
                }
            }
        } finally {
            if (is_resource($connection)) {
                sqlsrv_close($connection);
            }
        }

        if ($cache !== null) {
            try {
                $cache->save($cacheKey, $periodStatus, 60);
            } catch (Throwable $e) {
                log_message('warning', 'ECOE TS: escritura de caché de disponibilidad omitida: {message}.', ['message' => $e->getMessage()]);
            }
        }

        return $periodStatus;
    }

    /** @return array<string, array{periodos: array<int, array{anio: int, mes: int}>}> */
    private function periodosReporteDisponibles(): array
    {
        $currentMonth = new \DateTimeImmutable('first day of this month');
        $year = (int) $currentMonth->format('Y');
        $ranges = [];

        foreach ([1, 4, 7, 10] as $quarterIndex => $startMonth) {
            $periods = [];
            for ($offset = 0; $offset < 3; $offset++) {
                $period = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $startMonth));
                $period = $period->modify('+' . $offset . ' months');
                $periods[] = ['anio' => (int) $period->format('Y'), 'mes' => (int) $period->format('n')];
            }
            $ranges['q' . ($quarterIndex + 1)] = ['periodos' => $periods];
        }

        $sixMonthStart = $currentMonth->modify('-5 months');
        $sixMonthPeriods = [];
        for ($offset = 0; $offset < 6; $offset++) {
            $period = $sixMonthStart->modify('+' . $offset . ' months');
            $sixMonthPeriods[] = ['anio' => (int) $period->format('Y'), 'mes' => (int) $period->format('n')];
        }
        $ranges['last6'] = ['periodos' => $sixMonthPeriods];

        $yearPeriods = [];
        for ($month = 1; $month <= 12; $month++) {
            $yearPeriods[] = ['anio' => $year, 'mes' => $month];
        }
        $ranges['year'] = ['periodos' => $yearPeriods];

        return $ranges;
    }

    private function obtenerConsultaReporte(string $rateLimitBucket): array|\CodeIgniter\HTTP\ResponseInterface
    {
        $distribuidoraId = $this->normalizarDistribuidoraId($this->request->getPost('distribuidora_id'));
        $correlativo = $this->normalizarCorrelativo($this->request->getPost('correlativo'));

        if ($distribuidoraId === null || $correlativo === null) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'El NIS/correlativo o la distribuidora no tiene un formato válido.']], 422);
        }

        if (! $this->permiteConsultaPublica('lookup')) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Demasiadas consultas. Espera un momento e inténtalo de nuevo.']], 429);
        }

        $distribuidora = $this->distribuidoraModel->find($distribuidoraId);
        if (! $distribuidora || (int) ($distribuidora['status'] ?? 0) !== 1) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Distribuidora no encontrada.']], 404);
        }

        return [
            'distribuidora_id' => $distribuidoraId,
            'distribuidora' => $distribuidora,
            'correlativo' => $correlativo,
        ];
    }

    /** POST api/ecoe/ts/reporte-disponibilidad */
    public function apiDisponibilidadReporte(): mixed
    {
        $consulta = $this->obtenerConsultaReporte('report-availability');
        if (! is_array($consulta)) {
            return $consulta;
        }

        $ranges = $this->periodosReporteDisponibles();
        $requestedByKey = [];
        $currentPeriodKey = date('Y-m');
        foreach ($ranges as $range) {
            foreach ($range['periodos'] as $period) {
                $periodKey = sprintf('%04d-%02d', $period['anio'], $period['mes']);
                if ($periodKey <= $currentPeriodKey) {
                    $requestedByKey[$periodKey] = $period;
                }
            }
        }

        try {
            $periodStatus = $this->consultarDisponibilidadMesesEcoe(
                (string) ($consulta['distribuidora']['nombre'] ?? ''),
                $consulta['correlativo'],
                array_values($requestedByKey)
            );
        } catch (Throwable $e) {
            log_message('error', 'ECOE TS: disponibilidad de reporte fallida. distribuidora={distribuidora}, referencia={referencia}, mensaje={message}.', [
                'distribuidora' => (string) ($consulta['distribuidora']['nombre'] ?? ''),
                'referencia' => substr(hash('sha256', $consulta['correlativo']), 0, 12),
                'message' => $e->getMessage(),
            ]);
            $unavailable = [];
            foreach ($ranges as $key => $_range) {
                $unavailable[$key] = [
                    'available' => false,
                    'tables_available' => false,
                    'has_data' => false,
                    'validation_incomplete' => true,
                ];
            }

            return $this->encryptedJsonResponse([
                'ok' => true,
                'data' => [
                    'periods' => $unavailable,
                    'validation_incomplete' => true,
                ],
            ]);
        }

        $availability = [];
        foreach ($ranges as $key => $range) {
            $tablesAvailable = true;
            $hasData = false;
            $containsFutureMonths = false;
            foreach ($range['periodos'] as $period) {
                $periodKey = sprintf('%04d-%02d', $period['anio'], $period['mes']);
                if ($periodKey > $currentPeriodKey) {
                    $containsFutureMonths = true;
                    $tablesAvailable = false;
                    continue;
                }

                $month = $periodStatus[$periodKey] ?? [];
                $tablesAvailable = $tablesAvailable && ! empty($month['tabla_disponible']);
                $hasData = $hasData || ! empty($month['registro_encontrado']);
            }

            $availability[$key] = [
                'available' => $tablesAvailable && $hasData && ! $containsFutureMonths,
                'tables_available' => $tablesAvailable,
                'has_data' => $hasData,
            ];
        }

        return $this->encryptedJsonResponse(['ok' => true, 'data' => ['periods' => $availability]]);
    }

    /** POST api/ecoe/ts/reporte-periodo */
    public function apiReportePeriodo(): mixed
    {
        $consulta = $this->obtenerConsultaReporte('report-period');
        if (! is_array($consulta)) {
            return $consulta;
        }

        $rawRangeKey = $this->request->getPost('periodo');
        $rangeKey = is_scalar($rawRangeKey) ? trim((string) $rawRangeKey) : '';
        $ranges = $this->periodosReporteDisponibles();
        if (! isset($ranges[$rangeKey])) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'El período seleccionado no es válido.']], 422);
        }

        try {
            $history = $this->consultarMesesEcoe(
                (string) ($consulta['distribuidora']['nombre'] ?? ''),
                $consulta['correlativo'],
                (int) date('Y'),
                $ranges[$rangeKey]['periodos']
            );
        } catch (Throwable $e) {
            log_message('error', 'ECOE TS: reporte por período fallido. distribuidora={distribuidora}, referencia={referencia}, periodo={periodo}, mensaje={message}.', [
                'distribuidora' => (string) ($consulta['distribuidora']['nombre'] ?? ''),
                'referencia' => substr(hash('sha256', $consulta['correlativo']), 0, 12),
                'periodo' => $rangeKey,
                'message' => $e->getMessage(),
            ]);
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'No fue posible consultar el período seleccionado.']], 503);
        }

        $tablesAvailable = count(array_filter($history['meses'], static fn (array $month): bool => ! empty($month['_tabla_disponible']))) === count($ranges[$rangeKey]['periodos']);
        if (! $tablesAvailable || empty($history['hay_registros'])) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'No hay información completa disponible para este período.']], 422);
        }

        $months = [];
        foreach ($history['meses'] as $month) {
            $month['ahorro'] = (float) $month['beneficio_tarifa_social'] + abs((float) $month['aporte_inde']);
            unset($month['_periodo_key'], $month['_mes_num'], $month['_tabla_disponible'], $month['_registro_encontrado'], $month['_precio']);
            $months[] = $month;
        }

        return $this->encryptedJsonResponse([
            'ok' => true,
            'data' => [
                'nis' => $consulta['correlativo'],
                'distribuidora' => (string) ($consulta['distribuidora']['nombre'] ?? ''),
                'periodo' => $rangeKey,
                'meses' => $months,
                'beneficio_tarifa_social_total' => array_sum(array_column($months, 'beneficio_tarifa_social')),
                'aporte_total' => array_sum(array_map(static fn (array $month): float => abs((float) $month['aporte_inde']), $months)),
                'ahorro_total' => array_sum(array_column($months, 'ahorro')),
            ],
        ]);
    }

    /**
     * POST api/ecoe/ts/consultar-nis
     * Body: distribuidora_id, correlativo
     */
    public function apiConsultarNis(): mixed
    {
        $rawDistributorId = $this->request->getPost('distribuidora_id');
        $rawCorrelativo = $this->request->getPost('correlativo');
        $distribuidoraId = $this->normalizarDistribuidoraId($rawDistributorId);
        $correlativo = $this->normalizarCorrelativo($rawCorrelativo);

        log_message('debug', 'ECOE TS: solicitud recibida. distribuidora_id={distribuidora_id}, correlativo_referencia={referencia}, longitud_correlativo={longitud}.', [
            'distribuidora_id' => $distribuidoraId ?? 0,
            'referencia' => $correlativo !== null ? substr(hash('sha256', $correlativo), 0, 12) : 'invalida',
            'longitud' => is_scalar($rawCorrelativo) ? strlen(trim((string) $rawCorrelativo)) : 0,
        ]);

        if ($distribuidoraId === null || $correlativo === null) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'El NIS/correlativo o la distribuidora no tiene un formato válido.']], 422);
        }

        if (! $this->permiteConsultaPublica('lookup')) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Demasiadas consultas. Espera un momento e inténtalo de nuevo.']], 429);
        }

        $distribuidora = $this->distribuidoraModel->find($distribuidoraId);
        if (! $distribuidora || (int) ($distribuidora['status'] ?? 0) !== 1) {
            log_message('warning', 'ECOE TS: distribuidora no encontrada/inactiva. distribuidora_id={distribuidora_id}.', [
                'distribuidora_id' => $distribuidoraId,
            ]);
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Distribuidora no encontrada.']], 404);
        }

        $correlativoRef = substr(hash('sha256', $correlativo), 0, 12);
        $nis = $this->nisModel->findByCorrelativo($correlativo, $distribuidoraId);
        log_message('debug', 'ECOE TS: resultado de base local ecoe_nis_base. distribuidora={distribuidora}, referencia={referencia}, encontrado_local={encontrado}.', [
            'distribuidora' => (string) ($distribuidora['nombre'] ?? ''),
            'referencia' => $correlativoRef,
            'encontrado' => $nis !== null ? 'si' : 'no',
        ]);

        try {
            $historial = $this->consultarMesesEcoe(
                (string) ($distribuidora['nombre'] ?? ''),
                $correlativo,
                (int) date('Y')
            );
        } catch (Throwable $e) {
            log_message('error', 'ECOE consulta mensual fallida para {distribuidora}/{referencia}: {message}', [
                'distribuidora' => (string) ($distribuidora['nombre'] ?? ''),
                'referencia' => $correlativoRef,
                'message' => $e->getMessage(),
            ]);

            $errorData = [
                'message' => 'No fue posible consultar el historial de consumo.',
                'error_code' => 'ECOE_SQLSERVER_QUERY_FAILED',
            ];

            if (str_contains($e->getMessage(), 'no contiene tablas mensuales')) {
                $errorData['message'] = 'La base de la distribuidora no contiene tablas mensuales de consumo configuradas.';
                $errorData['error_code'] = 'ECOE_MONTHLY_TABLES_NOT_FOUND';
            }

            if (ENVIRONMENT !== 'production') {
                $errorData['detail'] = $e->getMessage();
                if (preg_match('/SQLSTATE:\s*([A-Z0-9]+)/i', $e->getMessage(), $matches) === 1) {
                    $errorData['sqlstate'] = strtoupper($matches[1]);
                }
            }

            return $this->encryptedJsonResponse([
                'ok' => false,
                'data' => $errorData,
            ], 503);
        }

        $meses = $historial['meses'];
        $hayRegistrosExternos = (bool) ($historial['hay_registros'] ?? false);
        $hayDatosExternos = (bool) array_filter($meses, static fn (array $mes): bool =>
            (float) $mes['consumo_kwh'] !== 0.0
            || (float) $mes['costo_tarifa_plena'] !== 0.0
            || (float) $mes['costo_tarifa_social'] !== 0.0
            || (float) $mes['aporte_inde'] !== 0.0
        );

        log_message('debug', 'ECOE TS: resultado de validacion externa. distribuidora={distribuidora}, referencia={referencia}, encontrado_local={local}, registro_externo={registro}, datos_externos={datos}.', [
            'distribuidora' => (string) ($distribuidora['nombre'] ?? ''),
            'referencia' => $correlativoRef,
            'local' => $nis !== null ? 'si' : 'no',
            'registro' => $hayRegistrosExternos ? 'si' : 'no',
            'datos' => $hayDatosExternos ? 'si' : 'no',
        ]);

        if (! $nis && ! $hayRegistrosExternos) {
            log_message('notice', 'ECOE TS: correlativo no encontrado ni en base local ni en las particiones externas. distribuidora={distribuidora}, referencia={referencia}.', [
                'distribuidora' => (string) ($distribuidora['nombre'] ?? ''),
                'referencia' => $correlativoRef,
            ]);

            return $this->encryptedJsonResponse([
                'ok' => false,
                'data' => ['message' => 'El NIS o correlativo no fue encontrado.'],
            ], 404);
        }

        $mesesConConsumo = array_values(array_filter($meses, static fn (array $mes): bool => (float) $mes['consumo_kwh'] > 0));
        $consumoPromedio = $mesesConConsumo !== []
            ? array_sum(array_column($mesesConConsumo, 'consumo_kwh')) / count($mesesConConsumo)
            : (float) ($nis['consumo_kwh'] ?? 0);

        $aplicaTarifaSocial = $consumoPromedio > 0.0 && $consumoPromedio <= 300.0;
        $tarifasMes = $aplicaTarifaSocial
            ? $this->tarifaMensualModel->getLatestQuarterRates($distribuidoraId)
            : [];

        if ($aplicaTarifaSocial && count($tarifasMes) !== 3) {
            return $this->encryptedJsonResponse([
                'ok' => false,
                'data' => [
                    'message' => 'No hay un trimestre completo de tarifas configurado para esta distribuidora.',
                    'error_code' => 'ECOE_TARIFF_RATES_NOT_FOUND',
                ],
            ], 503);
        }

        foreach ($meses as $monthPosition => &$mes) {
            $consumoMes = (float) $mes['consumo_kwh'];

            if ($aplicaTarifaSocial && $consumoMes > 0.0) {
                $tarifas = $tarifasMes[$monthPosition + 1];
                $costoPlena = $consumoMes * $tarifas['plena'];
                $costoSocial = $consumoMes * $tarifas['social'];

                $mes['costo_tarifa_plena'] = round($costoPlena, 2);
                $mes['costo_tarifa_no_social'] = round($costoPlena, 2);
                $mes['costo_tarifa_social'] = round($costoSocial, 2);
                $mes['beneficio_tarifa_social'] = round(max(0.0, $costoPlena - $costoSocial), 2);
            } elseif (! $aplicaTarifaSocial) {
                $mes['beneficio_tarifa_social'] = 0.0;
            }

            $mes['ahorro'] = $mes['beneficio_tarifa_social'] + abs((float) $mes['aporte_inde']);
            unset($mes['_mes_num']);
        }
        unset($mes);

        $aporteTotal = array_sum(array_map(static fn (array $mes): float => abs((float) $mes['aporte_inde']), $meses));
        $beneficioTarifaSocialTotal = array_sum(array_column($meses, 'beneficio_tarifa_social'));
        $costoPlenaTotal = array_sum(array_column($meses, 'costo_tarifa_plena'));
        $costoSocialTotal = array_sum(array_column($meses, 'costo_tarifa_social'));
        $costoNoSocialTotal = array_sum(array_column($meses, 'costo_tarifa_no_social'));
        $ahorroTotal = array_sum(array_column($meses, 'ahorro'));

        log_message('debug', 'ECOE TS: totales normalizados para JSON. referencia={referencia}, beneficio_tarifa_social_total={beneficio}, ahorro_total={ahorro}.', [
            'referencia' => $correlativoRef,
            'beneficio' => $beneficioTarifaSocialTotal,
            'ahorro' => $ahorroTotal,
        ]);
        $tieneAporte = $aporteTotal > 0.0 || ($consumoPromedio > 0.0 && $consumoPromedio < 100.0);

        $data = [
            'nis' => $correlativo,
            'nombre_usuario' => (string) ($nis['nombre_usuario'] ?? ''),
            'activ_economica' => (string) ($nis['activ_economica'] ?? ''),
            'tiene_aporte' => $tieneAporte,
            'rango_tarifa' => $consumoPromedio > 300.0 ? 'no_social' : ($tieneAporte ? 'aporte_social' : 'social_sin_aporte'),
            'consumo_promedio' => (float) $consumoPromedio,
            'precio_kwh_social' => (float) $historial['precio_kwh_social'],
            'beneficio_tarifa_social_total' => (float) $beneficioTarifaSocialTotal,
            'ahorro_total' => (float) $ahorroTotal,
            'costo_tarifa_plena_total' => (float) $costoPlenaTotal,
            'costo_tarifa_social_total' => (float) $costoSocialTotal,
            'costo_tarifa_no_social_total' => (float) $costoNoSocialTotal,
            'costo_social_total' => (float) $costoSocialTotal,
            'costo_no_social_total' => (float) $costoNoSocialTotal,
            'meses' => $meses,
        ];

        return $this->encryptedJsonResponse(['ok' => true, 'data' => $data]);
    }

    /**
     * POST api/ecoe/ts/crear-ticket
     * Body (multipart): nombre, direccion, dpi, telefono, distribuidora_id, correlativo
     * Files: dpi_frontal, dpi_reverso, factura, fachada1, fachada2
     */
    public function apiCrearTicket(): mixed
    {
        $nombre          = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 160);
        $direccion       = mb_substr(trim((string) $this->request->getPost('direccion')), 0, 255);
        $dpi             = preg_replace('/\D/', '', (string) $this->request->getPost('dpi') ?? '');
        $telefono        = mb_substr(preg_replace('/\D/', '', (string) $this->request->getPost('telefono') ?? ''), 0, 20);
        $distribuidoraId = $this->normalizarDistribuidoraId($this->request->getPost('distribuidora_id'));
        $correlativo = $this->normalizarCorrelativo($this->request->getPost('correlativo'));

        // ── Validaciones básicas ──────────────────────────────────────────────
        if ($nombre === '' || $direccion === '' || strlen($dpi) < 13 || $distribuidoraId === null || $correlativo === null) {
            return $this->encryptedJsonResponse([
                'ok'   => false,
                'data' => ['message' => 'Todos los campos obligatorios deben estar completos.'],
            ], 422);
        }

        if (! $this->permiteConsultaPublica('create-ticket')) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Demasiadas solicitudes. Espera un momento e inténtalo de nuevo.']], 429);
        }

        // ── Verificar que el NIS existe y no está bloqueado ───────────────────
        $nis = $this->nisModel->findByCorrelativo($correlativo, $distribuidoraId);

        if ($nis && ((float) $nis['consumo_kwh']) >= 100.0) {
            return $this->encryptedJsonResponse([
                'ok'   => false,
                'data' => ['message' => 'El correlativo supera el límite de consumo permitido (100 kWh).'],
            ], 422);
        }

        // ── Verificar duplicado por DPI ───────────────────────────────────────
        $existente = $this->ticketModel->where('dpi', $dpi)->first();

        if ($existente) {
            return $this->encryptedJsonResponse([
                'ok'   => false,
                'data' => [
                    'message'           => 'Ya existe una solicitud registrada con ese DPI.',
                    'codigo_referencia' => esc($existente['codigo_referencia']),
                ],
            ], 409);
        }

        // ── Validar y guardar adjuntos ────────────────────────────────────────
        $tiposRequeridos = ['dpi_frontal', 'dpi_reverso', 'factura'];
        $archivosGuardados = [];

        $uploadPath = WRITEPATH . self::UPLOAD_DIR;

        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0750, true);
        }

        foreach (TsAdjuntoModel::tiposValidos() as $tipo) {
            $uploadedFile = $this->request->getFile($tipo);

            if (! $uploadedFile || ! $uploadedFile->isValid() || $uploadedFile->hasMoved()) {
                if (in_array($tipo, $tiposRequeridos, true)) {
                    return $this->encryptedJsonResponse([
                        'ok'   => false,
                        'data' => ['message' => "El archivo '{$tipo}' es obligatorio."],
                    ], 422);
                }

                continue;
            }

            // Validar tamaño
            if ($uploadedFile->getSize() > self::MAX_FILE_BYTES) {
                return $this->encryptedJsonResponse([
                    'ok'   => false,
                    'data' => ['message' => "El archivo '{$tipo}' excede el límite de 5 MB."],
                ], 422);
            }

            $validation = $this->validateImageUpload($uploadedFile);

            if (! $validation['ok']) {
                return $this->encryptedJsonResponse([
                    'ok'   => false,
                    'data' => ['message' => "El archivo '{$tipo}' es invalido: " . ($validation['error'] ?? 'Error de validacion.')],
                ], 422);
            }

            $ext      = (string) $validation['ext'];
            $newName  = bin2hex(random_bytes(16)) . '.' . $ext;
            $target   = $uploadPath . $newName;

            if (! $this->sanitizeAndStoreImage($uploadedFile->getTempName(), $target, (string) $validation['mime'])) {
                return $this->encryptedJsonResponse([
                    'ok'   => false,
                    'data' => ['message' => "No fue posible sanear la imagen '{$tipo}'. Intenta con otra imagen valida."],
                ], 422);
            }

            $archivosGuardados[$tipo] = self::UPLOAD_DIR . $newName;
        }

        // ── Crear ticket ──────────────────────────────────────────────────────
        $idSolicitud      = $this->ticketModel->nextIdSolicitud();
        $codigoReferencia = $this->generarCodigoReferencia($dpi, $idSolicitud);

        // Estado inicial = 'Ingresado' (orden_paso = 1)
        $estadoIngresado = $this->estadoModel->where('orden_paso', 1)->first();
        $estadoId        = $estadoIngresado ? (int) $estadoIngresado['id'] : 1;

        $ticketId = $this->ticketModel->insert([
            'id_solicitud'      => $idSolicitud,
            'codigo_referencia' => $codigoReferencia,
            'nombre'            => $nombre,
            'direccion'         => $direccion,
            'dpi'               => $dpi,
            'telefono'          => $telefono,
            'estado_id'         => $estadoId,
            'fecha_ingreso'     => date('Y-m-d H:i:s'),
        ]);

        // ── Guardar adjuntos en BD ─────────────────────────────────────────────
        foreach ($archivosGuardados as $tipo => $ruta) {
            $this->adjuntoModel->insert([
                'ticket_id'    => $ticketId,
                'tipo_archivo' => $tipo,
                'ruta_archivo' => $ruta,
            ]);
        }

        $this->registrarBitacoraEstado(
            (int) $ticketId,
            (int) $estadoId,
            (string) ($estadoIngresado['nombre'] ?? 'Ingresado'),
            'Solicitud creada desde el portal público.',
            null,
        );

        return $this->encryptedJsonResponse([
            'ok'   => true,
            'data' => [
                'message'           => 'Solicitud creada exitosamente.',
                'codigo_referencia' => $codigoReferencia,
                'estado'            => (string) ($estadoIngresado['nombre'] ?? 'Ingresado'),
                'pdf_download_url'  => site_url('api/ecoe/ts/solicitud-pdf?token=' . rawurlencode($this->buildTicketPdfToken((int) $ticketId))),
            ],
        ]);
    }

    /**
     * GET api/ecoe/ts/historial-pdf?correlativo=...&anio=...
     * Descarga un PDF con el historial de ayuda social del correlativo.
     */
    public function descargarHistorialPdf(): mixed
    {
        $correlativo = $this->normalizarCorrelativo($this->request->getGet('correlativo'));
        $rawYear = $this->request->getGet('anio');
        $anio = is_scalar($rawYear) ? trim((string) $rawYear) : '';

        if ($correlativo === null || ($anio !== '' && (preg_match('/\A(?:19|20)[0-9]{2}\z/', $anio) !== 1 || (int) $anio > (int) date('Y')))) {
            return $this->response->setStatusCode(422)->setBody('El correlativo o año no tiene un formato válido.');
        }

        if (! $this->permiteConsultaPublica('history-pdf')) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', (string) self::ECOE_RATE_LIMIT_WINDOW_SECONDS)->setBody('Demasiadas consultas. Inténtalo de nuevo más tarde.');
        }

        $historial = $this->consultarHistorialEcoe($correlativo, $anio !== '' ? $anio : null);

        if (! ($historial['ok'] ?? false) || ! ($historial['encontrado'] ?? false)) {
            return $this->response->setStatusCode(404)->setBody('No se encontró historial para el correlativo indicado.');
        }

        $html = $this->construirHtmlPdfHistorial($historial, $anio !== '' ? $anio : null);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = 'historial-ts-' . preg_replace('/[^A-Z0-9\-]+/i', '_', $correlativo) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($dompdf->output());
    }

    /**
     * GET api/ecoe/ts/solicitud-pdf?token=...
     * Descarga comprobante PDF de solicitud usando token firmado.
     */
    public function descargarSolicitudPdf(): mixed
    {
        $token = trim((string) $this->request->getGet('token'));
        $tokenData = $this->verifyTicketPdfToken($token);

        if (! $tokenData) {
            return $this->response->setStatusCode(403)->setBody('Token de descarga inválido o expirado.');
        }

        $ticketId = (int) ($tokenData['ticket_id'] ?? 0);
        $ticket = $this->ticketModel->findConEstadoById($ticketId);

        if (! $ticket) {
            return $this->response->setStatusCode(404)->setBody('Solicitud no encontrada.');
        }

        $template = $this->templateModel->getDefaultActive();

        if (! $template) {
            return $this->response->setStatusCode(500)->setBody('No existe una plantilla PDF activa.');
        }

        $bitacora = $this->bitacoraModel->listByTicket((int) $ticketId);
        $html = $this->renderTicketPdfHtml($template, $ticket, $bitacora);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = 'solicitud-ts-' . preg_replace('/[^A-Z0-9\-]+/i', '_', (string) $ticket['codigo_referencia']) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($dompdf->output());
    }

    /**
     * POST api/ecoe/ts/rastrear
     * Body: valor (DPI o código de referencia)
     */
    public function apiRastrear(): mixed
    {
        $rawValue = $this->request->getPost('valor');
        $valor = is_scalar($rawValue) ? strtoupper(trim((string) $rawValue)) : '';

        if (strlen($valor) > 50 || preg_match('/\A(?:[0-9]{13}|TS-[0-9]{5}-[0-9]{7})\z/', $valor) !== 1) {
            return $this->encryptedJsonResponse([
                'ok'   => false,
                'data' => ['message' => 'Ingresa un DPI o código de referencia con formato válido.'],
            ], 422);
        }

        if (! $this->permiteConsultaPublica('ticket-tracking')) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Demasiadas consultas. Espera un momento e inténtalo de nuevo.']], 429);
        }

        $ticket = $this->ticketModel->buscarPorRastreo($valor);

        if (! $ticket) {
            return $this->encryptedJsonResponse([
                'ok'   => false,
                'data' => ['message' => 'No se encontró ninguna solicitud con ese dato.'],
            ], 404);
        }

        return $this->encryptedJsonResponse([
            'ok'   => true,
            'data' => [
                'codigo_referencia'   => esc($ticket['codigo_referencia']),
                'nombre'              => esc($ticket['nombre']),
                'estado_nombre'       => esc($ticket['estado_nombre']),
                'estado_descripcion'  => esc($ticket['estado_descripcion'] ?? ''),
                'orden_paso'          => (int) $ticket['orden_paso'],
                'fecha_ingreso'       => esc($ticket['fecha_ingreso']),
                'pdf_download_url'    => site_url('api/ecoe/ts/solicitud-pdf?token=' . rawurlencode($this->buildTicketPdfToken((int) ($ticket['id'] ?? 0)))),
            ],
        ]);
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    /**
     * Genera código de referencia ECOE Tarifa Social: TS + últimos 5 dígitos del DPI + número del sistema.
     */
    private function generarCodigoReferencia(string $dpi, int $systemNumber): string
    {
        $dpiDigits = preg_replace('/\D/', '', $dpi);
        $sufijoDpi = str_pad(substr($dpiDigits, -5), 5, '0', STR_PAD_LEFT);
        $system = str_pad((string) max(1, $systemNumber), 7, '0', STR_PAD_LEFT);

        return strtoupper('TS-' . $sufijoDpi . '-' . $system);
    }

    private function buildTicketPdfToken(int $ticketId): string
    {
        $payload = json_encode([
            'ticket_id' => $ticketId,
            'exp'       => time() + (60 * 60 * 24 * 45),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $payloadB64 = rtrim(strtr(base64_encode((string) $payload), '+/', '-_'), '=');
        $sigRaw = hash_hmac('sha256', $payloadB64, $this->resolveCipherKey(), true);
        $sigB64 = rtrim(strtr(base64_encode($sigRaw), '+/', '-_'), '=');

        return $payloadB64 . '.' . $sigB64;
    }

    private function verifyTicketPdfToken(string $token): ?array
    {
        if ($token === '' || ! str_contains($token, '.')) {
            return null;
        }

        [$payloadB64, $sigB64] = explode('.', $token, 2);

        if ($payloadB64 === '' || $sigB64 === '') {
            return null;
        }

        $expectedSig = rtrim(strtr(base64_encode(hash_hmac('sha256', $payloadB64, $this->resolveCipherKey(), true)), '+/', '-_'), '=');

        if (! hash_equals($expectedSig, $sigB64)) {
            return null;
        }

        $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/') . str_repeat('=', (4 - strlen($payloadB64) % 4) % 4), true);

        if ($payloadJson === false) {
            return null;
        }

        $data = json_decode($payloadJson, true);

        if (! is_array($data)) {
            return null;
        }

        $ticketId = (int) ($data['ticket_id'] ?? 0);
        $exp      = (int) ($data['exp'] ?? 0);

        if ($ticketId <= 0 || $exp < time()) {
            return null;
        }

        return $data;
    }

    private function renderTicketPdfHtml(array $template, array $ticket, array $bitacora = []): string
    {
        $bitacoraHtml = $this->buildBitacoraHtml($bitacora);

        $map = [
            '{{id_solicitud}}'      => esc((string) ($ticket['id_solicitud'] ?? '')),
            '{{codigo_referencia}}' => esc((string) ($ticket['codigo_referencia'] ?? '')),
            '{{nombre}}'            => esc((string) ($ticket['nombre'] ?? '')),
            '{{direccion}}'         => esc((string) ($ticket['direccion'] ?? '')),
            '{{dpi}}'               => esc((string) ($ticket['dpi'] ?? '')),
            '{{telefono}}'          => esc((string) ($ticket['telefono'] ?? '')),
            '{{estado_nombre}}'     => esc((string) ($ticket['estado_nombre'] ?? '')),
            '{{estado_descripcion}}'=> esc((string) ($ticket['estado_descripcion'] ?? '')),
            '{{fecha_ingreso}}'     => esc((string) ($ticket['fecha_ingreso'] ?? '')),
            '{{fecha_emision}}'     => esc(date('Y-m-d H:i:s')),
            '{{instrucciones_html}}'=> (string) ($template['instrucciones_html'] ?? ''),
            '{{bitacora_html}}'     => $bitacoraHtml,
        ];

        $templateHtml = (string) ($template['html_template'] ?? '');
        $html = $templateHtml;
        $html = str_replace(array_keys($map), array_values($map), $html);

        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<\?(php|=).*?\?>/is', '', $html) ?? $html;

        if (! str_contains($templateHtml, '{{bitacora_html}}')) {
            $append = '<h3>Bitácora de Estado</h3>' . $bitacoraHtml;
            if (str_contains($html, '</body>')) {
                $html = str_replace('</body>', $append . '</body>', $html);
            } else {
                $html .= $append;
            }
        }

        return $html;
    }

    private function getTicketDataForPreview(): array
    {
        $latest = $this->ticketModel->orderBy('id', 'DESC')->first();

        if ($latest && ! empty($latest['id'])) {
            $ticket = $this->ticketModel->findConEstadoById((int) $latest['id']);

            if ($ticket) {
                return $ticket;
            }
        }

        return [
            'id'                 => 0,
            'id_solicitud'       => 1001,
            'codigo_referencia'  => 'TS-00000-0000001',
            'nombre'             => 'Solicitante de Ejemplo',
            'direccion'          => 'Zona 1, Ciudad de Guatemala',
            'dpi'                => '1234567890101',
            'telefono'           => '55551234',
            'estado_nombre'      => 'Ingresado',
            'estado_descripcion' => 'Solicitud recibida y pendiente de revisión.',
            'fecha_ingreso'      => date('Y-m-d H:i:s'),
        ];
    }

    private function getBitacoraForPreview(int $ticketId): array
    {
        if ($ticketId > 0) {
            $hist = $this->bitacoraModel->listByTicket($ticketId);
            if (! empty($hist)) {
                return $hist;
            }
        }

        return [
            ['estado_nombre' => 'Ingresado', 'descripcion' => 'Solicitud creada desde portal público.', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
            ['estado_nombre' => 'En Revisión', 'descripcion' => 'Documentación validada por analista ECOE.', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
            ['estado_nombre' => 'Aprobado', 'descripcion' => 'Solicitud aprobada para continuidad del proceso.', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 days'))],
        ];
    }

    private function registrarBitacoraEstado(int $ticketId, int $estadoId, string $estadoNombre, string $descripcion, ?int $usuarioId): void
    {
        if ($ticketId <= 0 || $estadoId <= 0) {
            return;
        }

        $this->bitacoraModel->insert([
            'ticket_id'      => $ticketId,
            'estado_id'      => $estadoId,
            'estado_nombre'  => mb_substr(trim($estadoNombre), 0, 80),
            'descripcion'    => mb_substr(trim($descripcion), 0, 255),
            'usuario_id'     => ($usuarioId !== null && $usuarioId > 0) ? $usuarioId : null,
            'fecha_registro' => date('Y-m-d H:i:s'),
        ]);
    }

    private function buildBitacoraHtml(array $bitacora): string
    {
        if (empty($bitacora)) {
            return '<p>Sin movimientos registrados en la bitácora.</p>';
        }

        $rows = '';
        foreach ($bitacora as $item) {
            $rows .= '<tr>'
                . '<td style="border:1px solid #d1d5db; padding:6px;">' . esc((string) ($item['created_at'] ?? $item['fecha_registro'] ?? '')) . '</td>'
                . '<td style="border:1px solid #d1d5db; padding:6px;">' . esc((string) ($item['estado_nombre'] ?? '')) . '</td>'
                . '<td style="border:1px solid #d1d5db; padding:6px;">' . esc((string) ($item['descripcion'] ?? '')) . '</td>'
                . '</tr>';
        }

        return '<table style="width:100%; border-collapse:collapse; margin-top:8px;">'
            . '<thead><tr>'
            . '<th style="border:1px solid #d1d5db; padding:6px; background:#f3f4f6;">Fecha</th>'
            . '<th style="border:1px solid #d1d5db; padding:6px; background:#f3f4f6;">Estado</th>'
            . '<th style="border:1px solid #d1d5db; padding:6px; background:#f3f4f6;">Descripción</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>';
    }
}
