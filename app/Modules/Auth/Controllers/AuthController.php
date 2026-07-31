<?php

namespace App\Modules\Auth\Controllers;

use App\Controllers\BaseController;
use App\Modules\Auth\Models\UserModel;
use App\Modules\Auth\Services\RbacService;
use App\Modules\Auth\Services\TotpService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

class AuthController extends BaseController
{
    protected UserModel $users;
    protected RbacService $rbacService;
    protected TotpService $totpService;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->users = new UserModel();
        $this->rbacService = new RbacService();
        $this->totpService = new TotpService();
    }

    public function loginView(): string|RedirectResponse
    {
        $auth = $this->session->get('auth');

        if (is_array($auth) && ! empty($auth['user_id'])) {
            return redirect()->to($this->resolveHomePath($auth));
        }

        return view('App\Modules\Auth\Views\login');
    }

    public function login(): RedirectResponse
    {
        $identity = trim((string) $this->request->getPost('identity'));
        $password = (string) $this->request->getPost('password');

        if ($identity === '' || $password === '') {
            $this->logAuthEvent('auth.login.failed', 'Intento de login sin identidad o contrasena.');
            return redirect()->back()->withInput()->with('error', 'Debes ingresar tu usuario o correo y tu contrasena.');
        }

        $user = $this->users->findByIdentity($identity);

        if ($user === null || ! password_verify($password, $user['password'])) {
            $this->logAuthEvent('auth.login.failed', 'Credenciales invalidas para identidad: ' . $identity);
            return redirect()->back()->withInput()->with('error', 'Credenciales invalidas.');
        }

        if (($user['status'] ?? 'inactive') !== 'active') {
            $this->logAuthEvent('auth.login.failed', 'Intento de login de usuario inactivo: ' . (string) ($user['username'] ?? $identity), (int) $user['id']);
            return redirect()->back()->withInput()->with('error', 'Tu cuenta no se encuentra activa.');
        }

        $this->session->remove(['auth', 'pending_2fa', 'pending_2fa_setup']);

        if ((int) ($user['twofa_enabled'] ?? 0) === 1 && ! empty($user['google_2fa_secret'])) {
            $this->session->set('pending_2fa', [
                'user_id' => (int) $user['id'],
                'identity' => $user['username'],
            ]);

            return redirect()->to('2fa/verify')->with('message', 'Ingresa el codigo de 6 digitos generado por tu app de autenticacion.');
        }

        $auth = $this->signInUser((int) $user['id'], true);
        $this->logAuthEvent('auth.login.success', 'Sesion iniciada correctamente.', (int) $user['id']);

        return redirect()->to($this->resolveHomePath($auth))->with('message', 'Sesion iniciada correctamente.');
    }

    public function twoFactorView(): string|RedirectResponse
    {
        $pending = $this->session->get('pending_2fa');

        if (! is_array($pending) || empty($pending['user_id'])) {
            return redirect()->to('login')->with('error', 'No existe un proceso de verificacion 2FA pendiente.');
        }

        return view('App\Modules\Auth\Views\two_factor_verify');
    }

    public function verifyTwoFactor(): RedirectResponse
    {
        $pending = $this->session->get('pending_2fa');
        $code = preg_replace('/\D+/', '', (string) $this->request->getPost('code'));

        if (! is_array($pending) || empty($pending['user_id'])) {
            return redirect()->to('login')->with('error', 'La sesion de verificacion 2FA expiro.');
        }

        if (strlen($code) !== 6) {
            return redirect()->back()->withInput()->with('error', 'El codigo 2FA debe tener exactamente 6 digitos.');
        }

        $user = $this->users->find((int) $pending['user_id']);

        if (! is_array($user) || empty($user['google_2fa_secret'])) {
            return redirect()->to('login')->with('error', 'No fue posible validar el segundo factor del usuario.');
        }

        if (! $this->totpService->verifyCode($user['google_2fa_secret'], $code)) {
            return redirect()->back()->withInput()->with('error', 'El codigo 2FA es invalido o expiro. Verifica la hora automatica del telefono y del servidor.');
        }

        $this->session->remove('pending_2fa');
        $auth = $this->signInUser((int) $user['id'], true);

        return redirect()->to($this->resolveHomePath($auth))->with('message', 'Segundo factor verificado correctamente.');
    }

    public function setupTwoFactorView(): string|RedirectResponse
    {
        $auth = $this->requireAuthenticatedUser();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        if (($auth['twofa_enabled'] ?? false) === true) {
            return redirect()->to($this->resolveHomePath($auth))->with('message', 'Tu cuenta ya tiene doble factor habilitado.');
        }

        $pendingSetup = $this->session->get('pending_2fa_setup');
        $secret = is_array($pendingSetup) && ! empty($pendingSetup['secret'])
            ? (string) $pendingSetup['secret']
            : $this->totpService->generateSecret();

        $this->session->set('pending_2fa_setup', ['secret' => $secret]);

        $provisioning = $this->totpService->provisioningData(
            (string) ($auth['email'] ?: $auth['username']),
            $secret
        );

        return view('App\Modules\Auth\Views\two_factor_setup', [
            'secret' => $secret,
            'issuer' => $this->totpService->getIssuer(),
            'otpauthUri' => $provisioning['otpauthUri'],
            'qrSvg' => $provisioning['qrSvg'],
        ]);
    }

    public function confirmTwoFactorSetup(): RedirectResponse
    {
        $auth = $this->requireAuthenticatedUser();

        if ($auth instanceof RedirectResponse) {
            return $auth;
        }

        $pendingSetup = $this->session->get('pending_2fa_setup');
        $code = preg_replace('/\D+/', '', (string) $this->request->getPost('code'));

        if (! is_array($pendingSetup) || empty($pendingSetup['secret'])) {
            return redirect()->to('2fa/setup')->with('error', 'La solicitud de configuracion 2FA ya no es valida.');
        }

        if (strlen($code) !== 6 || ! $this->totpService->verifyCode((string) $pendingSetup['secret'], $code)) {
            return redirect()->back()->withInput()->with('error', 'El codigo no coincide con el secreto configurado. Revisa que uses la cuenta correcta en Google Authenticator y sincroniza la hora automatica.');
        }

        $this->users->update((int) $auth['user_id'], [
            'google_2fa_secret' => (string) $pendingSetup['secret'],
            'twofa_enabled' => 1,
        ]);

        $this->session->remove('pending_2fa_setup');
        $updatedAuth = $auth;
        $updatedAuth['twofa_enabled'] = true;
        $updatedAuth['is_2fa_verified'] = true;
        $this->session->set('auth', $updatedAuth);

        return redirect()->to($this->resolveHomePath($updatedAuth))->with('message', 'Doble factor habilitado correctamente.');
    }

    public function logout(): RedirectResponse
    {
        $auth = $this->session->get('auth');
        $this->logAuthEvent('auth.logout', 'Sesion cerrada por el usuario.', is_array($auth) ? (int) ($auth['user_id'] ?? 0) : null);
        $this->session->destroy();

        return redirect()->to('login')->with('message', 'La sesion se cerro correctamente.');
    }

    protected function signInUser(int $userId, bool $twoFactorVerified): array
    {
        $profile = $this->rbacService->getUserAccessProfile($userId);

        if ($profile === null) {
            throw new RuntimeException('No fue posible construir el perfil de acceso del usuario autenticado.');
        }

        $auth = [
            'user_id' => (int) $profile['id'],
            'gerencia_id' => isset($profile['gerencia_id']) ? (int) $profile['gerencia_id'] : null,
            'gerencia_slug' => $profile['gerencia_slug'],
            'gerencia_nombre' => $profile['gerencia_nombre'],
            'username' => $profile['username'],
            'email' => $profile['email'],
            'roles' => $profile['roles'],
            'permissions' => $profile['permissions'],
            'twofa_enabled' => (bool) $profile['twofa_enabled'],
            'is_2fa_verified' => $twoFactorVerified,
        ];

        $this->session->set('auth', $auth);
        $this->session->regenerate(true);

        return $auth;
    }

    protected function resolveHomePath(array $auth): string
    {
        $permissions = (array) ($auth['permissions'] ?? []);

        $hasAdminSectionPermission = false;

        foreach ($permissions as $permission) {
            if (str_starts_with((string) $permission, 'admin.') && str_ends_with((string) $permission, '.view')) {
                $hasAdminSectionPermission = true;
                break;
            }
        }

        if (in_array('superadmin.access', $permissions, true) || in_array('admin.access', $permissions, true) || $hasAdminSectionPermission) {
            return 'admin';
        }

        $slug = (string) ($auth['gerencia_slug'] ?? 'gero');

        return 'gerencias/' . $slug;
    }

    protected function requireAuthenticatedUser(): array|RedirectResponse
    {
        $auth = $this->session->get('auth');

        if (! is_array($auth) || empty($auth['user_id'])) {
            return redirect()->to('login')->with('error', 'Debes autenticarte antes de continuar.');
        }

        return $auth;
    }

    protected function logAuthEvent(string $eventType, string $action, ?int $userId = null): void
    {
        try {
            $db = db_connect();

            if (! $db->tableExists('activity_logs')) {
                return;
            }

            $auth = $this->session->get('auth');

            $db->table('activity_logs')->insert([
                'user_id' => $userId ?: (is_array($auth) ? (int) ($auth['user_id'] ?? 0) : null),
                'username' => is_array($auth) ? (string) ($auth['username'] ?? '') : null,
                'event_type' => $eventType,
                'action' => $action,
                'description' => (string) $this->request->getUserAgent(),
                'endpoint' => '/' . trim((string) $this->request->getUri()->getPath(), '/'),
                'http_method' => strtoupper($this->request->getMethod()),
                'status_code' => 200,
                'ip_address' => (string) $this->request->getIPAddress(),
                'payload_json' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Auth event log error: ' . $e->getMessage());
        }
    }
}