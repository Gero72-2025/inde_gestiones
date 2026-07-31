<?php

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\GerenciaModel;
use App\Modules\Admin\Services\GerenciaModuleScaffolder;
use Throwable;

class GerenciasController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\gerencias');
    }

    public function list()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $gerencias = $this->rbac->getAccessibleGerencias((int) $auth['user_id']);
        $permissions = (array) ($auth['permissions'] ?? []);

        if ($gerencias === [] && (
            in_array('superadmin.access', $permissions, true)
            || in_array('gerencias.all.access', $permissions, true)
            || in_array('admin.access', $permissions, true)
        )) {
            $gerencias = db_connect()->table('gerencias')->select('id, nombre, slug, status')->orderBy('nombre', 'ASC')->get()->getResultArray();
        }

        return $this->encryptedAdminResponse([
            'gerencias' => $gerencias,
        ]);
    }

    public function store()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []))) {
            return $this->encryptedAdminResponse(['message' => 'No tienes permisos para crear gerencias.'], 403);
        }

        $data = [
            'nombre' => trim((string) $this->request->getPost('nombre')),
            'descripcion' => trim((string) $this->request->getPost('descripcion')),
            'slug' => strtolower(trim((string) $this->request->getPost('slug'))),
            'status' => (string) $this->request->getPost('status') ?: 'active',
        ];

        if ($data['nombre'] === '' || $data['slug'] === '') {
            return $this->encryptedAdminResponse(['message' => 'Nombre y slug son obligatorios.'], 422);
        }

        if (preg_match('/^[a-z][a-z0-9_]*$/', $data['slug']) !== 1) {
            return $this->encryptedAdminResponse(['message' => 'El slug solo permite letras minusculas, numeros y guion bajo. Debe iniciar con letra.'], 422);
        }

        if (in_array($data['slug'], ['admin', 'auth'], true)) {
            return $this->encryptedAdminResponse(['message' => 'El slug seleccionado no esta permitido.'], 422);
        }

        $model = new GerenciaModel();
        $scaffolder = new GerenciaModuleScaffolder();

        if ($model->where('slug', $data['slug'])->countAllResults() > 0) {
            return $this->encryptedAdminResponse(['message' => 'El slug ya existe.'], 422);
        }

        if ($scaffolder->moduleExists($data['slug'])) {
            return $this->encryptedAdminResponse(['message' => 'Ya existe una carpeta de modulo para este slug.'], 422);
        }

        try {
            $scaffolder->scaffold($data['slug'], $data['nombre']);
        } catch (Throwable $exception) {
            return $this->encryptedAdminResponse(['message' => 'No fue posible crear el modulo MVC: ' . $exception->getMessage()], 500);
        }

        $model->insert($data);

        $db = db_connect();
        $permissionsTable = $db->table('permissions');
        $rolePermissionsTable = $db->table('role_permissions');

        $permissionSpecs = [
            [
                'slug' => 'gerencia.' . $data['slug'] . '.access',
                'nombre' => 'Acceso base a gerencia ' . $data['nombre'],
                'descripcion' => 'Permite acceder al modulo de gerencia ' . $data['nombre'] . '.',
            ],
            [
                'slug' => 'gerencia.' . $data['slug'] . '.dashboard.access',
                'nombre' => 'Acceso dashboard de gerencia ' . $data['nombre'],
                'descripcion' => 'Permite ingresar al dashboard inicial de la gerencia ' . $data['nombre'] . '.',
            ],
            [
                'slug' => 'gerencia.' . $data['slug'] . '.modulo.access',
                'nombre' => 'Acceso submodulos de gerencia ' . $data['nombre'],
                'descripcion' => 'Permite acceder a submodulos de la gerencia ' . $data['nombre'] . '.',
            ],
        ];

        $superAdminRole = $db->table('roles')->select('id')->where('nombre', 'Super Administrador')->get()->getRowArray();
        $superAdminRoleId = is_array($superAdminRole) ? (int) $superAdminRole['id'] : 0;

        foreach ($permissionSpecs as $permissionSpec) {
            $existingPermission = $permissionsTable->where('slug', $permissionSpec['slug'])->get()->getRowArray();

            if (! is_array($existingPermission)) {
                $permissionsTable->insert($permissionSpec);
                $permissionId = (int) $db->insertID();
            } else {
                $permissionId = (int) $existingPermission['id'];
                $permissionsTable->where('id', $permissionId)->update([
                    'nombre' => $permissionSpec['nombre'],
                    'descripcion' => $permissionSpec['descripcion'],
                ]);
            }

            if ($superAdminRoleId > 0 && $permissionId > 0) {
                $linkExists = $rolePermissionsTable
                    ->where('role_id', $superAdminRoleId)
                    ->where('permission_id', $permissionId)
                    ->countAllResults();

                if ($linkExists === 0) {
                    $rolePermissionsTable->insert([
                        'role_id' => $superAdminRoleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        return $this->encryptedAdminResponse([
            'message' => 'Gerencia creada correctamente con modulo MVC inicial y permisos base.',
            'id' => $model->getInsertID(),
        ]);
    }

    public function update(int $id)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $model = new GerenciaModel();
        $gerencia = $model->find($id);

        if (! is_array($gerencia)) {
            return $this->encryptedAdminResponse(['message' => 'Gerencia no encontrada.'], 404);
        }

        $isSuperAdmin = $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []));

        if (! $isSuperAdmin && (int) ($auth['gerencia_id'] ?? 0) !== $id) {
            return $this->encryptedAdminResponse(['message' => 'No puedes editar otra gerencia.'], 403);
        }

        $payload = [
            'nombre' => trim((string) $this->request->getPost('nombre')),
            'descripcion' => trim((string) $this->request->getPost('descripcion')),
            'status' => (string) $this->request->getPost('status') ?: 'active',
        ];

        if ($isSuperAdmin) {
            $slug = strtolower(trim((string) $this->request->getPost('slug')));

            if ($slug !== '') {
                $payload['slug'] = $slug;
            }
        }

        $model->update($id, $payload);

        return $this->encryptedAdminResponse(['message' => 'Gerencia actualizada.']);
    }

    public function delete(int $id)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        if (! $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []))) {
            return $this->encryptedAdminResponse(['message' => 'No tienes permisos para eliminar gerencias.'], 403);
        }

        $db = db_connect();
        $usersCount = $db->table('users')->where('gerencia_id', $id)->where('deleted_at', null)->countAllResults();

        if ($usersCount > 0) {
            return $this->encryptedAdminResponse(['message' => 'No se puede eliminar la gerencia porque tiene usuarios asignados.'], 422);
        }

        $model = new GerenciaModel();
        $model->delete($id);

        return $this->encryptedAdminResponse(['message' => 'Gerencia eliminada.']);
    }
}
