<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BlockUserRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Укажите причину блокировки.',
            'reason.min' => 'Причина блокировки должна быть не короче 10 символов.',
            'reason.max' => 'Причина блокировки не должна быть длиннее 1000 символов.',
        ];
    }
}
