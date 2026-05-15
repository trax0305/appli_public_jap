<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();

        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        self::start();

        unset($_SESSION[$key]);
    }

    public static function flash(string $key, string $message): void
    {
        self::put('_flash_' . $key, $message);
    }

    public static function getFlash(string $key): ?string
    {
        $message = self::get('_flash_' . $key);

        self::forget('_flash_' . $key);

        return $message;
    }

    public static function regenerate(): void
    {
        self::start();

        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}