<?php

namespace App\Modules\Etcee\Models;

class CortesModel extends BaseModuleModel
{
    protected $table = 'etcee_cortes';
    protected $allowedFields = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'color',
        'created_by',
        'updated_by',
    ];

    public function catalogoDepartamentos(): array
    {
        if (! $this->db->tableExists('cat_departamentos')) {
            return [];
        }

        return $this->db->table('cat_departamentos')
            ->select('id, nombre')
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function catalogoMunicipios(?int $departamentoId = null): array
    {
        if (! $this->db->tableExists('cat_municipios')) {
            return [];
        }

        $builder = $this->db->table('cat_municipios')
            ->select('id, departamento_id, nombre')
            ->orderBy('nombre', 'ASC');

        if (($departamentoId ?? 0) > 0) {
            $builder->where('departamento_id', (int) $departamentoId);
        }

        return $builder->get()->getResultArray();
    }

    public function listCalendar(?int $departamentoId = null, ?int $municipioId = null): array
    {
        if (! $this->db->tableExists('etcee_cortes') || ! $this->db->tableExists('etcee_cortes_ubicaciones')) {
            return [];
        }

        $builder = $this->db->table('etcee_cortes c')
            ->select('c.id, c.titulo, c.descripcion, c.estado, c.color, c.fecha_inicio, c.fecha_fin')
            ->join('etcee_cortes_ubicaciones cu', 'cu.corte_id = c.id', 'inner')
            ->groupBy('c.id')
            ->orderBy('c.fecha_inicio', 'ASC');

        if (($departamentoId ?? 0) > 0) {
            $builder->where('cu.departamento_id', (int) $departamentoId);
        }

        if (($municipioId ?? 0) > 0) {
            $builder->where('cu.municipio_id', (int) $municipioId);
        }

        $events = $builder->get()->getResultArray();

        if ($events === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $events)));
        $locationsByEvent = $this->loadLocationsByEvent($ids);

        return array_map(function (array $event) use ($locationsByEvent): array {
            $id = (int) ($event['id'] ?? 0);

            return [
                'id' => $id,
                'title' => (string) ($event['titulo'] ?? ''),
                'start' => (string) ($event['fecha_inicio'] ?? ''),
                'end' => (string) ($event['fecha_fin'] ?? ''),
                'color' => (string) ($event['color'] ?? '#1f6feb'),
                'extendedProps' => [
                    'descripcion' => (string) ($event['descripcion'] ?? ''),
                    'estado' => (string) ($event['estado'] ?? 'programado'),
                    'locations' => $locationsByEvent[$id] ?? [],
                ],
            ];
        }, $events);
    }

    public function findEvent(int $id): ?array
    {
        $event = $this->where('id', $id)->first();

        if (! is_array($event)) {
            return null;
        }

        $event['locations'] = $this->loadLocationsByEvent([$id])[$id] ?? [];

        return $event;
    }

    public function resolveLocations(array $departmentIds, array $municipalityIds): array
    {
        $departmentIds = $this->sanitizeIds($departmentIds);
        $municipalityIds = $this->sanitizeIds($municipalityIds);

        $locations = [];

        foreach ($departmentIds as $departmentId) {
            $key = $departmentId . '-0';
            $locations[$key] = [
                'departamento_id' => $departmentId,
                'municipio_id' => null,
            ];
        }

        if ($municipalityIds !== [] && $this->db->tableExists('cat_municipios')) {
            $municipalities = $this->db->table('cat_municipios')
                ->select('id, departamento_id')
                ->whereIn('id', $municipalityIds)
                ->get()
                ->getResultArray();

            foreach ($municipalities as $municipality) {
                $departmentId = (int) ($municipality['departamento_id'] ?? 0);
                $municipalityId = (int) ($municipality['id'] ?? 0);

                if ($departmentId <= 0 || $municipalityId <= 0) {
                    continue;
                }

                $key = $departmentId . '-' . $municipalityId;
                $locations[$key] = [
                    'departamento_id' => $departmentId,
                    'municipio_id' => $municipalityId,
                ];
            }
        }

        return array_values($locations);
    }

    public function saveEvent(?int $id, array $payload, array $locations): int
    {
        $this->db->transStart();

        if ($id === null) {
            $this->insert($payload);
            $id = (int) $this->getInsertID();
        } else {
            $this->update($id, $payload);
            $this->db->table('etcee_cortes_ubicaciones')->where('corte_id', $id)->delete();
        }

        foreach ($locations as $location) {
            $this->db->table('etcee_cortes_ubicaciones')->insert([
                'corte_id' => $id,
                'departamento_id' => (int) $location['departamento_id'],
                'municipio_id' => $location['municipio_id'] !== null ? (int) $location['municipio_id'] : null,
            ]);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('No fue posible guardar el corte.');
        }

        return $id;
    }

    public function deleteEvent(int $id): bool
    {
        return (bool) $this->delete($id);
    }

    private function loadLocationsByEvent(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $hasDepartamentos = $this->db->tableExists('cat_departamentos');
        $hasMunicipios = $this->db->tableExists('cat_municipios');

        $builder = $this->db->table('etcee_cortes_ubicaciones cu')
            ->whereIn('cu.corte_id', $eventIds);

        if ($hasDepartamentos) {
            $builder->join('cat_departamentos d', 'd.id = cu.departamento_id', 'left');
        }

        if ($hasMunicipios) {
            $builder->join('cat_municipios m', 'm.id = cu.municipio_id', 'left');
        }

        $builder->select(
            'cu.corte_id, cu.departamento_id, cu.municipio_id'
            . ($hasDepartamentos ? ', d.nombre AS departamento' : ', cu.departamento_id AS departamento')
            . ($hasMunicipios ? ', m.nombre AS municipio' : ', cu.municipio_id AS municipio')
        );

        if ($hasDepartamentos) {
            $builder->orderBy('d.nombre', 'ASC');
        } else {
            $builder->orderBy('cu.departamento_id', 'ASC');
        }

        if ($hasMunicipios) {
            $builder->orderBy('m.nombre', 'ASC');
        } else {
            $builder->orderBy('cu.municipio_id', 'ASC');
        }

        $rows = $builder->get()->getResultArray();

        $grouped = [];

        foreach ($rows as $row) {
            $eventId = (int) ($row['corte_id'] ?? 0);

            if ($eventId <= 0) {
                continue;
            }

            $grouped[$eventId][] = [
                'departamento_id' => (int) ($row['departamento_id'] ?? 0),
                'municipio_id' => isset($row['municipio_id']) ? (int) $row['municipio_id'] : null,
                'departamento' => (string) ($row['departamento'] ?? ''),
                'municipio' => (string) ($row['municipio'] ?? ''),
            ];
        }

        return $grouped;
    }

    private function sanitizeIds(array $ids): array
    {
        $clean = [];

        foreach ($ids as $id) {
            $value = (int) $id;

            if ($value > 0) {
                $clean[$value] = $value;
            }
        }

        return array_values($clean);
    }
}
