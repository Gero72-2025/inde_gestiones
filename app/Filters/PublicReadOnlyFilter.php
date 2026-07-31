<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class PublicReadOnlyFilter implements FilterInterface
{
    /**
     * Allow read-only public interactions: page reads and query-only POST requests.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $method = strtoupper($request->getMethod());
        $allowedMethods = ['GET', 'HEAD', 'OPTIONS', 'POST'];

        if (! in_array($method, $allowedMethods, true)) {
            return service('response')
                ->setStatusCode(405)
                ->setJSON([
                    'ok' => false,
                    'message' => 'Metodo no permitido en el portal publico.',
                ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
