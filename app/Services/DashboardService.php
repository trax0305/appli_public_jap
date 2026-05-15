<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class DashboardService
{
    public function getDashboardData(int $userId): array
    {
        $user = $this->getUser($userId);

        if ($user === null) {
            throw new \RuntimeException('Utilisateur introuvable.');
        }

        if ($user['learning_mode'] === 'free') {
            return [
                'user' => $user,
                'mode' => 'free',
                'current_path' => null,
                'current_mission' => null,
                'progress' => null,
                'last_session' => $this->getLastQuizSession($userId),
            ];
        }

        $currentPath = $this->getCurrentPath($userId);

        if ($currentPath === null) {
            return [
                'user' => $user,
                'mode' => 'guided_without_path',
                'current_path' => null,
                'current_mission' => null,
                'progress' => null,
                'last_session' => null,
            ];
        }

        $currentMission = $this->getCurrentMission((int) $currentPath['current_mission_id']);
        $progress = $this->getPathProgress($userId, (int) $currentPath['path_id']);

        return [
            'user' => $user,
            'mode' => 'guided',
            'current_path' => $currentPath,
            'current_mission' => $currentMission,
            'progress' => $progress,
            'last_session' => $this->getLastQuizSession($userId),
        ];
    }

    private function getUser(int $userId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $userId,
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    private function getCurrentPath(int $userId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                up.*,
                lp.code AS path_code,
                lp.title AS path_title,
                lp.description AS path_description,
                lp.kana_set
             FROM user_paths up
             INNER JOIN learning_paths lp ON lp.id = up.path_id
             WHERE up.user_id = :user_id
               AND up.status = :status
             ORDER BY lp.sort_order ASC
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'status' => 'in_progress',
        ]);

        $path = $stmt->fetch();

        return $path ?: null;
    }

    private function getCurrentMission(?int $missionId): ?array
    {
        if ($missionId === null) {
            return null;
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM missions
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $missionId,
        ]);

        $mission = $stmt->fetch();

        return $mission ?: null;
    }

    private function getPathProgress(int $userId, int $pathId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                COUNT(m.id) AS total_missions,
                SUM(CASE WHEN um.status = "completed" THEN 1 ELSE 0 END) AS completed_missions
             FROM missions m
             LEFT JOIN user_missions um
                ON um.mission_id = m.id
               AND um.user_id = :user_id
             WHERE m.path_id = :path_id
               AND m.is_active = 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'path_id' => $pathId,
        ]);

        $progress = $stmt->fetch();

        $total = (int) ($progress['total_missions'] ?? 0);
        $completed = (int) ($progress['completed_missions'] ?? 0);

        return [
            'total' => $total,
            'completed' => $completed,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }

    private function getLastQuizSession(int $userId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM quiz_sessions
             WHERE user_id = :user_id
             ORDER BY started_at DESC
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
        ]);

        $session = $stmt->fetch();

        return $session ?: null;
    }
}