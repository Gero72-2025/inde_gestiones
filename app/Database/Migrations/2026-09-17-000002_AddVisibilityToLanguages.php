<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVisibilityToLanguages extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('languages')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('languages'));

        if (! in_array('is_visible', $fields, true)) {
            $this->forge->addColumn('languages', [
                'is_visible' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 1,
                    'after' => 'is_active',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('languages')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('languages'));

        if (in_array('is_visible', $fields, true)) {
            $this->forge->dropColumn('languages', 'is_visible');
        }
    }
}