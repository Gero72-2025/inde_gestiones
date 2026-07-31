<?php

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\AdminUserModel;

class UsersController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\usuarios', [
            'gerencias' => $this->rbac->getAccessibleGerencias((int) $auth['user_id']),
        ]);
    }

    public function list()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $db = db_connect();
        $builder = $db->table('users u')
            ->select('u.id, u.username, u.email, u.status, u.gerencia_id, g.nombre AS gerencia_nombre')
            ->join('gerencias g', 'g.id = u.gerencia_id', 'left')
            ->where('u.deleted_at', null);

        if (! $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []))) {
            $builder->where('u.gerencia_id', (int) ($auth['gerencia_id'] ?? 0));
        }

        $users = $builder->orderBy('u.id', 'DESC')->get()->getResultArray();

        return $this->encryptedAdminResponse(['usuarios' => $users]);
    }

    public function store()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $gerenciaId = (int) $this->request->getPost('gerencia_id');

        if (! $this->rbac->canManageGerenciaId((int) $auth['user_id'], $gerenciaId)) {
            return $this->encryptedAdminResponse(['message' => 'No puedes crear usuarios para otra gerencia.'], 403);
        }

        $password = (string) $this->request->getPost('password');

        if (strlen($password) < 8) {
            return $this->encryptedAdminResponse(['message' => 'La contrasena debe tener al menos 8 caracteres.'], 422);
        }

        $model = new AdminUserModel();
        $inserted = $model->insert([
            'gerencia_id' => $gerenciaId,
            'username' => trim((string) $this->request->getPost('username')),
            'email' => trim((string) $this->request->getPost('email')),
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => trim((string) $this->request->getPost('first_name')),
            'last_name' => trim((string) $this->request->getPost('last_name')),
            'status' => (string) $this->request->getPost('status') ?: 'active',
            'twofa_enabled' => 0,
        ]);

        if ($inserted === false) {
            $errors = $model->errors();
            $message = $errors !== []
                ? implode(' ', array_values($errors))
                : 'No fue posible crear el usuario. Revisa username, email y gerencia.';

            return $this->encryptedAdminResponse(['message' => $message], 422);
        }

        $userId = (int) $model->getInsertID();

        if ($userId <= 0) {
            return $this->encryptedAdminResponse(['message' => 'No fue posible obtener el ID del usuario creado.'], 500);
        }

        $roleName = (string) $this->request->getPost('role_nombre') ?: 'Lector';

        $db = db_connect();
        $role = $db->table('roles')->select('id')->where('nombre', $roleName)->get()->getRowArray();

        if (is_array($role)) {
            $db->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => (int) $role['id'],
            ]);
        }

        return $this->encryptedAdminResponse(['message' => 'Usuario creado correctamente.']);
    }

    public function update(int $id)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $model = new AdminUserModel();
        $user = $model->find($id);

        if (! is_array($user)) {
            return $this->encryptedAdminResponse(['message' => 'Usuario no encontrado.'], 404);
        }

        if (! $this->rbac->canManageGerenciaId((int) $auth['user_id'], (int) $user['gerencia_id'])) {
            return $this->encryptedAdminResponse(['message' => 'No puedes editar usuarios de otra gerencia.'], 403);
        }

        $newGerencia = (int) $this->request->getPost('gerencia_id');

        if (! $this->rbac->canManageGerenciaId((int) $auth['user_id'], $newGerencia)) {
            return $this->encryptedAdminResponse(['message' => 'No puedes reasignar usuarios a otra gerencia.'], 403);
        }

        $payload = [
            'gerencia_id' => $newGerencia,
            'first_name' => trim((string) $this->request->getPost('first_name')),
            'last_name' => trim((string) $this->request->getPost('last_name')),
            'status' => (string) $this->request->getPost('status') ?: 'active',
        ];

        $password = (string) $this->request->getPost('password');

        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
            $payload['password_changed_at'] = date('Y-m-d H:i:s');
        }

        $model->update($id, $payload);

        return $this->encryptedAdminResponse(['message' => 'Usuario actualizado correctamente.']);
    }

    public function delete(int $id)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $model = new AdminUserModel();
        $user = $model->find($id);

        if (! is_array($user)) {
            return $this->encryptedAdminResponse(['message' => 'Usuario no encontrado.'], 404);
        }

        if (! $this->rbac->canManageGerenciaId((int) $auth['user_id'], (int) $user['gerencia_id'])) {
            return $this->encryptedAdminResponse(['message' => 'No puedes eliminar usuarios de otra gerencia.'], 403);
        }

        $model->delete($id);

        return $this->encryptedAdminResponse(['message' => 'Usuario eliminado correctamente.']);
    }
}
