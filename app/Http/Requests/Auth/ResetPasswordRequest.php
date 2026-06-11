<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Некорректная ссылка для сброса пароля.',
            'email.required' => 'Введите почту.',
            'email.email' => 'Введите корректную почту.',
            'password.required' => 'Введите новый пароль.',
            'password.min' => 'Пароль должен быть не короче :min символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ];
    }
}
