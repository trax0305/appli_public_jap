<?php

declare(strict_types=1);

namespace App\Core;

final class AuthMiddleware
{
    public static function requireAuth(): void
    {
        if (Session::get('user_id') === null) {
            Session::flash('error', 'Tu dois être connecté pour accéder à cette page.');
            redirect('/login');
        }
    }

    public static function guestOnly(): void
    {
        if (Session::get('user_id') !== null) {
            redirect('/dashboard');
        }
    }
}