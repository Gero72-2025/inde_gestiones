<?php

namespace App\Modules\Etcee\Controllers;

use App\Modules\Admin\Controllers\AdminBaseController;
use App\Modules\Etcee\Models\CortesModel;
use App\Modules\Etcee\Models\EstadosMantenimientoModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\Files\UploadedFile;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class CortesController extends AdminBaseController
{
    private const IMPORT_HEADERS = [
        'Título',
        'Clave de Estado',
        'Fecha Inicio (YYYY-MM-DD HH:mm)',
        'Fecha Fin (YYYY-MM-DD HH:mm)',
        'Descripción',
        'Departamentos (IDs o Nombres)',
        'Municipios (IDs o Nombres)',
    ];

    private const MAX_IMPORT_ROWS = 500;

    private CortesModel $cortesModel;
    private EstadosMantenimientoModel $estadosModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->cortesModel = new CortesModel();
        $this->estadosModel = new EstadosMantenimientoModel();
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
            'estadosMantenimiento' => $this->estadosModel->listAll(),
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
            'estados_mantenimiento' => $this->estadosModel->listAll(),
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

    public function downloadTemplate()
    {
        $auth = $this->authProfile();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (! $this->canAccessEtcee($auth)) {
            return redirect()->to('admin')->with('error', 'No tienes permisos para descargar la plantilla ETCEE.');
        }

        $spreadsheet = new Spreadsheet();
        $instructions = $spreadsheet->getActiveSheet();
        $instructions->setTitle('Instrucciones');
        $instructions->fromArray([
            ['Plantilla de mantenimientos programados'],
            ['Completa únicamente la hoja "Mantenimientos" y conserva sus encabezados.'],
            ['Las fechas deben usar el formato YYYY-MM-DD HH:mm, por ejemplo 2026-10-15 08:00.'],
            ['La clave debe existir y estar activa en Estados de Mantenimiento (por ejemplo P o NP).'],
            ['Separa varios departamentos o municipios con punto y coma (;) o barra vertical (|).'],
            ['Puedes indicar ubicaciones por ID o nombre; los municipios deben pertenecer a los departamentos indicados.'],
            ['La fila de ejemplo de abajo solo ilustra el formato; no la copies a la hoja Mantenimientos.'],
            ['Título', 'Clave', 'Fecha inicio', 'Fecha fin', 'Descripción', 'Departamentos', 'Municipios'],
            ['Ejemplo (no importar)', 'P', '2026-10-15 08:00', '2026-10-15 18:00', 'Mantenimiento programado', '2; 5 o nombres', '46; 50 o nombres'],
        ], null, 'A1');
        $instructions->getColumnDimension('A')->setWidth(54);
        foreach (range('B', 'G') as $column) {
            $instructions->getColumnDimension($column)->setWidth(25);
        }
        $instructions->getStyle('A1:G9')->getAlignment()->setWrapText(true);
        $instructions->getStyle('A1:G1')->getFont()->setBold(true)->setSize(14);
        $instructions->getStyle('A8:G8')->getFont()->setBold(true);

        $dataSheet = $spreadsheet->createSheet();
        $dataSheet->setTitle('Mantenimientos');
        $dataSheet->fromArray(self::IMPORT_HEADERS, null, 'A1');
        $dataSheet->getStyle('A1:G1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $dataSheet->getStyle('A1:G1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF1A56DB');
        $dataSheet->getStyle('A1:G1')->getAlignment()->setWrapText(true);
        $dataSheet->freezePane('A2');
        $dataSheet->setAutoFilter('A1:G1');
        foreach (range('A', 'G') as $column) {
            $dataSheet->getColumnDimension($column)->setWidth(30);
        }

        ob_start();
        try {
            (new Xlsx($spreadsheet))->save('php://output');
            $contents = (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            $spreadsheet->disconnectWorksheets();
            return $this->response->setStatusCode(500)->setBody('No fue posible generar la plantilla.');
        }
        $spreadsheet->disconnectWorksheets();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="plantilla_mantenimientos_etcee.xlsx"')
            ->setBody($contents);
    }

    public function importTemplate()
    {
        $auth = $this->requireApiAuth();

        if ($auth !== null) {
            return $auth;
        }

        $file = $this->request->getFile('maintenance_file');
        if (! $file instanceof UploadedFile || ! $file->isValid() || $file->hasMoved()) {
            return $this->encryptedAdminResponse(['message' => 'Selecciona un archivo XLSX o CSV válido.'], 422);
        }

        $extension = strtolower((string) $file->getClientExtension());
        $maxBytes = max(1, (int) env('security.uploadMaxSizeKB', 10240)) * 1024;
        if (! in_array($extension, ['xlsx', 'csv'], true) || $file->getSize() > $maxBytes) {
            return $this->encryptedAdminResponse(['message' => 'Solo se permiten archivos .xlsx o .csv de hasta ' . (int) ceil($maxBytes / 1024 / 1024) . ' MB.'], 422);
        }

        try {
            $parsed = $this->readMaintenanceImport($file, $extension);
        } catch (Throwable $exception) {
            return $this->encryptedAdminResponse(['message' => 'No fue posible leer la plantilla. Verifica que no esté dañada y usa la hoja o encabezados descargados desde este módulo.'], 422);
        }

        $expectedHeaders = array_map(static fn (string $header): string => mb_strtolower($header), self::IMPORT_HEADERS);
        $receivedHeaders = array_map(static fn ($header): string => mb_strtolower(trim((string) $header)), $parsed['headers']);
        if ($receivedHeaders !== $expectedHeaders) {
            return $this->encryptedAdminResponse(['message' => 'Los encabezados no coinciden con la plantilla. Descarga una plantilla nueva y conserva sus columnas en el mismo orden.'], 422);
        }

        if ($parsed['rows'] === []) {
            return $this->encryptedAdminResponse(['message' => 'La hoja no contiene filas para importar.'], 422);
        }

        if (count($parsed['rows']) > self::MAX_IMPORT_ROWS) {
            return $this->encryptedAdminResponse(['message' => 'El archivo supera el máximo de ' . self::MAX_IMPORT_ROWS . ' mantenimientos por carga.'], 422);
        }

        $validatedRows = [];
        $errors = [];
        foreach ($parsed['rows'] as $row) {
            $values = array_pad((array) $row['values'], count(self::IMPORT_HEADERS), '');
            $title = trim((string) $values[0]);
            $stateKey = strtoupper(trim((string) $values[1]));
            $start = $this->normalizeImportDate($values[2]);
            $end = $this->normalizeImportDate($values[3]);
            $description = trim((string) $values[4]);
            $departmentReferences = $this->splitImportReferences($values[5]);
            $municipalityReferences = $this->splitImportReferences($values[6]);
            $rowErrors = [];

            if ($title === '' || mb_strlen($title) > 180) {
                $rowErrors[] = 'El título es obligatorio y admite hasta 180 caracteres.';
            }
            if ($stateKey === '') {
                $rowErrors[] = 'La clave de estado es obligatoria.';
                $maintenanceState = null;
            } else {
                $maintenanceState = $this->estadosModel->findActiveByKey($stateKey);
                if ($maintenanceState === null) {
                    $rowErrors[] = 'La clave de estado "' . $stateKey . '" no existe o está inactiva.';
                }
            }
            if ($start === null) {
                $rowErrors[] = 'La fecha de inicio no es válida; usa YYYY-MM-DD HH:mm.';
            }
            if ($end === null) {
                $rowErrors[] = 'La fecha de fin no es válida; usa YYYY-MM-DD HH:mm.';
            } elseif ($start !== null && strtotime($end) <= strtotime($start)) {
                $rowErrors[] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
            }

            $locations = [];
            try {
                $locations = $this->cortesModel->resolveImportedLocations($departmentReferences, $municipalityReferences);
            } catch (InvalidArgumentException $exception) {
                $rowErrors[] = $exception->getMessage();
            }

            if ($rowErrors !== []) {
                $errors[] = 'Fila ' . (int) $row['line'] . ': ' . implode(' ', $rowErrors);
                continue;
            }

            $stateKeyForLegacyStatus = (string) $maintenanceState['clave'];
            $validatedRows[] = [
                'event' => [
                    'titulo' => $title,
                    'descripcion' => $description,
                    'fecha_inicio' => $start,
                    'fecha_fin' => $end,
                    'estado' => $this->legacyStatusForKey($stateKeyForLegacyStatus),
                    'estado_mantenimiento_id' => (int) $maintenanceState['id'],
                    'created_by' => (int) ($this->session->get('auth')['user_id'] ?? 0),
                    'updated_by' => (int) ($this->session->get('auth')['user_id'] ?? 0),
                ],
                'locations' => $locations,
            ];
        }

        if ($errors !== []) {
            return $this->encryptedAdminResponse([
                'message' => 'No se importó ningún registro porque hay filas con errores.',
                'errors' => array_slice($errors, 0, 50),
                'error_count' => count($errors),
            ], 422);
        }

        try {
            $created = $this->cortesModel->insertEventsAtomically($validatedRows);
        } catch (Throwable $exception) {
            log_message('error', 'Importación ETCEE cancelada: {message}', ['message' => $exception->getMessage()]);
            return $this->encryptedAdminResponse(['message' => 'No se pudo guardar el lote completo. La transacción fue revertida.'], 500);
        }

        return $this->encryptedAdminResponse([
            'message' => 'Carga completada correctamente.',
            'created_count' => $created,
        ]);
    }

    private function readMaintenanceImport(UploadedFile $file, string $extension): array
    {
        $headers = [];
        $rows = [];

        if ($extension === 'csv') {
            $handle = fopen($file->getTempName(), 'rb');
            if (! is_resource($handle)) {
                throw new \RuntimeException('No se pudo abrir el CSV.');
            }

            try {
                $firstLine = fgets($handle);
                if ($firstLine === false) {
                    return ['headers' => [], 'rows' => []];
                }
                $delimiter = $this->detectCsvDelimiter($firstLine);
                rewind($handle);
                $headers = fgetcsv($handle, 0, $delimiter) ?: [];
                if (isset($headers[0])) {
                    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
                }
                $line = 1;
                while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $line++;
                    if ($this->importRowIsEmpty($values)) {
                        continue;
                    }
                    $rows[] = ['line' => $line, 'values' => $values];
                }
            } finally {
                fclose($handle);
            }

            return ['headers' => $headers, 'rows' => $rows];
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getTempName());
        try {
            $sheet = $spreadsheet->getSheetByName('Mantenimientos');
            if ($sheet === null) {
                throw new \InvalidArgumentException('Falta la hoja Mantenimientos.');
            }

            $data = $sheet->toArray(null, true, true, false);
            $headers = (array) ($data[0] ?? []);
            foreach (array_slice($data, 1, null, true) as $index => $values) {
                if ($this->importRowIsEmpty((array) $values)) {
                    continue;
                }
                $rows[] = ['line' => (int) $index + 1, 'values' => $values];
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function detectCsvDelimiter(string $line): string
    {
        $delimiter = ',';
        $maxColumns = 1;

        foreach ([',', ';', "\t"] as $candidate) {
            $columnCount = count(str_getcsv($line, $candidate));
            if ($columnCount > $maxColumns) {
                $delimiter = $candidate;
                $maxColumns = $columnCount;
            }
        }

        return $delimiter;
    }

    private function importRowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function splitImportReferences(mixed $value): array
    {
        $parts = preg_split('/[;|\r\n]+/u', trim((string) $value)) ?: [];
        $references = [];
        foreach ($parts as $part) {
            $reference = trim($part);
            if ($reference !== '') {
                $references[$reference] = $reference;
            }
        }

        return array_values($references);
    }

    private function normalizeImportDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_numeric($value) && (float) $value > 0) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
            } catch (Throwable $exception) {
                return null;
            }
        }

        $value = trim(str_replace('T', ' ', (string) $value));
        foreach (['!Y-m-d H:i', '!Y-m-d H:i:s'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return null;
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

        $maintenanceState = $this->resolveMaintenanceState($payload);
        if ($maintenanceState === null) {
            return $this->encryptedAdminResponse(['message' => 'Selecciona un estado de mantenimiento activo.'], 422);
        }

        try {
            $eventId = $this->cortesModel->saveEvent(null, [
                'titulo' => trim((string) $payload['titulo']),
                'descripcion' => trim((string) ($payload['descripcion'] ?? '')),
                'fecha_inicio' => (string) $payload['fecha_inicio'],
                'fecha_fin' => (string) $payload['fecha_fin'],
                'estado' => $this->legacyStatusForKey((string) $maintenanceState['clave']),
                'estado_mantenimiento_id' => (int) $maintenanceState['id'],
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

        $maintenanceState = $this->resolveMaintenanceState($payload, $id);
        if ($maintenanceState === null) {
            return $this->encryptedAdminResponse(['message' => 'Selecciona un estado de mantenimiento activo.'], 422);
        }

        try {
            $this->cortesModel->saveEvent($id, [
                'titulo' => trim((string) $payload['titulo']),
                'descripcion' => trim((string) ($payload['descripcion'] ?? '')),
                'fecha_inicio' => (string) $payload['fecha_inicio'],
                'fecha_fin' => (string) $payload['fecha_fin'],
                'estado' => $this->legacyStatusForKey((string) $maintenanceState['clave']),
                'estado_mantenimiento_id' => (int) $maintenanceState['id'],
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

    private function resolveMaintenanceState(array $payload, ?int $eventId = null): ?array
    {
        $id = max((int) ($payload['estado_mantenimiento_id'] ?? 0), 0);
        $state = $id > 0 ? $this->estadosModel->find($id) : null;

        if (! is_array($state)) {
            return null;
        }

        if (! empty($state['activo'])) {
            return $state;
        }

        $existingEvent = ($eventId ?? 0) > 0 ? $this->cortesModel->find((int) $eventId) : null;

        return is_array($existingEvent) && (int) ($existingEvent['estado_mantenimiento_id'] ?? 0) === $id
            ? $state
            : null;
    }

    private function legacyStatusForKey(string $key): string
    {
        return match (strtoupper($key)) {
            'C' => 'cancelado',
            'FP' => 'finalizado',
            'IF', 'A' => 'activo',
            default => 'programado',
        };
    }
}
