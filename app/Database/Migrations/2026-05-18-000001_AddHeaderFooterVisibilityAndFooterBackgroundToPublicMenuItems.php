<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHeaderFooterVisibilityAndFooterBackgroundToPublicMenuItems extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('public_menu_items')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('public_menu_items'));

        if (! in_array('show_header', $fields, true)) {
            $this->forge->addColumn('public_menu_items', [
                'show_header' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'after' => 'background_image_path',
                ],
            ]);
        }

        if (! in_array('footer_background_image_path', $fields, true)) {
            $this->forge->addColumn('public_menu_items', [
                'footer_background_image_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'show_header',
                ],
            ]);
        }

        if (! in_array('show_footer', $fields, true)) {
            $this->forge->addColumn('public_menu_items', [
                'show_footer' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'after' => 'footer_background_image_path',
                ],
            ]);
        }

        $this->db->query('UPDATE public_menu_items SET show_header = 1 WHERE show_header IS NULL');
        $this->db->query('UPDATE public_menu_items SET show_footer = 1 WHERE show_footer IS NULL');
    }

    public function down()
    {
        if (! $this->db->tableExists('public_menu_items')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('public_menu_items'));

        foreach (['show_footer', 'footer_background_image_path', 'show_header'] as $column) {
            if (in_array($column, $fields, true)) {
                $this->forge->dropColumn('public_menu_items', $column);
            }
        }
    }
}
