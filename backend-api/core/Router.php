<?php

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[] = ['method' => $method, 'pattern' => $pattern, 'handler' => $handler, 'path' => $path];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = rtrim(parse_url($uri, PHP_URL_PATH), '/');

        if (empty($uri)) {
            Response::json(['success' => true, 'message' => 'SmugFlex POS API v1.0', 'docs' => '/api/v1/docs']);
            return;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_array($route['handler'])) {
                    [$controllerClass, $action] = $route['handler'];
                    $controller = new $controllerClass();
                    call_user_func_array([$controller, $action], $params);
                } else {
                    call_user_func_array($route['handler'], $params);
                }
                return;
            }
        }

        Response::error('Route not found: ' . $method . ' ' . $uri, 404);
    }
}
