<?php

namespace App\Modules\Ecoe\Models;

class DistribuidoraModel extends EcoeBaseModel
{
    protected $table         = 'ecoe_distribuidoras';
    protected $useTimestamps = false;
    protected $allowedFields = ['nombre', 'status'];

    /** Listado activo para dropdowns. */
    public function listActivas(): array
    {
        return $this->where('status', 1)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }
}
