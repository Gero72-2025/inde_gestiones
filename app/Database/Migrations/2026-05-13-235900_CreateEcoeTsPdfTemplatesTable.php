<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEcoeTsPdfTemplatesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'            => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'slug'              => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'descripcion'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false, 'default' => ''],
            'html_template'     => ['type' => 'MEDIUMTEXT', 'null' => false],
            'instrucciones_html'=> ['type' => 'MEDIUMTEXT', 'null' => false],
            'is_default'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
            'updated_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => '1970-01-01 00:00:00'],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug', 'uq_ts_pdf_template_slug');
        $this->forge->addKey('is_default', false, false, 'idx_ts_pdf_template_default');
        $this->forge->addKey('is_active', false, false, 'idx_ts_pdf_template_active');
        $this->forge->createTable('ecoe_ts_pdf_templates', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);

        $html = <<<'HTML'
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 12px; }
.header { border-bottom: 2px solid #0d6efd; margin-bottom: 12px; padding-bottom: 8px; }
.title { font-size: 18px; font-weight: bold; margin: 0; }
.subtitle { margin: 4px 0 0 0; color: #4b5563; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
th { background: #f3f4f6; width: 30%; }
.badge { display: inline-block; background: #e9f2ff; border: 1px solid #9ec5fe; border-radius: 4px; padding: 2px 8px; font-weight: bold; }
.footer { margin-top: 16px; font-size: 11px; color: #6b7280; }
</style>
</head>
<body>
  <div class="header">
    <p class="title">Comprobante de Solicitud - Tarifa Social ECOE</p>
    <p class="subtitle">Codigo de referencia: {{codigo_referencia}}</p>
  </div>

  <table>
    <tr><th>ID Solicitud</th><td>{{id_solicitud}}</td></tr>
    <tr><th>Nombre solicitante</th><td>{{nombre}}</td></tr>
    <tr><th>DPI</th><td>{{dpi}}</td></tr>
    <tr><th>Telefono</th><td>{{telefono}}</td></tr>
    <tr><th>Direccion</th><td>{{direccion}}</td></tr>
    <tr><th>Estado actual</th><td><span class="badge">{{estado_nombre}}</span></td></tr>
    <tr><th>Descripcion de estado</th><td>{{estado_descripcion}}</td></tr>
    <tr><th>Fecha de ingreso</th><td>{{fecha_ingreso}}</td></tr>
    <tr><th>Fecha de emision</th><td>{{fecha_emision}}</td></tr>
  </table>

  <h3>Instrucciones para seguimiento</h3>
  {{instrucciones_html}}

  <div class="footer">
    Documento generado automaticamente por el Portal Informativo INDE - ECOE.
  </div>
</body>
</html>
HTML;

        $instrucciones = <<<'HTML'
<ul>
  <li>Conserva este comprobante y tu codigo de referencia.</li>
  <li>Para rastrear el estado, ingresa tu DPI o codigo en el modulo de seguimiento del portal.</li>
  <li>Si necesitas apoyo, presenta este PDF ante el personal de atencion.</li>
</ul>
HTML;

        $this->db->table('ecoe_ts_pdf_templates')->insert([
            'nombre'             => 'Plantilla Base Tarifa Social',
            'slug'               => 'base-tarifa-social',
            'descripcion'        => 'Plantilla inicial para comprobante de solicitud de Tarifa Social.',
            'html_template'      => $html,
            'instrucciones_html' => $instrucciones,
            'is_default'         => 1,
            'is_active'          => 1,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('ecoe_ts_pdf_templates', true);
    }
}
