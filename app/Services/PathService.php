<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class PathService
{
    public function getPathsForUser(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                lp.*,
                COALESCE(up.status, "available") AS user_status,
                up.current_mission_id,
                COALESCE(mt.total_missions, 0) AS total_missions,
                COALESCE(mc.completed_missions, 0) AS completed_missions
             FROM learning_paths lp
             LEFT JOIN user_paths up
                ON up.path_id = lp.id
               AND up.user_id = :user_id
             LEFT JOIN (
                SELECT path_id, COUNT(*) AS total_missions
                FROM missions
                WHERE is_active = 1
                GROUP BY path_id
             ) mt ON mt.path_id = lp.id
             LEFT JOIN (
                SELECT m.path_id, COUNT(*) AS completed_missions
                FROM missions m
                INNER JOIN user_missions um ON um.mission_id = m.id
                WHERE um.user_id = :user_id_completed
                  AND um.status = "completed"
                  AND m.is_active = 1
                GROUP BY m.path_id
             ) mc ON mc.path_id = lp.id
             WHERE lp.is_active = 1
             ORDER BY lp.sort_order ASC'
        );

        $stmt->execute([
            'user_id' => $userId,
            'user_id_completed' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public function getPathWithMissions(int $userId, int $pathId): ?array
    {
        $path = $this->getPath($userId, $pathId);

        if ($path === null) {
            return null;
        }

        $path['missions'] = $this->getMissionsForPath($userId, $pathId);

        return $path;
    }

    private function getPath(int $userId, int $pathId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                lp.*,
                COALESCE(up.status, "available") AS user_status,
                up.current_mission_id
             FROM learning_paths lp
             LEFT JOIN user_paths up
                ON up.path_id = lp.id
               AND up.user_id = :user_id
             WHERE lp.id = :path_id
               AND lp.is_active = 1
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'path_id' => $pathId,
        ]);

        $path = $stmt->fetch();

        return $path ?: null;
    }

    private function getMissionsForPath(int $userId, int $pathId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                m.*,
                COALESCE(um.status, "locked") AS user_status,
                COALESCE(mkc.kana_count, 0) AS kana_count
             FROM missions m
             LEFT JOIN user_missions um
                ON um.mission_id = m.id
               AND um.user_id = :user_id
             LEFT JOIN (
                SELECT mission_id, COUNT(*) AS kana_count
                FROM mission_kana
                GROUP BY mission_id
             ) mkc ON mkc.mission_id = m.id
             WHERE m.path_id = :path_id
               AND m.is_active = 1
             ORDER BY m.sort_order ASC'
        );

        $stmt->execute([
            'user_id' => $userId,
            'path_id' => $pathId,
        ]);

        return $stmt->fetchAll();
    }
}