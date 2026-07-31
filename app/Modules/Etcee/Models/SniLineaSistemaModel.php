<?php

namespace App\Modules\Etcee\Models;

use CodeIgniter\Model;

class SniLineaSistemaModel extends Model
{
    protected $table = 'etcee_sni_lineas_sistema';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'nombre',
        'slug',
        'descripcion',
        'estado',
        'fecha_registro',
    ];
    protected $useTimestamps = false;
}
