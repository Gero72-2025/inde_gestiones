<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGeroComunidadesCargaTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'division' => ['type' => 'ENUM', 'constraint' => ['DICODER', 'DOSODEP', 'DIVOC'], 'null' => false],
            'nombre_archivo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'tabla_fisica' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'hash_esquema' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'columnas_json' => ['type' => 'LONGTEXT', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['division', 'created_at'], false, false, 'idx_gero_fuentes_division');
        $this->forge->createTable('gero_comunidades_fuentes', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'fuente_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'columna_normalizada' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'etiqueta_landing' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => false],
            'mostrar_landing' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            'fase_clave' => ['type' => 'ENUM', 'constraint' => ['fase_1', 'fase_2', 'fase_3'], 'null' => true],
            'usar_para_hito' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'mostrar_card' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['fuente_id', 'columna_normalizada'], 'uq_gero_fuente_mapeo_columna');
        $this->forge->addKey('fuente_id', false, false, 'idx_gero_fuente_mapeo_fuente');
        $this->forge->createTable('gero_comunidades_fuente_mapeo', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'division' => ['type' => 'ENUM', 'constraint' => ['DICODER', 'DOSODEP', 'DIVOC'], 'null' => false],
            'tabla_fisica' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'nuevos' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0],
            'modificados' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0],
            'sin_cambios' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0],
            'eliminados' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0],
            'total_filas' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0],
            'usuario_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'cargado_en' => ['type' => 'DATETIME', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('cargado_en', false, false, 'idx_gero_carga_log_fecha');
        $this->forge->addKey('division', false, false, 'idx_gero_carga_log_division');
        $this->forge->createTable('gero_comunidades_cargas_log', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->db->query('ALTER TABLE gero_comunidades_fuente_mapeo
            ADD CONSTRAINT fk_gero_fuente_mapeo_fuente
            FOREIGN KEY (fuente_id) REFERENCES gero_comunidades_fuentes(id)
            ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE gero_comunidades_fuente_mapeo DROP FOREIGN KEY fk_gero_fuente_mapeo_fuente');
        $this->forge->dropTable('gero_comunidades_cargas_log', true);
        $this->forge->dropTable('gero_comunidades_fuente_mapeo', true);
        $this->forge->dropTable('gero_comunidades_fuentes', true);
    }
}
