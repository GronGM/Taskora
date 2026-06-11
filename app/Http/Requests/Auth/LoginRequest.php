<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Введите почту.',
            'email.email' => 'Введите корректную почту.',
            'password.required' => 'Введите пароль.',
        ];
    }

    public function authenticate(): void
    {
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            throw ValidationException::withMessages([
                'auth' => 'Неверная почта или пароль.',
            ]);
        }

        $user = $this->user();

        if ($user?->isBlocked()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'auth' => 'Аккаунт заблокирован. Обратитесь к администратору.',
            ]);
        }

        $user?->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $this->ip(),
        ])->save();

        $this->session()->regenerate();
    }
}
