<?php

namespace App\Http\Requests\Customer;

use App\Models\ModerationFlag;
use App\Models\Task;
use App\Models\TaskType;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class TaskPayloadRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'task_type_id' => ['nullable', 'integer', Rule::exists('task_types', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:6000'],
            'budget_min' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'budget_max' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:today'],
            'publish' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->filled('budget_min') && $this->filled('budget_max') && (int) $this->input('budget_min') > (int) $this->input('budget_max')) {
                $validator->errors()->add('budget_min', 'Минимальный бюджет не должен быть больше максимального бюджета.');

                return;
            }

            $categoryId = (int) $this->input('category_id');
            $activeTaskTypesExist = TaskType::query()
                ->where('category_id', $categoryId)
                ->active()
                ->exists();

            if ($activeTaskTypesExist && ! $this->filled('task_type_id')) {
                $validator->errors()->add('task_type_id', 'Выберите вид задания.');

                return;
            }

            if ($this->filled('task_type_id')) {
                $taskTypeBelongsToCategory = TaskType::query()
                    ->whereKey((int) $this->input('task_type_id'))
                    ->where('category_id', $categoryId)
                    ->active()
                    ->exists();

                if (! $taskTypeBelongsToCategory) {
                    $validator->errors()->add('task_type_id', 'Выбранный вид задания не относится к этой категории.');

                    return;
                }
            }

            $violation = $this->firstContactViolation();

            if (! $violation) {
                return;
            }

            [$field, $label, $result] = $violation;

            $this->recordModerationFlag($result);

            $validator->errors()->add(
                $field,
                "В поле «{$label}» обнаружены контактные данные или предложение перейти вне Таскоры. Уберите контакты и обсуждайте заказ внутри платформы.",
            );
        });
    }

    /**
     * @return array{0: string, 1: string, 2: ContactGuardResult}|null
     */
    private function firstContactViolation(): ?array
    {
        $guard = app(ContactGuard::class);

        foreach ([
            'title' => 'Название задания',
            'description' => 'Описание задания',
        ] as $field => $label) {
            $result = $guard->check($this->input($field));

            if ($result->failedCheck()) {
                return [$field, $label, $result];
            }
        }

        return null;
    }

    private function recordModerationFlag(ContactGuardResult $result): void
    {
        $task = $this->route('task');

        ModerationFlag::create([
            'user_id' => $this->user()?->id,
            'entity_type' => Task::class,
            'entity_id' => $task instanceof Task ? $task->id : null,
            'reason' => 'contact_detected_in_task',
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function taskData(): array
    {
        return collect($this->safe()->only([
            'category_id',
            'task_type_id',
            'title',
            'description',
            'budget_min',
            'budget_max',
            'deadline_at',
        ]))->all();
    }
}
