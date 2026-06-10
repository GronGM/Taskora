<?php

namespace App\Http\Requests\Customer;

use App\Models\Task;
use Illuminate\Support\Facades\Gate;

class UpdateTaskRequest extends TaskPayloadRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && Gate::allows('update', $task);
    }
}
