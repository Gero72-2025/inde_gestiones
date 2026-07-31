<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class UploadLogModel extends Model
{
    protected $table = 'upload_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'gerencia_id',
        'usuario_id',
        'nombre_archivo',
        'ruta_archivo',
        'mime_type',
        'tamano_bytes',
        'registros_procesados',
        'fecha_creacion',
    ];
    protected $useTimestamps = false;
}
