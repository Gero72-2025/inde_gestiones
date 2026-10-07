<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class CreateEtceeMaintenanceStates extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('etcee_estados_mantenimiento')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'nombre' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'clave' => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
                'color' => ['type' => 'CHAR', 'constraint' => 7, 'null' => false, 'default' => '#1A56DB'],
                'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('nombre', 'uq_etcee_estado_mantenimiento_nombre');
            $this->forge->addUniqueKey('clave', 'uq_etcee_estado_mantenimiento_clave');
            $this->forge->addKey('activo', false, false, 'idx_etcee_estado_mantenimiento_activo');
            $this->forge->createTable('etcee_estados_mantenimiento', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }

        $states = [
            ['nombre' => 'Mantenimiento Programado', 'clave' => 'P', 'color' => '#1A56DB'],
            ['nombre' => 'Mantenimiento No Programado', 'clave' => 'NP', 'color' => '#0F6D8F'],
            ['nombre' => 'Interrupcion Fortuita', 'clave' => 'IF', 'color' => '#D45A0B'],
            ['nombre' => 'Cancelado', 'clave' => 'C', 'color' => '#B42318'],
            ['nombre' => 'Finalizado Programado', 'clave' => 'FP', 'color' => '#198754'],
            ['nombre' => 'Mantenimiento Activo', 'clave' => 'A', 'color' => '#2D7D6F'],
        ];

        foreach ($states as $state) {
            $this->db->table('etcee_estados_mantenimiento')->ignore(true)->insert($state);
        }

        if (! $this->db->tableExists('etcee_cortes')) {
            return;
        }

        $columns = array_map('strtolower', $this->db->getFieldNames('etcee_cortes'));
        if (! in_array('estado_mantenimiento_id', $columns, true)) {
            $this->forge->addColumn('etcee_cortes', [
                'estado_mantenimiento_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'estado',
                ],
            ]);
        }

        try {
            $this->db->query('ALTER TABLE etcee_cortes ADD KEY idx_etcee_cortes_estado_mantenimiento (estado_mantenimiento_id)');
        } catch (Throwable $exception) {
            // Keep the migration re-runnable if the index already exists.
        }

        try {
            $this->db->query('ALTER TABLE etcee_cortes ADD CONSTRAINT fk_etcee_cortes_estado_mantenimiento FOREIGN KEY (estado_mantenimiento_id) REFERENCES etcee_estados_mantenimiento (id) ON UPDATE CASCADE ON DELETE RESTRICT');
        } catch (Throwable $exception) {
            // Keep the migration re-runnable if the foreign key already exists.
        }

        $legacyStateKeys = [
            'programado' => 'P',
            'activo' => 'A',
            'finalizado' => 'FP',
            'cancelado' => 'C',
        ];

        foreach ($legacyStateKeys as $legacyState => $key) {
            $state = $this->db->table('etcee_estados_mantenimiento')
                ->select('id')
                ->where('clave', $key)
                ->get()
                ->getRowArray();

            if (is_array($state)) {
                $this->db->table('etcee_cortes')
                    ->where('estado', $legacyState)
                    ->where('estado_mantenimiento_id', null)
                    ->update(['estado_mantenimiento_id' => (int) $state['id']]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('etcee_cortes')) {
            try {
                $this->db->query('ALTER TABLE etcee_cortes DROP FOREIGN KEY fk_etcee_cortes_estado_mantenimiento');
            } catch (Throwable $exception) {
                // Ignore when the foreign key is already absent.
            }

            try {
                $this->db->query('ALTER TABLE etcee_cortes DROP INDEX idx_etcee_cortes_estado_mantenimiento');
            } catch (Throwable $exception) {
                // Ignore when the index is already absent.
            }

            if (in_array('estado_mantenimiento_id', array_map('strtolower', $this->db->getFieldNames('etcee_cortes')), true)) {
                $this->forge->dropColumn('etcee_cortes', 'estado_mantenimiento_id');
            }
        }

        $this->forge->dropTable('etcee_estados_mantenimiento', true);
    }
}