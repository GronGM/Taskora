<?php

namespace App\Http\Requests\Customer;

use App\Models\Task;
use Illuminate\Support\Facades\Gate;

class StoreTaskRequest extends TaskPayloadRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Task::class);
    }
}
