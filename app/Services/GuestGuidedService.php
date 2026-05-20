<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

final class GuestGuidedService
{
    private const SESSION_KEY = 'guest_guided';

    public function start(string $pathCode): array
    {
        $pathCode = $this->normalizePathCode($pathCode);
        $mission = $this->getFirstMissionForPath($pathCode);

        if ($mission === null) {
            throw new \RuntimeException('Mission invitée introuvable.');
        }

        $state = $this->getState();

        if (!isset($state['paths'][$pathCode])) {
            $state['paths'][$pathCode] = [
                'path_code' => $pathCode,
                'mission_id' => (int) $mission['id'],
                'objective_success_counts' => [],
            ];
        }

        $state['active_path_code'] = $pathCode;
        $this->putState($state);

        return $state['paths'][$pathCode];
    }

    public function getMissionForGuest(int $missionId): ?array
    {
        $progress = $this->getProgressForMission($missionId);

        if ($progress === null) {
            return null;
        }

        $mission = $this->getMission($missionId, (string) $progress['path_code']);

        if ($mission === null) {
            return null;
        }

        $mission['kana'] = $this->getMissionKana($missionId);
        $mission['objectives'] = $this->decorateObjectives(
            $this->getMissionObjectives($missionId),
            $this->objectiveSuccessCounts($progress)
        );
        $mission['is_completed'] = $this->isMissionCompleted($mission['objectives']);

        return $mission;
    }

    public function completeDiscovery(int $missionId): void
    {
        $progress = $this->getProgressForMission($missionId);
        $mission = $progress !== null ? $this->getMissionForGuest($missionId) : null;

        if ($progress === null || $mission === null) {
            throw new \RuntimeException('Mission invitée introuvable.');
        }

        foreach ($mission['objectives'] as $objective) {
            if ($objective['objective_type'] === 'discovery') {
                $this->setObjectiveSuccessCount(
                    (string) $progress['path_code'],
                    (int) $objective['id'],
                    (int) $objective['required_success_count']
                );
                return;
            }
        }

        throw new \RuntimeException('Découverte introuvable.');
    }

    public function startQuiz(int $objectiveId): int
    {
        $progress = $this->getActiveProgress();

        if ($progress === null) {
            throw new \RuntimeException('Parcours invité introuvable.');
        }

        $mission = $this->getMissionForGuest((int) $progress['mission_id']);

        if ($mission === null) {
            throw new \RuntimeException('Mission invitée introuvable.');
        }

        $targetObjective = null;

        foreach ($mission['objectives'] as $objective) {
            if ((int) $objective['id'] === $objectiveId) {
                $targetObjective = $objective;
                break;
            }
        }

        if (
            $targetObjective === null
            || $targetObjective['objective_type'] === 'discovery'
            || $targetObjective['user_status'] !== 'available'
        ) {
            throw new \RuntimeException('Objectif invité indisponible.');
        }

        return (new QuizGeneratorService())->startGuestObjectiveQuiz(
            $objectiveId,
            (string) $progress['path_code'],
            (int) $progress['mission_id']
        );
    }

    public function completeObjectiveFromQuizSession(array $session): void
    {
        if ($session['user_id'] !== null || ($session['source_type'] ?? '') !== 'objective') {
            return;
        }

        if ((int) ($session['score_percent'] ?? 0) !== 100) {
            return;
        }

        $settings = json_decode((string) ($session['settings_json'] ?? '{}'), true);

        if (!is_array($settings) || empty($settings['guest'])) {
            return;
        }

        $pathCode = $this->normalizePathCode((string) ($settings['guest_path_code'] ?? 'hiragana_base'));
        $missionId = (int) ($settings['guest_mission_id'] ?? 0);
        $objectiveId = (int) ($session['source_id'] ?? 0);

        $progress = $this->getProgressForPath($pathCode);

        if ($progress === null || (int) $progress['mission_id'] !== $missionId || $objectiveId <= 0) {
            return;
        }

        $objective = $this->getObjective($objectiveId, $missionId);

        if ($objective === null) {
            return;
        }

        $this->incrementObjectiveSuccessCount(
            $pathCode,
            $objectiveId,
            (int) $objective['required_success_count']
        );
    }

    public function getResultContext(array $session): array
    {
        $settings = json_decode((string) ($session['settings_json'] ?? '{}'), true);
        $missionId = is_array($settings) ? (int) ($settings['guest_mission_id'] ?? 0) : 0;
        $mission = $missionId > 0 ? $this->getMissionForGuest($missionId) : null;

        return [
            'mission_id' => $missionId,
            'mission_completed' => $mission !== null && !empty($mission['is_completed']),
        ];
    }

