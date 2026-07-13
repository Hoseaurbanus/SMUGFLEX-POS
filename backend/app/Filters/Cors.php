<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Cors implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = service('response');
            $this->setCorsHeaders($response);
            return $response->setStatusCode(204);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $this->setCorsHeaders($response);
        return $response;
    }

    private function setCorsHeaders(ResponseInterface $response): void
    {
        $config = config('Cors');

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $config->allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
        }

        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $config->allowedMethods));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $config->allowedHeaders));
        $response->setHeader('Access-Control-Max-Age', (string) $config->maxAge);
    }
}
