<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Session\Session;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    protected Session $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $this->session = service('session');
    }

    protected function encryptedJsonResponse(array $payload, int $statusCode = 200): ResponseInterface
    {
        $key = $this->resolveCipherKey();
        $iv = random_bytes(16);
        $plaintext = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new RuntimeException('No fue posible cifrar la respuesta AJAX.');
        }

        $mac = hash_hmac('sha256', $iv . $ciphertext, $key, true);

        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'encrypted' => true,
                'algorithm' => 'AES-256-CBC',
                'payload' => base64_encode($iv . $mac . $ciphertext),
            ]);
    }

    protected function decryptAjaxEnvelopeFromRequest(bool $requireEncrypted = true): array
    {
        $body = (array) ($this->request->getJSON(true) ?? []);

        if (! isset($body['payload'])) {
            if ($requireEncrypted) {
                throw new InvalidArgumentException('La solicitud cifrada es obligatoria.');
            }

            return $body;
        }

        $rawPayload = base64_decode((string) $body['payload'], true);

        if ($rawPayload === false || strlen($rawPayload) <= 48) {
            throw new InvalidArgumentException('El payload cifrado no tiene un formato valido.');
        }

        $iv = substr($rawPayload, 0, 16);
        $mac = substr($rawPayload, 16, 32);
        $cipher = substr($rawPayload, 48);

        $key = $this->resolveCipherKey();
        $expectedMac = hash_hmac('sha256', $iv . $cipher, $key, true);

        if (! hash_equals($expectedMac, $mac)) {
            throw new InvalidArgumentException('No se pudo validar la integridad del payload cifrado.');
        }

        $plaintext = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($plaintext === false) {
            throw new InvalidArgumentException('No se pudo descifrar el payload enviado.');
        }

        $decoded = json_decode($plaintext, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('El payload descifrado no contiene un objeto JSON valido.');
        }

        return $decoded;
    }

    protected function resolveCipherKey(): string
    {
        $configuredKey = (string) env('security.ajaxCipherKey', '');

        if ($configuredKey === '') {
            throw new RuntimeException('Define security.ajaxCipherKey en el archivo .env para cifrar respuestas AJAX.');
        }

        $keyMaterial = str_starts_with($configuredKey, 'base64:')
            ? base64_decode(substr($configuredKey, 7), true)
            : $configuredKey;

        if ($keyMaterial === false) {
            throw new RuntimeException('La llave security.ajaxCipherKey no tiene un formato base64 valido.');
        }

        return strlen($keyMaterial) === 32 ? $keyMaterial : hash('sha256', $keyMaterial, true);
    }
}
