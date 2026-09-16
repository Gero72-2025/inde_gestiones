<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSourceTextToTranslations extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('translations')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('translations'));

        if (! in_array('source_text', $fields, true)) {
            $this->forge->addColumn('translations', [
                'source_text' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                    'after' => 'translation_key',
                ],
            ]);
        }

        try {
            $this->db->query('ALTER TABLE translations ADD INDEX idx_translations_source (lang_code, source_text(191))');
        } catch (\Throwable $e) {
            // Ignore if the index already exists.
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('translations')) {
            return;
        }

        try {
            $this->db->query('ALTER TABLE translations DROP INDEX idx_translations_source');
        } catch (\Throwable $e) {
            // Ignore if the index does not exist.
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('translations'));

        if (in_array('source_text', $fields, true)) {
            $this->forge->dropColumn('translations', 'source_text');
        }
    }
}
