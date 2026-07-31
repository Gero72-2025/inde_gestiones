<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class GerenciaModel extends Model
{
    protected $table = 'gerencias';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'nombre',
        'descripcion',
        'slug',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
