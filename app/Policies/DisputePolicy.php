<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;

class DisputePolicy
{
    public function create(User $user, Order $order): bool
    {
        if (! $this->isOrderParticipant($user, $order)) {
            return false;
        }

        if ($order->payment_status !== Order::PAYMENT_HELD) {
            return false;
        }

        if (! in_array($order->status, [
            Order::STATUS_IN_PROGRESS,
            Order::STATUS_SUBMITTED_FOR_REVIEW,
            Order::STATUS_REVISION_REQUESTED,
        ], true)) {
            return false;
        }

        return ! $order->activeDispute()->exists();
    }

    public function view(User $user, Dispute $dispute): bool
    {
        return $this->viewModeratorAny($user)
            || $this->isOrderParticipant($user, $dispute->order);
    }

    public function message(User $user, Dispute $dispute): bool
    {
        return $this->view($user, $dispute)
            && in_array($dispute->status, [Dispute::STATUS_OPEN, Dispute::STATUS_UNDER_REVIEW], true);
    }

    public function viewModeratorAny(User $user): bool
    {
        return $user->isModerator() || $user->isAdmin();
    }

    public function take(User $user, Dispute $dispute): bool
    {
        return $this->viewModeratorAny($user)
            && $dispute->status === Dispute::STATUS_OPEN;
    }

    public function resolve(User $user, Dispute $dispute): bool
    {
        return $this->viewModeratorAny($user)
            && in_array($dispute->status, [Dispute::STATUS_OPEN, Dispute::STATUS_UNDER_REVIEW], true);
    }

    private function isOrderParticipant(User $user, Order $order): bool
    {
        return ($user->isCustomer() && $order->customer_id === $user->id)
            || ($user->isPerformer() && $order->performer_id === $user->id);
    }
}
