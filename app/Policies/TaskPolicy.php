<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCustomer();
    }

    public function view(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, Task $task): bool
    {
        return $this->owns($user, $task) && $task->status !== Task::STATUS_ARCHIVED;
    }

    public function publish(User $user, Task $task): bool
    {
        return $this->owns($user, $task) && $task->status === Task::STATUS_DRAFT;
    }

    public function archive(User $user, Task $task): bool
    {
        return $this->owns($user, $task) && $task->status !== Task::STATUS_ARCHIVED;
    }

    private function owns(User $user, Task $task): bool
    {
        return $user->isCustomer() && $task->user_id === $user->id;
    }
}
