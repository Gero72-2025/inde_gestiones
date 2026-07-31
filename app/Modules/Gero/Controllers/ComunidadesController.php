<?php

namespace App\Modules\Gero\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Gero\Models\ComunidadesModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\Files\UploadedFile;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ComunidadesController extends AdminBaseController
{
    private ComunidadesModel $comunidadesModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->comunidadesModel = new ComunidadesModel();
    }

    public function index()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccess($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para gestionar comunidades GERO.');
        }

        return $this->adminView('App\\Modules\\Gero\\Views\\comunidades_index', [
            'moduleSlug' => 'gero',
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
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => $exception->getMessage()]], 400);
        }

        $q = mb_substr(trim((string) ($payload['q'] ?? '')), 0, 120);
        $fase = trim((string) ($payload['fase_actual'] ?? ''));

        return $this->encryptedJsonResponse([
            'ok' => true,
            'data' => [
                'items' => $this->comunidadesModel->listForAdmin($q, $fase),
            ],
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
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => $exception->getMessage()]], 400);
        }

        $sanitized = $this->sanitizePayload($payload);
        $validation = $this->validatePayload($sanitized, null);

        if ($validation !== null) {
            return $validation;
        }

        $insertData = $this->comunidadesModel->hydrateNormalization($sanitized);
        $insertId = (int) $this->comunidadesModel->insert($insertData, true);

        if ($insertId <= 0) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'No fue posible crear la comunidad.']], 500);
        }

        $this->comunidadesModel->addBitacora(
            $insertId,
            (string) ($insertData['fase_actual'] ?? 'fase_1'),
            (string) ($insertData['estado_actual'] ?? 'Ingresado'),
            'Registro inicial de comunidad',
            (int) (($this->session->get('auth')['user_id'] ?? 0) ?: null)
        );

        return $this->encryptedJsonResponse([
            'ok' => true,
            'data' => [
                'message' => 'Comunidad creada correctamente.',
                'item' => $this->comunidadesModel->find($insertId),
            ],
        ]);
    }

    public function update(int $id)
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        $current = $this->comunidadesModel->find($id);

        if (! is_array($current)) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Comunidad no encontrada.']], 404);
        }

        try {
            $payload = $this->decryptAjaxEnvelopeFromRequest(true);
        } catch (InvalidArgumentException $exception) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => $exception->getMessage()]], 400);
        }

        $sanitized = $this->sanitizePayload($payload);
        $validation = $this->validatePayload($sanitized, $id);

        if ($validation !== null) {
            return $validation;
        }

        $updateData = $this->comunidadesModel->hydrateNormalization($sanitized);

        $this->comunidadesModel->update($id, $updateData);

        if (
            (string) ($current['fase_actual'] ?? '') !== (string) ($updateData['fase_actual'] ?? '')
            || (string) ($current['estado_actual'] ?? '') !== (string) ($updateData['estado_actual'] ?? '')
        ) {
            $this->comunidadesModel->addBitacora(
                $id,
                (string) ($updateData['fase_actual'] ?? 'fase_1'),
                (string) ($updateData['estado_actual'] ?? 'Ingresado'),
                'Actualizacion de fase/estado desde modulo administrativo',
                (int) (($this->session->get('auth')['user_id'] ?? 0) ?: null)
            );
        }

        return $this->encryptedJsonResponse([
            'ok' => true,
            'data' => [
                'message' => 'Comunidad actualizada correctamente.',
                'item' => $this->comunidadesModel->find($id),
            ],
        ]);
    }

    public function delete(int $id)
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        if (! is_array($this->comunidadesModel->find($id))) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Comunidad no encontrada.']], 404);
        }

        $this->comunidadesModel->delete($id);

        return $this->encryptedJsonResponse([
            'ok' => true,
            'data' => ['message' => 'Comunidad eliminada correctamente.'],
        ]);
    }

    public function bitacora(int $id)
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        if (! is_array($this->comunidadesModel->find($id))) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Comunidad no encontrada.']], 404);
        }

        return $this->encryptedJsonResponse([
            'ok' => true,
            'data' => [
                'items' => $this->comunidadesModel->listBitacora($id),
            ],
        ]);
    }

    public function sourceUpload()
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        $division = strtoupper(trim((string) $this->request->getPost('division')));
        if (! in_array($division, ['DICODER', 'DOSODEP', 'DIVOC'], true)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Division invalida.'])->setStatusCode(422);
        }

        $archivo = $this->request->getFile('archivo');
        if (! $archivo instanceof UploadedFile || ! $archivo->isValid()) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Archivo no valido.'])->setStatusCode(422);
        }

        $ext = strtolower($archivo->getExtension() ?? '');
        if (! in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Solo se permiten archivos .csv, .xlsx o .xls.'])->setStatusCode(422);
        }

        try {
            $parsed = $this->extractRowsFromUploadedFile($archivo);
        } catch (\Throwable $exception) {
            return $this->response->setJSON(['ok' => false, 'message' => 'No fue posible leer el archivo.', 'error' => $exception->getMessage()])->setStatusCode(422);
        }

        $headers = $parsed['headers'];
        $rows = $parsed['rows'];

        if ($headers === [] || $rows === []) {
            return $this->response->setJSON(['ok' => false, 'message' => 'El archivo no contiene encabezados o filas de datos.'])->setStatusCode(422);
        }

        $columnsMeta = $this->buildColumnsMeta($headers, $rows);
        $normalizedColumns = array_map(static fn(array $col): string => (string) ($col['nombre_normalizado'] ?? ''), $columnsMeta);
        $incomingSchemaSet = $this->normalizeSchemaColumns($normalizedColumns);
        $schemaHash = hash('sha256', implode('|', $incomingSchemaSet));
        $tablaFisica = 'gero_comunidades_' . strtolower($division);

        $fuentesTable = db_connect()->table('gero_comunidades_fuentes');
        $latestSource = $fuentesTable->where('division', $division)->orderBy('id', 'DESC')->get()->getFirstRow('array');

        if (is_array($latestSource)) {
            $latestColumnsMeta = json_decode((string) ($latestSource['columnas_json'] ?? '[]'), true);
            $latestSchemaSet = $this->schemaSetFromColumnsMeta(is_array($latestColumnsMeta) ? $latestColumnsMeta : []);

            if ($latestSchemaSet !== [] && $latestSchemaSet !== $incomingSchemaSet) {
                return $this->response->setJSON([
                    'ok' => false,
                    'message' => 'El esquema no coincide con la primera carga de esta division. Usa el mismo set de columnas.',
                ])->setStatusCode(422);
            }
        }

        $now = date('Y-m-d H:i:s');
        $fuentesTable->insert([
            'division' => $division,
            'nombre_archivo' => $archivo->getClientName(),
            'tabla_fisica' => $tablaFisica,
            'hash_esquema' => $schemaHash,
            'columnas_json' => json_encode($columnsMeta, JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $fuenteId = (int) db_connect()->insertID();

        $this->seedMappingForSource($fuenteId, $columnsMeta, is_array($latestSource) ? (int) ($latestSource['id'] ?? 0) : 0);

        $sync = $this->syncDivisionRows($division, $rows, $normalizedColumns);

        db_connect()->table('gero_comunidades_cargas_log')->insert([
            'division' => $division,
            'tabla_fisica' => $tablaFisica,
            'nuevos' => $sync['created'],
            'modificados' => $sync['updated'],
            'sin_cambios' => $sync['unchanged'],
            'eliminados' => $sync['deleted'],
            'total_filas' => $sync['total_filas'],
            'usuario_id' => (int) (($this->session->get('auth')['user_id'] ?? 0) ?: null),
            'cargado_en' => $now,
            'created_at' => $now,
        ]);

        return $this->response->setJSON([
            'ok' => true,
            'division' => $division,
            'fuente_id' => $fuenteId,
            'tabla_fisica' => $tablaFisica,
            'total_filas' => $sync['total_filas'],
            'created' => $sync['created'],
            'updated' => $sync['updated'],
            'unchanged' => $sync['unchanged'],
            'deleted' => $sync['deleted'],
        ]);
    }

    public function sourceSchemaList()
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        $rows = db_connect()->table('gero_comunidades_fuentes')
            ->select('id, division, nombre_archivo, tabla_fisica, created_at')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $mappingTable = db_connect()->table('gero_comunidades_fuente_mapeo');

        foreach ($rows as &$row) {
            $source = db_connect()->table('gero_comunidades_fuentes')->select('columnas_json')->where('id', (int) $row['id'])->get()->getFirstRow('array');
            $columns = json_decode((string) ($source['columnas_json'] ?? '[]'), true);
            $columns = is_array($columns) ? $columns : [];

            $mappings = $mappingTable
                ->where('fuente_id', (int) $row['id'])
                ->get()
                ->getResultArray();

            $mappingByColumn = [];
            foreach ($mappings as $mapping) {
                $mappingByColumn[(string) ($mapping['columna_normalizada'] ?? '')] = $mapping;
            }

            $row['columnas'] = array_map(function (array $column) use ($mappingByColumn): array {
                $normalized = (string) ($column['nombre_normalizado'] ?? '');
                $map = $mappingByColumn[$normalized] ?? null;

                return [
                    'nombre_original' => (string) ($column['nombre_original'] ?? $normalized),
                    'nombre_normalizado' => $normalized,
                    'muestra_valor' => (string) ($column['muestra_valor'] ?? ''),
                    'etiqueta_landing' => (string) (($map['etiqueta_landing'] ?? '') ?: $this->prettifyColumn($normalized)),
                    'mostrar_landing' => (int) ($map['mostrar_landing'] ?? 1),
                    'fase_clave' => $map['fase_clave'] ?? null,
                    'usar_para_hito' => (int) ($map['usar_para_hito'] ?? 0),
                    'mostrar_card' => (int) ($map['mostrar_card'] ?? 0),
                ];
            }, $columns);
            $row['creado_en'] = $row['created_at'] ?? null;
        }
        unset($row);

        return $this->response->setJSON([
            'ok' => true,
            'data' => $rows,
        ]);
    }

    public function sourceSchemaSaveMapping()
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Payload invalido.'])->setStatusCode(422);
        }

        $fuenteId = (int) ($payload['fuente_id'] ?? 0);
        $mapeos = $payload['mapeos'] ?? [];

        if ($fuenteId <= 0 || ! is_array($mapeos)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Datos de mapeo incompletos.'])->setStatusCode(422);
        }

        $source = db_connect()->table('gero_comunidades_fuentes')->where('id', $fuenteId)->get()->getFirstRow('array');
        if (! is_array($source)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Fuente no encontrada.'])->setStatusCode(404);
        }

        $table = db_connect()->table('gero_comunidades_fuente_mapeo');
        $table->where('fuente_id', $fuenteId)->delete();

        $now = date('Y-m-d H:i:s');
        $processedColumns = [];
        foreach ($mapeos as $map) {
            if (! is_array($map)) {
                continue;
            }

            $column = mb_substr(trim((string) ($map['columna_normalizada'] ?? '')), 0, 120);
            if ($column === '') {
                continue;
            }

            if (isset($processedColumns[$column])) {
                continue;
            }
            $processedColumns[$column] = true;

            $phase = trim((string) ($map['fase_clave'] ?? ''));
            if (! in_array($phase, ['fase_1', 'fase_2', 'fase_3'], true)) {
                $phase = null;
            }

            $table->insert([
                'fuente_id' => $fuenteId,
                'columna_normalizada' => $column,
                'etiqueta_landing' => mb_substr(trim((string) ($map['etiqueta_landing'] ?? '')), 0, 160),
                'mostrar_landing' => ! empty($map['mostrar_landing']) ? 1 : 0,
                'fase_clave' => $phase,
                'usar_para_hito' => ! empty($map['usar_para_hito']) ? 1 : 0,
                'mostrar_card' => ! empty($map['mostrar_card']) ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Mapeo guardado correctamente.',
        ]);
    }

    public function sourceLogsList()
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        $rows = db_connect()->table('gero_comunidades_cargas_log')
            ->select('division, tabla_fisica, nuevos, modificados, total_filas, cargado_en')
            ->orderBy('id', 'DESC')
            ->limit(200)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'ok' => true,
            'data' => $rows,
        ]);
    }

    public function sourceCleanup()
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        $legacyTables = [
            'comunidades_dicoder',
            'comunidades_dosodep',
            'comunidades_divoc',
            'comunidades_fuentes',
            'comunidades_fuente_columnas',
            'comunidades_cargas_log',
        ];

        $dropped = [];
        foreach ($legacyTables as $table) {
            if (db_connect()->tableExists($table)) {
                db_connect()->query('DROP TABLE `' . $table . '`');
                $dropped[] = $table;
            }
        }

        $deletedSources = db_connect()->table('gero_comunidades_fuentes')->countAllResults();
        db_connect()->table('gero_comunidades_fuentes')->truncate();
        db_connect()->table('gero_comunidades_cargas_log')->truncate();

        return $this->response->setJSON([
            'ok' => true,
            'dropped_tables' => $dropped,
            'deleted_sources' => $deletedSources,
        ]);
    }

    private function extractRowsFromUploadedFile(UploadedFile $file): array
    {
        $path = $file->getTempName();
        $ext = strtolower($file->getExtension() ?? '');

        if ($ext === 'csv') {
            $handle = fopen($path, 'rb');
            if (! is_resource($handle)) {
                throw new \RuntimeException('No se pudo abrir el CSV.');
            }

            $headers = [];
            $rows = [];
            $line = 0;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $line++;
                if ($line === 1) {
                    $headers = array_map(static fn($v): string => trim((string) $v), $data);
                    continue;
                }

                $assoc = [];
                foreach ($headers as $idx => $header) {
                    $assoc[$header] = trim((string) ($data[$idx] ?? ''));
                }
                if ($this->rowIsEmpty($assoc)) {
                    continue;
                }
                $rows[] = $assoc;
            }

            fclose($handle);
            return ['headers' => $headers, 'rows' => $rows];
        }

        $sheet = IOFactory::load($path)->getActiveSheet();
        $data = $sheet->toArray('', true, true, false);
        if (! is_array($data) || count($data) < 2) {
            throw new \RuntimeException('El archivo no contiene suficientes filas.');
        }

        $headers = array_map(static fn($v): string => trim((string) $v), (array) ($data[0] ?? []));
        $rows = [];

        for ($i = 1, $len = count($data); $i < $len; $i++) {
            $line = (array) ($data[$i] ?? []);
            $assoc = [];
            foreach ($headers as $idx => $header) {
                $assoc[$header] = trim((string) ($line[$idx] ?? ''));
            }
            if ($this->rowIsEmpty($assoc)) {
                continue;
            }
            $rows[] = $assoc;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function buildColumnsMeta(array $headers, array $rows): array
    {
        $firstRow = $rows[0] ?? [];
        $result = [];
        $seen = [];

        foreach ($headers as $header) {
            $normalized = $this->normalizeColumnName((string) $header);
            if ($normalized === '') {
                continue;
            }

            if (isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;

            $result[] = [
                'nombre_original' => (string) $header,
                'nombre_normalizado' => $normalized,
                'muestra_valor' => mb_substr(trim((string) ($firstRow[$header] ?? '')), 0, 200),
            ];
        }

        return $result;
    }

    private function seedMappingForSource(int $fuenteId, array $columnsMeta, int $copyFromFuenteId = 0): void
    {
        $table = db_connect()->table('gero_comunidades_fuente_mapeo');
        $now = date('Y-m-d H:i:s');
        $existing = [];
        $inserted = [];

        if ($copyFromFuenteId > 0) {
            $existingRows = $table->where('fuente_id', $copyFromFuenteId)->get()->getResultArray();
            foreach ($existingRows as $row) {
                $existing[(string) ($row['columna_normalizada'] ?? '')] = $row;
            }
        }

        foreach ($columnsMeta as $column) {
            $normalized = (string) ($column['nombre_normalizado'] ?? '');
            if ($normalized === '') {
                continue;
            }

            if (isset($inserted[$normalized])) {
                continue;
            }
            $inserted[$normalized] = true;

            $seed = $existing[$normalized] ?? $this->defaultMappingForColumn($normalized);
            $table->insert([
                'fuente_id' => $fuenteId,
                'columna_normalizada' => $normalized,
                'etiqueta_landing' => (string) ($seed['etiqueta_landing'] ?? $this->prettifyColumn($normalized)),
                'mostrar_landing' => (int) ($seed['mostrar_landing'] ?? 1),
                'fase_clave' => $seed['fase_clave'] ?? null,
                'usar_para_hito' => (int) ($seed['usar_para_hito'] ?? 0),
                'mostrar_card' => (int) ($seed['mostrar_card'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function syncDivisionRows(string $division, array $rows, array $normalizedColumns): array
    {
        $existingRows = $this->comunidadesModel->where('division_origen', $division)->findAll();
        $existingByKey = [];
        $remainingIds = [];

        foreach ($existingRows as $existing) {
            $key = $this->communityKeyFromRecord($existing);
            if ($key === '') {
                continue;
            }
            $existingByKey[$key] = $existing;
            $remainingIds[(int) ($existing['id'] ?? 0)] = true;
        }

        $created = 0;
        $updated = 0;
        $unchanged = 0;

        foreach ($rows as $sourceRow) {
            $normalizedRow = [];
            foreach ($sourceRow as $header => $value) {
                $normalizedHeader = $this->normalizeColumnName((string) $header);
                if ($normalizedHeader === '') {
                    continue;
                }
                $normalizedRow[$normalizedHeader] = trim((string) $value);
            }

            $payload = $this->mapSourceRowToComunidadPayload($normalizedRow, $division, $normalizedColumns);
            $recordKey = $this->communityKeyFromPayload($payload);

            if ($recordKey === '') {
                continue;
            }

            $current = $existingByKey[$recordKey] ?? null;
            if (! is_array($current)) {
                $current = $this->findExistingByUniqueFields((string) ($payload['codigo_comunidad'] ?? ''), (string) ($payload['numero_snip'] ?? ''));
            }

            $finalPayload = $this->comunidadesModel->hydrateNormalization($payload);

            if (! is_array($current)) {
                $newId = (int) $this->comunidadesModel->insert($finalPayload, true);
                if ($newId > 0) {
                    $created++;
                    $this->comunidadesModel->addBitacora(
                        $newId,
                        (string) ($finalPayload['fase_actual'] ?? 'fase_1'),
                        (string) ($finalPayload['estado_actual'] ?? 'Ingresado'),
                        'Carga masiva (' . $division . ')',
                        (int) (($this->session->get('auth')['user_id'] ?? 0) ?: null)
                    );
                }
                continue;
            }

            $currentId = (int) ($current['id'] ?? 0);
            unset($remainingIds[$currentId]);

            if (! $this->hasRecordChanges($current, $finalPayload)) {
                $unchanged++;
                continue;
            }

            $this->comunidadesModel->update($currentId, $finalPayload);
            if (
                (string) ($current['fase_actual'] ?? '') !== (string) ($finalPayload['fase_actual'] ?? '')
                || (string) ($current['estado_actual'] ?? '') !== (string) ($finalPayload['estado_actual'] ?? '')
            ) {
                $this->comunidadesModel->addBitacora(
                    $currentId,
                    (string) ($finalPayload['fase_actual'] ?? 'fase_1'),
                    (string) ($finalPayload['estado_actual'] ?? 'Ingresado'),
                    'Actualizacion por carga masiva (' . $division . ')',
                    (int) (($this->session->get('auth')['user_id'] ?? 0) ?: null)
                );
            }
            $updated++;
        }

        $deleteIds = array_keys($remainingIds);
        if ($deleteIds !== []) {
            $this->comunidadesModel->whereIn('id', $deleteIds)->delete();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'deleted' => count($deleteIds),
            'total_filas' => count($rows),
        ];
    }

    private function findExistingByUniqueFields(string $codigo, string $snip): ?array
    {
        if ($codigo !== '') {
            $byCode = $this->comunidadesModel->where('codigo_comunidad', $codigo)->first();
            if (is_array($byCode)) {
                return $byCode;
            }
        }

        if ($snip !== '') {
            $bySnip = $this->comunidadesModel->where('numero_snip', $snip)->first();
            if (is_array($bySnip)) {
                return $bySnip;
            }
        }

        return null;
    }

    private function hasRecordChanges(array $current, array $payload): bool
    {
        $fields = [
            'codigo_comunidad',
            'numero_snip',
            'nombre_comunidad',
            'municipio',
            'departamento',
            'fase_actual',
            'estado_actual',
            'solicitud_firmada',
            'estudio_socioeconomico',
            'snip_aprobado',
            'licitacion_terminada',
            'obra_energizada',
            'division_origen',
            'campos_adicionales_json',
        ];

        foreach ($fields as $field) {
            if ((string) ($current[$field] ?? '') !== (string) ($payload[$field] ?? '')) {
                return true;
            }
        }

        return false;
    }

    private function mapSourceRowToComunidadPayload(array $row, string $division, array $normalizedColumns): array
    {
        $codigo = $this->pickFirst($row, ['codigo_comunidad', 'codigo', 'cod_comunidad', 'id_comunidad']);
        $snip = $this->pickFirst($row, ['numero_snip', 'snip', 'no_snip']);
        $nombre = $this->pickFirst($row, ['nombre_comunidad', 'comunidad', 'nombre', 'aldea']);
        $municipio = $this->pickFirst($row, ['municipio']);
        $departamento = $this->pickFirst($row, ['departamento']);
        $estado = $this->pickFirst($row, ['estado_actual', 'estado', 'estatus']);

        $fase = $this->normalizeFaseValue($this->pickFirst($row, ['fase_actual', 'fase', 'etapa']));

        $payload = [
            'codigo_comunidad' => $this->normalizeNullableText($codigo, 60),
            'numero_snip' => $this->normalizeNullableText($snip, 60),
            'nombre_comunidad' => mb_substr($nombre, 0, 255),
            'municipio' => mb_substr($municipio, 0, 120),
            'departamento' => mb_substr($departamento, 0, 120),
            'fase_actual' => $fase,
            'estado_actual' => mb_substr($estado !== '' ? $estado : 'Ingresado', 0, 120),
            'division_origen' => $division,
            'solicitud_firmada' => $this->normalizeBoolValue($this->pickFirst($row, ['solicitud_firmada', 'solicitud', 'solicitud_aprobada'])) ? 1 : 0,
            'estudio_socioeconomico' => $this->normalizeBoolValue($this->pickFirst($row, ['estudio_socioeconomico', 'estudio', 'estudio_realizado'])) ? 1 : 0,
            'snip_aprobado' => $this->normalizeBoolValue($this->pickFirst($row, ['snip_aprobado', 'snip', 'snip_ok'])) ? 1 : 0,
            'licitacion_terminada' => $this->normalizeBoolValue($this->pickFirst($row, ['licitacion_terminada', 'licitacion', 'licitacion_finalizada'])) ? 1 : 0,
            'obra_energizada' => $this->normalizeBoolValue($this->pickFirst($row, ['obra_energizada', 'energizada', 'obra_finalizada'])) ? 1 : 0,
            'campos_adicionales_json' => json_encode($this->extractAdditionalFields($row, $normalizedColumns), JSON_UNESCAPED_UNICODE),
        ];

        if ($payload['nombre_comunidad'] === '') {
            $codigoLabel = trim((string) ($payload['codigo_comunidad'] ?? ''));
            $snipLabel = trim((string) ($payload['numero_snip'] ?? ''));
            $payload['nombre_comunidad'] = $codigoLabel !== '' ? $codigoLabel : ($snipLabel !== '' ? $snipLabel : 'SIN NOMBRE');
        }
        if ($payload['municipio'] === '') {
            $payload['municipio'] = 'SIN MUNICIPIO';
        }

        return $payload;
    }

    private function extractAdditionalFields(array $row, array $normalizedColumns): array
    {
        $known = [
            'codigo_comunidad', 'codigo', 'cod_comunidad', 'id_comunidad',
            'numero_snip', 'snip', 'no_snip',
            'nombre_comunidad', 'comunidad', 'nombre', 'aldea',
            'municipio', 'departamento',
            'fase_actual', 'fase', 'etapa',
            'estado_actual', 'estado', 'estatus',
            'solicitud_firmada', 'solicitud', 'solicitud_aprobada',
            'estudio_socioeconomico', 'estudio', 'estudio_realizado',
            'snip_aprobado', 'snip_ok',
            'licitacion_terminada', 'licitacion', 'licitacion_finalizada',
            'obra_energizada', 'energizada', 'obra_finalizada',
        ];

        $result = [];
        foreach ($normalizedColumns as $column) {
            if (in_array($column, $known, true)) {
                continue;
            }
            $value = trim((string) ($row[$column] ?? ''));
            if ($value === '') {
                continue;
            }
            $result[$column] = $value;
        }

        return $result;
    }

    private function normalizeColumnName(string $value): string
    {
        $text = mb_strtolower(trim($value));
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('/[^a-z0-9]+/', '_', $text) ?? '';
        $text = trim($text, '_');

        return mb_substr($text, 0, 120);
    }

    private function normalizeSchemaColumns(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $column) {
            $name = mb_substr(trim((string) $column), 0, 120);
            if ($name === '') {
                continue;
            }
            $normalized[$name] = true;
        }

        $result = array_keys($normalized);
        sort($result);

        return $result;
    }

    private function schemaSetFromColumnsMeta(array $columnsMeta): array
    {
        $columns = [];
        foreach ($columnsMeta as $column) {
            if (! is_array($column)) {
                continue;
            }
            $columns[] = (string) ($column['nombre_normalizado'] ?? '');
        }

        return $this->normalizeSchemaColumns($columns);
    }

    private function normalizeFaseValue(string $value): string
    {
        $normalized = mb_strtolower(trim($value));

        if (preg_match('/\b3\b|fase[_\s-]*3|ejecucion|contratacion/u', $normalized) === 1) {
            return 'fase_3';
        }

        if (preg_match('/\b2\b|fase[_\s-]*2|pre\s*inversion|preinversion/u', $normalized) === 1) {
            return 'fase_2';
        }

        return 'fase_1';
    }

    private function normalizeBoolValue(string $value): bool
    {
        $normalized = mb_strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, ['1', 'si', 'sí', 'true', 'x', 'ok', 'completo', 'aprobado', 'finalizado'], true);
    }

    private function pickFirst(array $row, array $candidates): string
    {
        foreach ($candidates as $column) {
            $value = trim((string) ($row[$column] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function communityKeyFromPayload(array $payload): string
    {
        $codigo = trim((string) ($payload['codigo_comunidad'] ?? ''));
        if ($codigo !== '') {
            return 'codigo:' . mb_strtolower($codigo);
        }

        $snip = trim((string) ($payload['numero_snip'] ?? ''));
        if ($snip !== '') {
            return 'snip:' . mb_strtolower($snip);
        }

        $nombre = $this->comunidadesModel->normalizeKey((string) ($payload['nombre_comunidad'] ?? ''));
        $municipio = $this->comunidadesModel->normalizeKey((string) ($payload['municipio'] ?? ''));

        if ($nombre === '') {
            return '';
        }

        return 'name:' . $nombre . '|' . $municipio;
    }

    private function communityKeyFromRecord(array $record): string
    {
        return $this->communityKeyFromPayload([
            'codigo_comunidad' => $record['codigo_comunidad'] ?? '',
            'numero_snip' => $record['numero_snip'] ?? '',
            'nombre_comunidad' => $record['nombre_comunidad'] ?? '',
            'municipio' => $record['municipio'] ?? '',
        ]);
    }

    private function prettifyColumn(string $value): string
    {
        $parts = array_filter(explode('_', mb_strtolower($value)));
        $parts = array_map(static fn(string $part): string => mb_convert_case($part, MB_CASE_TITLE, 'UTF-8'), $parts);

        return trim(implode(' ', $parts));
    }

    private function defaultMappingForColumn(string $column): array
    {
        $phase = null;
        if (str_contains($column, 'fase_1') || str_contains($column, 'solicitud')) {
            $phase = 'fase_1';
        } elseif (str_contains($column, 'fase_2') || str_contains($column, 'preinversion') || str_contains($column, 'snip')) {
            $phase = 'fase_2';
        } elseif (str_contains($column, 'fase_3') || str_contains($column, 'licitacion') || str_contains($column, 'energizada')) {
            $phase = 'fase_3';
        }

        return [
            'etiqueta_landing' => $this->prettifyColumn($column),
            'mostrar_landing' => 1,
            'fase_clave' => $phase,
            'usar_para_hito' => (str_contains($column, 'fase') || str_contains($column, 'estado') || str_contains($column, 'hito')) ? 1 : 0,
            'mostrar_card' => (str_contains($column, 'fase') || str_contains($column, 'estado')) ? 1 : 0,
        ];
    }

    private function sanitizePayload(array $payload): array
    {
        $data = [
            'codigo_comunidad' => $this->normalizeNullableText($payload['codigo_comunidad'] ?? null, 60),
            'numero_snip' => $this->normalizeNullableText($payload['numero_snip'] ?? null, 60),
            'nombre_comunidad' => mb_substr(trim((string) ($payload['nombre_comunidad'] ?? '')), 0, 255),
            'municipio' => mb_substr(trim((string) ($payload['municipio'] ?? '')), 0, 120),
            'departamento' => mb_substr(trim((string) ($payload['departamento'] ?? '')), 0, 120),
            'fase_actual' => trim((string) ($payload['fase_actual'] ?? 'fase_1')),
            'estado_actual' => mb_substr(trim((string) ($payload['estado_actual'] ?? 'Ingresado')), 0, 120),
            'division_origen' => strtoupper(trim((string) ($payload['division_origen'] ?? ''))),
            'solicitud_firmada' => ! empty($payload['solicitud_firmada']) ? 1 : 0,
            'solicitud_firmada_fecha' => $this->normalizeDate($payload['solicitud_firmada_fecha'] ?? null),
            'estudio_socioeconomico' => ! empty($payload['estudio_socioeconomico']) ? 1 : 0,
            'estudio_socioeconomico_fecha' => $this->normalizeDate($payload['estudio_socioeconomico_fecha'] ?? null),
            'snip_aprobado' => ! empty($payload['snip_aprobado']) ? 1 : 0,
            'snip_aprobado_fecha' => $this->normalizeDate($payload['snip_aprobado_fecha'] ?? null),
            'licitacion_terminada' => ! empty($payload['licitacion_terminada']) ? 1 : 0,
            'licitacion_terminada_fecha' => $this->normalizeDate($payload['licitacion_terminada_fecha'] ?? null),
            'obra_energizada' => ! empty($payload['obra_energizada']) ? 1 : 0,
            'obra_energizada_fecha' => $this->normalizeDate($payload['obra_energizada_fecha'] ?? null),
        ];

        if (! in_array($data['fase_actual'], ['fase_1', 'fase_2', 'fase_3'], true)) {
            $data['fase_actual'] = 'fase_1';
        }

        if (! in_array($data['division_origen'], ['DICODER', 'DOSODEP', 'DIVOC'], true)) {
            $data['division_origen'] = null;
        }

        return $data;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $ts = strtotime($text);

        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }

    private function normalizeNullableText(mixed $value, int $maxLength): ?string
    {
        $text = mb_substr(trim((string) $value), 0, $maxLength);

        return $text === '' ? null : $text;
    }

    private function validatePayload(array $data, ?int $excludeId)
    {
        if ($data['nombre_comunidad'] === '' || $data['municipio'] === '') {
            return $this->encryptedJsonResponse([
                'ok' => false,
                'data' => ['message' => 'Nombre de comunidad y municipio son obligatorios.'],
            ], 422);
        }

        if (trim((string) ($data['codigo_comunidad'] ?? '')) !== '') {
            $duplicate = $this->comunidadesModel
                ->where('codigo_comunidad', $data['codigo_comunidad'])
                ->where('id !=', $excludeId ?? -1)
                ->first();

            if (is_array($duplicate)) {
                return $this->encryptedJsonResponse([
                    'ok' => false,
                    'data' => ['message' => 'El codigo de comunidad ya existe.'],
                ], 422);
            }
        }

        if (trim((string) ($data['numero_snip'] ?? '')) !== '') {
            $duplicate = $this->comunidadesModel
                ->where('numero_snip', $data['numero_snip'])
                ->where('id !=', $excludeId ?? -1)
                ->first();

            if (is_array($duplicate)) {
                return $this->encryptedJsonResponse([
                    'ok' => false,
                    'data' => ['message' => 'El numero SNIP ya existe.'],
                ], 422);
            }
        }

        return null;
    }

    private function requireApiAuth()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'Sesion no valida.']], 401);
        }

        if (! $this->canAccess($auth)) {
            return $this->encryptedJsonResponse(['ok' => false, 'data' => ['message' => 'No tienes permisos para administrar GERO Comunidades.']], 403);
        }

        return null;
    }

    private function canAccess(array $auth): bool
    {
        $permissions = array_map('strval', (array) ($auth['permissions'] ?? []));

        return $this->rbac->isSuperAdminByPermissions($permissions)
            || $this->rbac->hasPermission($permissions, 'gerencia.gero.comunidades.access')
            || $this->rbac->hasPermission($permissions, 'gerencia.gero.modulo.access')
            || $this->rbac->hasPermission($permissions, 'gerencia.gero.dashboard.access')
            || (
                (($auth['gerencia_slug'] ?? '') === 'gero')
                && $this->rbac->hasPermission($permissions, 'gerencia.gero.access')
            );
    }
}
