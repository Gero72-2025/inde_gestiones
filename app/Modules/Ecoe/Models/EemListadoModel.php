<?php

namespace App\Modules\Ecoe\Models;

class EemListadoModel extends EcoeBaseModel
{
    protected $table = 'ecoe_eem_listado';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $useAutoIncrement = true;

    protected $allowedFields = ['nombre', 'descripcion', 'estado'];

    public function listActivas(): array
    {
        return $this->where('estado', 1)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    public function listAll(): array
    {
        return $this->orderBy('estado', 'DESC')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }
}