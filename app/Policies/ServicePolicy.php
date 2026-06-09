<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPerformer();
    }

    public function view(User $user, Service $service): bool
    {
        return $this->owns($user, $service);
    }

    public function create(User $user): bool
    {
        return $user->isPerformer();
    }

    public function update(User $user, Service $service): bool
    {
        return $this->owns($user, $service);
    }

    public function submitReview(User $user, Service $service): bool
    {
        return $this->owns($user, $service)
            && in_array($service->status, [Service::STATUS_DRAFT, Service::STATUS_REJECTED], true);
    }

    public function archive(User $user, Service $service): bool
    {
        return $this->owns($user, $service);
    }

    private function owns(User $user, Service $service): bool
    {
        return $user->isPerformer() && $service->user_id === $user->id;
    }
}
