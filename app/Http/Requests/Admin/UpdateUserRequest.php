<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)],
            'role' => ['required', 'string', Rule::in(User::roles())],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Введите имя пользователя.',
            'name.max' => 'Имя не должно быть длиннее 255 символов.',
            'email.required' => 'Введите почту.',
            'email.email' => 'Введите корректную почту.',
            'email.unique' => 'Пользователь с такой почтой уже существует.',
            'role.required' => 'Выберите роль.',
            'role.in' => 'Выберите доступную роль.',
            'admin_note.max' => 'Админская заметка не должна быть длиннее 5000 символов.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User $target */
                $target = $this->route('user');
                $actor = $this->user();
                $newRole = (string) $this->input('role');

                if ($actor && $target->is($actor) && $newRole !== User::ROLE_ADMIN) {
                    $validator->errors()->add('role', 'Нельзя изменить собственную роль так, чтобы потерять доступ к админке.');
                }

                if ($target->isAdmin() && $target->status === User::STATUS_ACTIVE && $newRole !== User::ROLE_ADMIN && ! $this->anotherActiveAdminExists($target)) {
                    $validator->errors()->add('role', 'Нельзя снять роль администратора с последнего активного администратора.');
                }
            },
        ];
    }

    private function anotherActiveAdminExists(User $target): bool
    {
        return User::query()
            ->whereKeyNot($target->id)
            ->where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->exists();
    }
}
