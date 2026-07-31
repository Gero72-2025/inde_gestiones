<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBackgroundImageToPublicMenuItems extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('public_menu_items')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('public_menu_items'));

        if (! in_array('background_image_path', $fields, true)) {
            $this->forge->addColumn('public_menu_items', [
                'background_image_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'icon_class',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('public_menu_items')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('public_menu_items'));

        if (in_array('background_image_path', $fields, true)) {
            $this->forge->dropColumn('public_menu_items', 'background_image_path');
        }
    }
}
