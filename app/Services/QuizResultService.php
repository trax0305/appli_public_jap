<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

final class QuizResultService
{
    public function buildResultContext(int $userId, int $sessionId): ?array
    {
        $session = $this->getSession($userId, $sessionId);

        if ($session === null) {
            return null;
        }

        $wrongAnswers = $this->getWrongAnswers($sessionId);
        $objectiveContext = $this->getObjectiveContext($userId, $session);
        $missionContext = $this->getMissionContext($userId, $objectiveContext);
        $pathContext = $this->getPathContext($userId, $missionContext);

        $score = (int) $session['score_percent'];

        return [
            'session' => $session,
            'score_label' => $this->getScoreLabel($score),
            'is_perfect' => $score === 100,
            'wrong_answers' => $wrongAnswers,
            'objective_context' => $objectiveContext,
            'mission_context' => $missionContext,
            'path_context' => $pathContext,
            'unlocked_badges' => $this->consumeNewBadges($sessionId),
        ];
    }

    private function getSession(int $userId, int $sessionId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM quiz_sessions
             WHERE id = :id
               AND user_id = :user_id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $sessionId,
            'user_id' => $userId,
        ]);

        $session = $stmt->fetch();

        return $session ?: null;
    }

    private function getWrongAnswers(int $sessionId): array
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
               AND qa.is_correct = 0
             ORDER BY qa.question_order ASC'
        );

        $stmt->execute(['session_id' => $sessionId]);

        return $stmt->fetchAll();
    }

    private function getObjectiveContext(int $userId, array $session): ?array
    {
        if (($session['source_type'] ?? '') !== 'objective') {
            return null;
        }

        $objectiveId = (int) ($session['source_id'] ?? 0);

        if ($objectiveId <= 0) {
            return null;
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                o.id,
                o.title,
                o.objective_type,
                o.required_success_count,
                o.sort_order,
                o.mission_id,
                COALESCE(uo.success_count, 0) AS success_count,
                COALESCE(uo.status, "locked") AS user_status
             FROM objectives o
             LEFT JOIN user_objectives uo
                ON uo.objective_id = o.id
               AND uo.user_id = :user_id
             WHERE o.id = :objective_id
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'objective_id' => $objectiveId,
        ]);

        $objective = $stmt->fetch();

        if (!$objective) {
            return null;
        }

        $nextTitle = null;

        $stmt = $pdo->prepare(
            'SELECT o2.title
             FROM objectives o2
             LEFT JOIN user_objectives uo2
                ON uo2.objective_id = o2.id
               AND uo2.user_id = :user_id
             WHERE o2.mission_id = :mission_id
               AND o2.sort_order > :sort_order
               AND COALESCE(uo2.status, "locked") IN ("available", "in_progress")
             ORDER BY o2.sort_order ASC
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'mission_id' => (int) $objective['mission_id'],
            'sort_order' => (int) $objective['sort_order'],
        ]);

        $next = $stmt->fetch();

        if ($next) {
            $nextTitle = (string) $next['title'];
        }

        $completed = ((string) $objective['user_status']) === 'completed';

        return [
            'objective_title' => (string) $objective['title'],
            'objective_type' => (string) $objective['objective_type'],
            'success_count' => (int) $objective['success_count'],
            'required_success_count' => (int) $objective['required_success_count'],
            'objective_completed' => $completed,
            'next_objective_title' => $nextTitle,
            'mission_id' => (int) $objective['mission_id'],
        ];
    }

    private function getMissionContext(int $userId, ?array $objectiveContext): ?array
    {
        if ($objectiveContext === null) {
            return null;
        }

        $missionId = (int) $objectiveContext['mission_id'];
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                m.id,
                m.title,
                m.sort_order,
                m.path_id,
                COALESCE(um.status, "locked") AS user_status
             FROM missions m
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

        if (!$mission) {
            return null;
        }

        $nextTitle = null;

        $stmt = $pdo->prepare(
            'SELECT m2.title
             FROM missions m2
             LEFT JOIN user_missions um2
                ON um2.mission_id = m2.id
               AND um2.user_id = :user_id
             WHERE m2.path_id = :path_id
               AND m2.sort_order > :sort_order
               AND m2.is_active = 1
               AND COALESCE(um2.status, "locked") IN ("available", "in_progress")
             ORDER BY m2.sort_order ASC
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'path_id' => (int) $mission['path_id'],
            'sort_order' => (int) $mission['sort_order'],
        ]);

        $next = $stmt->fetch();

        if ($next) {
            $nextTitle = (string) $next['title'];
        }

        return [
            'mission_title' => (string) $mission['title'],
            'mission_completed' => ((string) $mission['user_status']) === 'completed',
            'next_mission_title' => $nextTitle,
            'path_id' => (int) $mission['path_id'],
        ];
    }

    private function getPathContext(int $userId, ?array $missionContext): ?array
    {
        if ($missionContext === null) {
            return null;
        }

        $pathId = (int) $missionContext['path_id'];
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                lp.id,
                lp.title,
                lp.sort_order,
                COALESCE(up.status, "locked") AS user_status
             FROM learning_paths lp
             LEFT JOIN user_paths up
                ON up.path_id = lp.id
               AND up.user_id = :user_id
             WHERE lp.id = :path_id
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'path_id' => $pathId,
        ]);

        $path = $stmt->fetch();

        if (!$path) {
            return null;
        }

        $nextTitle = null;

        $stmt = $pdo->prepare(
            'SELECT lp2.title
             FROM learning_paths lp2
             LEFT JOIN user_paths up2
                ON up2.path_id = lp2.id
               AND up2.user_id = :user_id
             WHERE lp2.sort_order > :sort_order
               AND lp2.is_active = 1
               AND COALESCE(up2.status, "locked") IN ("available", "in_progress")
             ORDER BY lp2.sort_order ASC
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'sort_order' => (int) $path['sort_order'],
        ]);

        $next = $stmt->fetch();

        if ($next) {
            $nextTitle = (string) $next['title'];
        }

        return [
            'path_title' => (string) $path['title'],
            'path_completed' => ((string) $path['user_status']) === 'completed',
            'next_path_title' => $nextTitle,
        ];
    }

    private function getScoreLabel(int $score): string
    {
        if ($score === 100) {
            return 'Parfait.';
        }

        if ($score >= 80) {
            return 'Très bien, continue comme ça.';
        }

        return 'Pas encore, refais les erreurs puis réessaie.';
    }

    private function consumeNewBadges(int $sessionId): array
    {
        $badges = Session::get('new_badges_quiz_' . $sessionId, []);
        Session::forget('new_badges_quiz_' . $sessionId);

        return is_array($badges) ? $badges : [];
    }
}
