<?php

namespace App\Modules\Admin\Controllers;

class LogsController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\logs', [
            'title' => 'Logs de Acciones',
        ]);
    }

    public function list()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $db = db_connect();

        if (! $db->tableExists('activity_logs')) {
            return $this->encryptedAdminResponse(['logs' => [], 'message' => 'La tabla activity_logs no existe todavia. Ejecuta migraciones.']);
        }

        $level = trim((string) $this->request->getGet('level'));
        $search = trim((string) $this->request->getGet('q'));
        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = max(10, min(100, (int) $this->request->getGet('per_page') ?: 25));

        $builder = $db->table('activity_logs');

        if ($level !== '') {
            $builder->where('event_type', $level);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('action', $search)
                ->orLike('username', $search)
                ->orLike('endpoint', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        if (! $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []))) {
            $builder->where('user_id', (int) $auth['user_id']);
        }

        $total = (clone $builder)->countAllResults();
        $rows = $builder->orderBy('id', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return $this->encryptedAdminResponse([
            'logs' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }
}
