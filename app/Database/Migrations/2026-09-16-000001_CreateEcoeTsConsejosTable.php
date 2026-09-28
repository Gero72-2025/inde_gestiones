<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEcoeTsConsejosTable extends Migration
{
    public function up(): void
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'ecoe_ts_consejos'")->getNumRows() > 0;

        if ($tableExists) {
            $fieldRows = $this->db->query('SHOW COLUMNS FROM ecoe_ts_consejos')->getResultArray();
            $fields = array_map(static fn(array $row): string => strtolower((string) ($row['Field'] ?? '')), $fieldRows);

            if (! in_array('clave', $fields, true)) {
                $this->forge->addColumn('ecoe_ts_consejos', [
                    'clave' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'id'],
                ]);
            }

            if (! in_array('activo', $fields, true)) {
                $this->forge->addColumn('ecoe_ts_consejos', [
                    'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'null' => false, 'default' => 1, 'after' => 'orden'],
                ]);
            }

            $rows = $this->db->table('ecoe_ts_consejos')->select('id, clave, status')->get()->getResultArray();

            foreach ($rows as $row) {
                $payload = [];
                if (trim((string) ($row['clave'] ?? '')) === '') {
                    $payload['clave'] = 'tarifaSocial.tips.item' . (int) $row['id'];
                }
                if (isset($row['status'])) {
                    $payload['activo'] = strtolower((string) $row['status']) === 'active' ? 1 : 0;
                }
                if ($payload !== []) {
                    $this->db->table('ecoe_ts_consejos')->where('id', (int) $row['id'])->update($payload);
                }
            }

            try {
                $this->db->query('ALTER TABLE ecoe_ts_consejos ADD UNIQUE KEY uq_ecoe_ts_consejos_clave (clave)');
            } catch (\Throwable $e) {
                // The compatibility index may already exist.
            }

            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'clave' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => false],
            'texto' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => false],
            'icono' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false, 'default' => 'bi-lightbulb'],
            'orden' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => false, 'default' => 0],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('clave', 'uq_ecoe_ts_consejos_clave');
        $this->forge->addKey(['activo', 'orden'], false, false, 'idx_ecoe_ts_consejos_visible');
        $this->forge->createTable('ecoe_ts_consejos', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);

        $this->db->table('ecoe_ts_consejos')->insertBatch([
            [
                'clave' => 'tarifaSocial.tips.item1',
                'texto' => 'Usa focos ahorradores o LED en toda tu vivienda.',
                'icono' => 'bi-lightbulb',
                'orden' => 1,
                'activo' => 1,
            ],
            [
                'clave' => 'tarifaSocial.tips.item2',
                'texto' => 'Desconecta aparatos electrónicos que no estés usando.',
                'icono' => 'bi-plug',
                'orden' => 2,
                'activo' => 1,
            ],
            [
                'clave' => 'tarifaSocial.tips.item3',
                'texto' => 'Aprovecha la luz natural durante el día.',
                'icono' => 'bi-sun',
                'orden' => 3,
                'activo' => 1,
            ],
            [
                'clave' => 'tarifaSocial.tips.item4',
                'texto' => 'Evita el uso prolongado de planchas y calentadores.',
                'icono' => 'bi-thermometer-half',
                'orden' => 4,
                'activo' => 1,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('ecoe_ts_consejos', true);
    }
}
