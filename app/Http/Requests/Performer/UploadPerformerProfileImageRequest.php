<?php

namespace App\Http\Requests\Performer;

use App\Models\PerformerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UploadPerformerProfileImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('profile');

        if ($profile instanceof PerformerProfile) {
            return Gate::allows('update', $profile);
        }

        return $this->user()?->isPerformer() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
