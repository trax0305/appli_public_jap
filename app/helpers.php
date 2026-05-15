<?php

declare(strict_types=1);

function base_path(string $path = ''): string
{
    $basePath = dirname(__DIR__);

    return $path === '' ? $basePath : $basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function app_path(string $path = ''): string
{
    return base_path('app' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
}

function view_path(string $path = ''): string
{
    return app_path('Views' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
}

function config_path(string $path = ''): string
{
    return base_path('config' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
}

function public_path(string $path = ''): string
{
    return base_path('public' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
}

function storage_path(string $path = ''): string
{
    return base_path('storage' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
}

function env_value(string $key, mixed $default = null): mixed
{
    static $env = null;

    if ($env === null) {
        $env = [];

        $envFile = base_path('.env');

        if (is_file($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);

                $name = trim($name);
                $value = trim($value);

                if (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                $env[$name] = $value;
            }
        }
    }

    return $env[$key] ?? $default;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(\App\Core\Csrf::token()) . '">';
}