<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

final class GuestFreePracticeService
{
    public const LIMIT = 3;
    private const SESSION_KEY = 'guest_free_quiz_count';

    public function getUsedCount(): int
    {
        return max(0, (int) Session::get(self::SESSION_KEY, 0));
    }

    public function getRemainingCount(): int
    {
        return max(0, self::LIMIT - $this->getUsedCount());
    }

    public function hasReachedLimit(): bool
    {
        return $this->getUsedCount() >= self::LIMIT;
    }

    public function incrementCount(): void
    {
        Session::put(self::SESSION_KEY, min(self::LIMIT, $this->getUsedCount() + 1));
    }

    public function normalizeConfig(array $input): array
    {
        $kanaSet = (string) ($input['kana_set'] ?? 'hiragana');
        $group = (string) ($input['group'] ?? 'VOWEL');
        $direction = (string) ($input['direction'] ?? 'kana_to_romaji');
        $questionCount = (int) ($input['question_count'] ?? 5);

        if (!in_array($kanaSet, ['hiragana', 'katakana'], true)) {
            $kanaSet = 'hiragana';
        }

        if (!array_key_exists($group, $this->groupOptions())) {
            $group = 'VOWEL';
        }

        if (!in_array($direction, ['kana_to_romaji', 'romaji_to_kana', 'written'], true)) {
            $direction = 'kana_to_romaji';
        }

        if (!in_array($questionCount, [5, 10], true)) {
            $questionCount = 5;
        }

        return [
            'kana_set' => $kanaSet,
            'group' => $group,
            'direction' => $direction,
            'question_count' => $questionCount,
        ];
    }

    public function groupOptions(): array
    {
        return [
            'VOWEL' => 'Voyelles',
            'K' => 'K',
            'S' => 'S',
            'T' => 'T',
            'N' => 'N',
            'H' => 'H',
            'M' => 'M',
            'R' => 'R',
            'Y_W_N' => 'Y / W / ん',
        ];
    }
}
