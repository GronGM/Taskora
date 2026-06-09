<?php

namespace App\Http\Requests\Performer;

use App\Models\Service;
use Illuminate\Support\Facades\Gate;

class UpdateServiceRequest extends ServicePayloadRequest
{
    public function authorize(): bool
    {
        $service = $this->route('service');

        return $service instanceof Service && Gate::allows('update', $service);
    }
}
