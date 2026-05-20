<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;
use App\Services\GuestProgressService;

final class AuthController
{
    public function login(): void
    {
        AuthMiddleware::guestOnly();

        View::render('auth.login', [
            'title' => 'Connexion',
        ], 'auth');
    }

    public function handleLogin(): void
    {
        AuthMiddleware::guestOnly();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/login');
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $auth = new AuthService();

        if (!$auth->login($email, $password)) {
            Session::flash('error', 'Identifiants incorrects.');
            redirect('/login');
        }

        redirect('/dashboard');
    }

    public function register(): void
    {
        AuthMiddleware::guestOnly();

        View::render('auth.register', [
            'title' => 'Inscription',
            'errors' => [],
        ], 'auth');
    }

    public function handleRegister(): void
    {
        AuthMiddleware::guestOnly();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/register');
        }

        $auth = new AuthService();

        $result = $auth->register(
            $_POST['username'] ?? '',
            $_POST['email'] ?? '',
            $_POST['password'] ?? ''
        );

        if (!$result['success']) {
            View::render('auth.register', [
                'title' => 'Inscription',
                'errors' => $result['errors'],
                'old' => [
                    'username' => $_POST['username'] ?? '',
                    'email' => $_POST['email'] ?? '',
                ],
            ], 'auth');

            return;
        }

        $guestProgress = new GuestProgressService();

        if ($guestProgress->transferToUser((int) $result['user_id'])) {
            Session::flash('success', 'Compte créé. Ta progression invitée a été sauvegardée.');
            redirect('/dashboard');
        }

        redirect('/onboarding');
    }

    public function logout(): void
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/dashboard');
        }

        $auth = new AuthService();
        $auth->logout();

        redirect('/');
    }
}
