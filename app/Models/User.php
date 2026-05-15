<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([
            'email' => $email,
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([
            'id' => $id,
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function create(string $username, string $email, string $password): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, learning_mode, theme_preference)
             VALUES (:username, :email, :password_hash, :learning_mode, :theme_preference)'
        );

        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'learning_mode' => 'guided',
            'theme_preference' => 'system',
        ]);

        return (int) $pdo->lastInsertId();
    }
}