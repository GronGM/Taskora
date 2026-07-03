<?php

namespace App\Http\Requests\Order;

use App\Models\Dispute;
use App\Models\ModerationFlag;
use App\Models\Order;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && Gate::allows('create', [Dispute::class, $order]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(Dispute::reasons())],
            'description' => ['required', 'string', 'min:10', 'max:4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Выберите причину спора.',
            'reason.in' => 'Выберите причину спора.',
            'description.required' => 'Опишите проблему.',
            'description.min' => 'Описание проблемы должно быть не короче :min символов.',
            'description.max' => 'Описание проблемы не должно превышать :max символов.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => 'причина спора',
            'description' => 'описание проблемы',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $result = app(ContactGuard::class)->check($this->input('description'));

            if (! $result->failedCheck()) {
                return;
            }

            $this->recordModerationFlag($result);

            $validator->errors()->add(
                'description',
                'В описании спора обнаружены контактные данные или предложение перейти вне Таскоры. Уберите контакты: спор рассматривается внутри платформы.',
            );
        });
    }

    private function recordModerationFlag(ContactGuardResult $result): void
    {
        $order = $this->route('order');

        ModerationFlag::create([
            'user_id' => $this->user()?->id,
            'entity_type' => Order::class,
            'entity_id' => $order instanceof Order ? $order->id : null,
            'reason' => 'contact_detected_in_dispute_description',
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }
}
