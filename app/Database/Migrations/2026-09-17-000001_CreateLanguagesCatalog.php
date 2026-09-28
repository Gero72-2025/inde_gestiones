<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLanguagesCatalog extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('languages')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
                'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code', 'uq_languages_code');
            $this->forge->addKey('is_active', false, false, 'idx_languages_active');
            $this->forge->createTable('languages', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }

        $languages = [
            ['code' => 'es', 'name' => 'español'],
            ['code' => 'en', 'name' => 'Ingles'],
            ['code' => 'quc', 'name' => "K'iche'"],
            ['code' => 'qeq', 'name' => "Q'eqchi'"],
            ['code' => 'cak', 'name' => 'Kaqchikel'],
            ['code' => 'mam', 'name' => 'Mam'],
            ['code' => 'usp', 'name' => 'Uspanteko'],
            ['code' => 'poc', 'name' => 'Poqomam'],
            ['code' => 'poh', 'name' => "Poqomchi'"],
            ['code' => 'kjb', 'name' => "Q'anjob'al"],
            ['code' => 'tzh', 'name' => "Tz'utujil"],
            ['code' => 'mop', 'name' => 'Mopan'],
            ['code' => 'agu', 'name' => 'Awakateko'],
            ['code' => 'chq', 'name' => 'Chuj'],
            ['code' => 'jac', 'name' => 'Jakalteko'],
            ['code' => 'gar', 'name' => 'Garifuna'],
            ['code' => 'xnk', 'name' => 'Xinka'],
        ];

        foreach ($languages as $language) {
            $this->db->table('languages')->ignore(true)->insert($language);
        }

        $fields = $this->db->tableExists('translations')
            ? array_map('strtolower', $this->db->getFieldNames('translations'))
            : [];

        if ($this->db->tableExists('translations') && in_array('lang_code', $fields, true)) {
            try {
                $this->db->query(
                    'ALTER TABLE translations ADD CONSTRAINT fk_translations_language '
                    . 'FOREIGN KEY (lang_code) REFERENCES languages(code) '
                    . 'ON UPDATE CASCADE ON DELETE RESTRICT'
                );
            } catch (\Throwable $e) {
                // The migration is safe to re-run when the constraint already exists.
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('translations')) {
            try {
                $this->db->query('ALTER TABLE translations DROP FOREIGN KEY fk_translations_language');
            } catch (\Throwable $e) {
                // Ignore when the constraint is already absent.
            }
        }

        $this->forge->dropTable('languages', true);
    }
}