<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTranslationsTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('translations')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'lang_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'translation_key' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => false],
            'translation_value' => ['type' => 'TEXT', 'null' => true],
            'is_autodiscovered' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['lang_code', 'translation_key'], 'uq_translations_lang_key');
        $this->forge->addKey('is_autodiscovered', false, false, 'idx_translations_autodiscovered');
        $this->forge->createTable('translations', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('translations', true);
    }
}
