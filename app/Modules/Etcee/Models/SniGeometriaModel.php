<?php

namespace App\Modules\Etcee\Models;

use CodeIgniter\Model;

class SniGeometriaModel extends Model
{
    protected $table = 'etcee_sni_geometrias';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'categoria_id',
        'linea_sistema_id',
        'nombre',
        'coordenadas',
        'propiedades',
        'usuario_id',
        'fecha_registro',
    ];
    protected $useTimestamps = false;
}
