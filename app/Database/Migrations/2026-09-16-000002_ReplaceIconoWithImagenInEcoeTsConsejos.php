<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReplaceIconoWithImagenInEcoeTsConsejos extends Migration
{
    public function up(): void
    {
        $fieldRows = $this->db->query('SHOW COLUMNS FROM ecoe_ts_consejos')->getResultArray();
        $fields = array_map(static fn(array $row): string => strtolower((string) ($row['Field'] ?? '')), $fieldRows);

        if (! in_array('imagen', $fields, true)) {
            $this->forge->addColumn('ecoe_ts_consejos', [
                'imagen' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'texto',
                ],
            ]);
        }

        if (in_array('icono', $fields, true)) {
            $this->forge->dropColumn('ecoe_ts_consejos', 'icono');
        }
    }

    public function down(): void
    {
        $fieldRows = $this->db->query('SHOW COLUMNS FROM ecoe_ts_consejos')->getResultArray();
        $fields = array_map(static fn(array $row): string => strtolower((string) ($row['Field'] ?? '')), $fieldRows);

        if (! in_array('icono', $fields, true)) {
            $this->forge->addColumn('ecoe_ts_consejos', [
                'icono' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                    'after' => 'texto',
                ],
            ]);
        }

        if (in_array('imagen', $fields, true)) {
            $this->forge->dropColumn('ecoe_ts_consejos', 'imagen');
        }
    }
}
