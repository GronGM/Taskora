<?php

namespace App\Services\Search;

use Illuminate\Database\Eloquent\Builder;

/**
 * Взвешенный поиск по нормализованным колонкам search_title / search_text.
 *
 * Колонки заполняются в моделях уже приведенными к нижнему регистру в PHP,
 * поэтому поиск не зависит от регистра и для кириллицы (lower() в SQLite
 * работает только с ASCII). Все слова запроса должны встретиться в записи;
 * совпадение в заголовке весит больше совпадения в тексте, совпадение
 * с начала заголовка получает дополнительный бонус.
 */
class RelevanceSearch
{
    private const MAX_WORDS = 5;

    private const TITLE_WEIGHT = 3;

    private const TITLE_PREFIX_BONUS = 2;

    private const TEXT_WEIGHT = 1;

    public function apply(Builder $query, string $search): Builder
    {
        $words = $this->words($search);

        if ($words === []) {
            return $query;
        }

        foreach ($words as $word) {
            $like = '%'.$this->escapeLike($word).'%';

            $query->where(function (Builder $query) use ($like): void {
                $query
                    ->whereRaw("search_title like ? escape '\\'", [$like])
                    ->orWhereRaw("search_text like ? escape '\\'", [$like]);
            });
        }

        $scoreParts = [];
        $bindings = [];

        foreach ($words as $word) {
            $like = '%'.$this->escapeLike($word).'%';
            $prefix = $this->escapeLike($word).'%';

            $scoreParts[] = "(case when search_title like ? escape '\\' then ".self::TITLE_WEIGHT.' else 0 end)';
            $bindings[] = $like;
            $scoreParts[] = "(case when search_title like ? escape '\\' then ".self::TITLE_PREFIX_BONUS.' else 0 end)';
            $bindings[] = $prefix;
            $scoreParts[] = "(case when search_text like ? escape '\\' then ".self::TEXT_WEIGHT.' else 0 end)';
            $bindings[] = $like;
        }

        return $query->orderByRaw('('.implode(' + ', $scoreParts).') desc', $bindings);
    }

    public static function normalize(?string ...$parts): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', implode(' ', array_map(fn (?string $part): string => (string) $part, $parts))) ?? '');

        return str_replace('ё', 'е', mb_strtolower($text));
    }

    /**
     * @return array<int, string>
     */
    private function words(string $search): array
    {
        $normalized = self::normalize($search);

        if ($normalized === '') {
            return [];
        }

        return collect(preg_split('/\s+/u', $normalized) ?: [])
            ->filter(fn (string $word): bool => mb_strlen($word) >= 2)
            ->take(self::MAX_WORDS)
            ->values()
            ->all();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
