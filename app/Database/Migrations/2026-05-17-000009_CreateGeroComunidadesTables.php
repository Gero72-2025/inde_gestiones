<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGeroComunidadesTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'codigo_comunidad' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'numero_snip' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'nombre_comunidad' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'municipio' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'departamento' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'clave_comunidad_normalizada' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'clave_municipio_normalizada' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'fase_actual' => ['type' => 'ENUM', 'constraint' => ['fase_1', 'fase_2', 'fase_3'], 'null' => false, 'default' => 'fase_1'],
            'estado_actual' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false, 'default' => 'Ingresado'],
            'solicitud_firmada' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'solicitud_firmada_fecha' => ['type' => 'DATE', 'null' => true],
            'estudio_socioeconomico' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'estudio_socioeconomico_fecha' => ['type' => 'DATE', 'null' => true],
            'snip_aprobado' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'snip_aprobado_fecha' => ['type' => 'DATE', 'null' => true],
            'licitacion_terminada' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'licitacion_terminada_fecha' => ['type' => 'DATE', 'null' => true],
            'obra_energizada' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'obra_energizada_fecha' => ['type' => 'DATE', 'null' => true],
            'division_origen' => ['type' => 'ENUM', 'constraint' => ['DICODER', 'DOSODEP', 'DIVOC'], 'null' => true],
            'campos_adicionales_json' => ['type' => 'LONGTEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codigo_comunidad', 'uq_gero_comunidad_codigo');
        $this->forge->addUniqueKey('numero_snip', 'uq_gero_comunidad_snip');
        $this->forge->addKey('nombre_comunidad', false, false, 'idx_gero_comunidad_nombre');
        $this->forge->addKey('fase_actual', false, false, 'idx_gero_comunidad_fase');
        $this->forge->addKey(['clave_comunidad_normalizada', 'clave_municipio_normalizada'], false, false, 'idx_gero_comunidad_claves');
        $this->forge->createTable('gero_comunidades', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'comunidad_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'fase_clave' => ['type' => 'ENUM', 'constraint' => ['fase_1', 'fase_2', 'fase_3'], 'null' => false],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false, 'default' => ''],
            'usuario_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'fecha_evento' => ['type' => 'DATETIME', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('comunidad_id', false, false, 'idx_gero_bitacora_comunidad');
        $this->forge->addKey('fase_clave', false, false, 'idx_gero_bitacora_fase');
        $this->forge->addKey('fecha_evento', false, false, 'idx_gero_bitacora_fecha');
        $this->forge->createTable('gero_comunidades_bitacora', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->db->query('ALTER TABLE gero_comunidades_bitacora
            ADD CONSTRAINT fk_gero_bitacora_comunidad
            FOREIGN KEY (comunidad_id) REFERENCES gero_comunidades(id)
            ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE gero_comunidades_bitacora DROP FOREIGN KEY fk_gero_bitacora_comunidad');
        $this->forge->dropTable('gero_comunidades_bitacora', true);
        $this->forge->dropTable('gero_comunidades', true);
    }
}
