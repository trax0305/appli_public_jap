<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class StatsService
{
    public function recordKanaAnswer(?int $userId, int $sessionId, int $kanaId, bool $isCorrect, string $displayedValue): void
    {
        if ($userId === null || $userId <= 0) {
            return;
        }

        $pdo = Database::connection();

        $session = $this->getSession($sessionId);

        if ($session === null) {
            return;
        }

        $kanaSet = $this->resolveKanaSet((string) $session['kana_set'], $kanaId, $displayedValue);

        $stmt = $pdo->prepare(
            'INSERT INTO user_kana_stats (
                user_id,
                kana_id,
                kana_set,
                seen_count,
                correct_count,
                wrong_count,
                current_streak,
                best_streak,
                mastery_score,
                last_seen_at,
                last_wrong_at
            ) VALUES (
                :user_id,
                :kana_id,
                :kana_set,
                1,
                :correct_increment,
                :wrong_increment,
                :initial_current_streak,
                :initial_best_streak,
                :initial_mastery_score,
                NOW(),
                :initial_last_wrong_at
            )
            ON DUPLICATE KEY UPDATE
                seen_count = seen_count + 1,
                correct_count = correct_count + VALUES(correct_count),
                wrong_count = wrong_count + VALUES(wrong_count),
                current_streak = IF(VALUES(correct_count) = 1, current_streak + 1, 0),
                best_streak = GREATEST(best_streak, IF(VALUES(correct_count) = 1, current_streak + 1, 0)),
                mastery_score = ROUND(((correct_count + VALUES(correct_count)) / (seen_count + 1)) * 100),
                last_seen_at = NOW(),
                last_wrong_at = IF(VALUES(wrong_count) = 1, NOW(), last_wrong_at)'
        );

        $correctIncrement = $isCorrect ? 1 : 0;
        $wrongIncrement = $isCorrect ? 0 : 1;

        $stmt->execute([
            'user_id' => $userId,
            'kana_id' => $kanaId,
            'kana_set' => $kanaSet,
            'correct_increment' => $correctIncrement,
            'wrong_increment' => $wrongIncrement,
            'initial_current_streak' => $isCorrect ? 1 : 0,
            'initial_best_streak' => $isCorrect ? 1 : 0,
            'initial_mastery_score' => $isCorrect ? 100 : 0,
            'initial_last_wrong_at' => $isCorrect ? null : date('Y-m-d H:i:s'),
        ]);
    }

    public function getStatsPageData(int $userId): array
    {
        $global = $this->getGlobalProgress($userId);
        $alphabet = $this->getAlphabetProgress($userId);

        return [
            'global' => $global,
            'alphabet' => $alphabet,
            'frequent_errors' => $this->getFrequentErrors($userId),
            'weakest_kana' => $this->getWeakestKana($userId),
            'streak_summary' => $this->getStreakSummary($userId),
            'badges' => $this->getUserBadges($userId),
            'has_any_stat' => (int) $global['kana_seen'] > 0,
        ];
    }

    private function getGlobalProgress(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(COUNT(*), 0) AS total_quiz_completed,
                COALESCE(ROUND(AVG(score_percent)), 0) AS average_score
             FROM quiz_sessions
             WHERE user_id = :user_id
               AND completed_at IS NOT NULL'
        );

        $stmt->execute(['user_id' => $userId]);
        $quiz = $stmt->fetch() ?: [];

        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(COUNT(*), 0) AS kana_seen,
                COALESCE(SUM(CASE WHEN mastery_score >= 90 AND seen_count >= 3 THEN 1 ELSE 0 END), 0) AS kana_mastered
             FROM user_kana_stats
             WHERE user_id = :user_id
               AND seen_count > 0'
        );

        $stmt->execute(['user_id' => $userId]);
        $kana = $stmt->fetch() ?: [];

        return [
            'total_quiz_completed' => (int) ($quiz['total_quiz_completed'] ?? 0),
            'average_score' => (int) ($quiz['average_score'] ?? 0),
            'kana_seen' => (int) ($kana['kana_seen'] ?? 0),
            'kana_mastered' => (int) ($kana['kana_mastered'] ?? 0),
        ];
    }

    private function getAlphabetProgress(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                uks.kana_set,
                SUM(CASE WHEN uks.seen_count > 0 THEN 1 ELSE 0 END) AS seen_count,
                SUM(CASE WHEN uks.mastery_score >= 90 AND uks.seen_count >= 3 THEN 1 ELSE 0 END) AS mastered_count
             FROM user_kana_stats uks
             WHERE uks.user_id = :user_id
             GROUP BY uks.kana_set'
        );

        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        $progress = [
            'hiragana' => ['total' => $this->getKanaTotalBySet('hiragana'), 'seen' => 0, 'mastered' => 0, 'mastery_percent' => 0],
            'katakana' => ['total' => $this->getKanaTotalBySet('katakana'), 'seen' => 0, 'mastered' => 0, 'mastery_percent' => 0],
        ];

        foreach ($rows as $row) {
            $set = (string) $row['kana_set'];

            if (!isset($progress[$set])) {
                continue;
            }

            $progress[$set]['seen'] = (int) $row['seen_count'];
            $progress[$set]['mastered'] = (int) $row['mastered_count'];
        }

        foreach ($progress as $set => $data) {
            $total = (int) $data['total'];
            $mastered = (int) $data['mastered'];

            $progress[$set]['mastery_percent'] = $total > 0
                ? (int) round(($mastered / $total) * 100)
                : 0;
        }

        return $progress;
    }

    private function getKanaTotalBySet(string $kanaSet): int
    {
        $pdo = Database::connection();

        $column = $kanaSet === 'katakana' ? 'kata' : 'hira';

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total FROM kana WHERE {$column} IS NOT NULL AND {$column} <> ''"
        );

        $stmt->execute();

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    private function getFrequentErrors(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                uks.kana_set,
                uks.wrong_count,
                uks.mastery_score,
                k.hira,
                k.kata,
                k.romaji
             FROM user_kana_stats uks
             INNER JOIN kana k ON k.id = uks.kana_id
             WHERE uks.user_id = :user_id
               AND uks.wrong_count > 0
             ORDER BY uks.wrong_count DESC, uks.mastery_score ASC
             LIMIT 10'
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    private function getWeakestKana(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                uks.kana_set,
                uks.seen_count,
                uks.wrong_count,
                uks.mastery_score,
                k.hira,
                k.kata,
                k.romaji
             FROM user_kana_stats uks
             INNER JOIN kana k ON k.id = uks.kana_id
             WHERE uks.user_id = :user_id
               AND uks.seen_count > 0
             ORDER BY uks.mastery_score ASC, uks.wrong_count DESC, uks.seen_count DESC
             LIMIT 10'
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    private function getStreakSummary(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(MAX(best_streak), 0) AS best_streak_global,
                COALESCE(SUM(CASE WHEN current_streak >= 3 THEN 1 ELSE 0 END), 0) AS kana_with_streak_3
             FROM user_kana_stats
             WHERE user_id = :user_id'
        );

        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch() ?: [];

        return [
            'best_streak_global' => (int) ($result['best_streak_global'] ?? 0),
            'kana_with_streak_3' => (int) ($result['kana_with_streak_3'] ?? 0),
        ];
    }

    private function getUserBadges(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
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

    private function getSession(int $sessionId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, kana_set
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

    private function resolveKanaSet(string $sessionKanaSet, int $kanaId, string $displayedValue): string
    {
        if ($sessionKanaSet === 'hiragana' || $sessionKanaSet === 'katakana') {
            return $sessionKanaSet;
        }

        $kana = $this->getKana($kanaId);

        if ($kana !== null) {
            if ($displayedValue === $kana['hira']) {
                return 'hiragana';
            }

            if ($displayedValue === $kana['kata']) {
                return 'katakana';
            }
        }

        return 'hiragana';
    }

    private function getKana(int $kanaId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, hira, kata
             FROM kana
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $kanaId,
        ]);

        $kana = $stmt->fetch();

        return $kana ?: null;
    }
}
