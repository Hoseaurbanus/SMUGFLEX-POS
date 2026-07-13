<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class JwtAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return service('response')->setJSON([
                'success' => false,
                'message' => 'Authorization token required'
            ])->setStatusCode(401);
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = verify_jwt($token);

            if ($decoded === null) {
                return service('response')->setJSON([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ])->setStatusCode(401);
            }

            $request->user = $decoded;
            $request->userId = (int) $decoded->user_id ?? 0;
        } catch (\Exception $e) {
            return service('response')->setJSON([
                'success' => false,
                'message' => 'Invalid token: ' . $e->getMessage()
            ])->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
