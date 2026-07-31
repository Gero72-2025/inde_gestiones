<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ActivityLogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        try {
            $db = db_connect();

            if (! $db->tableExists('activity_logs')) {
                return $response;
            }

            $auth = session('auth');
            $userId = is_array($auth) && ! empty($auth['user_id']) ? (int) $auth['user_id'] : null;
            $path = trim((string) $request->getUri()->getPath(), '/');

            $payload = [];
            $post = $request->getPost();
            if (is_array($post)) {
                foreach ($post as $key => $value) {
                    if (in_array((string) $key, ['password', 'csrf_test_name', 'code'], true)) {
                        continue;
                    }
                    $payload[$key] = is_scalar($value) ? (string) $value : '[complex]';
                }
            }

            $db->table('activity_logs')->insert([
                'user_id' => $userId,
                'username' => is_array($auth) ? (string) ($auth['username'] ?? '') : null,
                'event_type' => 'http.request',
                'action' => strtoupper($request->getMethod()) . ' ' . $path,
                'description' => (string) $request->getUserAgent(),
                'endpoint' => '/' . $path,
                'http_method' => strtoupper($request->getMethod()),
                'status_code' => (int) $response->getStatusCode(),
                'ip_address' => (string) $request->getIPAddress(),
                'payload_json' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'ActivityLogFilter error: ' . $e->getMessage());
        }

        return $response;
    }
}
