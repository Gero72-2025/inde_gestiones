<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsToEcoeNisBase extends Migration
{
    public function up(): void
    {
        $fields = [
            'nombre_usuario' => [
                'type'       => 'VARCHAR',
                'constraint' => 160,
                'null'       => true,
                'default'    => null,
                'after'      => 'id_usuario',
            ],
            'departamento' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'default'    => null,
                'after'      => 'nombre_usuario',
            ],
            'municipio' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'default'    => null,
                'after'      => 'departamento',
            ],
            'aldea' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'default'    => null,
                'after'      => 'municipio',
            ],
            'direccion' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'aldea',
            ],
            'revision' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'default'    => null,
                'after'      => 'activ_economica',
            ],
            'mes' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'revision',
            ],
        ];

        $this->forge->addColumn('ecoe_nis_base', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('ecoe_nis_base', [
            'nombre_usuario',
            'departamento',
            'municipio',
            'aldea',
            'direccion',
            'revision',
            'mes',
        ]);
    }
}
