<?php

namespace App\Models;

use CodeIgniter\Model;

class LanguageModel extends Model
{
    protected $table = 'languages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['code', 'name', 'is_active', 'is_visible'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'code' => 'required|max_length[10]|regex_match[/^[a-z][a-z0-9-]{1,9}$/]',
        'name' => 'required|max_length[100]',
    ];
}