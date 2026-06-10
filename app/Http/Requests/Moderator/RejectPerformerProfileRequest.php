<?php

namespace App\Http\Requests\Moderator;

use App\Models\PerformerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RejectPerformerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('profile');

        return $profile instanceof PerformerProfile && Gate::allows('reject', $profile);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
