<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

final class GuestProgressService
{
    private const SESSION_KEY = 'guest_guided';

    public function transferToUser(int $userId): bool
    {
        $contexts = $this->getTransferableContexts();

        if ($contexts === []) {
            return false;
        }

        $pdo = Database::connection();

        $pdo->beginTransaction();

        try {
            $this->markUserAsGuided($userId);

            foreach ($contexts as $context) {
                $this->transferContext($userId, $context);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        $this->clearGuestProgress();

        return true;
    }

    public function clearGuestProgress(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    private function transferContext(int $userId, array $context): void
    {
        $completedMission = $this->isMissionCompleted($context);
        $nextMission = $completedMission ? $context['next_mission'] : null;
        $currentMission = $completedMission && $nextMission !== null
            ? $nextMission
            : $context['mission'];

        $this->upsertUserPath($userId, $context['path'], $currentMission, $completedMission && $nextMission === null);
        $this->upsertMissions($userId, $context, $completedMission, $nextMission);
        $this->upsertObjectives($userId, $context, $completedMission, $nextMission);

        $completedObjectives = $this->completedObjectives($context);
        $this->unlockObjectiveBadges($userId, $completedObjectives);

        if ($completedMission && $nextMission === null) {
            (new BadgeService())->checkPathBadges($userId, (string) $context['path']['code']);
        }
    }

    private function getTransferableContexts(): array
    {
        $state = $this->getState();
        $contexts = [];

        foreach ($state['paths'] as $progress) {
            $context = $this->getValidatedContext($progress);

            if ($context === null) {
                continue;
            }

            if ($this->hasAnyProgress($context)) {
                $contexts[] = $context;
            }
        }

        return $contexts;
    }

    private function getValidatedContext(array $progress): ?array
    {
        $pathCode = (string) ($progress['path_code'] ?? '');
        $missionId = (int) ($progress['mission_id'] ?? 0);

        if (!in_array($pathCode, ['hiragana_base', 'katakana_base'], true) || $missionId <= 0) {
            return null;
        }

        $path = $this->findPathByCode($pathCode);

        if ($path === null) {
            return null;
        }

        $missions = $this->findMissionsByPathId((int) $path['id']);

        if ($missions === []) {
            return null;
        }

        $mission = null;
        $nextMission = null;

        foreach ($missions as $index => $candidate) {
            if ((int) $candidate['id'] === $missionId) {
                $mission = $candidate;
                $nextMission = $missions[$index + 1] ?? null;
                break;
            }
        }

        if ($mission === null) {
            return null;
        }

        $objectives = $this->findObjectivesByMissionId($missionId);

        if ($objectives === []) {
            return null;
        }

        return [
            'path' => $path,
            'missions' => $missions,
            'mission' => $mission,
            'next_mission' => $nextMission,
            'objectives' => $objectives,
            'success_counts' => $this->objectiveSuccessCounts($progress),
        ];
    }

    private function markUserAsGuided(int $userId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'UPDATE users
             SET learning_mode = "guided"
             WHERE id = :user_id'
        );

        $stmt->execute(['user_id' => $userId]);
    }

    private function upsertUserPath(int $userId, array $path, array $currentMission, bool $pathCompleted): void
    {
        $pdo = Database::connection();
        $status = $pathCompleted ? 'completed' : 'in_progress';

        $stmt = $pdo->prepare(
            'INSERT INTO user_paths (user_id, path_id, status, current_mission_id, completed_at)
             VALUES (:user_id, :path_id, :status, :current_mission_id, :completed_at)
             ON DUPLICATE KEY UPDATE
                status = CASE
                    WHEN status = "completed" THEN status
                    WHEN VALUES(status) = "completed" THEN "completed"
                    ELSE "in_progress"
                END,
                current_mission_id = CASE
                    WHEN status = "completed" THEN current_mission_id
                    ELSE VALUES(current_mission_id)
                END,
                completed_at = CASE
                    WHEN VALUES(status) = "completed" THEN COALESCE(completed_at, NOW())
                    ELSE completed_at
                END'
        );

        $stmt->execute([
            'user_id' => $userId,
            'path_id' => (int) $path['id'],
            'status' => $status,
            'current_mission_id' => (int) $currentMission['id'],
            'completed_at' => $pathCompleted ? date('Y-m-d H:i:s') : null,
        ]);
    }

    private function upsertMissions(int $userId, array $context, bool $completedMission, ?array $nextMission): void
    {
        $pdo = Database::connection();
        $missionId = (int) $context['mission']['id'];
        $nextMissionId = $nextMission !== null ? (int) $nextMission['id'] : null;

        $stmt = $pdo->prepare(
            'INSERT INTO user_missions (user_id, mission_id, status, attempts_count, completed_at)
             VALUES (:user_id, :mission_id, :status, :attempts_count, :completed_at)
             ON DUPLICATE KEY UPDATE
                status = CASE
                    WHEN status = "completed" THEN status
                    WHEN VALUES(status) = "completed" THEN "completed"
                    WHEN status = "locked" AND VALUES(status) IN ("available", "in_progress") THEN VALUES(status)
                    WHEN status = "available" AND VALUES(status) = "in_progress" THEN "in_progress"
                    ELSE status
                END,
                attempts_count = GREATEST(attempts_count, VALUES(attempts_count)),
                completed_at = CASE
                    WHEN VALUES(status) = "completed" THEN COALESCE(completed_at, NOW())
                    ELSE completed_at
                END'
        );

        foreach ($context['missions'] as $mission) {
            $currentMissionId = (int) $mission['id'];
            $status = 'locked';
            $attemptsCount = 0;
            $completedAt = null;

            if ($currentMissionId === $missionId) {
                $status = $completedMission ? 'completed' : 'in_progress';
                $attemptsCount = 1;
                $completedAt = $completedMission ? date('Y-m-d H:i:s') : null;
            } elseif ($nextMissionId !== null && $currentMissionId === $nextMissionId) {
                $status = 'available';
            }

            $stmt->execute([
                'user_id' => $userId,
                'mission_id' => $currentMissionId,
                'status' => $status,
                'attempts_count' => $attemptsCount,
                'completed_at' => $completedAt,
            ]);
        }
    }

    private function upsertObjectives(int $userId, array $context, bool $completedMission, ?array $nextMission): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'INSERT INTO user_objectives (
                user_id,
                objective_id,
                status,
                attempts_count,
                success_count,
                best_score,
                completed_at
             ) VALUES (
                :user_id,
                :objective_id,
                :status,
                :attempts_count,
                :success_count,
                :best_score,
                :completed_at
             )
             ON DUPLICATE KEY UPDATE
                status = CASE
                    WHEN status = "completed" THEN status
                    WHEN VALUES(status) = "completed" THEN "completed"
                    WHEN status = "locked" AND VALUES(status) IN ("available", "in_progress") THEN VALUES(status)
                    WHEN status = "available" AND VALUES(status) = "in_progress" THEN "in_progress"
                    ELSE status
                END,
                attempts_count = GREATEST(attempts_count, VALUES(attempts_count)),
                success_count = GREATEST(success_count, VALUES(success_count)),
                best_score = GREATEST(COALESCE(best_score, 0), COALESCE(VALUES(best_score), 0)),
                completed_at = CASE
                    WHEN VALUES(status) = "completed" THEN COALESCE(completed_at, NOW())
                    ELSE completed_at
                END'
        );

