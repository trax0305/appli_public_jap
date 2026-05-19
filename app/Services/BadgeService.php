<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class BadgeService
{
    public function unlockBadgeByCode(int $userId, string $badgeCode): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM badges
             WHERE code = :code
             LIMIT 1'
        );

        $stmt->execute(['code' => $badgeCode]);
        $badge = $stmt->fetch();

        if (!$badge) {
            return null;
        }

        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO user_badges (user_id, badge_id)
             VALUES (:user_id, :badge_id)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'badge_id' => (int) $badge['id'],
        ]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        return [
            'code' => $badge['code'],
            'title' => $badge['title'],
            'description' => $badge['description'],
            'icon' => $badge['icon'],
        ];
    }

    public function checkQuizBadges(int $userId): array
    {
        $unlocked = [];

        $completed = $this->getCompletedQuizCount($userId);
        $perfect = $this->getPerfectQuizCount($userId);

        if ($completed >= 1) {
            $this->pushIfUnlocked($unlocked, $this->unlockBadgeByCode($userId, 'first_quiz'));
        }

        if ($perfect >= 1) {
            $this->pushIfUnlocked($unlocked, $this->unlockBadgeByCode($userId, 'first_perfect_score'));
        }

        if ($perfect >= 3) {
            $this->pushIfUnlocked($unlocked, $this->unlockBadgeByCode($userId, 'three_perfect_quizzes'));
        }

        if ($perfect >= 10) {
            $this->pushIfUnlocked($unlocked, $this->unlockBadgeByCode($userId, 'ten_perfect_quizzes'));
        }

        if ($this->hasPerfectEvaluation($userId)) {
            $this->pushIfUnlocked($unlocked, $this->unlockBadgeByCode($userId, 'evaluation_clean'));
        }

        return $unlocked;
    }

    public function checkObjectiveBadges(int $userId, array $objective): array
    {
        $unlocked = [];

        if (($objective['objective_type'] ?? '') === 'written') {
            $this->pushIfUnlocked($unlocked, $this->unlockBadgeByCode($userId, 'written_master'));
        }

        return $unlocked;
    }

    public function checkPathBadges(int $userId, string $pathCode): array
    {
        $unlocked = [];

        $map = [
            'hiragana_base' => 'hiragana_base_complete',
            'katakana_base' => 'katakana_base_complete',
            'final_kana_review' => 'all_kana_complete',
        ];

        if (!isset($map[$pathCode])) {
            return $unlocked;
        }

        $this->pushIfUnlocked($unlocked, $this->unlockBadgeByCode($userId, $map[$pathCode]));

        return $unlocked;
    }

    private function pushIfUnlocked(array &$stack, ?array $badge): void
    {
        if ($badge !== null) {
            $stack[] = $badge;
        }
    }

    private function getCompletedQuizCount(int $userId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM quiz_sessions
             WHERE user_id = :user_id
               AND completed_at IS NOT NULL'
        );

        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    private function getPerfectQuizCount(int $userId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM quiz_sessions
             WHERE user_id = :user_id
               AND completed_at IS NOT NULL
               AND score_percent = 100'
        );

        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    private function hasPerfectEvaluation(int $userId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM quiz_sessions qs
             INNER JOIN objectives o ON o.id = qs.source_id
             WHERE qs.user_id = :user_id
               AND qs.completed_at IS NOT NULL
               AND qs.score_percent = 100
               AND qs.source_type = "objective"
               AND o.objective_type = "evaluation"'
        );

        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0) >= 1;
    }
}
