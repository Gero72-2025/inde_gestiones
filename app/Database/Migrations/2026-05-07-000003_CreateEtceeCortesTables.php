<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEtceeCortesTables extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('etcee_cortes')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'titulo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                ],
                'descripcion' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'fecha_inicio' => [
                    'type' => 'DATETIME',
                ],
                'fecha_fin' => [
                    'type' => 'DATETIME',
                ],
                'estado' => [
                    'type' => 'ENUM',
                    'constraint' => ['programado', 'activo', 'finalizado', 'cancelado'],
                    'default' => 'programado',
                ],
                'color' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => '#1f6feb',
                ],
                'created_by' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
                ],
                'updated_by' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
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
            $this->forge->addKey(['fecha_inicio', 'fecha_fin']);
            $this->forge->addKey('estado');
            $this->forge->createTable('etcee_cortes', true);
        }

        if (! $this->db->tableExists('etcee_cortes_ubicaciones')) {
            $hasDepartamentos = $this->db->tableExists('cat_departamentos');
            $hasMunicipios = $this->db->tableExists('cat_municipios');

            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'corte_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                ],
                'departamento_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                ],
                'municipio_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
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
            $this->forge->addUniqueKey(['corte_id', 'departamento_id', 'municipio_id'], 'uk_etcee_corte_geo');
            $this->forge->addKey('corte_id', false, false, 'idx_etcee_cortes_ubicaciones_corte');
            $this->forge->addKey('departamento_id', false, false, 'idx_etcee_cortes_ubicaciones_departamento');
            $this->forge->addKey('municipio_id', false, false, 'idx_etcee_cortes_ubicaciones_municipio');
            $this->forge->addForeignKey('corte_id', 'etcee_cortes', 'id', 'CASCADE', 'CASCADE', 'fk_etcee_cortes_ubicaciones_corte');

            if ($hasDepartamentos) {
                $this->forge->addForeignKey('departamento_id', 'cat_departamentos', 'id', 'CASCADE', 'RESTRICT', 'fk_etcee_cortes_ubicaciones_departamento');
            }

            if ($hasMunicipios) {
                $this->forge->addForeignKey('municipio_id', 'cat_municipios', 'id', 'CASCADE', 'RESTRICT', 'fk_etcee_cortes_ubicaciones_municipio');
            }

            $this->forge->createTable('etcee_cortes_ubicaciones', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('etcee_cortes_ubicaciones', true);
        $this->forge->dropTable('etcee_cortes', true);
    }
}
