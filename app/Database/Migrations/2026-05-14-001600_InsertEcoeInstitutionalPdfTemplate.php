<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertEcoeInstitutionalPdfTemplate extends Migration
{
    public function up(): void
    {
        $exists = $this->db->table('ecoe_ts_pdf_templates')
            ->where('slug', 'institucional-inde-ecoe')
            ->get()
            ->getRowArray();

        if ($exists) {
            return;
        }

        $html = <<<'HTML'
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 11.5px; }
.header { border: 1px solid #0f766e; background: #ecfeff; padding: 12px; margin-bottom: 12px; }
.title { margin: 0; font-size: 17px; color: #0f766e; font-weight: bold; }
.subtitle { margin: 3px 0 0 0; color: #155e75; }
.grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
.grid th, .grid td { border: 1px solid #9ca3af; padding: 6px 7px; }
.grid th { background: #f3f4f6; width: 34%; text-transform: uppercase; font-size: 10px; }
.status { display: inline-block; border: 1px solid #0f766e; color: #0f766e; background: #f0fdfa; padding: 3px 8px; border-radius: 6px; font-weight: bold; }
.block { margin-top: 12px; border: 1px solid #d1d5db; padding: 10px; }
.footer { margin-top: 16px; font-size: 10px; color: #4b5563; border-top: 1px dashed #9ca3af; padding-top: 8px; }
.table-small { width: 100%; border-collapse: collapse; margin-top: 6px; }
.table-small th, .table-small td { border: 1px solid #d1d5db; padding: 4px 6px; }
.table-small th { background: #f9fafb; }
</style>
</head>
<body>
  <div class="header">
    <p class="title">INDE - ECOE | Comprobante Institucional de Solicitud</p>
    <p class="subtitle">Tarifa Social - Codigo: <strong>{{codigo_referencia}}</strong></p>
  </div>

  <table class="grid">
    <tr><th>ID de solicitud</th><td>{{id_solicitud}}</td></tr>
    <tr><th>Solicitante</th><td>{{nombre}}</td></tr>
    <tr><th>DPI</th><td>{{dpi}}</td></tr>
    <tr><th>Telefono</th><td>{{telefono}}</td></tr>
    <tr><th>Direccion</th><td>{{direccion}}</td></tr>
    <tr><th>Estado actual</th><td><span class="status">{{estado_nombre}}</span></td></tr>
    <tr><th>Descripcion del estado</th><td>{{estado_descripcion}}</td></tr>
    <tr><th>Fecha de ingreso</th><td>{{fecha_ingreso}}</td></tr>
    <tr><th>Fecha de emision</th><td>{{fecha_emision}}</td></tr>
  </table>

  <div class="block">
    <strong>Instrucciones de seguimiento</strong>
    <div>{{instrucciones_html}}</div>
  </div>

  <div class="block">
    <strong>Resumen de control</strong>
    <table class="table-small">
      <tr><th>Campo</th><th>Valor</th></tr>
      <tr><td>Codigo de referencia</td><td>{{codigo_referencia}}</td></tr>
      <tr><td>Estado</td><td>{{estado_nombre}}</td></tr>
      <tr><td>Emitido por</td><td>Portal Informativo INDE - ECOE</td></tr>
    </table>
  </div>

  <div class="footer">
    Documento de caracter informativo. Conservalo para consultas y seguimiento de tu solicitud.
  </div>
</body>
</html>
HTML;

        $instrucciones = <<<'HTML'
<ol>
  <li>Conserva este comprobante en formato digital o impreso.</li>
  <li>Realiza el rastreo con DPI o código de referencia en el portal de Tarifa Social.</li>
  <li>Si tu estado cambia, descarga un nuevo comprobante actualizado.</li>
  <li>Para soporte, presenta este documento en el canal oficial de atención.</li>
</ol>
HTML;

        $this->db->table('ecoe_ts_pdf_templates')->insert([
            'nombre'             => 'Plantilla Institucional INDE ECOE',
            'slug'               => 'institucional-inde-ecoe',
            'descripcion'        => 'Plantilla institucional con formato tabular y bloque de control.',
            'html_template'      => $html,
            'instrucciones_html' => $instrucciones,
            'is_default'         => 0,
            'is_active'          => 1,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
    }

    public function down(): void
    {
        $this->db->table('ecoe_ts_pdf_templates')
            ->where('slug', 'institucional-inde-ecoe')
            ->delete();
    }
}