        $firstAvailableWritten = false;

        foreach ($context['objectives'] as $objective) {
            $objectiveId = (int) $objective['id'];
            $requiredSuccessCount = (int) $objective['required_success_count'];
            $successCount = min($requiredSuccessCount, $context['success_counts'][$objectiveId] ?? 0);
            $status = $this->objectiveStatus($successCount, $requiredSuccessCount, $firstAvailableWritten);
            $bestScore = $successCount > 0 ? 100 : null;

            $stmt->execute([
                'user_id' => $userId,
                'objective_id' => $objectiveId,
                'status' => $status,
                'attempts_count' => $successCount,
                'success_count' => $successCount,
                'best_score' => $bestScore,
                'completed_at' => $status === 'completed' ? date('Y-m-d H:i:s') : null,
            ]);
        }

        if ($completedMission && $nextMission !== null) {
            $this->unlockFirstObjectiveForMission($userId, (int) $nextMission['id']);
        }
    }

    private function objectiveStatus(int $successCount, int $requiredSuccessCount, bool &$firstAvailableFound): string
    {
        if ($successCount >= $requiredSuccessCount) {
            return 'completed';
        }

        if (!$firstAvailableFound) {
            $firstAvailableFound = true;

            return $successCount > 0 ? 'in_progress' : 'available';
        }

        return 'locked';
    }

    private function unlockFirstObjectiveForMission(int $userId, int $missionId): void
    {
        $objectives = $this->findObjectivesByMissionId($missionId);

        if ($objectives === []) {
            return;
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'INSERT INTO user_objectives (user_id, objective_id, status)
             VALUES (:user_id, :objective_id, "available")
             ON DUPLICATE KEY UPDATE
                status = IF(status = "locked", "available", status)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'objective_id' => (int) $objectives[0]['id'],
        ]);
    }

    private function unlockObjectiveBadges(int $userId, array $objectives): void
    {
        $badgeService = new BadgeService();

        foreach ($objectives as $objective) {
            $badgeService->checkObjectiveBadges($userId, $objective);
        }
    }

    private function isMissionCompleted(array $context): bool
    {
        foreach ($context['objectives'] as $objective) {
            $objectiveId = (int) $objective['id'];
            $requiredSuccessCount = (int) $objective['required_success_count'];

            if (($context['success_counts'][$objectiveId] ?? 0) < $requiredSuccessCount) {
                return false;
            }
        }

        return $context['objectives'] !== [];
    }

    private function completedObjectives(array $context): array
    {
        return array_values(array_filter(
            $context['objectives'],
            fn (array $objective): bool => ($context['success_counts'][(int) $objective['id']] ?? 0) >= (int) $objective['required_success_count']
        ));
    }

    private function hasAnyProgress(array $context): bool
    {
        foreach ($context['success_counts'] as $successCount) {
            if ($successCount > 0) {
                return true;
            }
        }

        return false;
    }

    private function getState(): array
    {
        $raw = Session::get(self::SESSION_KEY, []);

        if (!is_array($raw)) {
            return [
                'active_path_code' => null,
                'paths' => [],
            ];
        }

        if (isset($raw['path_code'], $raw['mission_id'])) {
            $pathCode = $this->normalizePathCode((string) $raw['path_code']);

            return [
                'active_path_code' => $pathCode,
                'paths' => [
                    $pathCode => [
                        'path_code' => $pathCode,
                        'mission_id' => (int) $raw['mission_id'],
                        'objective_success_counts' => $this->legacySuccessCounts($raw),
                    ],
                ],
            ];
        }

        $paths = $raw['paths'] ?? [];

        return [
            'active_path_code' => $raw['active_path_code'] ?? null,
            'paths' => is_array($paths) ? $paths : [],
        ];
    }

    private function normalizePathCode(string $pathCode): string
    {
        return in_array($pathCode, ['hiragana_base', 'katakana_base'], true)
            ? $pathCode
            : 'hiragana_base';
    }

    private function objectiveSuccessCounts(array $progress): array
    {
        $counts = $progress['objective_success_counts'] ?? [];

        if (!is_array($counts)) {
            return [];
        }

        $normalized = [];

        foreach ($counts as $objectiveId => $successCount) {
            $normalized[(int) $objectiveId] = max(0, (int) $successCount);
        }

        return $normalized;
    }

    private function legacySuccessCounts(array $progress): array
    {
        $completed = $progress['completed_objectives'] ?? [];

        if (!is_array($completed)) {
            return [];
        }

        $counts = [];

        foreach ($completed as $objectiveId) {
            $counts[(string) (int) $objectiveId] = 999;
        }

        return $counts;
    }

    private function findPathByCode(string $code): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM learning_paths
             WHERE code = :code
               AND is_active = 1
             LIMIT 1'
        );

        $stmt->execute(['code' => $code]);
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

        $stmt->execute(['path_id' => $pathId]);

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

        $stmt->execute(['mission_id' => $missionId]);

        return $stmt->fetchAll();
    }
}
