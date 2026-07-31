<?php

namespace App\Modules\Ecoe\Models;

class TsPdfTemplateModel extends EcoeBaseModel
{
    protected $table            = 'ecoe_ts_pdf_templates';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'nombre',
        'slug',
        'descripcion',
        'html_template',
        'instrucciones_html',
        'is_default',
        'is_active',
    ];

    public function listAll(): array
    {
        return $this->orderBy('is_default', 'DESC')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    public function getDefaultActive(): ?array
    {
        $row = $this->where('is_active', 1)
            ->where('is_default', 1)
            ->first();

        if ($row) {
            return $row;
        }

        return $this->where('is_active', 1)->orderBy('id', 'ASC')->first() ?: null;
    }

    public function setAsDefault(int $templateId): void
    {
        $this->db->table($this->table)->set(['is_default' => 0])->update();
        $this->update($templateId, ['is_default' => 1, 'is_active' => 1]);
    }
}
