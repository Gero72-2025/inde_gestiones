<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePublicPortalTables extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('public_beneficiarios')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'dpi_hash' => [
                    'type' => 'CHAR',
                    'constraint' => 64,
                ],
                'estado_beneficio' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'estado_beneficio_en' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'estado_beneficio_quc' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'estado_beneficio_qeq' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'estado_beneficio_cak' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'gerencia_slug' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
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
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('dpi_hash');
            $this->forge->createTable('public_beneficiarios', true);
        }

        if (! $this->db->tableExists('public_electrificacion')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'comunidad' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                ],
                'comunidad_en' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                    'null' => true,
                ],
                'comunidad_quc' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                    'null' => true,
                ],
                'comunidad_qeq' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                    'null' => true,
                ],
                'comunidad_cak' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                    'null' => true,
                ],
                'fase_actual' => [
                    'type' => 'VARCHAR',
                    'constraint' => 140,
                ],
                'fase_actual_en' => [
                    'type' => 'VARCHAR',
                    'constraint' => 140,
                    'null' => true,
                ],
                'fase_actual_quc' => [
                    'type' => 'VARCHAR',
                    'constraint' => 140,
                    'null' => true,
                ],
                'fase_actual_qeq' => [
                    'type' => 'VARCHAR',
                    'constraint' => 140,
                    'null' => true,
                ],
                'fase_actual_cak' => [
                    'type' => 'VARCHAR',
                    'constraint' => 140,
                    'null' => true,
                ],
                'departamento' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'municipio' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
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
            $this->forge->addKey('departamento');
            $this->forge->addKey('municipio');
            $this->forge->createTable('public_electrificacion', true);
        }

        if (! $this->db->tableExists('public_cortes_energia')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'departamento' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'municipio' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'motivo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 200,
                ],
                'motivo_en' => [
                    'type' => 'VARCHAR',
                    'constraint' => 200,
                    'null' => true,
                ],
                'motivo_quc' => [
                    'type' => 'VARCHAR',
                    'constraint' => 200,
                    'null' => true,
                ],
                'motivo_qeq' => [
                    'type' => 'VARCHAR',
                    'constraint' => 200,
                    'null' => true,
                ],
                'motivo_cak' => [
                    'type' => 'VARCHAR',
                    'constraint' => 200,
                    'null' => true,
                ],
                'estado' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'default' => 'programado',
                ],
                'fecha_inicio' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'fecha_fin' => [
                    'type' => 'DATETIME',
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
            $this->forge->addKey('id', true);
            $this->forge->addKey('departamento');
            $this->forge->addKey('municipio');
            $this->forge->createTable('public_cortes_energia', true);
        }

        $now = date('Y-m-d H:i:s');

        if ($this->db->table('public_beneficiarios')->countAllResults() === 0) {
            $this->db->table('public_beneficiarios')->insertBatch([
                [
                    'dpi_hash' => hash('sha256', '1234567890101'),
                    'estado_beneficio' => 'Beneficio activo',
                    'estado_beneficio_en' => 'Benefit active',
                    'estado_beneficio_quc' => 'Beneficio k\'o',
                    'estado_beneficio_qeq' => 'Beneficio active',
                    'estado_beneficio_cak' => 'Beneficio k\'o',
                    'gerencia_slug' => 'gero',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'dpi_hash' => hash('sha256', '2222222222222'),
                    'estado_beneficio' => 'Sin beneficio vigente',
                    'estado_beneficio_en' => 'No active benefit',
                    'estado_beneficio_quc' => 'Maj beneficio wakami',
                    'estado_beneficio_qeq' => 'Moko beneficio active',
                    'estado_beneficio_cak' => 'Maj beneficio wakami',
                    'gerencia_slug' => 'gero',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if ($this->db->table('public_electrificacion')->countAllResults() === 0) {
            $this->db->table('public_electrificacion')->insertBatch([
                [
                    'comunidad' => 'Aldea El Pinal',
                    'comunidad_en' => 'El Pinal Village',
                    'comunidad_quc' => 'Aldea El Pinal',
                    'comunidad_qeq' => 'Aldea El Pinal',
                    'comunidad_cak' => 'Aldea El Pinal',
                    'fase_actual' => 'Ejecucion de obra',
                    'fase_actual_en' => 'Construction phase',
                    'fase_actual_quc' => 'K\'aslemal b\'anom',
                    'fase_actual_qeq' => 'Ejecucion obra',
                    'fase_actual_cak' => 'Rutz\'aqat b\'anom',
                    'departamento' => 'Alta Verapaz',
                    'municipio' => 'Coban',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'comunidad' => 'Caserio La Union',
                    'comunidad_en' => 'La Union Hamlet',
                    'comunidad_quc' => 'Caserio La Union',
                    'comunidad_qeq' => 'Caserio La Union',
                    'comunidad_cak' => 'Caserio La Union',
                    'fase_actual' => 'Diseno tecnico',
                    'fase_actual_en' => 'Technical design',
                    'fase_actual_quc' => 'Nuk\'utun rutz\'aqat',
                    'fase_actual_qeq' => 'Diseno tecnico',
                    'fase_actual_cak' => 'Nuchojmirisaxik',
                    'departamento' => 'Quiche',
                    'municipio' => 'Chichicastenango',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if ($this->db->table('public_cortes_energia')->countAllResults() === 0) {
            $this->db->table('public_cortes_energia')->insertBatch([
                [
                    'departamento' => 'Guatemala',
                    'municipio' => 'Villa Nueva',
                    'motivo' => 'Mantenimiento preventivo',
                    'motivo_en' => 'Preventive maintenance',
                    'motivo_quc' => 'Chajinem nab\'e',
                    'motivo_qeq' => 'Mantenimiento preventivo',
                    'motivo_cak' => 'Chajin nab\'ey',
                    'estado' => 'programado',
                    'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+1 day 08:00')),
                    'fecha_fin' => date('Y-m-d H:i:s', strtotime('+1 day 12:00')),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'departamento' => 'Escuintla',
                    'municipio' => 'Santa Lucia Cotzumalguapa',
                    'motivo' => 'Emergencia por clima',
                    'motivo_en' => 'Weather emergency',
                    'motivo_quc' => 'K\'axk\'ol ruma jab\'',
                    'motivo_qeq' => 'Emergencia clima',
                    'motivo_cak' => 'K\'axk\'ol ruma q\'ij',
                    'estado' => 'en curso',
                    'fecha_inicio' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'fecha_fin' => date('Y-m-d H:i:s', strtotime('+3 hours')),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('public_cortes_energia', true);
        $this->forge->dropTable('public_electrificacion', true);
        $this->forge->dropTable('public_beneficiarios', true);
    }
}
