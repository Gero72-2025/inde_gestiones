<?php

namespace App\Modules\Gero\Models;

use CodeIgniter\Model;

class ComunidadesModel extends Model
{
    protected $table = 'gero_comunidades';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'codigo_comunidad',
        'numero_snip',
        'nombre_comunidad',
        'municipio',
        'departamento',
        'clave_comunidad_normalizada',
        'clave_municipio_normalizada',
        'fase_actual',
        'estado_actual',
        'solicitud_firmada',
        'solicitud_firmada_fecha',
        'estudio_socioeconomico',
        'estudio_socioeconomico_fecha',
        'snip_aprobado',
        'snip_aprobado_fecha',
        'licitacion_terminada',
        'licitacion_terminada_fecha',
        'obra_energizada',
        'obra_energizada_fecha',
        'division_origen',
        'campos_adicionales_json',
    ];

    public function listForAdmin(string $q = '', string $fase = ''): array
    {
        $builder = $this->builder()
            ->select('id, codigo_comunidad, numero_snip, nombre_comunidad, municipio, departamento, fase_actual, estado_actual, division_origen, updated_at')
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC');

        if ($q !== '') {
            $builder->groupStart()
                ->like('codigo_comunidad', $q)
                ->orLike('numero_snip', $q)
                ->orLike('nombre_comunidad', $q)
                ->groupEnd();
        }

        if (in_array($fase, ['fase_1', 'fase_2', 'fase_3'], true)) {
            $builder->where('fase_actual', $fase);
        }

        return $builder->get()->getResultArray();
    }

    public function normalizeKey(string $value): string
    {
        $text = mb_strtolower(trim($value));
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    public function hydrateNormalization(array $payload): array
    {
        $payload['clave_comunidad_normalizada'] = $this->normalizeKey((string) ($payload['nombre_comunidad'] ?? ''));
        $payload['clave_municipio_normalizada'] = $this->normalizeKey((string) ($payload['municipio'] ?? ''));

        return $payload;
    }

    public function findByCodeOrName(string $term): array
    {
        $search = trim($term);

        if ($search === '') {
            return [];
        }

        $builder = $this->builder()
            ->select('id, codigo_comunidad, numero_snip, nombre_comunidad, municipio, departamento, fase_actual, estado_actual, division_origen, solicitud_firmada, estudio_socioeconomico, snip_aprobado, licitacion_terminada, obra_energizada, campos_adicionales_json, updated_at')
            ->groupStart()
            ->like('codigo_comunidad', $search)
            ->orLike('numero_snip', $search)
            ->orLike('nombre_comunidad', $search)
            ->groupEnd()
            ->orderBy('updated_at', 'DESC')
            ->limit(25);

        return $builder->get()->getResultArray();
    }

    public function listBitacora(int $comunidadId): array
    {
        return $this->db->table('gero_comunidades_bitacora')
            ->select('id, comunidad_id, fase_clave, estado, descripcion, usuario_id, fecha_evento')
            ->where('comunidad_id', $comunidadId)
            ->orderBy('fecha_evento', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function addBitacora(int $comunidadId, string $fase, string $estado, string $descripcion = '', ?int $usuarioId = null): void
    {
        $this->db->table('gero_comunidades_bitacora')->insert([
            'comunidad_id' => $comunidadId,
            'fase_clave' => in_array($fase, ['fase_1', 'fase_2', 'fase_3'], true) ? $fase : 'fase_1',
            'estado' => mb_substr(trim($estado), 0, 120),
            'descripcion' => mb_substr(trim($descripcion), 0, 255),
            'usuario_id' => $usuarioId,
            'fecha_evento' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
