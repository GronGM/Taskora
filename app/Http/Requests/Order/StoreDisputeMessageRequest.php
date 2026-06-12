<?php

namespace App\Http\Requests\Order;

use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDisputeMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dispute = $this->route('dispute');

        return $dispute instanceof Dispute && Gate::allows('message', $dispute);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Введите текст сообщения.',
            'body.max' => 'Сообщение не должно быть длиннее 4000 символов.',
        ];
    }
}
