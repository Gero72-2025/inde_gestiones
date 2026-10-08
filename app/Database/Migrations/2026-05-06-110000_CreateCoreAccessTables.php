<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoreAccessTables extends Migration
{
    private array $tableOptions = [
        'ENGINE' => 'InnoDB',
        'DEFAULT CHARSET' => 'utf8mb4',
        'COLLATE' => 'utf8mb4_unicode_ci',
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('gerencias')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'nombre' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
                'descripcion' => ['type' => 'TEXT', 'null' => true],
                'slug' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'null' => false, 'default' => 'active'],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
                'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('slug', 'uk_gerencias_slug');
            $this->forge->createTable('gerencias', true, $this->tableOptions);
        }

        if (! $this->db->tableExists('roles')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'nombre' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
                'descripcion' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
                'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('nombre', 'uk_roles_nombre');
            $this->forge->createTable('roles', true, $this->tableOptions);
        }

        if (! $this->db->tableExists('permissions')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'nombre' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
                'slug' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
                'descripcion' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
                'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('slug', 'uk_permissions_slug');
            $this->forge->createTable('permissions', true, $this->tableOptions);
        }

        if (! $this->db->tableExists('users')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'gerencia_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
                'username' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
                'email' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => false],
                'password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'first_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'last_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'locked', 'pending'], 'null' => false, 'default' => 'pending'],
                'google_2fa_secret' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'twofa_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
                'last_login_at' => ['type' => 'DATETIME', 'null' => true],
                'password_changed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
                'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
                'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('username', 'uk_users_username');
            $this->forge->addUniqueKey('email', 'uk_users_email');
            $this->forge->addKey('gerencia_id', false, false, 'idx_users_gerencia_id');
            $this->forge->addForeignKey('gerencia_id', 'gerencias', 'id', 'CASCADE', 'SET NULL', 'fk_users_gerencia');
            $this->forge->createTable('users', true, $this->tableOptions);
        }

        if (! $this->db->tableExists('user_roles')) {
            $this->forge->addField([
                'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'role_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey(['user_id', 'role_id'], true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_user_roles_user');
            $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE', 'fk_user_roles_role');
            $this->forge->createTable('user_roles', true, $this->tableOptions);
        }

        if (! $this->db->tableExists('role_permissions')) {
            $this->forge->addField([
                'role_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'permission_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey(['role_id', 'permission_id'], true);
            $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE', 'fk_role_permissions_role');
            $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE', 'fk_role_permissions_permission');
            $this->forge->createTable('role_permissions', true, $this->tableOptions);
        }

        if (! $this->db->tableExists('user_permissions')) {
            $this->forge->addField([
                'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'permission_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'is_granted' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
            $this->forge->addKey(['user_id', 'permission_id'], true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_user_permissions_user');
            $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE', 'fk_user_permissions_permission');
            $this->forge->createTable('user_permissions', true, $this->tableOptions);
        }

        if (! $this->db->tableExists('upload_logs')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'gerencia_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'nombre_archivo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'ruta_archivo' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => false],
                'mime_type' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'tamano_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false, 'default' => 0],
                'registros_procesados' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0],
                'fecha_creacion' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('gerencia_id', false, false, 'idx_upload_logs_gerencia');
            $this->forge->addKey('usuario_id', false, false, 'idx_upload_logs_usuario');
            $this->forge->addForeignKey('gerencia_id', 'gerencias', 'id', 'CASCADE', 'CASCADE', 'fk_upload_logs_gerencia');
            $this->forge->addForeignKey('usuario_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_upload_logs_usuario');
            $this->forge->createTable('upload_logs', true, $this->tableOptions);
        }

        $this->seedCoreData();
    }

    private function seedCoreData(): void
    {
        if (! $this->db->transBegin()) {
            throw new \RuntimeException('No fue posible iniciar la transacción de datos iniciales.');
        }

        try {
            $this->insertSeedRow('gerencias', [
                'nombre' => 'Gerencia Gero',
                'descripcion' => 'Modulo base de ejemplo para nuevas gerencias.',
                'slug' => 'gero',
                'status' => 'active',
            ]);

            $roles = [
                ['nombre' => 'Super Administrador', 'descripcion' => 'Acceso transversal a todas las gerencias y configuraciones.'],
                ['nombre' => 'Administrador de Gerencia', 'descripcion' => 'Administra contenido y usuarios de una gerencia concreta.'],
                ['nombre' => 'Editor', 'descripcion' => 'Puede editar contenido dentro de su gerencia.'],
                ['nombre' => 'Lector', 'descripcion' => 'Acceso de lectura a la informacion publicada.'],
            ];

            foreach ($roles as $role) {
                $this->insertSeedRow('roles', $role);
            }

            $permissions = [
                ['nombre' => 'Acceso transversal a gerencias', 'slug' => 'gerencias.all.access', 'descripcion' => 'Permite ingresar a cualquier modulo de gerencia.'],
                ['nombre' => 'Acceso total del sistema', 'slug' => 'superadmin.access', 'descripcion' => 'Permite ignorar restricciones de modulo.'],
                ['nombre' => 'Acceso al modulo Gero', 'slug' => 'gerencia.gero.access', 'descripcion' => 'Permite acceder al modulo de la Gerencia Gero.'],
                ['nombre' => 'Gestionar 2FA', 'slug' => 'security.2fa.manage', 'descripcion' => 'Permite administrar la configuracion de doble factor.'],
            ];

            foreach ($permissions as $permission) {
                $this->insertSeedRow('permissions', $permission);
            }

            $gerenciaId = $this->findId('gerencias', 'slug', 'gero');
            $superAdminRoleId = $this->findId('roles', 'nombre', 'Super Administrador');
            $managerRoleId = $this->findId('roles', 'nombre', 'Administrador de Gerencia');

            foreach (['gerencias.all.access', 'superadmin.access', 'gerencia.gero.access', 'security.2fa.manage'] as $slug) {
                $permissionId = $this->findId('permissions', 'slug', $slug);

                if ($superAdminRoleId !== null && $permissionId !== null) {
                    $this->insertSeedRow('role_permissions', [
                        'role_id' => $superAdminRoleId,
                        'permission_id' => $permissionId,
                    ]);
                }

                if ($managerRoleId !== null && $permissionId !== null && in_array($slug, ['gerencia.gero.access', 'security.2fa.manage'], true)) {
                    $this->insertSeedRow('role_permissions', [
                        'role_id' => $managerRoleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            if ($gerenciaId !== null) {
                $this->insertSeedRow('users', [
                    'gerencia_id' => $gerenciaId,
                    'username' => 'superadmin',
                    'email' => 'admin@portal-inde.local',
                    'password' => '$2y$10$9xqWD21AfAPwrCfWAoWx1Ol5mF5peGxlrD/pjji7t/G6/hFR74Qa.',
                    'first_name' => 'Super',
                    'last_name' => 'Administrador',
                    'status' => 'active',
                    'twofa_enabled' => 0,
                ]);

                $userId = $this->findId('users', 'username', 'superadmin');

                if ($userId !== null && $superAdminRoleId !== null) {
                    $this->insertSeedRow('user_roles', [
                        'user_id' => $userId,
                        'role_id' => $superAdminRoleId,
                    ]);
                }
            }

            if (! $this->db->transStatus()) {
                throw $this->seedWriteException('datos iniciales');
            }

            if (! $this->db->transCommit()) {
                throw $this->seedWriteException('commit de datos iniciales');
            }
        } catch (\Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    private function insertSeedRow(string $table, array $row): void
    {
        foreach ($this->db->getIndexData($table) as $index) {
            if (! in_array((string) ($index->type ?? ''), ['PRIMARY', 'UNIQUE'], true) || ! is_array($index->fields ?? null)) {
                continue;
            }

            $query = $this->db->table($table);
            $usable = true;
            foreach ($index->fields as $field) {
                if (! array_key_exists($field, $row) || $row[$field] === null) {
                    $usable = false;
                    break;
                }
                $query->where($field, $row[$field]);
            }

            if ($usable && $query->get()->getFirstRow('array') !== null) {
                return;
            }
        }

        $inserted = $this->db->table($table)->insert($row);
        if (! $inserted) {
            throw $this->seedWriteException($table);
        }
    }

    private function seedWriteException(string $operation): \RuntimeException
    {
        $error = $this->db->error();
        $message = (string) ($error['message'] ?? 'Error SQL sin detalle.');
        $query = (string) $this->db->getLastQuery();

        return new \RuntimeException('Falló ' . $operation . ': ' . $message . ($query !== '' ? ' SQL: ' . $query : ''));
    }

    private function findId(string $table, string $column, string $value): ?int
    {
        $row = $this->db->table($table)
            ->select('id')
            ->where($column, $value)
            ->get()
            ->getRowArray();

        return is_array($row) ? (int) $row['id'] : null;
    }

    public function down(): void
    {
        $this->forge->dropTable('upload_logs', true);
        $this->forge->dropTable('user_permissions', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('user_roles', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('gerencias', true);
    }
}