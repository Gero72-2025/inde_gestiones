<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEcoeTarifaSocialTables extends Migration
{
    public function up(): void
    {
        // ── 1. ecoe_distribuidoras ────────────────────────────────────────────
        $this->forge->addField([
            'id'     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nombre', 'uq_distribuidora_nombre');
        $this->forge->createTable('ecoe_distribuidoras', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        // Datos semilla – distribuidoras
        $this->db->table('ecoe_distribuidoras')->ignore(true)->insertBatch([
            ['nombre' => 'EEGSA',     'status' => 1],
            ['nombre' => 'ENERGUATE', 'status' => 1],
            ['nombre' => 'DEORSA',    'status' => 1],
            ['nombre' => 'DEOCSA',    'status' => 1],
        ]);

        // ── 2. ecoe_nis_base ─────────────────────────────────────────────────
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_usuario'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'activ_economica'  => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false, 'default' => ''],
            'consumo_kwh'      => ['type' => 'DECIMAL', 'constraint' => '10,4', 'null' => false, 'default' => 0],
            'distribuidora_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'created_at'       => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
            'updated_at'       => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['id_usuario', 'distribuidora_id'], 'uq_nis_usuario_dist');
        $this->forge->addKey('distribuidora_id', false, false, 'idx_nis_distribuidora');
        $this->forge->createTable('ecoe_nis_base', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        if (! $this->foreignKeyExists('ecoe_nis_base', 'fk_nis_distribuidora')) {
            $this->db->query('ALTER TABLE ecoe_nis_base
                ADD CONSTRAINT fk_nis_distribuidora
                FOREIGN KEY (distribuidora_id) REFERENCES ecoe_distribuidoras(id)
                ON DELETE RESTRICT ON UPDATE CASCADE');
        }

        // ── 3. ecoe_ts_estados ───────────────────────────────────────────────
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'orden_paso'  => ['type' => 'TINYINT', 'null' => false, 'default' => 0],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false, 'default' => ''],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nombre', 'uq_ts_estado_nombre');
        $this->forge->createTable('ecoe_ts_estados', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        // Datos semilla – estados del flujo
        $this->db->table('ecoe_ts_estados')->ignore(true)->insertBatch([
            ['nombre' => 'Ingresado',   'orden_paso' => 1, 'descripcion' => 'Solicitud recibida y pendiente de revisión'],
            ['nombre' => 'En Revisión', 'orden_paso' => 2, 'descripcion' => 'Documentación en proceso de verificación'],
            ['nombre' => 'Aprobado',    'orden_paso' => 3, 'descripcion' => 'Solicitud aprobada para Tarifa Social'],
            ['nombre' => 'Rechazado',   'orden_paso' => 4, 'descripcion' => 'Solicitud rechazada por incumplimiento de requisitos'],
            ['nombre' => 'Completado',  'orden_paso' => 5, 'descripcion' => 'Proceso concluido satisfactoriamente'],
        ]);

        // ── 4. ecoe_ts_tickets ───────────────────────────────────────────────
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_solicitud'      => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'codigo_referencia' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'nombre'            => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => false],
            'direccion'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'dpi'               => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'telefono'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => ''],
            'estado_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'fecha_ingreso'     => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
            'updated_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codigo_referencia', 'uq_ts_ticket_referencia');
        $this->forge->addUniqueKey('id_solicitud', 'uq_ts_ticket_solicitud');
        $this->forge->addKey('estado_id', false, false, 'idx_ts_ticket_estado');
        $this->forge->addKey('dpi', false, false, 'idx_ts_ticket_dpi');
        $this->forge->addKey('fecha_ingreso', false, false, 'idx_ts_ticket_fecha');
        $this->forge->createTable('ecoe_ts_tickets', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        if (! $this->foreignKeyExists('ecoe_ts_tickets', 'fk_ts_ticket_estado')) {
            $this->db->query('ALTER TABLE ecoe_ts_tickets
                ADD CONSTRAINT fk_ts_ticket_estado
                FOREIGN KEY (estado_id) REFERENCES ecoe_ts_estados(id)
                ON DELETE RESTRICT ON UPDATE CASCADE');
        }

        // ── 5. ecoe_ts_adjuntos ──────────────────────────────────────────────
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ticket_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'tipo_archivo' => ['type' => 'ENUM', 'constraint' => ['dpi_frontal', 'dpi_reverso', 'factura', 'fachada1', 'fachada2'], 'null' => false],
            'ruta_archivo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ticket_id', false, false, 'idx_ts_adjunto_ticket');
        $this->forge->createTable('ecoe_ts_adjuntos', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        if (! $this->foreignKeyExists('ecoe_ts_adjuntos', 'fk_ts_adjunto_ticket')) {
            $this->db->query('ALTER TABLE ecoe_ts_adjuntos
                ADD CONSTRAINT fk_ts_adjunto_ticket
                FOREIGN KEY (ticket_id) REFERENCES ecoe_ts_tickets(id)
                ON DELETE CASCADE ON UPDATE CASCADE');
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $row = $this->db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $constraint],
        )->getRowArray();

        return is_array($row);
    }

    public function down(): void
    {
        $this->forge->dropTable('ecoe_ts_adjuntos', true);
        $this->forge->dropTable('ecoe_ts_tickets', true);

        $this->forge->dropTable('ecoe_ts_estados', true);

        $this->forge->dropTable('ecoe_nis_base', true);

        $this->forge->dropTable('ecoe_distribuidoras', true);
    }
}

