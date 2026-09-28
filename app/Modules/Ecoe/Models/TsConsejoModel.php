<?php

namespace App\Modules\Ecoe\Models;

use CodeIgniter\Model;

class TsConsejoModel extends Model
{
    protected $table = 'ecoe_ts_consejos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['clave', 'texto', 'imagen', 'orden', 'activo'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'clave' => 'required|max_length[191]',
        'texto' => 'required|max_length[500]',
        'imagen' => 'permit_empty|max_length[255]',
        'orden' => 'required|is_natural',
        'activo' => 'required|in_list[0,1]',
    ];

    public function listOrdered(): array
    {
        return $this->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
    }
}
