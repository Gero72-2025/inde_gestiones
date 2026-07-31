<?php

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\UploadLogModel;
use App\Modules\Admin\Services\ExcelUploadService;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class UploadController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $auth;
        }

        return $this->adminView('App\\Modules\\Admin\\Views\\uploads', [
            'gerencias' => $this->rbac->getAccessibleGerencias((int) $auth['user_id']),
        ]);
    }

    public function store(): ResponseInterface
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $gerenciaId = (int) $this->request->getPost('gerencia_id');

        if (! $this->rbac->canManageGerenciaId((int) $auth['user_id'], $gerenciaId)) {
            return $this->encryptedAdminResponse(['message' => 'No puedes cargar archivos para otra gerencia.'], 403);
        }

        $file = $this->request->getFile('excel_file');

        if ($file === null) {
            return $this->encryptedAdminResponse(['message' => 'Debes seleccionar un archivo.'], 422);
        }

        try {
            $service = new ExcelUploadService();
            $result = $service->process($file, $gerenciaId, (int) $auth['user_id']);
        } catch (RuntimeException $exception) {
            return $this->encryptedAdminResponse(['message' => $exception->getMessage()], 422);
        }

        return $this->encryptedAdminResponse([
            'message' => 'Carga completada con exito.',
            'upload' => $result,
        ]);
    }

    public function list()
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }

        $db = db_connect();
        $builder = $db->table('upload_logs ul')
            ->select('ul.id, ul.nombre_archivo, ul.registros_procesados, ul.fecha_creacion, ul.gerencia_id, g.nombre AS gerencia_nombre, u.username AS usuario')
            ->join('gerencias g', 'g.id = ul.gerencia_id', 'left')
            ->join('users u', 'u.id = ul.usuario_id', 'left')
            ->orderBy('ul.id', 'DESC');

        if (! $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []))) {
            $builder->where('ul.gerencia_id', (int) ($auth['gerencia_id'] ?? 0));
        }

        return $this->encryptedAdminResponse(['uploads' => $builder->get()->getResultArray()]);
    }

    public function download(int $id)
    {
        $auth = $this->authProfile();

        if (! is_array($auth)) {
            return redirect()->to('login')->with('error', 'Sesion no valida.');
        }

        $model = new UploadLogModel();
        $log = $model->find($id);

        if (! is_array($log)) {
            return redirect()->back()->with('error', 'Archivo no encontrado.');
        }

        if (! $this->rbac->canManageGerenciaId((int) $auth['user_id'], (int) $log['gerencia_id'])) {
            return redirect()->back()->with('error', 'No tienes permiso para descargar este archivo.');
        }

        $path = (string) ($log['ruta_archivo'] ?? '');

        if ($path === '' || ! is_file($path)) {
            return redirect()->back()->with('error', 'El archivo ya no existe en el servidor.');
        }

        return $this->response->download($path, null)->setFileName((string) $log['nombre_archivo']);
    }
}
