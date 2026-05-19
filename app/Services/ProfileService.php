<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ProfileService
{
    public function getProfileData(int $userId): ?array
    {
        $user = $this->getUser($userId);

        if ($user === null) {
            return null;
        }

        return [
            'user' => $user,
            'masked_email' => $this->maskEmail((string) $user['email']),
            'current_path' => $this->getCurrentPath($userId),
            'badges' => $this->getUserBadges($userId),
        ];
    }

    public function updatePreferences(int $userId, array $input): array
    {
        $username = trim((string) ($input['username'] ?? ''));
        $learningMode = (string) ($input['learning_mode'] ?? '');
        $themePreference = (string) ($input['theme_preference'] ?? '');

        if ($username === '') {
            return ['success' => false, 'message' => 'Le pseudo est obligatoire.'];
        }

        $length = mb_strlen($username);

        if ($length < 3 || $length > 80) {
            return ['success' => false, 'message' => 'Le pseudo doit contenir entre 3 et 80 caractères.'];
        }

        if (!in_array($learningMode, ['guided', 'free'], true)) {
            return ['success' => false, 'message' => 'Mode d’apprentissage invalide.'];
        }

        if (!in_array($themePreference, ['light', 'dark', 'system'], true)) {
            return ['success' => false, 'message' => 'Préférence de thème invalide.'];
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'UPDATE users
             SET username = :username,
                 learning_mode = :learning_mode,
                 theme_preference = :theme_preference
             WHERE id = :id'
        );

        $stmt->execute([
            'username' => $username,
            'learning_mode' => $learningMode,
            'theme_preference' => $themePreference,
            'id' => $userId,
        ]);

        return ['success' => true, 'message' => 'Préférences mises à jour.'];
    }

    private function getUser(int $userId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, username, email, learning_mode, theme_preference, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $userId]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    private function getCurrentPath(int $userId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                lp.code AS path_code,
                lp.title AS path_title,
                up.status AS path_status,
                m.code AS mission_code,
                m.title AS mission_title
             FROM user_paths up
             INNER JOIN learning_paths lp ON lp.id = up.path_id
             LEFT JOIN missions m ON m.id = up.current_mission_id
             WHERE up.user_id = :user_id
             ORDER BY
                CASE up.status
                    WHEN "in_progress" THEN 1
                    WHEN "available" THEN 2
                    WHEN "locked" THEN 3
                    WHEN "completed" THEN 4
                    ELSE 5
                END,
                lp.sort_order ASC
             LIMIT 1'
        );

        $stmt->execute(['user_id' => $userId]);

        $path = $stmt->fetch();

        return $path ?: null;
    }

    private function getUserBadges(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                b.code,
                b.title,
                b.description,
                b.icon,
                ub.unlocked_at
             FROM user_badges ub
             INNER JOIN badges b ON b.id = ub.badge_id
             WHERE ub.user_id = :user_id
             ORDER BY ub.unlocked_at DESC'
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    private function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);

        if ($local === '') {
            return '***@' . $domain;
        }

        $first = mb_substr($local, 0, 1);
        $stars = str_repeat('*', max(3, mb_strlen($local) - 1));

        return $first . $stars . '@' . $domain;
    }
}
