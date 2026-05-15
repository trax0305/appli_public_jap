<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class MissionService
{
    public function getMissionForUser(int $userId, int $missionId): ?array
    {
        $mission = $this->getMission($userId, $missionId);

        if ($mission === null) {
            return null;
        }

        $mission['kana'] = $this->getMissionKana($missionId);
        $mission['objectives'] = $this->getMissionObjectives($userId, $missionId);

        return $mission;
    }

    public function getMissionKana(int $missionId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                k.*,
                cg.code AS consonant_code,
                cg.label AS consonant_label,
                vg.code AS vowel_code,
                vg.label AS vowel_label
             FROM mission_kana mk
             INNER JOIN kana k ON k.id = mk.kana_id
             LEFT JOIN consonant_groups cg ON cg.id = k.consonant_group_id
             LEFT JOIN vowel_groups vg ON vg.id = k.vowel_group_id
             WHERE mk.mission_id = :mission_id
             ORDER BY k.sort_order ASC'
        );

        $stmt->execute([
            'mission_id' => $missionId,
        ]);

        return $stmt->fetchAll();
    }

    private function getMission(int $userId, int $missionId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                m.*,
                lp.title AS path_title,
                lp.kana_set,
                COALESCE(um.status, "locked") AS user_status
             FROM missions m
             INNER JOIN learning_paths lp ON lp.id = m.path_id
             LEFT JOIN user_missions um
                ON um.mission_id = m.id
               AND um.user_id = :user_id
             WHERE m.id = :mission_id
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'mission_id' => $missionId,
        ]);

        $mission = $stmt->fetch();

        return $mission ?: null;
    }

    private function getMissionObjectives(int $userId, int $missionId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                o.*,
                COALESCE(uo.status, "locked") AS user_status,
                COALESCE(uo.attempts_count, 0) AS attempts_count,
                COALESCE(uo.success_count, 0) AS success_count,
                uo.best_score,
                uo.completed_at
             FROM objectives o
             LEFT JOIN user_objectives uo
                ON uo.objective_id = o.id
               AND uo.user_id = :user_id
             WHERE o.mission_id = :mission_id
             ORDER BY o.sort_order ASC'
        );

        $stmt->execute([
            'user_id' => $userId,
            'mission_id' => $missionId,
        ]);

        return $stmt->fetchAll();
    }

    public function completeDiscovery(int $userId, int $missionId): void
{
    $pdo = Database::connection();

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'SELECT id
             FROM objectives
             WHERE mission_id = :mission_id
               AND objective_type = "discovery"
             ORDER BY sort_order ASC
             LIMIT 1'
        );

        $stmt->execute([
            'mission_id' => $missionId,
        ]);

        $discoveryObjective = $stmt->fetch();

        if (!$discoveryObjective) {
            throw new \RuntimeException('Objectif découverte introuvable.');
        }

        $stmt = $pdo->prepare(
            'UPDATE user_objectives
             SET status = "completed",
                 attempts_count = attempts_count + 1,
                 success_count = 1,
                 best_score = 100,
                 completed_at = NOW()
             WHERE user_id = :user_id
               AND objective_id = :objective_id'
        );

        $stmt->execute([
            'user_id' => $userId,
            'objective_id' => (int) $discoveryObjective['id'],
        ]);

        $stmt = $pdo->prepare(
            'SELECT id
             FROM objectives
             WHERE mission_id = :mission_id
               AND sort_order > (
                    SELECT sort_order
                    FROM objectives
                    WHERE id = :objective_id
               )
             ORDER BY sort_order ASC
             LIMIT 1'
        );

        $stmt->execute([
            'mission_id' => $missionId,
            'objective_id' => (int) $discoveryObjective['id'],
        ]);

        $nextObjective = $stmt->fetch();

        if ($nextObjective) {
            $stmt = $pdo->prepare(
                'INSERT INTO user_objectives (user_id, objective_id, status)
                 VALUES (:user_id, :objective_id, "available")
                 ON DUPLICATE KEY UPDATE
                    status = IF(status = "locked", "available", status)'
            );

            $stmt->execute([
                'user_id' => $userId,
                'objective_id' => (int) $nextObjective['id'],
            ]);
        }

        $stmt = $pdo->prepare(
            'UPDATE user_missions
             SET status = "in_progress",
                 attempts_count = attempts_count + 1
             WHERE user_id = :user_id
               AND mission_id = :mission_id
               AND status IN ("available", "in_progress")'
        );

        $stmt->execute([
            'user_id' => $userId,
            'mission_id' => $missionId,
        ]);

        $pdo->commit();
    } catch (\Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}
}