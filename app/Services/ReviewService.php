<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ReviewService
{
    public function getReviewPageData(int $userId): array
    {
        $recommended = $this->getRecommendedKana($userId, 10);
        $frequentErrors = $this->getFrequentErrors($userId, 6);
        $recentSeen = $this->getRecentSeenKana($userId, 6);

        return [
            'recommended_kana' => $recommended,
            'frequent_errors' => $frequentErrors,
            'recent_seen' => $recentSeen,
            'can_start_review' => count($recommended) > 0,
        ];
    }

    public function startSmartReview(int $userId): int
    {
        $selectedKana = $this->getRecommendedKana($userId, 10);

        if ($selectedKana === []) {
            throw new \RuntimeException('Aucune statistique disponible pour lancer la révision.');
        }

        $direction = $this->resolveDirection($userId);
        $kanaSet = $this->resolveKanaSet($selectedKana);

        $generator = new QuizGeneratorService();

        return $generator->startSmartReviewQuiz($userId, $selectedKana, $direction, $kanaSet);
    }

    private function getRecommendedKana(int $userId, int $limit): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                uks.kana_set,
                uks.seen_count,
                uks.wrong_count,
                uks.mastery_score,
                uks.last_wrong_at,
                uks.last_seen_at,
                k.id,
                k.hira,
                k.kata,
                k.romaji
             FROM user_kana_stats uks
             INNER JOIN kana k ON k.id = uks.kana_id
             WHERE uks.user_id = :user_id
               AND uks.seen_count > 0
               AND (uks.mastery_score < 90 OR uks.wrong_count > 0)
             ORDER BY uks.mastery_score ASC,
                      uks.wrong_count DESC,
                      uks.last_wrong_at DESC,
                      uks.last_seen_at ASC
             LIMIT :limit'
        );

        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $priority = $stmt->fetchAll();

        if (count($priority) >= $limit) {
            return $priority;
        }

        $existingIds = array_map(static fn (array $row): int => (int) $row['id'], $priority);

        $recent = $this->getRecentSeenKana($userId, $limit * 2);

        foreach ($recent as $row) {
            if (count($priority) >= $limit) {
                break;
            }

            if (in_array((int) $row['id'], $existingIds, true)) {
                continue;
            }

            $priority[] = $row;
            $existingIds[] = (int) $row['id'];
        }

        return $priority;
    }

    private function getFrequentErrors(int $userId, int $limit): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                uks.kana_set,
                uks.wrong_count,
                uks.mastery_score,
                k.id,
                k.hira,
                k.kata,
                k.romaji
             FROM user_kana_stats uks
             INNER JOIN kana k ON k.id = uks.kana_id
             WHERE uks.user_id = :user_id
               AND uks.wrong_count > 0
             ORDER BY uks.wrong_count DESC, uks.mastery_score ASC
             LIMIT :limit'
        );

        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function getRecentSeenKana(int $userId, int $limit): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                uks.kana_set,
                uks.seen_count,
                uks.wrong_count,
                uks.mastery_score,
                uks.last_seen_at,
                k.id,
                k.hira,
                k.kata,
                k.romaji
             FROM user_kana_stats uks
             INNER JOIN kana k ON k.id = uks.kana_id
             WHERE uks.user_id = :user_id
               AND uks.seen_count > 0
             ORDER BY uks.last_seen_at DESC
             LIMIT :limit'
        );

        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function resolveDirection(int $userId): string
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM user_kana_stats
             WHERE user_id = :user_id
               AND seen_count >= 3'
        );

        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();

        return ((int) ($result['total'] ?? 0) >= 5) ? 'written' : 'kana_to_romaji';
    }

    private function resolveKanaSet(array $kana): string
    {
        $sets = array_values(array_unique(array_map(static fn (array $row): string => (string) $row['kana_set'], $kana)));

        if ($sets === ['hiragana']) {
            return 'hiragana';
        }

        if ($sets === ['katakana']) {
            return 'katakana';
        }

        return 'mixed';
    }
}
