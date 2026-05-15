<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][] = [
            'path' => $this->normalizePath($path),
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $this->normalizePath($this->removeBasePath($path));

        foreach ($this->routes[$method] ?? [] as $route) {
            $params = $this->match($route['path'], $path);

            if ($params === null) {
                continue;
            }

            $this->execute($route['handler'], $params);
            return;
        }

        http_response_code(404);
        echo '404 — Page introuvable';
    }

    private function execute(callable|array $handler, array $params = []): void
    {
        if (is_array($handler)) {
            [$class, $action] = $handler;
            $controller = new $class();
            $controller->{$action}(...$params);
            return;
        }

        $handler(...$params);
    }

    private function match(string $routePath, string $currentPath): ?array
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $currentParts = explode('/', trim($currentPath, '/'));

        if ($routePath === '/') {
            return $currentPath === '/' ? [] : null;
        }

        if (count($routeParts) !== count($currentParts)) {
            return null;
        }

        $params = [];

        foreach ($routeParts as $index => $routePart) {
            $currentPart = $currentParts[$index];

            if (preg_match('/^\{[a-zA-Z_][a-zA-Z0-9_]*}$/', $routePart)) {
                $params[] = $currentPart;
                continue;
            }

            if ($routePart !== $currentPart) {
                return null;
            }
        }

        return $params;
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function removeBasePath(string $path): string
    {
        $scriptName = str_replace('\\\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptName = rtrim($scriptName, '/');

        if ($scriptName !== '' && $scriptName !== '/' && str_starts_with($path, $scriptName)) {
            return substr($path, strlen($scriptName)) ?: '/';
        }

        return $path;
    }
}