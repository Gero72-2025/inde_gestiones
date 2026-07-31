<?php

namespace App\Modules\Ecoe\Models;

class TsBitacoraModel extends EcoeBaseModel
{
    protected $table         = 'ecoe_ts_bitacora';
    protected $allowedFields = [
        'ticket_id',
        'estado_id',
        'estado_nombre',
        'descripcion',
        'usuario_id',
        'fecha_registro',
    ];

    public function listByTicket(int $ticketId): array
    {
        if ($ticketId <= 0) {
            return [];
        }

        return $this->where('ticket_id', $ticketId)
            ->orderBy('fecha_registro', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
