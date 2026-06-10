<?php

namespace App\Http\Requests\Customer;

use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\Review;
use App\Services\Moderation\ContactGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && Gate::allows('create', [Review::class, $order]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $result = app(ContactGuard::class)->check($this->input('comment'));

            if ($result->passed) {
                return;
            }

            ModerationFlag::create([
                'user_id' => $this->user()?->id,
                'entity_type' => Review::class,
                'entity_id' => null,
                'reason' => 'contact_detected_in_review',
                'matched_type' => $result->matchedType,
                'matched_value' => $result->matchedValue,
                'status' => ModerationFlag::STATUS_OPEN,
            ]);

            $validator->errors()->add(
                'comment',
                'Отзыв не сохранен: нельзя передавать контакты или договариваться об оплате вне Таскоры.',
            );
        });
    }
}
