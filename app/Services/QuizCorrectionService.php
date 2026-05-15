<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class QuizCorrectionService
{
    public function getCurrentQuestion(int $sessionId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT qa.*, qs.total_questions
             FROM quiz_answers qa
             INNER JOIN quiz_sessions qs ON qs.id = qa.session_id
             WHERE qa.session_id = :session_id
               AND qa.user_answer IS NULL
             ORDER BY qa.question_order ASC
             LIMIT 1'
        );

        $stmt->execute([
            'session_id' => $sessionId,
        ]);

        $question = $stmt->fetch();

        return $question ?: null;
    }

    public function getSession(int $sessionId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM quiz_sessions
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $sessionId,
        ]);

        $session = $stmt->fetch();

        return $session ?: null;
    }

    public function answerQuestion(int $sessionId, int $answerId, string $userAnswer): array
{
    $pdo = Database::connection();

    $stmt = $pdo->prepare(
        'SELECT *
         FROM quiz_answers
         WHERE id = :id
           AND session_id = :session_id
         LIMIT 1'
    );

    $stmt->execute([
        'id' => $answerId,
        'session_id' => $sessionId,
    ]);

    $answer = $stmt->fetch();

    if (!$answer) {
        throw new \RuntimeException('Question introuvable.');
    }

    $session = $this->getSession($sessionId);

    if ($session !== null && $session['direction'] === 'written') {
        $isCorrect = strtolower(trim($userAnswer)) === strtolower(trim($answer['expected_answer']));
    } else {
        $isCorrect = trim($userAnswer) === trim($answer['expected_answer']);
    }

    $stmt = $pdo->prepare(
        'UPDATE quiz_answers
         SET user_answer = :user_answer,
             is_correct = :is_correct,
             answered_at = NOW()
         WHERE id = :id'
    );

    $stmt->execute([
        'user_answer' => trim($userAnswer),
        'is_correct' => $isCorrect ? 1 : 0,
        'id' => $answerId,
    ]);

    if ($this->getCurrentQuestion($sessionId) === null) {
        $this->completeSession($sessionId);
    }

    return [
        'is_correct' => $isCorrect,
        'user_answer' => trim($userAnswer),
        'expected_answer' => $answer['expected_answer'],
    ];
}

    public function completeSession(int $sessionId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS correct
             FROM quiz_answers
             WHERE session_id = :session_id'
        );

        $stmt->execute([
            'session_id' => $sessionId,
        ]);

        $result = $stmt->fetch();

        $total = (int) $result['total'];
        $correct = (int) $result['correct'];
        $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

        $stmt = $pdo->prepare(
            'UPDATE quiz_sessions
             SET correct_answers = :correct_answers,
                 score_percent = :score_percent,
                 completed_at = NOW()
             WHERE id = :session_id'
        );

        $stmt->execute([
            'correct_answers' => $correct,
            'score_percent' => $score,
            'session_id' => $sessionId,
        ]);

        $this->updateObjectiveProgress($sessionId, $score);
    }

    private function updateObjectiveProgress(int $sessionId, int $score): void
    {
        $pdo = Database::connection();

        $session = $this->getSession($sessionId);

        if ($session === null || $session['source_type'] !== 'objective') {
            return;
        }

        $objectiveId = (int) $session['source_id'];
        $userId = (int) $session['user_id'];

        $stmt = $pdo->prepare(
            'SELECT *
             FROM objectives
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $objectiveId,
        ]);

        $objective = $stmt->fetch();

        if (!$objective) {
            return;
        }

        $isSuccess = $score >= (int) $objective['required_score'];

        $stmt = $pdo->prepare(
            'UPDATE user_objectives
             SET attempts_count = attempts_count + 1,
                 success_count = success_count + :success_increment,
                 best_score = GREATEST(COALESCE(best_score, 0), :score)
             WHERE user_id = :user_id
               AND objective_id = :objective_id'
        );

        $stmt->execute([
            'success_increment' => $isSuccess ? 1 : 0,
            'score' => $score,
            'user_id' => $userId,
            'objective_id' => $objectiveId,
        ]);

        if ($isSuccess) {
            $this->completeObjectiveIfNeeded($userId, $objective);
        }
    }

    private function completeObjectiveIfNeeded(int $userId, array $objective): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM user_objectives
             WHERE user_id = :user_id
               AND objective_id = :objective_id
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'objective_id' => (int) $objective['id'],
        ]);

        $userObjective = $stmt->fetch();

        if (!$userObjective) {
            return;
        }

        if ((int) $userObjective['success_count'] < (int) $objective['required_success_count']) {
            return;
        }

        $stmt = $pdo->prepare(
            'UPDATE user_objectives
             SET status = "completed",
                 completed_at = COALESCE(completed_at, NOW())
             WHERE user_id = :user_id
               AND objective_id = :objective_id'
        );

        $stmt->execute([
            'user_id' => $userId,
            'objective_id' => (int) $objective['id'],
        ]);

        $this->unlockNextObjective($userId, $objective);
        $this->completeMissionIfNeeded($userId, (int) $objective['mission_id']);
    }

    private function unlockNextObjective(int $userId, array $objective): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id
             FROM objectives
             WHERE mission_id = :mission_id
               AND sort_order > :sort_order
             ORDER BY sort_order ASC
             LIMIT 1'
        );

        $stmt->execute([
            'mission_id' => (int) $objective['mission_id'],
            'sort_order' => (int) $objective['sort_order'],
        ]);

        $nextObjective = $stmt->fetch();

        if (!$nextObjective) {
            return;
        }

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

    private function completeMissionIfNeeded(int $userId, int $missionId): void
{
    $pdo = Database::connection();

    $stmt = $pdo->prepare(
        'SELECT
            COUNT(o.id) AS total_objectives,
            SUM(CASE WHEN uo.status = "completed" THEN 1 ELSE 0 END) AS completed_objectives
         FROM objectives o
         LEFT JOIN user_objectives uo
            ON uo.objective_id = o.id
           AND uo.user_id = :user_id
         WHERE o.mission_id = :mission_id'
    );

    $stmt->execute([
        'user_id' => $userId,
        'mission_id' => $missionId,
    ]);

    $result = $stmt->fetch();

    $total = (int) ($result['total_objectives'] ?? 0);
    $completed = (int) ($result['completed_objectives'] ?? 0);

    if ($total === 0 || $completed < $total) {
        return;
    }

    $stmt = $pdo->prepare(
        'UPDATE user_missions
         SET status = "completed",
             completed_at = COALESCE(completed_at, NOW())
         WHERE user_id = :user_id
           AND mission_id = :mission_id'
    );

    $stmt->execute([
        'user_id' => $userId,
        'mission_id' => $missionId,
    ]);

    $this->unlockNextMission($userId, $missionId);
}

