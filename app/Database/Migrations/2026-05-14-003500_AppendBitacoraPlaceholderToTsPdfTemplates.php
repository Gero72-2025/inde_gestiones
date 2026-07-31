<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AppendBitacoraPlaceholderToTsPdfTemplates extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('ecoe_ts_pdf_templates')) {
            return;
        }

        $rows = $this->db->table('ecoe_ts_pdf_templates')
            ->select('id, html_template')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $html = (string) ($row['html_template'] ?? '');

            if ($html === '' || str_contains($html, '{{bitacora_html}}')) {
                continue;
            }

            $append = '<h3>Bitácora de Estado</h3>{{bitacora_html}}';
            $updated = str_contains($html, '</body>')
                ? str_replace('</body>', $append . '</body>', $html)
                : ($html . $append);

            $this->db->table('ecoe_ts_pdf_templates')
                ->where('id', (int) $row['id'])
                ->update([
                    'html_template' => $updated,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('ecoe_ts_pdf_templates')) {
            return;
        }

        $rows = $this->db->table('ecoe_ts_pdf_templates')
            ->select('id, html_template')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $html = (string) ($row['html_template'] ?? '');
            $updated = str_replace('<h3>Bitácora de Estado</h3>{{bitacora_html}}', '', $html);

            if ($updated !== $html) {
                $this->db->table('ecoe_ts_pdf_templates')
                    ->where('id', (int) $row['id'])
                    ->update([
                        'html_template' => $updated,
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
            }
        }
    }
}
