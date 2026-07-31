<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCatalogoUbicacionesTables extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('cat_departamentos')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'codigo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 10,
                    'null' => true,
                ],
                'nombre' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['active', 'inactive'],
                    'default' => 'active',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('codigo', 'uk_cat_departamentos_codigo');
            $this->forge->addUniqueKey('nombre', 'uk_cat_departamentos_nombre');
            $this->forge->createTable('cat_departamentos', true);
        }

        if (! $this->db->tableExists('cat_municipios')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'departamento_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                ],
                'codigo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 10,
                    'null' => true,
                ],
                'nombre' => [
                    'type' => 'VARCHAR',
                    'constraint' => 140,
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['active', 'inactive'],
                    'default' => 'active',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('departamento_id');
            $this->forge->addUniqueKey(['departamento_id', 'nombre'], 'uk_cat_municipios_dep_nombre');
            $this->forge->addUniqueKey('codigo', 'uk_cat_municipios_codigo');
            $this->forge->addForeignKey('departamento_id', 'cat_departamentos', 'id', 'CASCADE', 'RESTRICT', 'fk_cat_municipios_departamento');
            $this->forge->createTable('cat_municipios', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('cat_municipios', true);
        $this->forge->dropTable('cat_departamentos', true);
    }
}
