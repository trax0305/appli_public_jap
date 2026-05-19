<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class FreePracticeService
{
    public function getPageData(): array
    {
        return [
            'alphabet_options' => [
                'hiragana' => 'Hiragana',
                'katakana' => 'Katakana',
                'mixed' => 'Les deux',
            ],
            'quick_filters' => [
                'vowels' => 'Voyelles',
                'K' => 'Groupe K',
                'S' => 'Groupe S',
                'T' => 'Groupe T',
                'N' => 'Groupe N',
                'H' => 'Groupe H',
                'M' => 'Groupe M',
                'R' => 'Groupe R',
                'Y_W_N' => 'Y / W / ん',
                'variants' => 'Variantes',
                'combos' => 'Combinaisons',
                'all' => 'Tout',
            ],
            'training_types' => [
                'kana_to_romaji' => 'Kana vers romaji',
                'romaji_to_kana' => 'Romaji vers kana',
                'written' => 'Écrit',
            ],
            'question_counts' => [10, 20, 30],
        ];
    }

    public function startFreePracticeQuiz(int $userId, array $input): int
    {
        $config = $this->sanitizeConfig($input);

        $selectedKana = $this->selectKana($userId, $config);

        if ($selectedKana === []) {
            throw new \RuntimeException('Aucun kana trouvé pour cette sélection.');
        }

        $generator = new QuizGeneratorService();

        return $generator->startFreeQuiz($userId, $selectedKana, $config);
    }

    private function sanitizeConfig(array $input): array
    {
        $alphabet = (string) ($input['alphabet'] ?? 'hiragana');

        if (!in_array($alphabet, ['hiragana', 'katakana', 'mixed'], true)) {
            $alphabet = 'hiragana';
        }

        $direction = (string) ($input['direction'] ?? 'kana_to_romaji');

        if (!in_array($direction, ['kana_to_romaji', 'romaji_to_kana', 'written'], true)) {
            $direction = 'kana_to_romaji';
        }

        $quickFilters = $input['quick_filters'] ?? [];

        if (!is_array($quickFilters)) {
            $quickFilters = [];
        }

        $allowed = ['vowels', 'K', 'S', 'T', 'N', 'H', 'M', 'R', 'Y_W_N', 'variants', 'combos', 'all'];
        $quickFilters = array_values(array_intersect($allowed, $quickFilters));

        if ($quickFilters === []) {
            $quickFilters = ['vowels'];
        }

        $questionCount = (string) ($input['question_count'] ?? '20');
        $customCount = isset($input['custom_count']) ? (int) $input['custom_count'] : 20;

        $count = match ($questionCount) {
            '10' => 10,
            '20' => 20,
            '30' => 30,
            'custom' => ($customCount >= 5 && $customCount <= 100) ? $customCount : 20,
            default => 20,
        };

        $includeWrong = (($input['include_wrong'] ?? '0') === '1');
        $optionsScope = (string) ($input['options_scope'] ?? 'selected');

        if (!in_array($optionsScope, ['selected', 'learned'], true)) {
            $optionsScope = 'selected';
        }

        return [
            'alphabet' => $alphabet,
            'direction' => $direction,
            'quick_filters' => $quickFilters,
            'question_count' => $count,
            'question_count_mode' => $questionCount,
            'include_wrong' => $includeWrong,
            'options_scope' => $optionsScope,
        ];
    }

    private function selectKana(int $userId, array $config): array
    {
        $baseKana = $this->fetchKanaByFilters($config['quick_filters']);

        if ($baseKana === []) {
            return [];
        }

        $byId = [];

        foreach ($baseKana as $row) {
            $byId[(int) $row['id']] = $row;
        }

        if ($config['include_wrong']) {
            foreach ($this->fetchFrequentWrongKana($userId, 10) as $row) {
                $byId[(int) $row['id']] = $row;
            }
        }

        return array_values($byId);
    }

    private function fetchKanaByFilters(array $quickFilters): array
    {
        $pdo = Database::connection();

        if (in_array('all', $quickFilters, true)) {
            $stmt = $pdo->prepare(
                'SELECT k.*
                 FROM kana k
                 ORDER BY k.sort_order ASC'
            );
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $conditions = [];
        $params = [];

        $groupMap = [
            'vowels' => ['VOWEL'],
            'K' => ['K'],
            'S' => ['S'],
            'T' => ['T'],
            'N' => ['N'],
            'H' => ['H'],
            'M' => ['M'],
            'R' => ['R'],
            'Y_W_N' => ['Y', 'W', 'N_FINAL'],
        ];

        foreach ($quickFilters as $index => $filter) {
            if ($filter === 'variants') {
                $conditions[] = 'k.is_variant = 1';
                continue;
            }

            if ($filter === 'combos') {
                $conditions[] = 'k.is_combo = 1';
                continue;
            }

            if (!isset($groupMap[$filter])) {
                continue;
            }

            $codes = $groupMap[$filter];
            $placeholders = [];

            foreach ($codes as $codeIndex => $code) {
                $key = ':g_' . $index . '_' . $codeIndex;
                $placeholders[] = $key;
                $params[$key] = $code;
            }

            $conditions[] = 'cg.code IN (' . implode(', ', $placeholders) . ')';
        }

        if ($conditions === []) {
            return [];
        }

        $sql = 'SELECT DISTINCT k.*
                FROM kana k
                LEFT JOIN consonant_groups cg ON cg.id = k.consonant_group_id
                WHERE (' . implode(' OR ', $conditions) . ')
                ORDER BY k.sort_order ASC';

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function fetchFrequentWrongKana(int $userId, int $limit): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT k.*
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
}
