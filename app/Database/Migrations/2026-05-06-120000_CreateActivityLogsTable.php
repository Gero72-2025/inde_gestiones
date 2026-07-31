<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'default' => 'http.request',
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'endpoint' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'http_method' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
            ],
            'status_code' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('event_type');
        $this->forge->addKey('created_at');
        $this->forge->createTable('activity_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('activity_logs', true);
    }
}
