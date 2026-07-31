<?php

namespace App\Modules\Ecoe\Models;

class NisBaseModel extends EcoeBaseModel
{
    protected $table         = 'ecoe_nis_base';
    protected $allowedFields = [
        'id_usuario',
        'nombre_usuario',
        'departamento',
        'municipio',
        'aldea',
        'direccion',
        'activ_economica',
        'revision',
        'mes',
        'consumo_kwh',
        'distribuidora_id',
    ];

    /**
     * Busca un usuario NIS por correlativo y distribuidora.
     */
    public function findByCorrelativo(string $idUsuario, int $distribuidoraId): ?array
    {
        $row = $this->where('id_usuario', trim($idUsuario))
            ->where('distribuidora_id', $distribuidoraId)
            ->first();

        return $row ?: null;
    }

    /**
     * Inserta o actualiza en lote los registros provenientes de un XLSX.
     * $rows debe ser un array de arrays con las claves definidas en $allowedFields.
     * Devuelve ['inserted' => int, 'updated' => int, 'errors' => int].
     */
    public function upsertBatch(array $rows, int $distribuidoraId): array
    {
        $inserted = 0;
        $updated  = 0;
        $errors   = 0;

        foreach ($rows as $row) {
            $idUsuario = trim((string) ($row['id_usuario'] ?? ''));

            if ($idUsuario === '') {
                $errors++;
                continue;
            }

            $existing = $this->where('id_usuario', $idUsuario)
                ->where('distribuidora_id', $distribuidoraId)
                ->first();

            $data = [
                'id_usuario'       => $idUsuario,
                'nombre_usuario'   => trim((string) ($row['nombre_usuario'] ?? '')),
                'departamento'     => trim((string) ($row['departamento'] ?? '')),
                'municipio'        => trim((string) ($row['municipio'] ?? '')),
                'aldea'            => trim((string) ($row['aldea'] ?? '')),
                'direccion'        => trim((string) ($row['direccion'] ?? '')),
                'activ_economica'  => trim((string) ($row['activ_economica'] ?? '')),
                'revision'         => trim((string) ($row['revision'] ?? '')),
                'mes'              => trim((string) ($row['mes'] ?? '')),
                'consumo_kwh'      => (float) ($row['consumo_kwh'] ?? 0),
                'distribuidora_id' => $distribuidoraId,
            ];

            if ($existing) {
                $this->update((int) $existing['id'], $data);
                $updated++;
            } else {
                $this->insert($data);
                $inserted++;
            }
        }

        return compact('inserted', 'updated', 'errors');
    }
}
