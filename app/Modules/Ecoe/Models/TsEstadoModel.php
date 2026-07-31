<?php

namespace App\Modules\Ecoe\Models;

class TsEstadoModel extends EcoeBaseModel
{
    protected $table         = 'ecoe_ts_estados';
    protected $useTimestamps = false;
    protected $allowedFields = ['nombre', 'orden_paso', 'descripcion'];

    /** Todos los estados ordenados por paso. */
    public function listOrdenados(): array
    {
        return $this->orderBy('orden_paso', 'ASC')->findAll();
    }
}
