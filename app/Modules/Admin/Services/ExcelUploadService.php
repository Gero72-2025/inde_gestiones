<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\UploadLogModel;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ExcelUploadService
{
    private const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public function process(UploadedFile $file, int $gerenciaId, int $usuarioId): array
    {
        if (! $file->isValid()) {
            throw new RuntimeException('El archivo no es valido para su procesamiento.');
        }

        $extension = strtolower((string) $file->getExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Extension no permitida. Solo se aceptan xlsx, xls y csv.');
        }

        $maxKb = (int) env('security.uploadMaxSizeKB', 10240);

        if ($file->getSizeByUnit('kb') > $maxKb) {
            throw new RuntimeException('El archivo excede el tamano maximo permitido de ' . $maxKb . ' KB.');
        }

        $targetPath = WRITEPATH . 'uploads/gerencias/' . $gerenciaId;

        if (! is_dir($targetPath) && ! mkdir($targetPath, 0755, true) && ! is_dir($targetPath)) {
            throw new RuntimeException('No se pudo crear el directorio de destino para la carga.');
        }

        $storedName = $file->getRandomName();
        $file->move($targetPath, $storedName, true);
        $fullPath = $targetPath . DIRECTORY_SEPARATOR . $storedName;

        $processedRows = $this->countRows($fullPath, $extension);

        $logModel = new UploadLogModel();
        $logModel->insert([
            'gerencia_id' => $gerenciaId,
            'usuario_id' => $usuarioId,
            'nombre_archivo' => $file->getClientName(),
            'ruta_archivo' => $fullPath,
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'tamano_bytes' => filesize($fullPath) ?: 0,
            'registros_procesados' => $processedRows,
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ]);

        return [
            'archivo' => $file->getClientName(),
            'ruta' => $fullPath,
            'registros_procesados' => $processedRows,
            'log_id' => $logModel->getInsertID(),
        ];
    }

    private function countRows(string $path, string $extension): int
    {
        if ($extension === 'csv') {
            $file = new File($path);
            $rows = 0;
            $handle = fopen($file->getRealPath(), 'rb');

            if ($handle === false) {
                return 0;
            }

            while (($data = fgetcsv($handle)) !== false) {
                if ($data === [null] || $data === false) {
                    continue;
                }

                $rows++;
            }

            fclose($handle);

            return max($rows - 1, 0);
        }

        if (! class_exists(IOFactory::class)) {
            return 0;
        }

        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestDataRow = (int) $worksheet->getHighestDataRow();

        return max($highestDataRow - 1, 0);
    }
}
