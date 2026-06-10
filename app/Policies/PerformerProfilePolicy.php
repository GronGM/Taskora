<?php

namespace App\Policies;

use App\Models\PerformerProfile;
use App\Models\User;

class PerformerProfilePolicy
{
    public function view(User $user, PerformerProfile $profile): bool
    {
        return $user->isPerformer() && $profile->user_id === $user->id;
    }

    public function update(User $user, PerformerProfile $profile): bool
    {
        return $this->view($user, $profile);
    }

    public function submitVerification(User $user, PerformerProfile $profile): bool
    {
        return $this->view($user, $profile)
            && in_array($profile->verification_status, [
                PerformerProfile::STATUS_NOT_SUBMITTED,
                PerformerProfile::STATUS_PENDING_REVIEW,
                PerformerProfile::STATUS_REJECTED,
                PerformerProfile::STATUS_VERIFIED,
            ], true);
    }

    public function reviewAny(User $user): bool
    {
        return $user->isModerator() || $user->isAdmin();
    }

    public function review(User $user, PerformerProfile $profile): bool
    {
        return $this->reviewAny($user);
    }

    public function approve(User $user, PerformerProfile $profile): bool
    {
        return $this->reviewAny($user)
            && $profile->verification_status === PerformerProfile::STATUS_PENDING_REVIEW;
    }

    public function reject(User $user, PerformerProfile $profile): bool
    {
        return $this->approve($user, $profile);
    }
}
