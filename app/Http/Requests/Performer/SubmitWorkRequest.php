<?php

namespace App\Http\Requests\Performer;

use App\Models\ModerationFlag;
use App\Models\Order;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class SubmitWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && Gate::allows('submitWork', $order);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
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
                'В сообщении о сдаче работы обнаружены контактные данные или предложение перейти вне Таскоры. Уберите контакты и обсуждайте заказ внутри платформы.',
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
            'reason' => 'contact_detected_in_work_submission',
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }
}
