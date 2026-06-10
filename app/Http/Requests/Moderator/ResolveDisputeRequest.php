<?php

namespace App\Http\Requests\Moderator;

use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dispute = $this->route('dispute');

        return $dispute instanceof Dispute && Gate::allows('resolve', $dispute);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolution' => ['required', 'string', Rule::in(Dispute::resolutions())],
            'moderator_comment' => ['required', 'string', 'min:10', 'max:4000'],
        ];
    }
}
