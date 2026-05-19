<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class QuizGeneratorService
{
    public function startObjectiveQuiz(int $userId, int $objectiveId): int
    {
        $pdo = Database::connection();

        $objective = $this->getObjective($objectiveId);

        if ($objective === null) {
            throw new \RuntimeException('Objectif introuvable.');
        }

        $kana = $this->getKanaForObjective($objective);

        if ($kana === []) {
            throw new \RuntimeException('Aucun kana trouvé pour cet objectif.');
        }

        $direction = $this->resolveDirection($objective);
        $questionCount = $this->resolveQuestionCount($objective, count($kana));

        $questions = $this->generateQuestions(
            $kana,
            $questionCount,
            $direction,
            $objective['kana_set']
        );

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO quiz_sessions (
                    user_id,
                    mode,
                    source_type,
                    source_id,
                    kana_set,
                    direction,
                    total_questions,
                    settings_json
                ) VALUES (
                    :user_id,
                    :mode,
                    :source_type,
                    :source_id,
                    :kana_set,
                    :direction,
                    :total_questions,
                    :settings_json
                )'
            );

            $stmt->execute([
                'user_id' => $userId,
                'mode' => 'mission',
                'source_type' => 'objective',
                'source_id' => $objectiveId,
                'kana_set' => $objective['kana_set'],
                'direction' => $direction,
                'total_questions' => count($questions),
                'settings_json' => json_encode([
                    'question_count_mode' => $objective['question_count_mode'],
                    'character_scope' => $objective['character_scope'],
                    'objective_code' => $objective['code'],
                    'objective_type' => $objective['objective_type'],
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $sessionId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO quiz_answers (
                    session_id,
                    kana_id,
                    question_order,
                    displayed_value,
                    expected_answer,
                    options_json
                ) VALUES (
                    :session_id,
                    :kana_id,
                    :question_order,
                    :displayed_value,
                    :expected_answer,
                    :options_json
                )'
            );

            foreach ($questions as $index => $question) {
                $stmt->execute([
                    'session_id' => $sessionId,
                    'kana_id' => (int) $question['kana_id'],
                    'question_order' => $index + 1,
                    'displayed_value' => $question['displayed_value'],
                    'expected_answer' => $question['expected_answer'],
                    'options_json' => json_encode($question['options'], JSON_UNESCAPED_UNICODE),
                ]);
            }

            $pdo->commit();

            return $sessionId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }



    public function startFreeQuiz(int $userId, array $selectedKana, array $config): int
    {
        if ($selectedKana === []) {
            throw new \RuntimeException('Aucun kana disponible pour ce quiz libre.');
        }

        $pdo = Database::connection();

        $direction = (string) ($config['direction'] ?? 'kana_to_romaji');
        $kanaSet = (string) ($config['alphabet'] ?? 'hiragana');
        $questionCount = max(1, (int) ($config['question_count'] ?? 20));

        $questions = $this->generateQuestions(
            $selectedKana,
            $questionCount,
            $direction,
            $kanaSet
        );

        $optionsKanaPool = $selectedKana;

        if (($config['options_scope'] ?? 'selected') === 'learned') {
            $learnedKana = $this->getLearnedKanaForUser($userId);

            if ($learnedKana !== []) {
                $optionsKanaPool = $learnedKana;
            }
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO quiz_sessions (
                    user_id,
                    mode,
                    source_type,
                    source_id,
                    kana_set,
                    direction,
                    total_questions,
                    settings_json
                ) VALUES (
                    :user_id,
                    :mode,
                    :source_type,
                    :source_id,
                    :kana_set,
                    :direction,
                    :total_questions,
                    :settings_json
                )'
            );

            $stmt->execute([
                'user_id' => $userId,
                'mode' => 'free',
                'source_type' => 'free',
                'source_id' => null,
                'kana_set' => $kanaSet,
                'direction' => $direction,
                'total_questions' => count($questions),
                'settings_json' => json_encode([
                    'quick_filters' => $config['quick_filters'] ?? [],
                    'question_count' => $questionCount,
                    'question_count_mode' => $config['question_count_mode'] ?? '20',
                    'include_wrong' => (bool) ($config['include_wrong'] ?? false),
                    'options_scope' => $config['options_scope'] ?? 'selected',
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $sessionId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO quiz_answers (
                    session_id,
                    kana_id,
                    question_order,
                    displayed_value,
                    expected_answer,
                    options_json
                ) VALUES (
                    :session_id,
                    :kana_id,
                    :question_order,
                    :displayed_value,
                    :expected_answer,
                    :options_json
                )'
            );

            foreach ($questions as $index => $question) {
                $options = $this->generateOptions(
                    $optionsKanaPool,
                    (string) $question['expected_answer'],
                    $direction,
                    $kanaSet
                );

                $stmt->execute([
                    'session_id' => $sessionId,
                    'kana_id' => (int) $question['kana_id'],
                    'question_order' => $index + 1,
                    'displayed_value' => $question['displayed_value'],
                    'expected_answer' => $question['expected_answer'],
                    'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
                ]);
            }

            $pdo->commit();

            return $sessionId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function startGuestFreeQuiz(): int
    {
        $pdo = Database::connection();
        $kanaSet = 'hiragana';
        $direction = 'kana_to_romaji';
        $questionCount = 5;
        $selectedKana = $this->getGuestVowelKana();

        if ($selectedKana === []) {
            throw new \RuntimeException('Aucun kana disponible pour le quiz invité.');
        }

        $questions = $this->generateQuestions(
            $selectedKana,
            $questionCount,
            $direction,
            $kanaSet
        );

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO quiz_sessions (
                    user_id,
                    mode,
                    source_type,
                    source_id,
                    kana_set,
                    direction,
                    total_questions,
                    settings_json
                ) VALUES (
                    NULL,
                    :mode,
                    :source_type,
                    NULL,
                    :kana_set,
                    :direction,
                    :total_questions,
                    :settings_json
                )'
            );

            $stmt->execute([
                'mode' => 'free',
                'source_type' => 'free',
                'kana_set' => $kanaSet,
                'direction' => $direction,
                'total_questions' => count($questions),
                'settings_json' => json_encode([
                    'guest' => true,
                    'quick_filters' => ['vowels'],
                    'question_count' => $questionCount,
                    'question_count_mode' => '5',
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $sessionId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO quiz_answers (
                    session_id,
                    kana_id,
                    question_order,
                    displayed_value,
                    expected_answer,
                    options_json
                ) VALUES (
                    :session_id,
                    :kana_id,
                    :question_order,
                    :displayed_value,
                    :expected_answer,
                    :options_json
                )'
            );

            foreach ($questions as $index => $question) {
                $stmt->execute([
                    'session_id' => $sessionId,
                    'kana_id' => (int) $question['kana_id'],
                    'question_order' => $index + 1,
                    'displayed_value' => $question['displayed_value'],
                    'expected_answer' => $question['expected_answer'],
                    'options_json' => json_encode($question['options'], JSON_UNESCAPED_UNICODE),
                ]);
            }

            $pdo->commit();

            return $sessionId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function startSmartReviewQuiz(int $userId, array $selectedKana, string $direction, string $kanaSet): int
    {
        if ($selectedKana === []) {
            throw new \RuntimeException('Aucun kana sélectionné pour la révision.');
        }

        $pdo = Database::connection();

        $questions = $this->generateQuestions(
            $selectedKana,
            count($selectedKana),
            $direction,
            $kanaSet
        );

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO quiz_sessions (
                    user_id,
                    mode,
                    source_type,
                    source_id,
                    kana_set,
                    direction,
                    total_questions,
                    settings_json
                ) VALUES (
                    :user_id,
                    :mode,
                    :source_type,
                    :source_id,
                    :kana_set,
                    :direction,
                    :total_questions,
                    :settings_json
                )'
            );

            $stmt->execute([
                'user_id' => $userId,
                'mode' => 'review',
                'source_type' => 'review',
                'source_id' => null,
                'kana_set' => $kanaSet,
                'direction' => $direction,
                'total_questions' => count($questions),
                'settings_json' => json_encode([
                    'review_type' => 'smart_review',
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $sessionId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO quiz_answers (
                    session_id,
                    kana_id,
                    question_order,
                    displayed_value,
                    expected_answer,
                    options_json
                ) VALUES (
                    :session_id,
                    :kana_id,
                    :question_order,
                    :displayed_value,
                    :expected_answer,
                    :options_json
                )'
            );

            foreach ($questions as $index => $question) {
                $stmt->execute([
                    'session_id' => $sessionId,
                    'kana_id' => (int) $question['kana_id'],
                    'question_order' => $index + 1,
                    'displayed_value' => $question['displayed_value'],
                    'expected_answer' => $question['expected_answer'],
                    'options_json' => json_encode($question['options'], JSON_UNESCAPED_UNICODE),
                ]);
            }

            $pdo->commit();

            return $sessionId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function resolveDirection(array $objective): string
    {
        if (
            $objective['objective_type'] === 'written'
            || $objective['objective_type'] === 'evaluation'
        ) {
            return 'written';
        }

        return $objective['quiz_direction'] ?? 'kana_to_romaji';
    }

    private function getObjective(int $objectiveId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT
                o.*,
                m.id AS mission_id,
                m.path_id,
                lp.kana_set
             FROM objectives o
             INNER JOIN missions m ON m.id = o.mission_id
             INNER JOIN learning_paths lp ON lp.id = m.path_id
             WHERE o.id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $objectiveId,
        ]);

        $objective = $stmt->fetch();

        return $objective ?: null;
    }

    private function getKanaForObjective(array $objective): array
    {
        return match ($objective['character_scope']) {
            'current_mission' => $this->getKanaForCurrentMission((int) $objective['mission_id']),
            'current_and_previous_missions' => $this->getKanaForCurrentAndPreviousMissions($objective),
            'current_path' => $this->getKanaForCurrentPath((int) $objective['path_id']),
            'completed_paths' => $this->getKanaForCurrentPath((int) $objective['path_id']),
            default => $this->getKanaForCurrentMission((int) $objective['mission_id']),
        };
    }

    private function getKanaForCurrentMission(int $missionId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT DISTINCT k.*
             FROM mission_kana mk
             INNER JOIN kana k ON k.id = mk.kana_id
             WHERE mk.mission_id = :mission_id
             ORDER BY k.sort_order ASC'
        );

        $stmt->execute([
            'mission_id' => $missionId,
        ]);

        return $stmt->fetchAll();
    }

    private function getKanaForCurrentAndPreviousMissions(array $objective): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT DISTINCT k.*
             FROM missions m
             INNER JOIN mission_kana mk ON mk.mission_id = m.id
             INNER JOIN kana k ON k.id = mk.kana_id
             WHERE m.path_id = :path_id
               AND m.sort_order <= (
                    SELECT sort_order
                    FROM missions
                    WHERE id = :mission_id
               )
             ORDER BY k.sort_order ASC'
        );

        $stmt->execute([
            'path_id' => (int) $objective['path_id'],
            'mission_id' => (int) $objective['mission_id'],
        ]);

        return $stmt->fetchAll();
    }

    private function getKanaForCurrentPath(int $pathId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT DISTINCT k.*
             FROM missions m
             INNER JOIN mission_kana mk ON mk.mission_id = m.id
             INNER JOIN kana k ON k.id = mk.kana_id
             WHERE m.path_id = :path_id
               AND m.is_active = 1
             ORDER BY k.sort_order ASC'
        );

        $stmt->execute([
            'path_id' => $pathId,
        ]);

        return $stmt->fetchAll();
    }

    private function resolveQuestionCount(array $objective, int $kanaCount): int
    {
        if ($objective['question_count_mode'] === 'one_per_character') {
            return $kanaCount;
        }

        return max(1, (int) $objective['question_count']);
    }

    private function generateQuestions(array $kana, int $questionCount, string $direction, string $kanaSet): array
    {
        $questions = [];

        if ($questionCount === count($kana)) {
            $selectedKana = $kana;
            shuffle($selectedKana);
        } else {
            $selectedKana = [];

            for ($i = 0; $i < $questionCount; $i++) {
                $selectedKana[] = $kana[array_rand($kana)];
            }
        }

        foreach ($selectedKana as $item) {
            $displayedValue = $this->getDisplayedValue($item, $direction, $kanaSet);
            $expectedAnswer = $this->getExpectedAnswer($item, $direction, $kanaSet);
            $options = $this->generateOptions($kana, $expectedAnswer, $direction, $kanaSet);

            $questions[] = [
                'kana_id' => (int) $item['id'],
                'displayed_value' => $displayedValue,
                'expected_answer' => $expectedAnswer,
                'options' => $options,
            ];
        }

        return $questions;
    }

    private function getDisplayedValue(array $kana, string $direction, string $kanaSet): string
    {
        if ($direction === 'romaji_to_kana') {
            return $kana['romaji'];
        }

        return $this->resolveKanaSymbol($kana, $kanaSet);
    }

    private function getExpectedAnswer(array $kana, string $direction, string $kanaSet): string
    {
        if ($direction === 'romaji_to_kana') {
            return $this->resolveKanaSymbol($kana, $kanaSet);
        }

        return $kana['romaji'];
    }

    private function resolveKanaSymbol(array $kana, string $kanaSet): string
    {
        if ($kanaSet === 'katakana') {
            return $kana['kata'];
        }

        if ($kanaSet === 'mixed') {
            return random_int(0, 1) === 1 ? $kana['kata'] : $kana['hira'];
        }

        return $kana['hira'];
    }

    private function generateOptions(array $allKana, string $expectedAnswer, string $direction, string $kanaSet): array
    {
        if ($direction === 'written') {
            return [];
        }

        $pool = [];

        foreach ($allKana as $kana) {
            $pool[] = $direction === 'romaji_to_kana'
                ? $this->resolveKanaSymbol($kana, $kanaSet)
                : $kana['romaji'];
        }

        $pool = array_values(array_unique($pool));
        $pool = array_values(array_filter($pool, fn (string $value): bool => $value !== $expectedAnswer));

        shuffle($pool);

        $options = array_slice($pool, 0, 3);
        $options[] = $expectedAnswer;

        shuffle($options);

        return $options;
    }

    private function getGuestVowelKana(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT *
             FROM kana
             WHERE romaji IN ("a", "i", "u", "e", "o")
               AND is_variant = 0
               AND is_combo = 0
             ORDER BY sort_order ASC'
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function startErrorReviewQuiz(int $userId, int $sourceSessionId): int
{
    $pdo = Database::connection();

    $sourceSession = $this->getSourceSessionForErrorReview($userId, $sourceSessionId);

    if ($sourceSession === null) {
        throw new \RuntimeException('Session source introuvable.');
    }

    $wrongAnswers = $this->getWrongAnswersFromSession($sourceSessionId);

    if ($wrongAnswers === []) {
        throw new \RuntimeException('Aucune erreur à revoir.');
    }

    $allKana = $this->getKanaFromSession($sourceSessionId);

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO quiz_sessions (
                user_id,
                mode,
                source_type,
                source_id,
                kana_set,
                direction,
                total_questions,
                settings_json
            ) VALUES (
                :user_id,
                :mode,
                :source_type,
                :source_id,
                :kana_set,
                :direction,
                :total_questions,
                :settings_json
            )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'mode' => 'review',
            'source_type' => 'review',
            'source_id' => $sourceSessionId,
            'kana_set' => $sourceSession['kana_set'],
            'direction' => $sourceSession['direction'],
            'total_questions' => count($wrongAnswers),
            'settings_json' => json_encode([
                'review_type' => 'wrong_answers',
                'source_session_id' => $sourceSessionId,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $newSessionId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO quiz_answers (
                session_id,
                kana_id,
                question_order,
                displayed_value,
                expected_answer,
                options_json
            ) VALUES (
                :session_id,
                :kana_id,
                :question_order,
                :displayed_value,
                :expected_answer,
                :options_json
            )'
        );

        foreach ($wrongAnswers as $index => $answer) {
            $options = $this->generateOptions(
                $allKana,
                $answer['expected_answer'],
                $sourceSession['direction'],
                $sourceSession['kana_set']
            );

            $stmt->execute([
                'session_id' => $newSessionId,
                'kana_id' => (int) $answer['kana_id'],
                'question_order' => $index + 1,
                'displayed_value' => $answer['displayed_value'],
                'expected_answer' => $answer['expected_answer'],
                'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
            ]);
        }

        $pdo->commit();

        return $newSessionId;
    } catch (\Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

private function getSourceSessionForErrorReview(int $userId, int $sessionId): ?array
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

private function getWrongAnswersFromSession(int $sessionId): array
{
    $pdo = Database::connection();

    $stmt = $pdo->prepare(
        'SELECT *
         FROM quiz_answers
         WHERE session_id = :session_id
           AND is_correct = 0
           AND user_answer IS NOT NULL
         ORDER BY question_order ASC'
    );

    $stmt->execute([
        'session_id' => $sessionId,
    ]);

    return $stmt->fetchAll();
}

private function getKanaFromSession(int $sessionId): array
{
    $pdo = Database::connection();

    $stmt = $pdo->prepare(
        'SELECT DISTINCT k.*
         FROM quiz_answers qa
         INNER JOIN kana k ON k.id = qa.kana_id
         WHERE qa.session_id = :session_id
         ORDER BY k.sort_order ASC'
    );

    $stmt->execute([
        'session_id' => $sessionId,
    ]);

    return $stmt->fetchAll();
}
    private function getLearnedKanaForUser(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT DISTINCT k.*
             FROM user_kana_stats uks
             INNER JOIN kana k ON k.id = uks.kana_id
             WHERE uks.user_id = :user_id
               AND uks.seen_count > 0
             ORDER BY k.sort_order ASC'
        );

        $stmt->execute([
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

}
