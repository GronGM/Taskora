<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;

class TaskOfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPerformer();
    }

    public function create(User $user, Task $task): bool
    {
        return $user->isPerformer()
            && $task->status === Task::STATUS_PUBLISHED
            && $task->user_id !== $user->id
            && ! $task->offers()->where('user_id', $user->id)->exists();
    }

    public function withdraw(User $user, TaskOffer $offer): bool
    {
        return $user->isPerformer()
            && $offer->user_id === $user->id
            && $offer->status === TaskOffer::STATUS_SUBMITTED;
    }

    public function reject(User $user, TaskOffer $offer): bool
    {
        return $user->isCustomer()
            && $offer->task?->user_id === $user->id
            && $offer->status === TaskOffer::STATUS_SUBMITTED;
    }
}
