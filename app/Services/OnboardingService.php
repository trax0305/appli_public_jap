<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class OnboardingService
{
    public function saveLearningMode(int $userId, string $learningMode, ?string $pathCode = null): void
    {
        $pdo = Database::connection();

        if (!in_array($learningMode, ['guided', 'free'], true)) {
            $learningMode = 'guided';
        }

        $stmt = $pdo->prepare(
            'UPDATE users
             SET learning_mode = :learning_mode
             WHERE id = :user_id'
        );

        $stmt->execute([
            'learning_mode' => $learningMode,
            'user_id' => $userId,
        ]);

        if ($learningMode === 'guided') {
            $this->initializeGuidedPath($userId, $pathCode ?? 'hiragana_base');
        }
    }

    private function initializeGuidedPath(int $userId, string $pathCode): void
    {
        $pdo = Database::connection();

        if (!in_array($pathCode, ['hiragana_base', 'katakana_base'], true)) {
            $pathCode = 'hiragana_base';
        }

        $path = $this->findPathByCode($pathCode);

        if ($path === null) {
            throw new \RuntimeException("Parcours introuvable : {$pathCode}");
        }

        $missions = $this->findMissionsByPathId((int) $path['id']);

        if ($missions === []) {
            throw new \RuntimeException("Aucune mission trouvée pour le parcours : {$pathCode}");
        }

        $firstMission = $missions[0];

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO user_paths (user_id, path_id, status, current_mission_id)
                 VALUES (:user_id, :path_id, :status, :current_mission_id)
                 ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    current_mission_id = VALUES(current_mission_id)'
            );

            $stmt->execute([
                'user_id' => $userId,
                'path_id' => (int) $path['id'],
                'status' => 'in_progress',
                'current_mission_id' => (int) $firstMission['id'],
            ]);

            foreach ($missions as $index => $mission) {
                $missionStatus = $index === 0 ? 'available' : 'locked';

                $stmt = $pdo->prepare(
                    'INSERT INTO user_missions (user_id, mission_id, status)
                     VALUES (:user_id, :mission_id, :status)
                     ON DUPLICATE KEY UPDATE
                        status = VALUES(status)'
                );

                $stmt->execute([
                    'user_id' => $userId,
                    'mission_id' => (int) $mission['id'],
                    'status' => $missionStatus,
                ]);
            }

            $objectives = $this->findObjectivesByMissionId((int) $firstMission['id']);

            foreach ($objectives as $index => $objective) {
                $objectiveStatus = $index === 0 ? 'available' : 'locked';

                $stmt = $pdo->prepare(
                    'INSERT INTO user_objectives (user_id, objective_id, status)
                     VALUES (:user_id, :objective_id, :status)
                     ON DUPLICATE KEY UPDATE
                        status = VALUES(status)'
                );

                $stmt->execute([
                    'user_id' => $userId,
                    'objective_id' => (int) $objective['id'],
                    'status' => $objectiveStatus,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function findPathByCode(string $code): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM learning_paths
             WHERE code = :code
             LIMIT 1'
        );

        $stmt->execute([
            'code' => $code,
        ]);

        $path = $stmt->fetch();

        return $path ?: null;
    }

    private function findMissionsByPathId(int $pathId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM missions
             WHERE path_id = :path_id
               AND is_active = 1
             ORDER BY sort_order ASC'
        );

        $stmt->execute([
            'path_id' => $pathId,
        ]);

        return $stmt->fetchAll();
    }

    private function findObjectivesByMissionId(int $missionId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM objectives
             WHERE mission_id = :mission_id
             ORDER BY sort_order ASC'
        );

        $stmt->execute([
            'mission_id' => $missionId,
        ]);

        return $stmt->fetchAll();
    }
}