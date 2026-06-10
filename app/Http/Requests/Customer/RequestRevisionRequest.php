<?php

namespace App\Http\Requests\Customer;

use App\Models\ModerationFlag;
use App\Models\Order;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class RequestRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && Gate::allows('requestRevision', $order);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'revision_comment' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'revision_comment.required' => 'Опишите, что именно нужно исправить.',
            'revision_comment.min' => 'Комментарий к доработке должен быть не короче :min символов.',
            'revision_comment.max' => 'Комментарий к доработке не должен превышать :max символов.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'revision_comment' => 'комментарий к доработке',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $result = app(ContactGuard::class)->check($this->input('revision_comment'));

            if (! $result->failedCheck()) {
                return;
            }

            $this->recordModerationFlag($result);

            $validator->errors()->add(
                'revision_comment',
                'В комментарии к доработке обнаружены контактные данные или предложение перейти вне Таскоры. Обсуждайте заказ внутри платформы.',
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
            'reason' => 'contact_detected_in_revision_comment',
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }
}
