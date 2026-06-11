<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\Moderation\ContactGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class TaskTypePayloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_ADMIN;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('description')) {
                return;
            }

            $result = app(ContactGuard::class)->check($this->input('description'));

            if (! $result->failedCheck()) {
                return;
            }

            $validator->errors()->add(
                'description',
                'В описании обнаружены контактные данные или предложение перейти вне Таскоры.',
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function taskTypeData(bool $defaultActive = true): array
    {
        return [
            'category_id' => (int) $this->input('category_id'),
            'name' => $this->string('name')->trim()->toString(),
            'description' => $this->filled('description') ? $this->string('description')->trim()->toString() : null,
            'sort_order' => (int) $this->input('sort_order', 0),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : $defaultActive,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Выберите активную категорию.',
            'category_id.exists' => 'Вид задания можно привязать только к активной категории.',
            'name.required' => 'Введите название вида задания.',
            'name.max' => 'Название вида задания не должно быть длиннее 120 символов.',
            'slug.max' => 'Slug не должен быть длиннее 160 символов.',
            'description.max' => 'Описание не должно быть длиннее 1000 символов.',
            'sort_order.required' => 'Укажите порядок сортировки.',
            'sort_order.integer' => 'Порядок сортировки должен быть числом.',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным.',
        ];
    }
}