private function unlockNextMission(int $userId, int $missionId): void
{
    $pdo = Database::connection();

    $stmt = $pdo->prepare(
        'SELECT *
         FROM missions
         WHERE id = :mission_id
         LIMIT 1'
    );

    $stmt->execute([
        'mission_id' => $missionId,
    ]);

    $currentMission = $stmt->fetch();

    if (!$currentMission) {
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT *
         FROM missions
         WHERE path_id = :path_id
           AND sort_order > :sort_order
           AND is_active = 1
         ORDER BY sort_order ASC
         LIMIT 1'
    );

    $stmt->execute([
        'path_id' => (int) $currentMission['path_id'],
        'sort_order' => (int) $currentMission['sort_order'],
    ]);

    $nextMission = $stmt->fetch();

    if (!$nextMission) {
        $this->completePath($userId, (int) $currentMission['path_id']);
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO user_missions (user_id, mission_id, status)
         VALUES (:user_id, :mission_id, "available")
         ON DUPLICATE KEY UPDATE
            status = IF(status = "locked", "available", status)'
    );

    $stmt->execute([
        'user_id' => $userId,
        'mission_id' => (int) $nextMission['id'],
    ]);

    $stmt = $pdo->prepare(
        'UPDATE user_paths
         SET current_mission_id = :current_mission_id,
             status = "in_progress"
         WHERE user_id = :user_id
           AND path_id = :path_id'
    );

    $stmt->execute([
        'current_mission_id' => (int) $nextMission['id'],
        'user_id' => $userId,
        'path_id' => (int) $currentMission['path_id'],
    ]);

    $this->initializeFirstObjectiveForMission($userId, (int) $nextMission['id']);
}

private function initializeFirstObjectiveForMission(int $userId, int $missionId): void
{
    $pdo = Database::connection();

    $stmt = $pdo->prepare(
        'SELECT id
         FROM objectives
         WHERE mission_id = :mission_id
         ORDER BY sort_order ASC
         LIMIT 1'
    );

    $stmt->execute([
        'mission_id' => $missionId,
    ]);

    $firstObjective = $stmt->fetch();

    if (!$firstObjective) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO user_objectives (user_id, objective_id, status)
         VALUES (:user_id, :objective_id, "available")
         ON DUPLICATE KEY UPDATE
            status = IF(status = "locked", "available", status)'
    );

    $stmt->execute([
        'user_id' => $userId,
        'objective_id' => (int) $firstObjective['id'],
    ]);
}

private function completePath(int $userId, int $pathId): void
{
    $pdo = Database::connection();

    $stmt = $pdo->prepare(
        'UPDATE user_paths
         SET status = "completed",
             completed_at = COALESCE(completed_at, NOW())
         WHERE user_id = :user_id
           AND path_id = :path_id'
    );

    $stmt->execute([
        'user_id' => $userId,
        'path_id' => $pathId,
    ]);
}

public function getSessionAnswers(int $sessionId): array
{
    $pdo = Database::connection();

    $stmt = $pdo->prepare(
        'SELECT
            qa.*,
            k.hira,
            k.kata,
            k.romaji
         FROM quiz_answers qa
         INNER JOIN kana k ON k.id = qa.kana_id
         WHERE qa.session_id = :session_id
         ORDER BY qa.question_order ASC'
    );

    $stmt->execute([
        'session_id' => $sessionId,
    ]);

    return $stmt->fetchAll();
}

public function getMissionIdFromSession(int $sessionId): ?int
{
    $pdo = Database::connection();

    $stmt = $pdo->prepare(
        'SELECT o.mission_id
         FROM quiz_sessions qs
         INNER JOIN objectives o ON o.id = qs.source_id
         WHERE qs.id = :session_id
           AND qs.source_type = "objective"
         LIMIT 1'
    );

    $stmt->execute([
        'session_id' => $sessionId,
    ]);

    $result = $stmt->fetch();

    return $result ? (int) $result['mission_id'] : null;
}
}