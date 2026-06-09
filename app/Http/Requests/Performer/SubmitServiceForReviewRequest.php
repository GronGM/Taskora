<?php

namespace App\Http\Requests\Performer;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SubmitServiceForReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $service = $this->route('service');

        return $service instanceof Service && Gate::allows('submitReview', $service);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
