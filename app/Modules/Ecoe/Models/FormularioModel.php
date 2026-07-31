<?php

namespace App\Modules\Ecoe\Models;

class FormularioModel extends EcoeBaseModel
{
    protected $table = 'ecoe_formularios';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'nombre',
        'codigo',
        'slug',
        'modulo_asignado',
        'descripcion',
        'plantilla_html',
        'instrucciones_html',
        'estado',
    ];

    public function listAll(): array
    {
        return $this->orderBy('modulo_asignado', 'ASC')
            ->orderBy('codigo', 'ASC')
            ->findAll();
    }

    public function listActivosByModulo(string $modulo): array
    {
        return $this->where('estado', 1)
            ->where('modulo_asignado', $modulo)
            ->orderBy('codigo', 'ASC')
            ->findAll();
    }

    public function findByCodigo(string $codigo): ?array
    {
        $row = $this->where('codigo', $codigo)->first();

        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $row = $this->where('slug', $slug)->first();

        return $row ?: null;
    }
}