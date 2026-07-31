<?php

namespace App\Modules\Etcee\Models;

use CodeIgniter\Model;

class SniCapaModel extends Model
{
    protected $table = 'etcee_sni_capas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'nombre',
        'slug',
        'estado',
        'color_default',
        'icono_path',
    ];
    protected $useTimestamps = false;
}
