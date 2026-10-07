<?php

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Services\AutoUpdateService;
use Throwable;

class AutoUpdateController extends AdminBaseController
{
    public function index()
    {
        $auth = $this->authProfile();
        if (! is_array($auth)) {
            return $auth;
        }
        if (! $this->isSuperAdmin($auth)) {
            return redirect()->to('admin')->with('error', 'Solo el super administrador puede gestionar paquetes de modulos.');
        }

        $service = new AutoUpdateService();

        return $this->adminView('App\\Modules\\Admin\\Views\\auto_update', [
            'title' => 'Auto Actualizacion y Empaquetado',
            'modules' => $service->listModules(),
            'report' => (array) ($this->session->getFlashdata('auto_update_report') ?? []),
        ]);
    }

    public function export(string $moduleId)
    {
        $auth = $this->authProfile();
        if (! is_array($auth)) {
            return $auth;
        }
        if (! $this->isSuperAdmin($auth)) {
            return redirect()->to('admin')->with('error', 'Solo el super administrador puede exportar modulos.');
        }

        try {
            $packagePath = (new AutoUpdateService())->createPackage($moduleId);
            register_shutdown_function(static function () use ($packagePath): void {
                if (is_file($packagePath)) {
                    @unlink($packagePath);
                }
            });
            return $this->response->download($packagePath, null, true)
                ->setFileName(basename($packagePath))
                ->setHeader('Content-Type', 'application/zip');
        } catch (Throwable $exception) {
            log_message('error', 'Auto-update export failed: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to('admin/update')->with('error', $exception->getMessage());
        }
    }

    public function analyze()
    {
        $auth = $this->authProfile();
        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }
        if (! $this->isSuperAdmin($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede analizar modulos.'], 403);
        }

        try {
            $analysis = (new AutoUpdateService())->analyzeModule((string) $this->request->getPost('module_id'));
            return $this->encryptedAdminResponse(['analysis' => $analysis]);
        } catch (Throwable $exception) {
            return $this->encryptedAdminResponse(['message' => $exception->getMessage()], 422);
        }
    }

    public function scanAttachments()
    {
        $auth = $this->authProfile();
        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }
        if (! $this->isSuperAdmin($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede escanear archivos.'], 403);
        }

        $selection = json_decode((string) $this->request->getPost('selection'), true);
        if (! is_array($selection)) {
            return $this->encryptedAdminResponse(['message' => 'La seleccion de registros no es valida.'], 422);
        }

        try {
            $result = (new AutoUpdateService())->scanSelectedData((string) $this->request->getPost('module_id'), $selection);
            unset($result['file_sources']);
            unset($result['tables']);
            return $this->encryptedAdminResponse(['scan' => $result]);
        } catch (Throwable $exception) {
            return $this->encryptedAdminResponse(['message' => $exception->getMessage()], 422);
        }
    }

    public function tablePreview()
    {
        $auth = $this->authProfile();
        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }
        if (! $this->isSuperAdmin($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede consultar registros.'], 403);
        }

        try {
            $table = (new AutoUpdateService())->previewTable(
                (string) $this->request->getPost('module_id'),
                (string) $this->request->getPost('table'),
                (int) $this->request->getPost('page')
            );
            return $this->encryptedAdminResponse(['table' => $table]);
        } catch (Throwable $exception) {
            return $this->encryptedAdminResponse(['message' => $exception->getMessage()], 422);
        }
    }

    public function exportSelected()
    {
        $auth = $this->authProfile();
        if (! is_array($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Sesion no valida.'], 401);
        }
        if (! $this->isSuperAdmin($auth)) {
            return $this->encryptedAdminResponse(['message' => 'Solo el super administrador puede exportar datos.'], 403);
        }

        $selection = json_decode((string) $this->request->getPost('selection'), true);
        if (! is_array($selection)) {
            return $this->encryptedAdminResponse(['message' => 'La seleccion de registros no es valida.'], 422);
        }

        try {
            $packagePath = (new AutoUpdateService())->createPackage((string) $this->request->getPost('module_id'), $selection);
            register_shutdown_function(static function () use ($packagePath): void {
                if (is_file($packagePath)) {
                    @unlink($packagePath);
                }
            });
            return $this->response->download($packagePath, null, true)
                ->setFileName(basename($packagePath))
                ->setHeader('Content-Type', 'application/zip');
        } catch (Throwable $exception) {
            log_message('error', 'Auto-update selected export failed: {message}', ['message' => $exception->getMessage()]);
            return $this->encryptedAdminResponse(['message' => $exception->getMessage()], 422);
        }
    }

    public function import()
    {
        $auth = $this->authProfile();
        if (! is_array($auth)) {
            return $auth;
        }
        if (! $this->isSuperAdmin($auth)) {
            return redirect()->to('admin')->with('error', 'Solo el super administrador puede instalar paquetes.');
        }

        $file = $this->request->getFile('package');
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to('admin/update')->with('error', 'Selecciona un archivo ZIP valido.');
        }

        try {
            $report = (new AutoUpdateService())->installPackage($file->getTempName());
            $hasErrors = count(array_filter($report, static fn (array $item): bool => ($item['status'] ?? '') === 'error')) > 0;
            $this->session->setFlashdata('auto_update_report', $report);
            return redirect()->to('admin/update')->with($hasErrors ? 'error' : 'success', $hasErrors ? 'El paquete tuvo errores; revisa el reporte.' : 'El paquete se proceso correctamente.');
        } catch (Throwable $exception) {
            log_message('error', 'Auto-update import rejected: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to('admin/update')->with('error', $exception->getMessage());
        }
    }

    private function isSuperAdmin(array $auth): bool
    {
        return $this->rbac->isSuperAdminByPermissions((array) ($auth['permissions'] ?? []));
    }
}