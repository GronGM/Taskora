<?php

namespace App\Http\Requests\Performer;

use App\Models\Service;
use Illuminate\Support\Facades\Gate;

class StoreServiceRequest extends ServicePayloadRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Service::class);
    }
}
