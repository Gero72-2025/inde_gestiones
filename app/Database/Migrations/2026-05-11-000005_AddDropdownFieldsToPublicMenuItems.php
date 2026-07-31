<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDropdownFieldsToPublicMenuItems extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('public_menu_items')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('public_menu_items'));

        if (! in_array('parent_id', $fields, true)) {
            $this->forge->addColumn('public_menu_items', [
                'parent_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'icon_class',
                ],
            ]);
        }

        if (! in_array('is_dropdown', $fields, true)) {
            $this->forge->addColumn('public_menu_items', [
                'is_dropdown' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'parent_id',
                ],
            ]);
        }

        $this->db->query('UPDATE public_menu_items SET is_dropdown = 0 WHERE is_dropdown IS NULL');
        $this->db->query('UPDATE public_menu_items SET parent_id = NULL WHERE parent_id = 0');

        try {
            $this->forge->addKey('parent_id');
        } catch (\Throwable $e) {
            // Ignore if key already exists.
        }

        try {
            $this->forge->addKey('is_dropdown');
        } catch (\Throwable $e) {
            // Ignore if key already exists.
        }

        try {
            $this->forge->addForeignKey('parent_id', 'public_menu_items', 'id', 'SET NULL', 'SET NULL');
        } catch (\Throwable $e) {
            // Ignore if FK already exists.
        }

        try {
            $this->forge->processIndexes('public_menu_items');
        } catch (\Throwable $e) {
            // Ignore index process failures for already-existing definitions.
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('public_menu_items')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('public_menu_items'));

        if (in_array('parent_id', $fields, true)) {
            try {
                $this->forge->dropForeignKey('public_menu_items', 'public_menu_items_parent_id_foreign');
            } catch (\Throwable $e) {
                // Ignore when FK name differs or does not exist.
            }

            try {
                $this->forge->dropColumn('public_menu_items', 'parent_id');
            } catch (\Throwable $e) {
                // Ignore if column already removed.
            }
        }

        if (in_array('is_dropdown', $fields, true)) {
            try {
                $this->forge->dropColumn('public_menu_items', 'is_dropdown');
            } catch (\Throwable $e) {
                // Ignore if column already removed.
            }
        }
    }
}
