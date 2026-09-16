<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class PublicMenuItemModel extends Model
{
    protected $table = 'public_menu_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'title',
        'title_en',
        'title_quc',
        'title_qeq',
        'title_cak',
        'description',
        'description_en',
        'description_quc',
        'description_qeq',
        'description_cak',
        'route_path',
        'icon_class',
        'background_image_path',
        'show_header',
        'footer_background_image_path',
        'show_footer',
        'parent_id',
        'is_dropdown',
        'sort_order',
        'is_published',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
