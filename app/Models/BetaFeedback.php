<?php

namespace App\Models;

use Database\Factories\BetaFeedbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'role',
    'page_url',
    'scenario',
    'type',
    'severity',
    'title',
    'description',
    'browser',
    'screen_size',
    'status',
])]
class BetaFeedback extends Model
{
    /** @use HasFactory<BetaFeedbackFactory> */
    use HasFactory;

    protected $table = 'beta_feedback';

    public const TYPE_BUG = 'bug';

    public const TYPE_UX_PROBLEM = 'ux_problem';

    public const TYPE_IDEA = 'idea';

    public const TYPE_QUESTION = 'question';

    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return array_keys(self::typeLabels());
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_BUG => 'Ошибка',
            self::TYPE_UX_PROBLEM => 'Проблема UX',
            self::TYPE_IDEA => 'Идея',
            self::TYPE_QUESTION => 'Вопрос',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function severities(): array
    {
        return array_keys(self::severityLabels());
    }

    /**
     * @return array<string, string>
     */
    public static function severityLabels(): array
    {
        return [
            self::SEVERITY_LOW => 'Низкая',
            self::SEVERITY_MEDIUM => 'Средняя',
            self::SEVERITY_HIGH => 'Высокая',
            self::SEVERITY_CRITICAL => 'Критичная',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return array_keys(self::statusLabels());
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_OPEN => 'Открыт',
            self::STATUS_IN_REVIEW => 'На рассмотрении',
            self::STATUS_RESOLVED => 'Решен',
            self::STATUS_REJECTED => 'Отклонен',
        ];
    }
}
