<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEcoeTsBitacoraTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ticket_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'estado_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'estado_nombre'  => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'descripcion'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false, 'default' => ''],
            'usuario_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'fecha_registro' => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
            'created_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
            'updated_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('ticket_id', false, false, 'idx_ts_bitacora_ticket');
        $this->forge->addKey('estado_id', false, false, 'idx_ts_bitacora_estado');
        $this->forge->addKey('fecha_registro', false, false, 'idx_ts_bitacora_fecha');
        $this->forge->createTable('ecoe_ts_bitacora', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);

        $this->db->query('ALTER TABLE ecoe_ts_bitacora
            ADD CONSTRAINT fk_ts_bitacora_ticket
            FOREIGN KEY (ticket_id) REFERENCES ecoe_ts_tickets(id)
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE ecoe_ts_bitacora
            ADD CONSTRAINT fk_ts_bitacora_estado
            FOREIGN KEY (estado_id) REFERENCES ecoe_ts_estados(id)
            ON DELETE RESTRICT ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE ecoe_ts_bitacora DROP FOREIGN KEY fk_ts_bitacora_ticket');
        $this->db->query('ALTER TABLE ecoe_ts_bitacora DROP FOREIGN KEY fk_ts_bitacora_estado');
        $this->forge->dropTable('ecoe_ts_bitacora', true);
    }
}
