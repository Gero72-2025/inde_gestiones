<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDistributorToEcoeTarifasMensuales extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('ecoe_tarifas_mensuales', [
            'distribuidora_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->db->query('ALTER TABLE ecoe_tarifas_mensuales
            DROP INDEX uq_ecoe_tarifas_anio_mes,
            ADD UNIQUE KEY uq_ecoe_tarifas_distribuidora_periodo (distribuidora_id, anio, mes),
            ADD KEY idx_ecoe_tarifas_distribuidora (distribuidora_id)');

        $this->db->query('ALTER TABLE ecoe_tarifas_mensuales
            ADD CONSTRAINT fk_ecoe_tarifas_distribuidora
            FOREIGN KEY (distribuidora_id) REFERENCES ecoe_distribuidoras(id)
            ON DELETE RESTRICT ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE ecoe_tarifas_mensuales
            DROP FOREIGN KEY fk_ecoe_tarifas_distribuidora,
            DROP INDEX uq_ecoe_tarifas_distribuidora_periodo,
            DROP INDEX idx_ecoe_tarifas_distribuidora,
            ADD UNIQUE KEY uq_ecoe_tarifas_anio_mes (anio, mes)');

        $this->forge->dropColumn('ecoe_tarifas_mensuales', 'distribuidora_id');
    }
}