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
