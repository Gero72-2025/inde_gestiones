<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEcoeDynamicFormEngineTables extends Migration
{
    public function up(): void
    {
        $this->createCatalogTables();
        $this->seedCatalogData();
        $this->seedDefaultForms();
    }

    public function down(): void
    {
        $defaultForms = $this->defaultForms();

        foreach (array_reverse($defaultForms) as $form) {
            $this->dropDynamicTable((string) $form['slug']);
        }

        if ($this->db->tableExists('ecoe_formulario_campos')) {
            $this->forge->dropTable('ecoe_formulario_campos', true);
        }

        if ($this->db->tableExists('ecoe_formularios')) {
            $this->forge->dropTable('ecoe_formularios', true);
        }

        if ($this->db->tableExists('ecoe_eem_listado')) {
            $this->forge->dropTable('ecoe_eem_listado', true);
        }
    }

    private function createCatalogTables(): void
    {
        if (! $this->db->tableExists('ecoe_formularios')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 160,
                ],
                'codigo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                ],
                'slug' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                ],
                'modulo_asignado' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                ],
                'descripcion' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'plantilla_html' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'instrucciones_html' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'estado' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
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
            $this->forge->addUniqueKey('slug', 'uq_ecoe_formularios_slug');
            $this->forge->addUniqueKey('codigo', 'uq_ecoe_formularios_codigo');
            $this->forge->addKey('modulo_asignado', false, false, 'idx_ecoe_formularios_modulo');
            $this->forge->addKey('estado', false, false, 'idx_ecoe_formularios_estado');
            $this->forge->createTable('ecoe_formularios', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }

        if (! $this->db->tableExists('ecoe_formulario_campos')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'formulario_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 160,
                ],
                'slug' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                ],
                'tipo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                ],
                'etiqueta' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 160,
                ],
                'ayuda' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'opciones_json' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'obligatorio' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'visible_pdf' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'visible_plantilla' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'estado' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'orden' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
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
            $this->forge->addKey('formulario_id', false, false, 'idx_ecoe_campos_formulario');
            $this->forge->addKey('estado', false, false, 'idx_ecoe_campos_estado');
            $this->forge->addUniqueKey(['formulario_id', 'slug'], 'uq_ecoe_campos_formulario_slug');
            $this->forge->addForeignKey('formulario_id', 'ecoe_formularios', 'id', 'CASCADE', 'CASCADE', 'fk_ecoe_campos_formulario');
            $this->forge->createTable('ecoe_formulario_campos', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }

        if (! $this->db->tableExists('ecoe_eem_listado')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 180,
                ],
                'descripcion' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'estado' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
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
            $this->forge->addKey('estado', false, false, 'idx_ecoe_eem_estado');
            $this->forge->createTable('ecoe_eem_listado', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }
    }

    private function seedCatalogData(): void
    {
        if ($this->db->table('ecoe_eem_listado')->countAllResults() > 0) {
            return;
        }

        $this->db->table('ecoe_eem_listado')->insertBatch([
            ['nombre' => 'Empresa Eléctrica Municipal de Guatemala', 'descripcion' => 'Referencia inicial para pruebas y formularios públicos.', 'estado' => 1, 'created_at' => date('Y-m-d H:i:s')],
            ['nombre' => 'Empresa Eléctrica Municipal de Quetzaltenango', 'descripcion' => 'Catálogo de demostración para trámites EEM.', 'estado' => 1, 'created_at' => date('Y-m-d H:i:s')],
            ['nombre' => 'Empresa Eléctrica Municipal de Escuintla', 'descripcion' => 'Catálogo de demostración para trámites EEM.', 'estado' => 1, 'created_at' => date('Y-m-d H:i:s')],
        ]);
    }

    private function seedDefaultForms(): void
    {
        foreach ($this->defaultForms() as $form) {
            $exists = $this->db->table('ecoe_formularios')
                ->where('slug', $form['slug'])
                ->orWhere('codigo', $form['codigo'])
                ->get()
                ->getRowArray();

            $fieldDefinitions = $form['fields'];
            unset($form['fields']);

            $now = date('Y-m-d H:i:s');
            if ($exists) {
                $formId = (int) $exists['id'];
            } else {
                $form['created_at'] = $now;
                $form['updated_at'] = $now;

                $this->db->table('ecoe_formularios')->insert($form);
                $formId = (int) $this->db->insertID();
            }

            if ($formId <= 0) {
                continue;
            }

            $this->seedDefaultFormFields($formId, $fieldDefinitions, $now);
            $this->createDynamicTable((string) $form['slug'], $fieldDefinitions);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    private function seedDefaultFormFields(int $formId, array $fields, string $now): void
    {
        foreach ($fields as $field) {
            $exists = $this->db->table('ecoe_formulario_campos')
                ->where('formulario_id', $formId)
                ->where('slug', (string) ($field['slug'] ?? ''))
                ->get()
                ->getRowArray();

            if ($exists) {
                continue;
            }

            $field['formulario_id'] = $formId;
            $field['created_at'] = $now;
            $field['updated_at'] = $now;

            $this->db->table('ecoe_formulario_campos')->insert($field);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultForms(): array
    {
        return [
            [
                'nombre' => 'Oferta de Suministro',
                'codigo' => 'GU1',
                'slug' => 'gu1_oferta_suministro',
                'modulo_asignado' => 'gu',
                'descripcion' => 'Formulario público para solicitudes de oferta de suministro.',
                'plantilla_html' => $this->defaultTemplateHtml('Oferta de Suministro'),
                'instrucciones_html' => '<p>La constancia refleja la recepción de tu oferta. Conserva el código generado para seguimiento.</p>',
                'estado' => 1,
                'fields' => $this->baseFields([
                    ['nombre' => 'Nombre completo', 'slug' => 'nombre_solicitante', 'tipo' => 'text', 'etiqueta' => 'Nombre completo', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 1],
                    ['nombre' => 'DPI', 'slug' => 'dpi', 'tipo' => 'text', 'etiqueta' => 'DPI', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 2],
                    ['nombre' => 'NIT', 'slug' => 'nit', 'tipo' => 'text', 'etiqueta' => 'NIT', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 3],
                    ['nombre' => 'Dirección', 'slug' => 'direccion', 'tipo' => 'textarea', 'etiqueta' => 'Dirección', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 4],
                    ['nombre' => 'Teléfono', 'slug' => 'telefono', 'tipo' => 'text', 'etiqueta' => 'Teléfono', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 5],
                    ['nombre' => 'Correo electrónico', 'slug' => 'correo', 'tipo' => 'email', 'etiqueta' => 'Correo electrónico', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 6],
                    ['nombre' => 'Documento soporte', 'slug' => 'documento_soporte', 'tipo' => 'file', 'etiqueta' => 'Documento soporte', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 0, 'orden' => 7, 'opciones_json' => json_encode(['allowed_exts' => ['pdf', 'jpg', 'jpeg', 'png'], 'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'], 'max_mb' => 8], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ]),
            ],
            [
                'nombre' => 'Quejas / Comentarios',
                'codigo' => 'GU2',
                'slug' => 'gu2_quejas_comentarios',
                'modulo_asignado' => 'gu',
                'descripcion' => 'Formulario público para presentar quejas y comentarios.',
                'plantilla_html' => $this->defaultTemplateHtml('Quejas / Comentarios'),
                'instrucciones_html' => '<p>Tu comentario fue registrado. El equipo revisará el caso y podrá solicitar información adicional.</p>',
                'estado' => 1,
                'fields' => $this->baseFields([
                    ['nombre' => 'Nombre completo', 'slug' => 'nombre_solicitante', 'tipo' => 'text', 'etiqueta' => 'Nombre completo', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 1],
                    ['nombre' => 'DPI', 'slug' => 'dpi', 'tipo' => 'text', 'etiqueta' => 'DPI', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 2],
                    ['nombre' => 'Teléfono', 'slug' => 'telefono', 'tipo' => 'text', 'etiqueta' => 'Teléfono', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 3],
                    ['nombre' => 'Comentario', 'slug' => 'comentario', 'tipo' => 'textarea', 'etiqueta' => 'Comentario', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 4],
                    ['nombre' => 'Archivo de respaldo', 'slug' => 'archivo_respaldo', 'tipo' => 'file', 'etiqueta' => 'Archivo de respaldo', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 0, 'orden' => 5, 'opciones_json' => json_encode(['allowed_exts' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], 'max_mb' => 8], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ]),
            ],
            [
                'nombre' => 'Nueva Conexión',
                'codigo' => 'EEM1',
                'slug' => 'eem1_nueva_conexion',
                'modulo_asignado' => 'eem',
                'descripcion' => 'Formulario público para nuevas conexiones EEM.',
                'plantilla_html' => $this->defaultTemplateHtml('Nueva Conexión'),
                'instrucciones_html' => '<p>La constancia confirma la recepción de la nueva conexión. Usa el código de referencia para seguimiento.</p>',
                'estado' => 1,
                'fields' => $this->baseFields([
                    ['nombre' => 'Nombre completo', 'slug' => 'nombre_solicitante', 'tipo' => 'text', 'etiqueta' => 'Nombre completo', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 1],
                    ['nombre' => 'DPI', 'slug' => 'dpi', 'tipo' => 'text', 'etiqueta' => 'DPI', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 2],
                    ['nombre' => 'NIT', 'slug' => 'nit', 'tipo' => 'text', 'etiqueta' => 'NIT', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 3],
                    ['nombre' => 'Empresa Eléctrica', 'slug' => 'empresa_nombre', 'tipo' => 'text', 'etiqueta' => 'Empresa Eléctrica', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 4],
                    ['nombre' => 'Dirección del proyecto', 'slug' => 'direccion', 'tipo' => 'textarea', 'etiqueta' => 'Dirección del proyecto', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 5],
                    ['nombre' => 'Documento de identidad', 'slug' => 'documento_identidad', 'tipo' => 'file', 'etiqueta' => 'Documento de identidad', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 0, 'orden' => 6, 'opciones_json' => json_encode(['allowed_exts' => ['pdf', 'jpg', 'jpeg', 'png'], 'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'], 'max_mb' => 8], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ]),
            ],
            [
                'nombre' => 'Gestionar Expediente',
                'codigo' => 'EEM2',
                'slug' => 'eem2_gestionar_expediente',
                'modulo_asignado' => 'eem',
                'descripcion' => 'Formulario público para gestionar un expediente existente.',
                'plantilla_html' => $this->defaultTemplateHtml('Gestionar Expediente'),
                'instrucciones_html' => '<p>La constancia acredita que el expediente fue recibido y quedó asociado a tu código de referencia.</p>',
                'estado' => 1,
                'fields' => $this->baseFields([
                    ['nombre' => 'Nombre completo', 'slug' => 'nombre_solicitante', 'tipo' => 'text', 'etiqueta' => 'Nombre completo', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 1],
                    ['nombre' => 'DPI', 'slug' => 'dpi', 'tipo' => 'text', 'etiqueta' => 'DPI', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 2],
                    ['nombre' => 'Número de expediente', 'slug' => 'numero_expediente', 'tipo' => 'text', 'etiqueta' => 'Número de expediente', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 3],
                    ['nombre' => 'Descripción', 'slug' => 'descripcion', 'tipo' => 'textarea', 'etiqueta' => 'Descripción', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 4],
                    ['nombre' => 'Archivo de respaldo', 'slug' => 'archivo_respaldo', 'tipo' => 'file', 'etiqueta' => 'Archivo de respaldo', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 0, 'orden' => 5, 'opciones_json' => json_encode(['allowed_exts' => ['pdf', 'jpg', 'jpeg', 'png'], 'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'], 'max_mb' => 10], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ]),
            ],
            [
                'nombre' => 'Capacitación Técnica',
                'codigo' => 'EEM3',
                'slug' => 'eem3_capacitacion_tecnica',
                'modulo_asignado' => 'eem',
                'descripcion' => 'Formulario público para solicitar capacitación técnica.',
                'plantilla_html' => $this->defaultTemplateHtml('Capacitación Técnica'),
                'instrucciones_html' => '<p>La constancia de recepción queda asociada a la solicitud de capacitación y permitirá su seguimiento en línea.</p>',
                'estado' => 1,
                'fields' => $this->baseFields([
                    ['nombre' => 'Nombre completo', 'slug' => 'nombre_solicitante', 'tipo' => 'text', 'etiqueta' => 'Nombre completo', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 1],
                    ['nombre' => 'DPI', 'slug' => 'dpi', 'tipo' => 'text', 'etiqueta' => 'DPI', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 2],
                    ['nombre' => 'Empresa eléctrica', 'slug' => 'empresa_nombre', 'tipo' => 'text', 'etiqueta' => 'Empresa eléctrica', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 3],
                    ['nombre' => 'Tema de capacitación', 'slug' => 'tema_capacitacion', 'tipo' => 'text', 'etiqueta' => 'Tema de capacitación', 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 4],
                    ['nombre' => 'Fecha sugerida', 'slug' => 'fecha_sugerida', 'tipo' => 'date', 'etiqueta' => 'Fecha sugerida', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 1, 'orden' => 5],
                    ['nombre' => 'Archivo adicional', 'slug' => 'archivo_adicional', 'tipo' => 'file', 'etiqueta' => 'Archivo adicional', 'obligatorio' => 0, 'visible_pdf' => 1, 'visible_plantilla' => 0, 'orden' => 6, 'opciones_json' => json_encode(['allowed_exts' => ['pdf', 'jpg', 'jpeg', 'png'], 'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'], 'max_mb' => 10], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ]),
            ],
        ];
    }

    private function baseFields(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $field['estado'] = (int) ($field['estado'] ?? 1);
            $field['obligatorio'] = (int) ($field['obligatorio'] ?? 1);
            $field['visible_pdf'] = (int) ($field['visible_pdf'] ?? 1);
            $field['visible_plantilla'] = (int) ($field['visible_plantilla'] ?? 0);
            $field['ayuda'] = $field['ayuda'] ?? null;
            $field['opciones_json'] = $field['opciones_json'] ?? null;
            $field['orden'] = (int) ($field['orden'] ?? 0);
            $normalized[] = $field;
        }

        return $normalized;
    }

    private function defaultTemplateHtml(string $titulo): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; color: #163247; font-size: 12px; }
    .sheet { border: 1px solid #d9e4eb; border-radius: 16px; padding: 24px; }
    .title { font-size: 20px; font-weight: 700; margin: 0 0 6px 0; }
    .meta { color: #587084; margin-bottom: 18px; }
    .code { display: inline-block; padding: 8px 12px; border-radius: 999px; background: #e9f6ef; color: #155c39; font-weight: 700; }
    table { width: 100%; border-collapse: collapse; margin-top: 18px; }
    td { padding: 8px 10px; border-bottom: 1px solid #e6edf2; vertical-align: top; }
    td.label { width: 36%; font-weight: 700; color: #24485f; }
    .instructions { margin-top: 18px; background: #f8fbfd; border: 1px solid #e2ebf2; padding: 14px 16px; border-radius: 14px; }
  </style>
</head>
<body>
  <div class="sheet">
    <div class="title">{{titulo}}</div>
    <div class="meta">Código de referencia: <span class="code">{{codigo_referencia}}</span></div>
    <div class="meta">Fecha de emisión: {{fecha_emision}}</div>
    <div>{{campos_html}}</div>
    <div class="instructions">{{instrucciones_html}}</div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    private function createDynamicTable(string $slug, array $fields): void
    {
        $tableName = $this->dynamicTableName($slug);
        $keySuffix = $this->normalizeSlug($slug);

        if ($this->db->tableExists($tableName)) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'formulario_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'codigo_referencia' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'empresa_electrica_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'dpi' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'nit' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'estado_tramite' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'recibido',
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
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

        foreach ($fields as $field) {
            $column = $this->normalizeSlug((string) ($field['slug'] ?? ''));

            if ($column === '' || in_array($column, ['id', 'formulario_id', 'codigo_referencia', 'empresa_electrica_id', 'dpi', 'nit', 'estado_tramite', 'payload_json', 'created_at', 'updated_at'], true)) {
                continue;
            }

            $this->forge->addField([$column => $this->forgeFieldDefinition($field)]);
        }

        $this->forge->addKey('id', true, false, $this->dynamicKeyName($keySuffix, 'pk'));
        $this->forge->addKey('formulario_id', false, false, $this->dynamicKeyName($keySuffix, 'idx_formulario'));
        $this->forge->addKey('codigo_referencia', false, false, $this->dynamicKeyName($keySuffix, 'idx_codigo_referencia'));
        $this->forge->addKey('dpi', false, false, $this->dynamicKeyName($keySuffix, 'idx_dpi'));
        $this->forge->addKey('nit', false, false, $this->dynamicKeyName($keySuffix, 'idx_nit'));
        $this->forge->addKey('estado_tramite', false, false, $this->dynamicKeyName($keySuffix, 'idx_estado_tramite'));
        $this->forge->addKey('created_at', false, false, $this->dynamicKeyName($keySuffix, 'idx_created_at'));
        $this->forge->addUniqueKey('codigo_referencia', $this->dynamicKeyName($keySuffix, 'uq_codigo_referencia'));
        $this->forge->addForeignKey('formulario_id', 'ecoe_formularios', 'id', 'CASCADE', 'CASCADE', $this->dynamicKeyName($keySuffix, 'fk_formulario'));

        if ($this->db->tableExists('ecoe_eem_listado')) {
            $this->forge->addForeignKey('empresa_electrica_id', 'ecoe_eem_listado', 'id', 'SET NULL', 'SET NULL', $this->dynamicKeyName($keySuffix, 'fk_empresa_electrica'));
        }

        $this->forge->createTable($tableName, true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function forgeFieldDefinition(array $field): array
    {
        $tipo = strtolower((string) ($field['tipo'] ?? 'text'));

        return match ($tipo) {
            'textarea' => ['type' => 'LONGTEXT', 'null' => true],
            'number' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'file' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'date', 'time', 'email', 'text', 'select', 'radio', 'checkbox' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            default => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        };
    }

    private function dropDynamicTable(string $slug): void
    {
        $tableName = $this->dynamicTableName($slug);

        if ($this->db->tableExists($tableName)) {
            $this->forge->dropTable($tableName, true);
        }
    }

    private function normalizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? '';
        $value = preg_replace('/_+/', '_', $value) ?? '';

        return trim($value, '_');
    }

    private function dynamicTableName(string $slug): string
    {
        return 'dyn_ecoe_' . $this->normalizeSlug($slug);
    }

    private function dynamicKeyName(string $slug, string $suffix): string
    {
        return 'dyn_' . $slug . '_' . $suffix;
    }
}