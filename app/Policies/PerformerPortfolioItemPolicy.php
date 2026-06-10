<?php

namespace App\Policies;

use App\Models\PerformerPortfolioItem;
use App\Models\User;

class PerformerPortfolioItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPerformer();
    }

    public function create(User $user): bool
    {
        return $user->isPerformer();
    }

    public function view(User $user, PerformerPortfolioItem $item): bool
    {
        return $this->owns($user, $item);
    }

    public function update(User $user, PerformerPortfolioItem $item): bool
    {
        return $this->owns($user, $item);
    }

    public function delete(User $user, PerformerPortfolioItem $item): bool
    {
        return $this->owns($user, $item);
    }

    public function publish(User $user, PerformerPortfolioItem $item): bool
    {
        return $this->owns($user, $item);
    }

    public function hide(User $user, PerformerPortfolioItem $item): bool
    {
        return $this->owns($user, $item);
    }

    private function owns(User $user, PerformerPortfolioItem $item): bool
    {
        $item->loadMissing('profile');

        return $user->isPerformer() && $item->profile?->user_id === $user->id;
    }
}
