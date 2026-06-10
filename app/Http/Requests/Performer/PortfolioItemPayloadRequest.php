<?php

namespace App\Http\Requests\Performer;

use App\Models\ModerationFlag;
use App\Models\PerformerPortfolioItem;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class PortfolioItemPayloadRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1500'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'external_url' => ['nullable', 'string', 'max:255', 'url', 'starts_with:http://,https://'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['nullable', Rule::in([PerformerPortfolioItem::STATUS_DRAFT, PerformerPortfolioItem::STATUS_PUBLISHED])],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt', 'max:10240'],
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
                "В поле «{$label}» обнаружены контактные данные, мессенджер, соцсеть или предложение перейти вне Таскоры. Уберите контакты и внешние договоренности.",
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function itemData(): array
    {
        $status = $this->validated('status', PerformerPortfolioItem::STATUS_PUBLISHED);

        return [
            'title' => $this->validated('title'),
            'description' => $this->validated('description'),
            'category_id' => $this->validated('category_id'),
            'external_url' => $this->validated('external_url'),
            'sort_order' => (int) $this->validated('sort_order', 0),
            'status' => $status,
            'is_public' => $status === PerformerPortfolioItem::STATUS_PUBLISHED,
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: ContactGuardResult}|null
     */
    private function firstContactViolation(): ?array
    {
        $guard = app(ContactGuard::class);

        foreach ($this->textFieldsForContactGuard() as $field => $label) {
            $result = $guard->check($this->input($field));

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
        return [
            'title' => 'Название работы',
            'description' => 'Описание работы',
            'external_url' => 'Внешняя ссылка',
        ];
    }

    private function recordModerationFlag(ContactGuardResult $result): void
    {
        $item = $this->route('item');

        ModerationFlag::create([
            'user_id' => $this->user()?->id,
            'entity_type' => PerformerPortfolioItem::class,
            'entity_id' => $item instanceof PerformerPortfolioItem ? $item->id : null,
            'reason' => 'contact_detected_in_portfolio',
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }
}