    public function normalizePathCode(string $pathCode): string
    {
        return in_array($pathCode, ['hiragana_base', 'katakana_base'], true)
            ? $pathCode
            : 'hiragana_base';
    }

    private function getActiveProgress(): ?array
    {
        $state = $this->getState();
        $activePathCode = (string) ($state['active_path_code'] ?? '');

        return $activePathCode !== '' && isset($state['paths'][$activePathCode])
            ? $state['paths'][$activePathCode]
            : null;
    }

    private function getProgressForMission(int $missionId): ?array
    {
        foreach ($this->getState()['paths'] as $progress) {
            if ((int) ($progress['mission_id'] ?? 0) === $missionId) {
                return $progress;
            }
        }

        return null;
    }

    private function getProgressForPath(string $pathCode): ?array
    {
        $state = $this->getState();

        return $state['paths'][$pathCode] ?? null;
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

    private function putState(array $state): void
    {
        Session::put(self::SESSION_KEY, $state);
    }

    private function setObjectiveSuccessCount(string $pathCode, int $objectiveId, int $successCount): void
    {
        $state = $this->getState();

        if (!isset($state['paths'][$pathCode])) {
            return;
        }

        $state['paths'][$pathCode]['objective_success_counts'][(string) $objectiveId] = $successCount;
        $state['active_path_code'] = $pathCode;

        $this->putState($state);
    }

    private function incrementObjectiveSuccessCount(string $pathCode, int $objectiveId, int $requiredSuccessCount): void
    {
        $state = $this->getState();

        if (!isset($state['paths'][$pathCode])) {
            return;
        }

        $counts = $this->objectiveSuccessCounts($state['paths'][$pathCode]);
        $current = $counts[$objectiveId] ?? 0;

        $state['paths'][$pathCode]['objective_success_counts'][(string) $objectiveId] = min(
            $requiredSuccessCount,
            $current + 1
        );
        $state['active_path_code'] = $pathCode;

        $this->putState($state);
    }

    private function decorateObjectives(array $objectives, array $successCounts): array
    {
        $firstOpenFound = false;

        foreach ($objectives as $index => $objective) {
            $objectiveId = (int) $objective['id'];
            $requiredSuccessCount = (int) $objective['required_success_count'];
            $successCount = min($requiredSuccessCount, $successCounts[$objectiveId] ?? 0);
            $isCompleted = $successCount >= $requiredSuccessCount;

            $objectives[$index]['success_count'] = $successCount;

            if ($isCompleted) {
                $objectives[$index]['user_status'] = 'completed';
                continue;
            }

            if (!$firstOpenFound) {
                $objectives[$index]['user_status'] = 'available';
                $firstOpenFound = true;
            } else {
                $objectives[$index]['user_status'] = 'locked';
            }
        }

        return $objectives;
    }

    private function isMissionCompleted(array $objectives): bool
    {
        if ($objectives === []) {
            return false;
        }

        foreach ($objectives as $objective) {
            if (($objective['user_status'] ?? '') !== 'completed') {
                return false;
            }
        }

        return true;
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

    private function getFirstMissionForPath(string $pathCode): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                m.*,
                lp.code AS path_code,
                lp.title AS path_title,
                lp.kana_set
             FROM learning_paths lp
             INNER JOIN missions m ON m.path_id = lp.id
             WHERE lp.code = :path_code
               AND lp.is_active = 1
               AND m.is_active = 1
             ORDER BY m.sort_order ASC
             LIMIT 1'
        );

        $stmt->execute(['path_code' => $pathCode]);

        $mission = $stmt->fetch();

        return $mission ?: null;
    }

    private function getMission(int $missionId, string $pathCode): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                m.*,
                lp.code AS path_code,
                lp.title AS path_title,
                lp.kana_set
             FROM missions m
             INNER JOIN learning_paths lp ON lp.id = m.path_id
             WHERE m.id = :mission_id
               AND lp.code = :path_code
               AND m.is_active = 1
               AND lp.is_active = 1
             LIMIT 1'
        );

        $stmt->execute([
            'mission_id' => $missionId,
            'path_code' => $pathCode,
        ]);

        $mission = $stmt->fetch();

        return $mission ?: null;
    }

    private function getObjective(int $objectiveId, int $missionId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM objectives
             WHERE id = :id
               AND mission_id = :mission_id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $objectiveId,
            'mission_id' => $missionId,
        ]);

        $objective = $stmt->fetch();

        return $objective ?: null;
    }

    private function getMissionKana(int $missionId): array
    {
        return (new MissionService())->getMissionKana($missionId);
    }

    private function getMissionObjectives(int $missionId): array
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
