<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEtceeSniTables extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('etcee_sni_capas')) {
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
                'estado' => [
                    'type' => 'ENUM',
                    'constraint' => ['activo', 'inactivo'],
                    'default' => 'activo',
                ],
                'color_default' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => '#1f6feb',
                ],
                'icono_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('slug', 'uk_etcee_sni_capas_slug');
            $this->forge->addKey('estado', false, false, 'idx_etcee_sni_capas_estado');
            $this->forge->createTable('etcee_sni_capas', true);
        }

        if (! $this->db->tableExists('etcee_sni_geometrias')) {
            $hasUsersTable = $this->db->tableExists('users');

            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'categoria_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                ],
                'nombre' => [
                    'type' => 'VARCHAR',
                    'constraint' => 220,
                ],
                'coordenadas' => [
                    'type' => 'LONGTEXT',
                ],
                'propiedades' => [
                    'type' => 'JSON',
                    'null' => true,
                ],
                'usuario_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
                ],
                'fecha_registro' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('categoria_id', false, false, 'idx_etcee_sni_geo_categoria');
            $this->forge->addKey('usuario_id', false, false, 'idx_etcee_sni_geo_usuario');
            $this->forge->addKey('fecha_registro', false, false, 'idx_etcee_sni_geo_fecha');
            $this->forge->addForeignKey('categoria_id', 'etcee_sni_capas', 'id', 'CASCADE', 'RESTRICT', 'fk_etcee_sni_geo_categoria');

            if ($hasUsersTable) {
                $this->forge->addForeignKey('usuario_id', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_etcee_sni_geo_usuario');
            }

            $this->forge->createTable('etcee_sni_geometrias', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('etcee_sni_geometrias', true);
        $this->forge->dropTable('etcee_sni_capas', true);
    }
}
