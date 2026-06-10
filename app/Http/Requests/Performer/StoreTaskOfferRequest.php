<?php

namespace App\Http\Requests\Performer;

use App\Models\ModerationFlag;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreTaskOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && Gate::allows('create', [TaskOffer::class, $task]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'price' => ['required', 'integer', 'min:100', 'max:10000000'],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $result = app(ContactGuard::class)->check($this->input('message'));

            if (! $result->failedCheck()) {
                return;
            }

            $this->recordModerationFlag($result);

            $validator->errors()->add(
                'message',
                'В отклике обнаружены контактные данные или предложение перейти вне Таскоры. Уберите контакты и обсуждайте заказ внутри платформы.',
            );
        });
    }

    private function recordModerationFlag(ContactGuardResult $result): void
    {
        ModerationFlag::create([
            'user_id' => $this->user()?->id,
            'entity_type' => TaskOffer::class,
            'entity_id' => null,
            'reason' => 'contact_detected_in_task_offer',
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function offerData(): array
    {
        return collect($this->safe()->only([
            'message',
            'price',
            'delivery_days',
        ]))->all();
    }
}
