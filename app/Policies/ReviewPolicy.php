<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user, Order $order): bool
    {
        return $user->isCustomer()
            && $order->customer_id === $user->id
            && $order->performer_id !== $user->id
            && $order->status === Order::STATUS_COMPLETED
            && $order->payment_status === Order::PAYMENT_RELEASED
            && ! $order->review()->exists();
    }

    public function view(User $user, Review $review): bool
    {
        return $user->isCustomer() && $review->customer_id === $user->id;
    }
}
