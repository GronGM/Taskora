<?php

namespace App\Http\Requests\Order;

use App\Models\Dispute;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && Gate::allows('create', [Dispute::class, $order]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(Dispute::reasons())],
            'description' => ['required', 'string', 'min:10', 'max:4000'],
        ];
    }
}
