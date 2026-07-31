<?php

namespace App\Modules\Etcee\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Admin\Models\UploadLogModel;
use App\Modules\Etcee\Models\SniCapaModel;
use App\Modules\Etcee\Models\SniGeometriaModel;
use App\Modules\Etcee\Models\SniLineaSistemaModel;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use SimpleXMLElement;
use Throwable;
use ZipArchive;

class SniController extends AdminBaseController
{
    private const PREVIEW_SESSION_KEY = 'etcee_sni_wizard_preview';
    private const KMZ_STORAGE_ROOT = 'uploads/sni_kmz';

    private SniCapaModel $capaModel;
    private SniGeometriaModel $geometriaModel;
    private SniLineaSistemaModel $lineaSistemaModel;
    private UploadLogModel $uploadLogModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->capaModel = new SniCapaModel();
        $this->geometriaModel = new SniGeometriaModel();
        $this->lineaSistemaModel = new SniLineaSistemaModel();
        $this->uploadLogModel = new UploadLogModel();
    }

    public function wizard()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccessSni($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar el modulo SNI de ETCEE.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'preview' => $this->handlePreview($auth),
                'save_kmz' => $this->handleStoreKmz($auth),
                'delete_kmz' => $this->handleDeleteStoredKmz($auth),
                'import' => $this->handleImport($auth),
                'reset' => $this->handleReset(),
                default => redirect()->back()->with('error', 'Accion no reconocida para el asistente SNI.'),
            };
        }

        $preview = $this->getPreviewFromSession((int) $auth['user_id']);
        $stats = $this->buildPreviewStats((array) ($preview['rows'] ?? []));

        return $this->adminView('App\\Modules\\Etcee\\Views\\sni_wizard', [
            'preview' => $preview,
            'previewCount' => count($preview['rows'] ?? []),
            'previewStats' => $stats,
            'capas' => $this->listCapasForSelect(),
            'lineasSistema' => $this->listLineasSistemaForSelect(),
            'storedKmzFiles' => $this->listStoredKmzFiles($auth),
        ]);
    }

    public function index()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccessSni($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar el SNI.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'save' => $this->handleSniSave($auth),
                'delete' => $this->handleSniDelete(),
                'delete_line' => $this->handleSniDeleteLine(),
                default => $this->encryptedAdminResponse(['message' => 'Accion no reconocida para el SNI.'], 400),
            };
        }

        $filters = [
            'linea_sistema_id' => max((int) $this->request->getGet('linea_sistema_id'), 0),
            'categoria_id' => max((int) $this->request->getGet('categoria_id'), 0),
            'q' => mb_substr(trim((string) $this->request->getGet('q')), 0, 120),
        ];

        $rows = $this->listGeometriasForCrud($filters);
        $page = max((int) $this->request->getGet('page'), 1);
        $accordion = $this->buildLineAccordionData($rows, $page, 10);

        return $this->adminView('App\\Modules\\Etcee\\Views\\sni_index', [
            'rows' => $rows,
            'capas' => $this->listCapasForSelect(),
            'lineasSistema' => $this->listLineasSistemaForSelect(),
            'activeFilters' => $filters,
            'lineGroups' => (array) ($accordion['items'] ?? []),
            'linePagination' => (array) ($accordion['meta'] ?? []),
        ]);
    }

    public function get(int $id = 0)
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return redirect()->to('login');
        }

        if (! $this->canAccessSni($auth)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Sin permisos.'])->setStatusCode(403);
        }

        if ($id <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'ID invalido.'])->setStatusCode(400);
        }

        $row = $this->findGeometryForCrud($id);

        if (! is_array($row)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Registro no encontrado.'])->setStatusCode(404);
        }

        return $this->response->setJSON(['ok' => true, 'data' => $row]);
    }

    public function capas()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccessSni($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar capas del SNI.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'save' => $this->handleCapaSave(),
                'delete' => $this->handleCapaDelete(),
                default => $this->response->setJSON(['ok' => false, 'message' => 'Accion no reconocida para capas SNI.'])->setStatusCode(400),
            };
        }

        return $this->adminView('App\\Modules\\Etcee\\Views\\sni_capas', [
            'rows' => $this->listCapasForCrud(),
        ]);
    }

    public function lineasSistema()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccessSni($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar lineas de sistema del SNI.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = strtolower(trim((string) $this->request->getPost('action')));

            return match ($action) {
                'save' => $this->handleLineaSistemaSave(),
                'delete' => $this->handleLineaSistemaDelete(),
                default => $this->response->setJSON(['ok' => false, 'message' => 'Accion no reconocida para lineas de sistema SNI.'])->setStatusCode(400),
            };
        }

        return $this->adminView('App\\Modules\\Etcee\\Views\\sni_lineas_sistema', [
            'rows' => $this->listLineasSistemaForCrud(),
        ]);
    }

    public function lineaSistemaGet(int $id = 0)
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return redirect()->to('login');
        }

        if (! $this->canAccessSni($auth)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Sin permisos.'])->setStatusCode(403);
        }

        if ($id <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'ID invalido.'])->setStatusCode(400);
        }

        $row = $this->lineaSistemaModel->find($id);

        if (! is_array($row)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Linea de sistema no encontrada.'])->setStatusCode(404);
        }

        return $this->response->setJSON(['ok' => true, 'data' => $row]);
    }

    public function capaIcons()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $this->response->setJSON(['ok' => false, 'message' => 'No autorizado.'])->setStatusCode(403);
        }

        $sniIconDir = FCPATH . 'assets/img/sni';
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
        $icons = [];

        if (is_dir($sniIconDir)) {
            $files = scandir($sniIconDir);

            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                    if (! in_array($ext, $allowed, true)) {
                        continue;
                    }

                    $fullPath = $sniIconDir . DIRECTORY_SEPARATOR . $file;

                    if (! is_file($fullPath)) {
                        continue;
                    }

                    $icons[] = [
                        'nombre' => $file,
                        'ruta' => 'assets/img/sni/' . $file,
                        'url' => base_url('assets/img/sni/' . $file),
                    ];
                }
            }
        }

        return $this->response->setJSON(['ok' => true, 'icons' => $icons]);
    }

    public function capaGet(int $id = 0)
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return redirect()->to('login');
        }

        if (! $this->canAccessSni($auth)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Sin permisos.'])->setStatusCode(403);
        }

        if ($id <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'ID invalido.'])->setStatusCode(400);
        }

        $row = $this->capaModel->find($id);

        if (! is_array($row)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Capa no encontrada.'])->setStatusCode(404);
        }

        return $this->response->setJSON(['ok' => true, 'data' => $row]);
    }

    private function handlePreview(array $auth): RedirectResponse
    {
        $storedToken = trim((string) $this->request->getPost('stored_kmz'));

        if ($storedToken !== '') {
            $storedPath = $this->resolveStoredKmzPath($auth, $storedToken);

            if ($storedPath === null || ! is_file($storedPath)) {
                return redirect()->back()->with('error', 'El archivo KMZ seleccionado no existe en el servidor.');
            }

            try {
                $rows = $this->parseKmzRows($storedPath);
            } catch (Throwable $exception) {
                return redirect()->back()->with('error', 'No fue posible leer el KMZ guardado: ' . $exception->getMessage());
            }

            if ($rows === []) {
                return redirect()->back()->with('error', 'El KMZ seleccionado no contiene placemarks validos para importar.');
            }

            $this->purgePreviewFile($this->getPreviewFromSession((int) $auth['user_id']));

            $preview = [
                'user_id' => (int) $auth['user_id'],
                'gerencia_id' => $this->resolveGerenciaId($auth),
                'source_file_name' => basename($storedPath),
                'stored_file_path' => $storedPath,
                'mime_type' => 'application/vnd.google-earth.kmz',
                'file_size' => (int) (filesize($storedPath) ?: 0),
                'rows' => $rows,
                'created_at' => date('Y-m-d H:i:s'),
                'persisted_file' => true,
            ];

            $this->session->set(self::PREVIEW_SESSION_KEY, $preview);

            return redirect()->to((string) current_url())->with('message', 'Previsualizacion generada desde KMZ guardado: ' . count($rows) . ' registros listos para importar.');
        }

        $file = $this->request->getFile('kmz_file');

        if ($file === null || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Debes seleccionar un archivo KMZ valido.');
        }

        $extension = strtolower((string) $file->getExtension());

        if ($extension !== 'kmz') {
            return redirect()->back()->with('error', 'Formato no permitido. Solo se aceptan archivos .kmz');
        }

        $maxKb = (int) env('security.uploadMaxSizeKB', 10240);

        if ($file->getSizeByUnit('kb') > $maxKb) {
            return redirect()->back()->with('error', 'El archivo excede el tamano maximo permitido de ' . $maxKb . ' KB.');
        }

        $gerenciaId = $this->resolveGerenciaId($auth);
        $targetPath = WRITEPATH . 'uploads/gerencias/' . $gerenciaId;

        if (! is_dir($targetPath) && ! mkdir($targetPath, 0755, true) && ! is_dir($targetPath)) {
            return redirect()->back()->with('error', 'No fue posible preparar el directorio de carga.');
        }

        $storedName = $file->getRandomName();
        $file->move($targetPath, $storedName, true);
        $fullPath = $targetPath . DIRECTORY_SEPARATOR . $storedName;

        try {
            $rows = $this->parseKmzRows($fullPath);
        } catch (Throwable $exception) {
            @unlink($fullPath);

            return redirect()->back()->with('error', 'No fue posible leer el KMZ: ' . $exception->getMessage());
        }

        if ($rows === []) {
            @unlink($fullPath);

            return redirect()->back()->with('error', 'El KMZ no contiene placemarks validos para importar.');
        }

        $this->purgePreviewFile($this->getPreviewFromSession((int) $auth['user_id']));

        $preview = [
            'user_id' => (int) $auth['user_id'],
            'gerencia_id' => $gerenciaId,
            'source_file_name' => (string) $file->getClientName(),
            'stored_file_path' => $fullPath,
            'mime_type' => (string) ($file->getClientMimeType() ?: 'application/vnd.google-earth.kmz'),
            'file_size' => (int) (filesize($fullPath) ?: 0),
            'rows' => $rows,
            'created_at' => date('Y-m-d H:i:s'),
            'persisted_file' => false,
        ];

        $this->session->set(self::PREVIEW_SESSION_KEY, $preview);

        return redirect()->to((string) current_url())->with('message', 'Previsualizacion generada: ' . count($rows) . ' registros listos para importar.');
    }

    private function handleStoreKmz(array $auth): RedirectResponse
    {
        $file = $this->request->getFile('kmz_file');

        if ($file === null || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Debes seleccionar un archivo KMZ valido para guardar.');
        }

        $extension = strtolower((string) $file->getExtension());

        if ($extension !== 'kmz') {
            return redirect()->back()->with('error', 'Formato no permitido. Solo se aceptan archivos .kmz');
        }

        $maxKb = (int) env('security.uploadMaxSizeKB', 10240);

        if ($file->getSizeByUnit('kb') > $maxKb) {
            return redirect()->back()->with('error', 'El archivo excede el tamano maximo permitido de ' . $maxKb . ' KB.');
        }

        $storageDir = $this->getStoredKmzDirectory($auth);

        if (! is_dir($storageDir) && ! mkdir($storageDir, 0755, true) && ! is_dir($storageDir)) {
            return redirect()->back()->with('error', 'No fue posible preparar el directorio de KMZ guardados.');
        }

        $originalName = (string) $file->getClientName();
        $nameNoExt = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBase = $this->slugifyGroupPath($nameNoExt);

        if ($safeBase === '' || $safeBase === 'sin-grupo') {
            $safeBase = 'archivo';
        }

        $storedName = date('Ymd_His') . '_' . $safeBase . '.kmz';
        $fullPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;
        $suffix = 1;

        while (is_file($fullPath)) {
            $storedName = date('Ymd_His') . '_' . $safeBase . '_' . $suffix . '.kmz';
            $fullPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;
            $suffix++;
        }

        $file->move($storageDir, $storedName, true);

        return redirect()->to((string) current_url())->with('message', 'KMZ guardado correctamente en servidor. Ahora puedes previsualizarlo desde la lista.');
    }

    private function handleDeleteStoredKmz(array $auth): RedirectResponse
    {
        $storedToken = trim((string) $this->request->getPost('stored_kmz'));

        if ($storedToken === '') {
            return redirect()->back()->with('error', 'Debes seleccionar un archivo KMZ guardado para eliminar.');
        }

        $path = $this->resolveStoredKmzPath($auth, $storedToken);

        if ($path === null || ! is_file($path)) {
            return redirect()->back()->with('error', 'El archivo seleccionado no existe o ya fue eliminado.');
        }

        $preview = $this->getPreviewFromSession((int) ($auth['user_id'] ?? 0));

        if (hash_equals((string) ($preview['stored_file_path'] ?? ''), $path)) {
            $this->session->remove(self::PREVIEW_SESSION_KEY);
        }

        if (! @unlink($path)) {
            return redirect()->back()->with('error', 'No fue posible eliminar el archivo KMZ seleccionado.');
        }

        return redirect()->to((string) current_url())->with('message', 'Archivo KMZ eliminado del servidor.');
    }

    private function handleImport(array $auth): RedirectResponse
    {
        $preview = $this->getPreviewFromSession((int) $auth['user_id']);
        $rows = $this->filterRowsForImport((array) ($preview['rows'] ?? []));
        $customName = trim((string) $this->request->getPost('import_nombre'));
        $customCategoryId = (int) $this->request->getPost('import_categoria_id');
        $lineaSistemaId = (int) $this->request->getPost('import_linea_sistema_id');
        $lineaSistema = null;

        if ($rows === []) {
            return redirect()->back()->with('error', 'No hay registros que coincidan con los filtros seleccionados para importar.');
        }

        if ($customCategoryId > 0) {
            $exists = $this->capaModel->where('id', $customCategoryId)->where('estado', 'activo')->first();

            if (! is_array($exists)) {
                return redirect()->back()->with('error', 'La capa seleccionada para importar no existe o esta inactiva.');
            }
        }

        if ($lineaSistemaId > 0) {
            $lineaSistema = $this->lineaSistemaModel
                ->where('id', $lineaSistemaId)
                ->where('estado', 'activo')
                ->first();

            if (! is_array($lineaSistema)) {
                return redirect()->back()->with('error', 'La linea de sistema seleccionada no existe o esta inactiva.');
            }
        }

        $userId = (int) ($auth['user_id'] ?? 0);
        $cache = [];
        $batch = [];
        $db = db_connect();

        $db->transStart();

        foreach ($rows as $index => $row) {
            $categoria = (array) ($row['categoria'] ?? []);
            $categoriaId = $customCategoryId > 0 ? $customCategoryId : $this->ensureCategory($categoria, $cache);
            $geojson = (array) ($row['geojson'] ?? []);
            $propiedades = (array) ($row['propiedades'] ?? []);
            $nombre = $customName !== ''
                ? (count($rows) > 1 ? ($customName . ' ' . ($index + 1)) : $customName)
                : (string) ($row['nombre'] ?? 'Elemento SNI');

            if ($lineaSistemaId > 0 && is_array($lineaSistema)) {
                $propiedades['linea_sistema_id'] = $lineaSistemaId;
                $propiedades['linea_sistema_nombre'] = (string) ($lineaSistema['nombre'] ?? '');
                $propiedades['linea_sistema_slug'] = (string) ($lineaSistema['slug'] ?? '');
            }

            $batch[] = [
                'categoria_id' => $categoriaId,
                'linea_sistema_id' => $lineaSistemaId > 0 ? $lineaSistemaId : null,
                'nombre' => $nombre,
                'coordenadas' => json_encode($geojson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'propiedades' => json_encode($propiedades, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'usuario_id' => $userId > 0 ? $userId : null,
                'fecha_registro' => date('Y-m-d H:i:s'),
            ];

            if (count($batch) >= 250) {
                $this->geometriaModel->insertBatch($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->geometriaModel->insertBatch($batch);
        }

        $this->uploadLogModel->insert([
            'gerencia_id' => (int) ($preview['gerencia_id'] ?? $this->resolveGerenciaId($auth)),
            'usuario_id' => $userId,
            'nombre_archivo' => (string) ($preview['source_file_name'] ?? 'sni.kmz'),
            'ruta_archivo' => (string) ($preview['stored_file_path'] ?? ''),
            'mime_type' => (string) ($preview['mime_type'] ?? 'application/vnd.google-earth.kmz'),
            'tamano_bytes' => (int) ($preview['file_size'] ?? 0),
            'registros_procesados' => count($rows),
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('error', 'Ocurrio un error al guardar la carga SNI.');
        }

        $this->session->remove(self::PREVIEW_SESSION_KEY);

        return redirect()->to(site_url('admin/etcee/sni'))->with('message', 'Carga masiva completada correctamente con ' . count($rows) . ' registros.');
    }

    private function handleReset(): RedirectResponse
    {
        $preview = $this->session->get(self::PREVIEW_SESSION_KEY);
        $this->purgePreviewFile(is_array($preview) ? $preview : []);
        $this->session->remove(self::PREVIEW_SESSION_KEY);

        return redirect()->to((string) current_url())->with('message', 'Previsualizacion descartada.');
    }

    private function handleSniSave(array $auth)
    {
        $id = (int) $this->request->getPost('id');
        $nombre = trim((string) $this->request->getPost('nombre'));
        $categoriaId = (int) $this->request->getPost('categoria_id');
        $lineaSistemaId = (int) $this->request->getPost('linea_sistema_id');
        $tipo = strtolower(trim((string) $this->request->getPost('tipo_geojson')));
        $lat = trim((string) $this->request->getPost('latitud'));
        $lon = trim((string) $this->request->getPost('longitud'));
        $coordenadasRaw = trim((string) $this->request->getPost('coordenadas_json'));
        $propiedadesRaw = trim((string) $this->request->getPost('propiedades_json'));
        $existingRow = $id > 0 ? $this->findGeometryForCrud($id) : null;
        $existingProps = json_decode((string) ($existingRow['propiedades'] ?? '{}'), true);
        $existingProps = is_array($existingProps) ? $existingProps : [];

        if ($nombre === '' || $categoriaId <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Nombre y capa son obligatorios.'])->setStatusCode(422);
        }

        $lineaSistema = null;

        if ($lineaSistemaId > 0) {
            $lineaSistema = $this->lineaSistemaModel->find($lineaSistemaId);

            if (! is_array($lineaSistema)) {
                return $this->response->setJSON(['ok' => false, 'message' => 'La linea de sistema seleccionada no existe.'])->setStatusCode(422);
            }
        }

        $coordenadas = null;

        if ($coordenadasRaw !== '') {
            $coordenadas = json_decode($coordenadasRaw, true);

            if (! is_array($coordenadas)) {
                return $this->response->setJSON(['ok' => false, 'message' => 'El JSON de coordenadas no es valido.'])->setStatusCode(422);
            }
        } elseif ($tipo === 'point' && $lat !== '' && $lon !== '') {
            if (! is_numeric($lat) || ! is_numeric($lon)) {
                return $this->response->setJSON(['ok' => false, 'message' => 'Latitud y longitud deben ser numericas.'])->setStatusCode(422);
            }

            $coordenadas = [
                'type' => 'Point',
                'coordinates' => [(float) $lon, (float) $lat],
            ];
        }

        if (! is_array($coordenadas) || ($coordenadas['type'] ?? '') === '') {
            return $this->response->setJSON(['ok' => false, 'message' => 'Debes proporcionar coordenadas validas en GeoJSON.'])->setStatusCode(422);
        }

        $propiedades = [];

        if ($propiedadesRaw !== '') {
            $propiedades = json_decode($propiedadesRaw, true);

            if (! is_array($propiedades)) {
                return $this->response->setJSON(['ok' => false, 'message' => 'El JSON de propiedades no es valido.'])->setStatusCode(422);
            }
        }

        if ($lineaSistemaId > 0 && is_array($lineaSistema)) {
            $propiedades['linea_sistema_id'] = $lineaSistemaId;
            $propiedades['linea_sistema_nombre'] = (string) ($lineaSistema['nombre'] ?? '');
            $propiedades['linea_sistema_slug'] = (string) ($lineaSistema['slug'] ?? '');
        } else {
            unset($propiedades['linea_sistema_id'], $propiedades['linea_sistema_nombre'], $propiedades['linea_sistema_slug']);
        }

        $payload = [
            'categoria_id' => $categoriaId,
            'linea_sistema_id' => $lineaSistemaId > 0 ? $lineaSistemaId : null,
            'nombre' => $nombre,
            'coordenadas' => json_encode($coordenadas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'propiedades' => json_encode($propiedades, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'usuario_id' => (int) ($auth['user_id'] ?? 0),
            'fecha_registro' => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            $db = db_connect();
            $db->transBegin();

            try {
                $updated = $this->geometriaModel->update($id, $payload);

                if ($updated === false) {
                    throw new RuntimeException('No fue posible actualizar el registro principal.');
                }

                $geoType = (string) ($coordenadas['type'] ?? '');
                $lineSlug = trim((string) ($propiedades['linea_slug'] ?? $existingProps['linea_slug'] ?? ''));
                $childrenUpdated = 0;

                // If editing a parent LineString, propagate the assigned line system to point children.
                if ($geoType === 'LineString' && $lineSlug !== '') {
                    $childrenUpdated = $this->propagateLineaSistemaToChildren($lineSlug, $lineaSistema, $lineaSistemaId > 0 ? $lineaSistemaId : null, $id);
                }

                if (! $db->transStatus()) {
                    throw new RuntimeException('Error de transaccion al actualizar registro y puntos hijos.');
                }

                $db->transCommit();

                $message = 'Registro actualizado.';

                if ($geoType === 'LineString') {
                    $message .= ' Puntos hijos actualizados automaticamente: ' . $childrenUpdated . '.';
                }

                return $this->response->setJSON(['ok' => true, 'message' => $message]);
            } catch (Throwable $exception) {
                $db->transRollback();

                return $this->response->setJSON([
                    'ok' => false,
                    'message' => 'No fue posible actualizar el registro y propagar la linea de sistema: ' . $exception->getMessage(),
                ])->setStatusCode(500);
            }
        }

        $this->geometriaModel->insert($payload);

        return $this->response->setJSON(['ok' => true, 'message' => 'Registro creado.']);
    }

    private function propagateLineaSistemaToChildren(string $lineSlug, ?array $lineaSistema, ?int $lineaSistemaId, int $excludeId = 0): int
    {
        $slug = trim($lineSlug);

        if ($slug === '') {
            return 0;
        }

        $rows = db_connect()->table('etcee_sni_geometrias')
            ->select('id, coordenadas, propiedades')
            ->like('propiedades', 'linea_slug')
            ->get()
            ->getResultArray();

        $updated = 0;

        foreach ($rows as $row) {
            $rowId = (int) ($row['id'] ?? 0);

            if ($rowId <= 0 || $rowId === $excludeId) {
                continue;
            }

            $props = json_decode((string) ($row['propiedades'] ?? '{}'), true);
            $props = is_array($props) ? $props : [];

            if (! hash_equals($slug, trim((string) ($props['linea_slug'] ?? '')))) {
                continue;
            }

            $geo = json_decode((string) ($row['coordenadas'] ?? '{}'), true);
            $geo = is_array($geo) ? $geo : [];

            if ((string) ($geo['type'] ?? '') !== 'Point') {
                continue;
            }

            if ($lineaSistemaId !== null && $lineaSistemaId > 0 && is_array($lineaSistema)) {
                $props['linea_sistema_id'] = $lineaSistemaId;
                $props['linea_sistema_nombre'] = (string) ($lineaSistema['nombre'] ?? '');
                $props['linea_sistema_slug'] = (string) ($lineaSistema['slug'] ?? '');
            } else {
                unset($props['linea_sistema_id'], $props['linea_sistema_nombre'], $props['linea_sistema_slug']);
            }

            $updatedRow = $this->geometriaModel->update($rowId, [
                'linea_sistema_id' => $lineaSistemaId !== null && $lineaSistemaId > 0 ? $lineaSistemaId : null,
                'propiedades' => json_encode($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            if ($updatedRow === false) {
                throw new RuntimeException('No fue posible actualizar el punto hijo con ID ' . $rowId . '.');
            }

            $updated++;
        }

        return $updated;
    }

    private function handleSniDelete()
    {
        $id = (int) $this->request->getPost('id');

        if ($id <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'ID invalido.'])->setStatusCode(400);
        }

        $this->geometriaModel->delete($id);

        return $this->response->setJSON(['ok' => true, 'message' => 'Registro eliminado.']);
    }

    private function handleSniDeleteLine()
    {
        $lineSlug = trim((string) $this->request->getPost('linea_slug'));

        if ($lineSlug === '') {
            return $this->response->setJSON(['ok' => false, 'message' => 'Linea invalida.'])->setStatusCode(400);
        }

        $rows = db_connect()->table('etcee_sni_geometrias')
            ->select('id, propiedades')
            ->get()
            ->getResultArray();

        $ids = [];

        foreach ($rows as $row) {
            $props = json_decode((string) ($row['propiedades'] ?? '{}'), true);
            $props = is_array($props) ? $props : [];
            $slug = trim((string) ($props['linea_slug'] ?? ''));

            if ($slug !== '' && hash_equals($lineSlug, $slug)) {
                $ids[] = (int) ($row['id'] ?? 0);
            }
        }

        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return $this->response->setJSON(['ok' => false, 'message' => 'No se encontraron registros para la linea seleccionada.'])->setStatusCode(404);
        }

        $this->geometriaModel->whereIn('id', $ids)->delete();

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Linea eliminada correctamente con ' . count($ids) . ' registros asociados.',
        ]);
    }

    private function handleCapaSave()
    {
        $id = (int) $this->request->getPost('id');
        $nombre = trim((string) $this->request->getPost('nombre'));
        $slugRaw = trim((string) $this->request->getPost('slug'));
        $estado = strtolower(trim((string) $this->request->getPost('estado')));
        $color = trim((string) $this->request->getPost('color_default'));
        $icono = trim((string) $this->request->getPost('icono_path'));

        if ($nombre === '') {
            return $this->response->setJSON(['ok' => false, 'message' => 'El nombre de la capa es obligatorio.'])->setStatusCode(422);
        }

        $slug = strtolower($slugRaw !== '' ? $slugRaw : $nombre);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            return $this->response->setJSON(['ok' => false, 'message' => 'No fue posible generar un slug valido.'])->setStatusCode(422);
        }

        if (! in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        if ($color === '') {
            $color = '#1f6feb';
        }

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1) {
            return $this->response->setJSON(['ok' => false, 'message' => 'El color debe estar en formato hexadecimal #RRGGBB.'])->setStatusCode(422);
        }

        $exists = $this->capaModel->where('slug', $slug)->first();

        if (is_array($exists) && (int) ($exists['id'] ?? 0) !== $id) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Ya existe una capa con ese slug.'])->setStatusCode(422);
        }

        $payload = [
            'nombre' => $nombre,
            'slug' => $slug,
            'estado' => $estado,
            'color_default' => $color,
            'icono_path' => $icono !== '' ? $icono : null,
        ];

        if ($id > 0) {
            $this->capaModel->update($id, $payload);

            return $this->response->setJSON(['ok' => true, 'message' => 'Capa actualizada correctamente.']);
        }

        $this->capaModel->insert($payload);

        return $this->response->setJSON(['ok' => true, 'message' => 'Capa creada correctamente.']);
    }

    private function handleCapaDelete()
    {
        $id = (int) $this->request->getPost('id');

        if ($id <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'ID invalido.'])->setStatusCode(400);
        }

        $uso = db_connect()->table('etcee_sni_geometrias')
            ->where('categoria_id', $id)
            ->countAllResults();

        if ($uso > 0) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'No se puede eliminar la capa porque tiene geometrias asociadas.',
            ])->setStatusCode(422);
        }

        $this->capaModel->delete($id);

        return $this->response->setJSON(['ok' => true, 'message' => 'Capa eliminada correctamente.']);
    }

    private function handleLineaSistemaSave()
    {
        $id = (int) $this->request->getPost('id');
        $nombre = trim((string) $this->request->getPost('nombre'));
        $slugRaw = trim((string) $this->request->getPost('slug'));
        $descripcion = trim((string) $this->request->getPost('descripcion'));
        $estado = strtolower(trim((string) $this->request->getPost('estado')));

        if ($nombre === '') {
            return $this->response->setJSON(['ok' => false, 'message' => 'El nombre de la linea de sistema es obligatorio.'])->setStatusCode(422);
        }

        $slug = strtolower($slugRaw !== '' ? $slugRaw : $nombre);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            return $this->response->setJSON(['ok' => false, 'message' => 'No fue posible generar un slug valido.'])->setStatusCode(422);
        }

        if (! in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $exists = $this->lineaSistemaModel->where('slug', $slug)->first();

        if (is_array($exists) && (int) ($exists['id'] ?? 0) !== $id) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Ya existe una linea de sistema con ese slug.'])->setStatusCode(422);
        }

        $payload = [
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'estado' => $estado,
        ];

        if ($id > 0) {
            $this->lineaSistemaModel->update($id, $payload);

            return $this->response->setJSON(['ok' => true, 'message' => 'Linea de sistema actualizada correctamente.']);
        }

        $payload['fecha_registro'] = date('Y-m-d H:i:s');
        $this->lineaSistemaModel->insert($payload);

        return $this->response->setJSON(['ok' => true, 'message' => 'Linea de sistema creada correctamente.']);
    }

    private function handleLineaSistemaDelete()
    {
        $id = (int) $this->request->getPost('id');

        if ($id <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'ID invalido.'])->setStatusCode(400);
        }

        $uso = db_connect()->table('etcee_sni_geometrias')
            ->where('linea_sistema_id', $id)
            ->countAllResults();

        if ($uso > 0) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'No se puede eliminar la linea de sistema porque tiene geometrias asociadas.',
            ])->setStatusCode(422);
        }

        $this->lineaSistemaModel->delete($id);

        return $this->response->setJSON(['ok' => true, 'message' => 'Linea de sistema eliminada correctamente.']);
    }

    private function listGeometriasForCrud(array $filters = []): array
    {
        $builder = db_connect()->table('etcee_sni_geometrias g')
            ->select('g.id, g.nombre, g.coordenadas, g.propiedades, g.fecha_registro, g.linea_sistema_id, c.id AS capa_id, c.nombre AS capa_nombre, c.color_default, ls.nombre AS linea_sistema_nombre')
            ->join('etcee_sni_capas c', 'c.id = g.categoria_id', 'left')
            ->join('etcee_sni_lineas_sistema ls', 'ls.id = g.linea_sistema_id', 'left')
            ->orderBy('g.id', 'DESC');

        $lineaSistemaId = max((int) ($filters['linea_sistema_id'] ?? 0), 0);
        $categoriaId = max((int) ($filters['categoria_id'] ?? 0), 0);
        $queryText = trim((string) ($filters['q'] ?? ''));

        if ($lineaSistemaId > 0) {
            $builder->where('g.linea_sistema_id', $lineaSistemaId);
        }

        if ($categoriaId > 0) {
            $builder->where('g.categoria_id', $categoriaId);
        }

        if ($queryText !== '') {
            $builder->groupStart()
                ->like('g.nombre', $queryText)
                ->orLike('g.propiedades', $queryText)
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    private function listCapasForSelect(): array
    {
        return $this->capaModel
            ->where('estado', 'activo')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    private function listCapasForCrud(): array
    {
        return $this->capaModel
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    private function listLineasSistemaForSelect(): array
    {
        return $this->lineaSistemaModel
            ->where('estado', 'activo')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    private function listLineasSistemaForCrud(): array
    {
        return $this->lineaSistemaModel
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    private function buildLineAccordionData(array $rows, int $currentPage, int $perPage): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $geo = json_decode((string) ($row['coordenadas'] ?? '{}'), true);
            $props = json_decode((string) ($row['propiedades'] ?? '{}'), true);
            $geo = is_array($geo) ? $geo : [];
            $props = is_array($props) ? $props : [];

            $geoType = (string) ($geo['type'] ?? 'N/D');
            $resolved = $this->resolveLineGroupingFromRow($row, $props, $geoType);
            $lineName = (string) ($resolved['line_name'] ?? 'Linea sin nombre');
            $lineSlug = (string) ($resolved['line_slug'] ?? 'sin-linea');
            $sistema = (string) ($resolved['sistema'] ?? 'Sin sistema');
            $subgrupo = (string) ($resolved['subgrupo'] ?? 'Sin subgrupo');

            if (! isset($groups[$lineSlug])) {
                $groups[$lineSlug] = [
                    'linea_slug' => $lineSlug,
                    'linea_nombre' => $lineName,
                    'sistema_linea' => $sistema,
                    'subgrupo_linea' => $subgrupo,
                    'line' => null,
                    'points' => [],
                    'others' => [],
                ];
            }

            $entry = [
                'id' => (int) ($row['id'] ?? 0),
                'nombre' => (string) ($row['nombre'] ?? ''),
                'capa_nombre' => (string) ($row['capa_nombre'] ?? 'N/D'),
                'color_default' => (string) ($row['color_default'] ?? '#6c757d'),
                'fecha_registro' => (string) ($row['fecha_registro'] ?? ''),
                'geo_type' => $geoType,
            ];

            if ($geoType === 'LineString') {
                if (! is_array($groups[$lineSlug]['line'])) {
                    $groups[$lineSlug]['line'] = $entry;
                } else {
                    $groups[$lineSlug]['others'][] = $entry;
                }

                continue;
            }

            if ($geoType === 'Point') {
                $groups[$lineSlug]['points'][] = $entry;

                continue;
            }

            $groups[$lineSlug]['others'][] = $entry;
        }

        uasort($groups, static fn (array $a, array $b): int => strcmp((string) ($a['linea_nombre'] ?? ''), (string) ($b['linea_nombre'] ?? '')));

        $all = array_values($groups);
        $total = count($all);
        $perPage = max($perPage, 1);
        $totalPages = max((int) ceil($total / $perPage), 1);
        $currentPage = min(max($currentPage, 1), $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        return [
            'items' => array_slice($all, $offset, $perPage),
            'meta' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    private function resolveLineGroupingFromRow(array $row, array $props, string $geoType): array
    {
        $rawName = trim((string) ($row['nombre'] ?? ''));
        $lineName = trim((string) ($props['linea_electrificada'] ?? ''));
        $lineSlug = trim((string) ($props['linea_slug'] ?? ''));
        $sistema = trim((string) ($props['sistema_linea'] ?? ''));
        $subgrupo = trim((string) ($props['subgrupo_linea'] ?? ''));
        $groupPath = trim((string) ($props['grupo_kml'] ?? ''));

        if ($lineName === '' && $groupPath !== '') {
            $normalized = $this->normalizeGroupPath($groupPath);
            $parts = array_values(array_filter(array_map('trim', explode('>', $normalized)), static fn (string $p): bool => $p !== ''));

            if ($parts !== []) {
                $lineName = (string) end($parts);

                if ($sistema === '' && isset($parts[0])) {
                    $sistema = (string) $parts[0];
                }

                if ($subgrupo === '' && count($parts) >= 3) {
                    $subgrupo = (string) $parts[count($parts) - 2];
                }
            }
        }

        if ($lineName === '') {
            $lineName = $rawName !== '' ? $rawName : 'Linea sin nombre';
        }

        if ($lineSlug === '') {
            $lineSlug = $this->slugifyGroupPath($lineName);
        }

        if ($lineSlug === '') {
            $lineSlug = 'linea-' . (int) ($row['id'] ?? 0);
        }

        if ($sistema === '') {
            $sistema = 'Sin sistema';
        }

        if ($subgrupo === '') {
            $subgrupo = 'Sin subgrupo';
        }

        // If a row is a LineString with explicit name, prefer it as canonical line header.
        if ($geoType === 'LineString' && $rawName !== '') {
            $lineName = $rawName;
        }

        return [
            'line_name' => $lineName,
            'line_slug' => $lineSlug,
            'sistema' => $sistema,
            'subgrupo' => $subgrupo,
        ];
    }

    private function findGeometryForCrud(int $id): ?array
    {
        $row = db_connect()->table('etcee_sni_geometrias g')
            ->select('g.id, g.nombre, g.coordenadas, g.propiedades, g.fecha_registro, g.categoria_id, g.linea_sistema_id')
            ->where('g.id', $id)
            ->limit(1)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    private function ensureCategory(array $category, array &$cache): int
    {
        $slug = trim((string) ($category['slug'] ?? 'sni-linea-general')) ?: 'sni-linea-general';

        if (isset($cache[$slug])) {
            return (int) $cache[$slug];
        }

        $existing = $this->capaModel->where('slug', $slug)->first();

        if (is_array($existing) && isset($existing['id'])) {
            $cache[$slug] = (int) $existing['id'];

            return (int) $existing['id'];
        }

        $this->capaModel->insert([
            'nombre' => (string) ($category['nombre'] ?? 'Linea SNI'),
            'slug' => $slug,
            'estado' => 'activo',
            'color_default' => (string) ($category['color_default'] ?? '#1f6feb'),
            'icono_path' => ($category['icono_path'] ?? null) !== null ? (string) $category['icono_path'] : null,
        ]);

        $id = (int) $this->capaModel->getInsertID();

        if ($id <= 0) {
            throw new RuntimeException('No fue posible resolver la categoria para el registro SNI.');
        }

        $cache[$slug] = $id;

        return $id;
    }

    private function parseKmzRows(string $kmzPath): array
    {
        $kml = $this->readKmlFromKmz($kmzPath);
        $xml = simplexml_load_string($kml);

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('El archivo KML interno no tiene un XML valido.');
        }

        $rows = [];
        $this->collectPlacemarkRows($xml, [], $rows);

        if ($rows === []) {
            return [];
        }

        foreach ($rows as $index => $row) {
            $rows[$index]['id'] = (int) $index + 1;
        }

        return $rows;
    }

    private function collectPlacemarkRows(SimpleXMLElement $node, array $folderStack, array &$rows): void
    {
        $containerNodes = $node->xpath('./*[local-name()="Document" or local-name()="Folder"]');

        if (is_array($containerNodes)) {
            foreach ($containerNodes as $container) {
                if (! $container instanceof SimpleXMLElement) {
                    continue;
                }

                $containerName = trim((string) ($container->name ?? ''));
                $nextStack = $folderStack;

                if ($containerName !== '') {
                    $nextStack[] = $containerName;
                }

                $this->collectPlacemarkRows($container, $nextStack, $rows);
            }
        }

        $placemarkNodes = $node->xpath('./*[local-name()="Placemark"]');

        if (! is_array($placemarkNodes)) {
            return;
        }

        foreach ($placemarkNodes as $placemark) {
            if (! $placemark instanceof SimpleXMLElement) {
                continue;
            }

            $nombre = trim((string) ($placemark->name ?? ''));
            $nombre = $nombre !== '' ? $nombre : 'Elemento SNI';

            $geojson = $this->extractGeoJsonFromPlacemark($placemark);

            if ($geojson === null) {
                continue;
            }

            $extended = $this->extractExtendedData($placemark);
            $hierarchy = $this->extractHierarchy($folderStack, $nombre);
            $groupPath = (string) ($hierarchy['group_path'] ?? 'Sin grupo');
            $groupSlug = $this->slugifyGroupPath($groupPath);
            $featureType = $this->resolveFeatureType($nombre, $groupPath, (string) ($geojson['type'] ?? ''));
            $extended['grupo_kml'] = $groupPath;
            $extended['grupo_slug'] = $groupSlug;
            $extended['tipo_elemento'] = $featureType;
            $extended['sistema_linea'] = (string) ($hierarchy['sistema'] ?? 'Sin sistema');
            $extended['subgrupo_linea'] = (string) ($hierarchy['subgrupo'] ?? 'Sin subgrupo');
            $extended['linea_electrificada'] = (string) ($hierarchy['linea'] ?? $nombre);
            $extended['linea_slug'] = (string) ($hierarchy['linea_slug'] ?? $groupSlug);

            $categoria = $this->resolveCategory($nombre, $extended, (string) ($geojson['type'] ?? ''));

            $extended['categoria_slug'] = $categoria['slug'];
            $extended['categoria_nombre'] = $categoria['nombre'];

            $rows[] = [
                'nombre' => $nombre,
                'grupo_path' => $groupPath,
                'grupo_slug' => $groupSlug,
                'feature_type' => $featureType,
                'categoria' => $categoria,
                'geojson' => $geojson,
                'propiedades' => $extended,
            ];
        }
    }

    private function readKmlFromKmz(string $kmzPath): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive no esta disponible en el servidor.');
        }

        $zip = new ZipArchive();

        if ($zip->open($kmzPath) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo KMZ.');
        }

        $kmlContent = '';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = strtolower((string) ($stat['name'] ?? ''));

            if ($name === '' || ! str_ends_with($name, '.kml')) {
                continue;
            }

            $content = $zip->getFromIndex($i);

            if ($content !== false) {
                $kmlContent = (string) $content;
                break;
            }
        }

        $zip->close();

        if ($kmlContent === '') {
            throw new RuntimeException('No se encontro ningun archivo KML dentro del KMZ.');
        }

        return $kmlContent;
    }

    private function extractExtendedData(SimpleXMLElement $placemark): array
    {
        $result = [];

        $description = trim((string) ($placemark->description ?? ''));

        if ($description !== '') {
            $result['descripcion'] = $description;
        }

        $dataNodes = $placemark->xpath('.//*[local-name()="Data"]');

        if (is_array($dataNodes)) {
            foreach ($dataNodes as $dataNode) {
                if (! $dataNode instanceof SimpleXMLElement) {
                    continue;
                }

                $key = strtolower(trim((string) ($dataNode['name'] ?? '')));
                $value = trim((string) ($dataNode->value ?? ''));

                if ($key !== '' && $value !== '') {
                    $result[$key] = $value;
                }
            }
        }

        $simpleDataNodes = $placemark->xpath('.//*[local-name()="SimpleData"]');

        if (is_array($simpleDataNodes)) {
            foreach ($simpleDataNodes as $node) {
                if (! $node instanceof SimpleXMLElement) {
                    continue;
                }

                $key = strtolower(trim((string) ($node['name'] ?? '')));
                $value = trim((string) $node);

                if ($key !== '' && $value !== '') {
                    $result[$key] = $value;
                }
            }
        }

        $kv = $this->extractVoltageFromText(
            implode(' ', array_filter([
                (string) ($result['nivel_kv'] ?? ''),
                (string) ($result['voltaje'] ?? ''),
                (string) ($result['voltage'] ?? ''),
                (string) ($result['descripcion'] ?? ''),
            ]))
        );

        if ($kv !== null) {
            $result['nivel_kv'] = $kv;
        }

        return $result;
    }

    private function extractGeoJsonFromPlacemark(SimpleXMLElement $placemark): ?array
    {
        $pointNodes = $placemark->xpath('.//*[local-name()="Point"]/*[local-name()="coordinates"]');

        if (is_array($pointNodes) && isset($pointNodes[0])) {
            $coords = $this->parseCoordinates((string) $pointNodes[0]);

            if ($coords !== []) {
                return [
                    'type' => 'Point',
                    'coordinates' => $coords[0],
                ];
            }
        }

        $lineNodes = $placemark->xpath('.//*[local-name()="LineString"]/*[local-name()="coordinates"]');

        if (is_array($lineNodes) && isset($lineNodes[0])) {
            $coords = $this->parseCoordinates((string) $lineNodes[0]);

            if (count($coords) >= 2) {
                return [
                    'type' => 'LineString',
                    'coordinates' => $coords,
                ];
            }
        }

        $polygonNodes = $placemark->xpath('.//*[local-name()="Polygon"]');

        if (is_array($polygonNodes) && isset($polygonNodes[0]) && $polygonNodes[0] instanceof SimpleXMLElement) {
            $polygon = $polygonNodes[0];
            $rings = [];
            $outer = $polygon->xpath('.//*[local-name()="outerBoundaryIs"]/*[local-name()="LinearRing"]/*[local-name()="coordinates"]');

            if (is_array($outer) && isset($outer[0])) {
                $outerCoords = $this->parseCoordinates((string) $outer[0]);
                if (count($outerCoords) >= 4) {
                    $rings[] = $outerCoords;
                }
            }

            $inners = $polygon->xpath('.//*[local-name()="innerBoundaryIs"]/*[local-name()="LinearRing"]/*[local-name()="coordinates"]');

            if (is_array($inners)) {
                foreach ($inners as $inner) {
                    $innerCoords = $this->parseCoordinates((string) $inner);
                    if (count($innerCoords) >= 4) {
                        $rings[] = $innerCoords;
                    }
                }
            }

            if ($rings !== []) {
                return [
                    'type' => 'Polygon',
                    'coordinates' => $rings,
                ];
            }
        }

        return null;
    }

    private function parseCoordinates(string $value): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        if ($normalized === '') {
            return [];
        }

        $items = explode(' ', $normalized);
        $coords = [];

        foreach ($items as $item) {
            $parts = array_values(array_filter(explode(',', trim($item)), static fn ($chunk) => $chunk !== ''));

            if (count($parts) < 2) {
                continue;
            }

            $lon = is_numeric($parts[0]) ? (float) $parts[0] : null;
            $lat = is_numeric($parts[1]) ? (float) $parts[1] : null;

            if ($lon === null || $lat === null) {
                continue;
            }

            $coord = [$lon, $lat];

            if (isset($parts[2]) && is_numeric($parts[2])) {
                $coord[] = (float) $parts[2];
            }

            $coords[] = $coord;
        }

        return $coords;
    }

    private function resolveCategory(string $name, array $extendedData, string $geometryType): array
    {
        $text = strtolower(trim($name . ' ' . implode(' ', array_map('strval', $extendedData))));
        $kv = isset($extendedData['nivel_kv']) ? (int) $extendedData['nivel_kv'] : $this->extractVoltageFromText($text);
        $isSubstation = $geometryType === 'Point'
            && (str_contains($text, 'subestacion') || str_contains($text, 'substation') || str_contains($text, 'sub-estacion'));

        if ($isSubstation) {
            return [
                'slug' => 'sni-subestaciones',
                'nombre' => 'Subestaciones',
                'color_default' => '#f59f00',
                'icono_path' => 'assets/img/sni/subestacion.png',
            ];
        }

        return match ($kv) {
            69 => [
                'slug' => 'sni-linea-69kv',
                'nombre' => 'Linea 69kV',
                'color_default' => '#2e7d32',
                'icono_path' => null,
            ],
            138 => [
                'slug' => 'sni-linea-138kv',
                'nombre' => 'Linea 138kV',
                'color_default' => '#c62828',
                'icono_path' => null,
            ],
            230 => [
                'slug' => 'sni-linea-230kv',
                'nombre' => 'Linea 230kV',
                'color_default' => '#1565c0',
                'icono_path' => null,
            ],
            default => [
                'slug' => 'sni-linea-general',
                'nombre' => 'Linea General SNI',
                'color_default' => '#455a64',
                'icono_path' => null,
            ],
        };
    }

    private function extractVoltageFromText(string $text): ?int
    {
        if (preg_match('/\b(69|138|230)\s*k?\s*v\b/i', $text, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/\b(69|138|230)\b/', $text, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function resolveFeatureType(string $name, string $groupPath, string $geometryType): string
    {
        $text = strtolower(trim($name . ' ' . $groupPath));

        if ($geometryType === 'LineString' || str_contains($text, 'ruta') || str_contains($text, 'linea')) {
            return 'ruta';
        }

        if (
            $geometryType === 'Point'
            || str_contains($text, 'waypoint')
            || str_contains($text, 'subestacion')
            || str_contains($text, 'poste')
        ) {
            return 'punto';
        }

        if ($geometryType === 'Polygon') {
            return 'area';
        }

        return 'otro';
    }

    private function slugifyGroupPath(string $path): string
    {
        $slug = strtolower(trim($path));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'sin-grupo';
    }

    private function normalizeGroupPath(string $path): string
    {
        $parts = array_values(array_filter(array_map('trim', explode('>', $path)), static fn (string $p): bool => $p !== ''));

        if ($parts === []) {
            return 'Sin grupo';
        }

        $technical = ['ruta', 'rutas', 'waypoint', 'waypoints', 'punto', 'puntos', 'point', 'points'];

        while ($parts !== []) {
            $last = strtolower(trim((string) end($parts)));
            $last = preg_replace('/\s+/', ' ', $last) ?? $last;

            if (! in_array($last, $technical, true)) {
                break;
            }

            array_pop($parts);
        }

        return $parts !== [] ? implode(' > ', $parts) : trim($path);
    }

    private function extractHierarchy(array $folderStack, string $nombre): array
    {
        $parts = array_values(array_filter(array_map('trim', $folderStack), static fn (string $p): bool => $p !== ''));

        if ($parts === []) {
            return [
                'sistema' => 'Sin sistema',
                'subgrupo' => 'Sin subgrupo',
                'linea' => $nombre,
                'linea_slug' => $this->slugifyGroupPath($nombre),
                'group_path' => $nombre,
            ];
        }

        $technical = ['ruta', 'rutas', 'waypoint', 'waypoints', 'punto', 'puntos', 'point', 'points'];

        while ($parts !== []) {
            $last = strtolower(trim((string) end($parts)));
            $last = preg_replace('/\s+/', ' ', $last) ?? $last;

            if (! in_array($last, $technical, true)) {
                break;
            }

            array_pop($parts);
        }

        if ($parts === []) {
            return [
                'sistema' => 'Sin sistema',
                'subgrupo' => 'Sin subgrupo',
                'linea' => $nombre,
                'linea_slug' => $this->slugifyGroupPath($nombre),
                'group_path' => $nombre,
            ];
        }

        $systemIndex = 0;

        foreach ($parts as $i => $part) {
            if (str_contains(strtolower($part), 'lineas sistema')) {
                $systemIndex = $i;
                break;
            }
        }

        $core = array_values(array_slice($parts, $systemIndex));
        $sistema = (string) ($core[0] ?? 'Sin sistema');

        $subgrupo = '';
        foreach (array_slice($core, 1) as $segment) {
            if (preg_match('/\b(l\s*)?(69|138|230|400)\s*k?\s*v?\b/i', $segment) === 1) {
                $subgrupo = trim((string) $segment);
                break;
            }
        }

        if ($subgrupo === '' && isset($core[1])) {
            $subgrupo = trim((string) $core[1]);
        }

        $linea = '';
        foreach (array_reverse(array_slice($core, 1)) as $segment) {
            $segment = trim((string) $segment);

            if ($segment === '') {
                continue;
            }

            if ($segment === $subgrupo) {
                continue;
            }

            if (preg_match('/\b(l\s*)?(69|138|230|400)\s*k?\s*v?\b/i', $segment) === 1) {
                continue;
            }

            $linea = $segment;
            break;
        }

        if ($linea === '') {
            $linea = $nombre;
        }

        $lineaSlug = $this->slugifyGroupPath($linea);
        $groupPath = implode(' > ', array_values(array_filter([$sistema, $subgrupo, $linea], static fn (string $s): bool => trim($s) !== '')));

        if ($groupPath === '') {
            $groupPath = $this->normalizeGroupPath(implode(' > ', $folderStack));
        }

        return [
            'sistema' => $sistema,
            'subgrupo' => $subgrupo !== '' ? $subgrupo : 'Sin subgrupo',
            'linea' => $linea,
            'linea_slug' => $lineaSlug !== '' ? $lineaSlug : $this->slugifyGroupPath($nombre),
            'group_path' => $groupPath,
        ];
    }

    private function buildPreviewStats(array $rows): array
    {
        $groups = [];
        $featureTypes = [
            'ruta' => 0,
            'punto' => 0,
            'area' => 0,
            'otro' => 0,
        ];
        $voltages = [
            '69' => 0,
            '138' => 0,
            '230' => 0,
            'otros' => 0,
        ];

        foreach ($rows as $row) {
            $groupSlug = (string) ($row['grupo_slug'] ?? 'sin-grupo');
            $groupPath = (string) ($row['grupo_path'] ?? 'Sin grupo');
            $featureType = (string) ($row['feature_type'] ?? 'otro');
            $kv = (int) (($row['propiedades']['nivel_kv'] ?? 0));

            if (! isset($groups[$groupSlug])) {
                $groups[$groupSlug] = [
                    'slug' => $groupSlug,
                    'path' => $groupPath,
                    'total' => 0,
                    'ruta' => 0,
                    'punto' => 0,
                    'area' => 0,
                    'otro' => 0,
                ];
            }

            $groups[$groupSlug]['total']++;

            if (isset($groups[$groupSlug][$featureType])) {
                $groups[$groupSlug][$featureType]++;
            }

            if (isset($featureTypes[$featureType])) {
                $featureTypes[$featureType]++;
            }

            if (in_array($kv, [69, 138, 230], true)) {
                $voltages[(string) $kv]++;
            } else {
                $voltages['otros']++;
            }
        }

        uasort($groups, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

        return [
            'groups' => array_values($groups),
            'feature_types' => $featureTypes,
            'voltages' => $voltages,
        ];
    }

    private function filterRowsForImport(array $rows): array
    {
        $selectedGroups = array_values(array_filter(array_map('strval', (array) $this->request->getPost('selected_groups'))));
        $selectedTypes = array_values(array_filter(array_map('strval', (array) $this->request->getPost('selected_types'))));
        $selectedVoltages = array_values(array_filter(array_map('intval', (array) $this->request->getPost('selected_kv'))));

        if ($selectedGroups === [] && $selectedTypes === [] && $selectedVoltages === []) {
            return $rows;
        }

        $groupSet = array_flip($selectedGroups);
        $typeSet = array_flip($selectedTypes);
        $voltageSet = array_flip($selectedVoltages);

        return array_values(array_filter($rows, static function (array $row) use ($groupSet, $typeSet, $voltageSet): bool {
            $groupOk = $groupSet === [] || isset($groupSet[(string) ($row['grupo_slug'] ?? '')]);
            $typeOk = $typeSet === [] || isset($typeSet[(string) ($row['feature_type'] ?? '')]);
            $kv = (int) (($row['propiedades']['nivel_kv'] ?? 0));
            $kvOk = $voltageSet === [] || isset($voltageSet[$kv]);

            return $groupOk && $typeOk && $kvOk;
        }));
    }

    private function canAccessSni(array $auth): bool
    {
        $permissions = array_map('strval', (array) ($auth['permissions'] ?? []));

        return $this->rbac->isSuperAdminByPermissions($permissions)
            || $this->rbac->hasPermission($permissions, 'gerencia.etcee.sni.access')
            || $this->rbac->hasPermission($permissions, 'gerencia.etcee.modulo.access')
            || $this->rbac->hasPermission($permissions, 'gerencia.etcee.dashboard.access');
    }

    private function resolveGerenciaId(array $auth): int
    {
        $gerenciaId = (int) ($auth['gerencia_id'] ?? 0);

        if ($gerenciaId > 0) {
            return $gerenciaId;
        }

        $row = db_connect()->table('gerencias')
            ->select('id')
            ->where('slug', 'etcee')
            ->limit(1)
            ->get()
            ->getRowArray();

        return max((int) ($row['id'] ?? 0), 1);
    }

    private function getPreviewFromSession(int $userId): array
    {
        $preview = $this->session->get(self::PREVIEW_SESSION_KEY);

        if (! is_array($preview)) {
            return [];
        }

        if ((int) ($preview['user_id'] ?? 0) !== $userId) {
            return [];
        }

        return $preview;
    }

    private function purgePreviewFile(array $preview): void
    {
        if ((bool) ($preview['persisted_file'] ?? false)) {
            return;
        }

        $path = (string) ($preview['stored_file_path'] ?? '');

        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    private function getStoredKmzDirectory(array $auth): string
    {
        $gerenciaId = $this->resolveGerenciaId($auth);

        return rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . self::KMZ_STORAGE_ROOT . DIRECTORY_SEPARATOR . $gerenciaId;
    }

    private function resolveStoredKmzPath(array $auth, string $storedToken): ?string
    {
        $token = trim($storedToken);

        if ($token === '' || preg_match('/^[a-zA-Z0-9._-]+$/', $token) !== 1) {
            return null;
        }

        $storageDir = $this->getStoredKmzDirectory($auth);
        $candidate = $storageDir . DIRECTORY_SEPARATOR . basename($token);

        if (! str_ends_with(strtolower($candidate), '.kmz')) {
            return null;
        }

        return $candidate;
    }

    private function listStoredKmzFiles(array $auth): array
    {
        $storageDir = $this->getStoredKmzDirectory($auth);

        if (! is_dir($storageDir)) {
            return [];
        }

        $paths = glob($storageDir . DIRECTORY_SEPARATOR . '*.kmz');

        if (! is_array($paths) || $paths === []) {
            return [];
        }

        $files = [];

        foreach ($paths as $path) {
            if (! is_string($path) || ! is_file($path)) {
                continue;
            }

            $name = basename($path);
            $files[] = [
                'token' => $name,
                'nombre' => $name,
                'tamano_bytes' => (int) (filesize($path) ?: 0),
                'fecha_modificacion' => date('Y-m-d H:i:s', (int) (filemtime($path) ?: time())),
            ];
        }

        usort($files, static function (array $a, array $b): int {
            return strcmp((string) ($b['fecha_modificacion'] ?? ''), (string) ($a['fecha_modificacion'] ?? ''));
        });

        return $files;
    }
}
