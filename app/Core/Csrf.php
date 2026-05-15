<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        Session::start();

        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function check(?string $token): bool
    {
        Session::start();

        return isset($_SESSION['_csrf_token'])
            && is_string($token)
            && hash_equals($_SESSION['_csrf_token'], $token);
    }
}