<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\User;

final class AuthService
{
    public function register(string $username, string $email, string $password): array
    {
        $errors = [];

        $username = trim($username);
        $email = trim(strtolower($email));

        if ($username === '') {
            $errors['username'] = 'Le pseudo est obligatoire.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        }

        if (strlen($password) < 12) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 12 caractères.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins une majuscule.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins une minuscule.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins un chiffre.';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        if ($email !== '' && User::findByEmail($email) !== null) {
            $errors['email'] = 'Un compte existe déjà avec cette adresse email.';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        $userId = User::create($username, $email, $password);

        Session::regenerate();
        Session::put('user_id', $userId);

        return [
            'success' => true,
            'user_id' => $userId,
        ];
    }

    public function login(string $email, string $password): bool
    {
        $email = trim(strtolower($email));

        $user = User::findByEmail($email);

        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::put('user_id', (int) $user['id']);

        return true;
    }

    public function logout(): void
    {
        Session::destroy();
    }

    public function user(): ?array
    {
        $userId = Session::get('user_id');

        if ($userId === null) {
            return null;
        }

        return User::findById((int) $userId);
    }
}