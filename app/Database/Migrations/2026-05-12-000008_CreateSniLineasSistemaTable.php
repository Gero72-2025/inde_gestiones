<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSniLineasSistemaTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('etcee_sni_lineas_sistema')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                ],
                'slug' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                ],
                'descripcion' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'estado' => [
                    'type' => 'ENUM',
                    'constraint' => ['activo', 'inactivo'],
                    'default' => 'activo',
                ],
                'fecha_registro' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('slug', 'uk_etcee_sni_lineas_sistema_slug');
            $this->forge->addKey('estado', false, false, 'idx_etcee_sni_lineas_sistema_estado');
            $this->forge->createTable('etcee_sni_lineas_sistema', true);
        }

        if ($this->db->tableExists('etcee_sni_geometrias') && ! $this->db->fieldExists('linea_sistema_id', 'etcee_sni_geometrias')) {
            $this->forge->addColumn('etcee_sni_geometrias', [
                'linea_sistema_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'categoria_id',
                ],
            ]);

            try {
                $this->db->query('ALTER TABLE `etcee_sni_geometrias` ADD INDEX `idx_etcee_sni_geo_linea_sistema` (`linea_sistema_id`)');
            } catch (\Throwable $e) {
                // Ignore when index already exists.
            }

            try {
                $this->db->query('ALTER TABLE `etcee_sni_geometrias` ADD CONSTRAINT `fk_etcee_sni_geo_linea_sistema` FOREIGN KEY (`linea_sistema_id`) REFERENCES `etcee_sni_lineas_sistema`(`id`) ON UPDATE CASCADE ON DELETE SET NULL');
            } catch (\Throwable $e) {
                // Ignore when constraint already exists.
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('etcee_sni_geometrias') && $this->db->fieldExists('linea_sistema_id', 'etcee_sni_geometrias')) {
            try {
                $this->db->query('ALTER TABLE `etcee_sni_geometrias` DROP FOREIGN KEY `fk_etcee_sni_geo_linea_sistema`');
            } catch (\Throwable $e) {
                // Ignore if FK does not exist.
            }

            try {
                $this->db->query('ALTER TABLE `etcee_sni_geometrias` DROP INDEX `idx_etcee_sni_geo_linea_sistema`');
            } catch (\Throwable $e) {
                // Ignore if index does not exist.
            }

            $this->forge->dropColumn('etcee_sni_geometrias', 'linea_sistema_id');
        }

        $this->forge->dropTable('etcee_sni_lineas_sistema', true);
    }
}
