<?php

namespace App\Modules\Etcee\Models;

class EstadosMantenimientoModel extends BaseModuleModel
{
    protected $table = 'etcee_estados_mantenimiento';
    protected $allowedFields = ['nombre', 'clave', 'color', 'activo'];

    public function listAll(): array
    {
        return $this->orderBy('nombre', 'ASC')->findAll();
    }

    public function listActive(): array
    {
        return $this->where('activo', 1)->orderBy('nombre', 'ASC')->findAll();
    }

    public function findActiveByKey(string $key): ?array
    {
        $state = $this->where('clave', strtoupper(trim($key)))
            ->where('activo', 1)
            ->first();

        return is_array($state) ? $state : null;
    }

    public function keyExists(string $key, ?int $exceptId = null): bool
    {
        $builder = $this->where('clave', $key);

        if (($exceptId ?? 0) > 0) {
            $builder->where('id !=', (int) $exceptId);
        }

        return $builder->countAllResults() > 0;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $builder = $this->where('nombre', $name);

        if (($exceptId ?? 0) > 0) {
            $builder->where('id !=', (int) $exceptId);
        }

        return $builder->countAllResults() > 0;
    }

    public function countLinkedEvents(int $id): int
    {
        if (! $this->db->tableExists('etcee_cortes')) {
            return 0;
        }

        return $this->db->table('etcee_cortes')
            ->where('estado_mantenimiento_id', $id)
            ->countAllResults();
    }
}