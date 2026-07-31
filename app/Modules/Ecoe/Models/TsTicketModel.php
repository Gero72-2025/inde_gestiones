<?php

namespace App\Modules\Ecoe\Models;

class TsTicketModel extends EcoeBaseModel
{
    protected $table         = 'ecoe_ts_tickets';
    protected $allowedFields = [
        'id_solicitud',
        'codigo_referencia',
        'nombre',
        'direccion',
        'dpi',
        'telefono',
        'estado_id',
        'fecha_ingreso',
    ];

    /**
     * Listado para tabla administrativa con join a estados.
     */
    public function listConEstado(array $filters = []): array
    {
        $builder = $this->db->table('ecoe_ts_tickets t')
            ->select('t.*, e.nombre AS estado_nombre, e.orden_paso')
            ->join('ecoe_ts_estados e', 'e.id = t.estado_id', 'left')
            ->orderBy('t.fecha_ingreso', 'DESC');

        $q = trim((string) ($filters['q'] ?? ''));

        if ($q !== '') {
            $builder->groupStart()
                ->like('t.nombre', $q)
                ->orLike('t.dpi', $q)
                ->orLike('t.codigo_referencia', $q)
                ->groupEnd();
        }

        if (! empty($filters['estado_id'])) {
            $builder->where('t.estado_id', (int) $filters['estado_id']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Busca un ticket por DPI o código de referencia para el portal público.
     */
    public function buscarPorRastreo(string $valor): ?array
    {
        $valor = trim($valor);

        $row = $this->db->table('ecoe_ts_tickets t')
            ->select('t.*, e.nombre AS estado_nombre, e.orden_paso, e.descripcion AS estado_descripcion')
            ->join('ecoe_ts_estados e', 'e.id = t.estado_id', 'left')
            ->groupStart()
                ->where('t.dpi', $valor)
                ->orWhere('t.codigo_referencia', $valor)
            ->groupEnd()
            ->orderBy('t.fecha_ingreso', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Obtiene un ticket por ID con información de estado para documentos/resumen.
     */
    public function findConEstadoById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->db->table('ecoe_ts_tickets t')
            ->select('t.*, e.nombre AS estado_nombre, e.orden_paso, e.descripcion AS estado_descripcion')
            ->join('ecoe_ts_estados e', 'e.id = t.estado_id', 'left')
            ->where('t.id', $id)
            ->limit(1)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Genera el próximo id_solicitud (auto-correlativo en la tabla).
     */
    public function nextIdSolicitud(): int
    {
        $row = $this->db->table('ecoe_ts_tickets')->selectMax('id_solicitud')->get()->getRowArray();

        return (int) ($row['id_solicitud'] ?? 0) + 1;
    }
}
