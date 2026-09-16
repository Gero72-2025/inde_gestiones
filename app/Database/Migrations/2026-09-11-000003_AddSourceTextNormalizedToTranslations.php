<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSourceTextNormalizedToTranslations extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('translations')) {
            return;
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('translations'));

        if (! in_array('source_text_normalized', $fields, true)) {
            $this->forge->addColumn('translations', [
                'source_text_normalized' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                    'after' => 'source_text',
                ],
            ]);
        }

        try {
            $this->db->query('ALTER TABLE translations ADD INDEX idx_translations_source_norm (lang_code, source_text_normalized(191))');
        } catch (\Throwable $e) {
            // Ignore if the index already exists.
        }

        // Backfill filas existentes normalizando su source_text actual.
        $rows = $this->db->table('translations')
            ->select('id, source_text')
            ->where('source_text_normalized IS NULL')
            ->where('source_text IS NOT NULL')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $normalized = function_exists('translation_normalize_text')
                ? translation_normalize_text((string) $row['source_text'])
                : mb_strtolower(trim((string) $row['source_text']), 'UTF-8');

            $this->db->table('translations')
                ->where('id', (int) $row['id'])
                ->update(['source_text_normalized' => $normalized]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('translations')) {
            return;
        }

        try {
            $this->db->query('ALTER TABLE translations DROP INDEX idx_translations_source_norm');
        } catch (\Throwable $e) {
            // Ignore if the index does not exist.
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('translations'));

        if (in_array('source_text_normalized', $fields, true)) {
            $this->forge->dropColumn('translations', 'source_text_normalized');
        }
    }
}
