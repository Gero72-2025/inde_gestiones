<?php

namespace App\Modules\Ecoe\Models;

class TsAdjuntoModel extends EcoeBaseModel
{
    protected $table         = 'ecoe_ts_adjuntos';
    protected $useTimestamps = false;
    protected $allowedFields = ['ticket_id', 'tipo_archivo', 'ruta_archivo'];

    public function porTicket(int $ticketId): array
    {
        return $this->where('ticket_id', $ticketId)->findAll();
    }

    /** Tipos de archivo permitidos. */
    public static function tiposValidos(): array
    {
        return ['dpi_frontal', 'dpi_reverso', 'factura', 'fachada1', 'fachada2'];
    }
}
