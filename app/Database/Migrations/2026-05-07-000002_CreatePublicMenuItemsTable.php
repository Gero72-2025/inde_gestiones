<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePublicMenuItemsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('public_menu_items')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'title_en' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'title_quc' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'title_qeq' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'title_cak' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'description_en' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'description_quc' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'description_qeq' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'description_cak' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'route_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'icon_class' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'default' => 'bi-grid',
            ],
            'sort_order' => [
                'type' => 'INT',
                'unsigned' => true,
                'default' => 100,
            ],
            'is_published' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->addKey('is_published');
        $this->forge->addKey('sort_order');
        $this->forge->createTable('public_menu_items', true);

        $now = date('Y-m-d H:i:s');
        $this->db->table('public_menu_items')->insertBatch([
            [
                'title' => 'Beneficiados',
                'title_en' => 'Beneficiaries',
                'description' => 'Consulta de estado de beneficio de tarifa social.',
                'description_en' => 'Lookup for social tariff benefit status.',
                'route_path' => 'consulta/beneficiados',
                'icon_class' => 'bi-person-check',
                'sort_order' => 10,
                'is_published' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Electrificacion',
                'title_en' => 'Electrification',
                'description' => 'Consulta de fase actual por comunidad.',
                'description_en' => 'Current project phase by community.',
                    'route_path' => 'consulta/comunidades',
                    'icon_class' => 'bi-buildings',
                    'sort_order' => 20,
                'is_published' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Mantenimientos Programados',
                'title_en' => 'Power Outages',
                'description' => 'Listado visual de cortes activos o programados.',
                'description_en' => 'Visual list of active or planned outages.',
                'route_path' => 'consulta/cortes',
                'icon_class' => 'bi-plug',
                'sort_order' => 30,
                'is_published' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('public_menu_items', true);
    }
}
