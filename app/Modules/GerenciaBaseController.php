<?php

namespace App\Modules;

use App\Controllers\BaseController;
use App\Exceptions\PageForbiddenException;
use App\Modules\Auth\Services\RbacService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class GerenciaBaseController extends BaseController
{
    protected string $moduleSlug = '';
    protected ?RbacService $rbacService = null;

    protected function rbac(): RbacService
    {
        if ($this->rbacService === null) {
            $this->rbacService = new RbacService();
        }

        return $this->rbacService;
    }

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if ($this->moduleSlug !== '') {
            $this->requireModuleAccess($this->moduleSlug);
        }
    }

    protected function requireModuleAccess(?string $moduleSlug = null, ?string $permissionSlug = null): array
    {
        $auth = $this->session->get('auth');

        if (! is_array($auth) || empty($auth['user_id'])) {
            throw PageForbiddenException::forPageForbidden('No existe una sesion valida para acceder al modulo.');
        }

        $resolvedModuleSlug = strtolower(trim($moduleSlug ?: $this->moduleSlug));

        if ($resolvedModuleSlug === '') {
            throw PageForbiddenException::forPageForbidden('No se pudo resolver la gerencia del modulo solicitado.');
        }

        $requiredPermission = $permissionSlug ?: ('gerencia.' . $resolvedModuleSlug . '.access');

        if (! $this->rbac()->userCanAccessModule((int) $auth['user_id'], $resolvedModuleSlug, $requiredPermission)) {
            throw PageForbiddenException::forPageForbidden('No tienes permiso para acceder a esta gerencia.');
        }

        return $auth;
    }

    protected function encryptedModuleResponse(array $payload, int $statusCode = 200)
    {
        return $this->encryptedJsonResponse([
            'ok' => $statusCode < 400,
            'module' => $this->moduleSlug,
            'data' => $payload,
        ], $statusCode);
    }

    protected function renderModulePage(string $innerView, array $innerData = [], ?string $title = null): string
    {
        $auth = $this->requireModuleAccess($this->moduleSlug);
        $rbac = $this->rbac();

        return view('App\\Modules\\Admin\\Views\\layout', [
            'title' => $title ?: ('Modulo ' . strtoupper($this->moduleSlug)),
            'innerView' => $innerView,
            'innerData' => $innerData,
            'auth' => $auth,
            'isSuperAdmin' => $this->rbac()->isSuperAdminByPermissions((array) ($auth['permissions'] ?? [])),
            'menuModules' => $rbac->getSidebarModules((int) $auth['user_id']),
            'ajaxCipherKey' => (string) env('security.ajaxCipherKey', ''),
        ]);
    }
}
