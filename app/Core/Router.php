<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Supports /path/{id} placeholders passed as named args to the controller method.
 */
class Router
{
    /** @var array<string, array<string, array{handler: callable|array, middleware: array}>> */
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array|callable $handler, array $middleware): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[$method][$regex] = ['handler' => $handler, 'middleware' => $middleware];
    }

    /**
     * Route a request. HEAD requests are served by the GET handlers (their
     * body is suppressed; the SAPI drops it anyway, this avoids generating
     * it). The session is started only once a route matches, so 404s do not
     * emit a session cookie.
     */
    public function dispatch(string $method, string $path): void
    {
        $isHead = $method === 'HEAD';
        if ($isHead) {
            $method = 'GET';
        }
        foreach ($this->routes[$method] ?? [] as $regex => $route) {
            if (preg_match($regex, rtrim($path, '/') ?: '/', $matches)) {
                Session::start();
                $args = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                foreach ($route['middleware'] as $mw) {
                    $mw();
                }
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $handler = [new $class(), $action];
                }
                $output = call_user_func_array($handler, array_values($args));
                if (!$isHead) {
                    echo $output;
                }
                return;
            }
        }
        http_response_code(404);
        echo View::render('errors/404', ['title' => 'Page introuvable']);
    }
}
