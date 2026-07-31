<?php

namespace App\Modules\Ecoe\Models;

use CodeIgniter\Model;

class BaseModuleModel extends Model
{
    protected $table = '';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
