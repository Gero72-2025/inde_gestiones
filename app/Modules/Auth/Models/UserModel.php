<?php

namespace App\Modules\Auth\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'gerencia_id',
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'status',
        'google_2fa_secret',
        'twofa_enabled',
        'last_login_at',
        'password_changed_at',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function findByIdentity(string $identity): ?array
    {
        return $this->groupStart()
            ->where('username', $identity)
            ->orWhere('email', $identity)
            ->groupEnd()
            ->first();
    }
}