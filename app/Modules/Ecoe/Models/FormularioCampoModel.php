<?php

namespace App\Modules\Ecoe\Models;

class FormularioCampoModel extends EcoeBaseModel
{
    protected $table = 'ecoe_formulario_campos';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'formulario_id',
        'nombre',
        'slug',
        'tipo',
        'etiqueta',
        'ayuda',
        'opciones_json',
        'obligatorio',
        'visible_pdf',
        'visible_plantilla',
        'estado',
        'orden',
    ];

    public function listByFormulario(int $formularioId, bool $onlyActive = true): array
    {
        $builder = $this->where('formulario_id', $formularioId)->orderBy('orden', 'ASC')->orderBy('id', 'ASC');

        if ($onlyActive) {
            $builder->where('estado', 1);
        }

        return $builder->findAll();
    }

    public function getActiveVisibleFields(int $formularioId): array
    {
        return $this->where('formulario_id', $formularioId)
            ->where('estado', 1)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}