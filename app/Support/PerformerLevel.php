<?php

namespace App\Support;

/**
 * Уровни исполнителей по объективным метрикам платформы.
 *
 * Уровень зависит только от завершенных оплаченных заказов, среднего
 * рейтинга по опубликованным отзывам и доли проигранных споров
 * (решение «средства возвращены заказчику») от числа завершенных заказов.
 * Уровень нельзя купить: он пересчитывается автоматически.
 */
class PerformerLevel
{
    public const NOVICE = 'novice';

    public const SPECIALIST = 'specialist';

    public const PRO = 'pro';

    public const EXPERT = 'expert';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::NOVICE => 'Новичок',
            self::SPECIALIST => 'Специалист',
            self::PRO => 'Профи',
            self::EXPERT => 'Эксперт',
        ];
    }

    public static function label(?string $level): string
    {
        return self::labels()[$level] ?? self::labels()[self::NOVICE];
    }

    public static function determine(int $completedOrders, ?float $rating, int $lostDisputes): string
    {
        $lostShare = $completedOrders > 0 ? $lostDisputes / $completedOrders : 0.0;

        if ($completedOrders >= 30 && $rating !== null && $rating >= 4.7 && $lostShare <= 0.05) {
            return self::EXPERT;
        }

        if ($completedOrders >= 10 && $rating !== null && $rating >= 4.5 && $lostShare <= 0.10) {
            return self::PRO;
        }

        if ($completedOrders >= 3 && ($rating === null || $rating >= 4.0) && $lostShare <= 0.20) {
            return self::SPECIALIST;
        }

        return self::NOVICE;
    }
}
