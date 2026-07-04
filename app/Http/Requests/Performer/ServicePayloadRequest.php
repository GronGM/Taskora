<?php

namespace App\Http\Requests\Performer;

use App\Models\ModerationFlag;
use App\Models\Service;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class ServicePayloadRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:160'],
            'short_description' => ['required', 'string', 'max:420'],
            'description' => ['nullable', 'string', 'max:6000'],
            'price_from' => ['required', 'integer', 'min:100', 'max:10000000'],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
            'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'submit_for_review' => ['nullable', 'boolean'],
            'packages' => ['required', 'array', 'min:1', 'max:3'],
            'packages.*.name' => ['required', 'string', 'max:80'],
            'packages.*.description' => ['nullable', 'string', 'max:1000'],
            'packages.*.price' => ['required', 'integer', 'min:100', 'max:10000000'],
            'packages.*.delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
            'packages.*.revisions_count' => ['required', 'integer', 'min:0', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
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

        foreach ($this->textFieldsForContactGuard() as $field => $label) {
            $result = $guard->check(data_get($this->all(), $field));

            if ($result->failedCheck()) {
                return [$field, $label, $result];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function textFieldsForContactGuard(): array
    {
        $fields = [
            'title' => 'Название услуги',
            'cover' => 'Обложка услуги',
            'short_description' => 'Краткое описание',
            'description' => 'Полное описание',
        ];

        foreach ((array) $this->input('packages', []) as $index => $package) {
            $number = $index + 1;
            $fields["packages.{$index}.name"] = "Название пакета {$number}";
            $fields["packages.{$index}.description"] = "Описание пакета {$number}";
        }

        return $fields;
    }

    private function recordModerationFlag(ContactGuardResult $result): void
    {
        $service = $this->route('service');

        ModerationFlag::create([
            'user_id' => $this->user()?->id,
            'entity_type' => Service::class,
            'entity_id' => $service instanceof Service ? $service->id : null,
            'reason' => 'contact_detected_in_service',
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => 'open',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function serviceData(): array
    {
        return collect($this->safe()->only([
            'category_id',
            'title',
            'short_description',
            'description',
            'price_from',
            'delivery_days',
        ]))->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function packageData(): array
    {
        return collect($this->validated('packages', []))
            ->values()
            ->map(fn (array $package, int $index): array => [
                'name' => $package['name'],
                'description' => $package['description'] ?? null,
                'price' => (int) $package['price'],
                'delivery_days' => (int) $package['delivery_days'],
                'revisions_count' => (int) $package['revisions_count'],
                'sort_order' => ($index + 1) * 10,
            ])
            ->all();
    }
}
