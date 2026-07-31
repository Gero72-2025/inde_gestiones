<?php

namespace App\Filters;

use App\Exceptions\PageForbiddenException;
use App\Modules\Auth\Services\RbacService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GerenciaPermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $auth = $session->get('auth');

        if (! is_array($auth) || empty($auth['user_id'])) {
            return redirect()->to('login')->with('error', 'Debes iniciar sesion para acceder al modulo solicitado.');
        }

        if (($auth['twofa_enabled'] ?? false) && ! ($auth['is_2fa_verified'] ?? false)) {
            return redirect()->to('2fa/verify')->with('error', 'Completa la verificacion de dos factores para continuar.');
        }

        $moduleSlug = strtolower(trim((string) ($arguments[0] ?? $request->getUri()->getSegment(2))));

        if ($moduleSlug === '') {
            throw PageForbiddenException::forPageForbidden('No se pudo resolver la gerencia del modulo solicitado.');
        }

        $requiredPermission = (string) ($arguments[1] ?? ('gerencia.' . $moduleSlug . '.access'));
        $rbac = new RbacService();

        if (! $rbac->userCanAccessModule((int) $auth['user_id'], $moduleSlug, $requiredPermission)) {
            throw PageForbiddenException::forPageForbidden('Tu usuario no tiene permisos para acceder a esta gerencia.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}