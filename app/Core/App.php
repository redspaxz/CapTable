<?php

declare(strict_types=1);

namespace App\Core;

class App
{
    private static array $config;
    private Router $router;

    public function __construct(string $configFile)
    {
        self::init($configFile);
        $this->router = new Router();
        // The session is started lazily in Router::dispatch() once a route
        // matches, so 404s (and HEAD probes of unknown paths) do not emit a
        // session cookie.
    }

    public static function init(string $configFile): void
    {
        self::$config = require $configFile;
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = self::$config;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public function loadRoutes(): void
    {
        $routes = glob(BASE_PATH . '/app/routes/*.php') ?: [];
        foreach ($routes as $file) {
            $router = $this->router;
            require $file;
        }
    }

    public function run(): void
    {
        try {
            $this->router->dispatch(Request::method(), Request::path());
        } catch (\Throwable $e) {
            http_response_code(500);
            if (self::config('app.debug')) {
                echo '<pre>' . \App\e($e->__toString()) . '</pre>';
            } else {
                echo View::render('errors/500', ['title' => 'Server error']);
            }
        }
    }
}
