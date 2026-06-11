<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\User;
use App\Services\Moderation\ContactGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class CategoryPayloadRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateParent($validator);
            $this->validateContactGuard($validator);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryData(bool $defaultActive = true): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'parent_id' => $this->filled('parent_id') ? (int) $this->input('parent_id') : null,
            'description' => $this->filled('description') ? $this->string('description')->trim()->toString() : null,
            'icon' => $this->filled('icon') ? $this->string('icon')->trim()->toString() : null,
            'sort_order' => (int) $this->input('sort_order', 0),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : $defaultActive,
        ];
    }

    private function validateParent(Validator $validator): void
    {
        if (! $this->filled('parent_id')) {
            return;
        }

        $category = $this->route('category');

        if (! $category instanceof Category) {
            return;
        }

        $parentId = (int) $this->input('parent_id');

        if ($parentId === $category->id) {
            $validator->errors()->add('parent_id', 'Категория не может быть родителем самой себя.');

            return;
        }

        $parent = Category::query()->find($parentId);

        while ($parent) {
            if ($parent->id === $category->id) {
                $validator->errors()->add('parent_id', 'Нельзя выбрать дочернюю категорию родителем: получится циклическая вложенность.');

                return;
            }

            $parent = $parent->parent;
        }
    }

    private function validateContactGuard(Validator $validator): void
    {
        if (! $this->filled('description')) {
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
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Введите название категории.',
            'name.max' => 'Название категории не должно быть длиннее 120 символов.',
            'slug.max' => 'Slug не должен быть длиннее 160 символов.',
            'parent_id.exists' => 'Выберите существующую родительскую категорию.',
            'description.max' => 'Описание не должно быть длиннее 1000 символов.',
            'icon.max' => 'Иконка не должна быть длиннее 80 символов.',
            'sort_order.required' => 'Укажите порядок сортировки.',
            'sort_order.integer' => 'Порядок сортировки должен быть числом.',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным.',
        ];
    }
}
