<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEcoeTarifasMensualesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'anio'          => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => false],
            'mes'           => ['type' => 'TINYINT', 'unsigned' => true, 'null' => false],
            'tarifa_plena'  => ['type' => 'DECIMAL', 'constraint' => '12,6', 'null' => false],
            'tarifa_social' => ['type' => 'DECIMAL', 'constraint' => '12,6', 'null' => false],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['anio', 'mes'], 'uq_ecoe_tarifas_anio_mes');
        $this->forge->createTable('ecoe_tarifas_mensuales', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('ecoe_tarifas_mensuales', true);
    }
}