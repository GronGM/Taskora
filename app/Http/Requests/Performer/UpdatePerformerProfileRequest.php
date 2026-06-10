<?php

namespace App\Http\Requests\Performer;

use App\Models\ModerationFlag;
use App\Models\PerformerProfile;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePerformerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('profile');

        if ($profile instanceof PerformerProfile) {
            return Gate::allows('update', $profile);
        }

        return $this->user()?->isPerformer() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:180'],
            'bio' => ['nullable', 'string', 'max:6000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'response_time_label' => ['nullable', 'string', 'max:120'],
            'portfolio_summary' => ['nullable', 'string', 'max:2000'],
            'specialization_ids' => ['nullable', 'array', 'max:7'],
            'specialization_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('is_active', true)],
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
     * @return array<string, mixed>
     */
    public function profileData(): array
    {
        return collect($this->safe()->only([
            'display_name',
            'headline',
            'bio',
            'experience_years',
            'response_time_label',
            'portfolio_summary',
        ]))->all();
    }

    /**
     * @return array<int, int>
     */
    public function specializationIds(): array
    {
        return collect($this->validated('specialization_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
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
            'display_name' => 'Публичное имя',
            'headline' => 'Короткий заголовок',
            'bio' => 'Описание',
            'portfolio_summary' => 'Описание портфолио',
        ];
    }

    private function recordModerationFlag(ContactGuardResult $result): void
    {
        $profile = $this->route('profile');
        $profileId = $profile instanceof PerformerProfile
            ? $profile->id
            : PerformerProfile::query()->where('user_id', $this->user()?->id)->value('id');

        ModerationFlag::create([
            'user_id' => $this->user()?->id,
            'entity_type' => PerformerProfile::class,
            'entity_id' => $profileId,
            'reason' => 'contact_detected_in_performer_profile',
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }
}
