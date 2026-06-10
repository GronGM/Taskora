<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewCustomerAny(User $user): bool
    {
        return $user->isCustomer();
    }

    public function viewPerformerAny(User $user): bool
    {
        return $user->isPerformer();
    }

    public function viewAsCustomer(User $user, Order $order): bool
    {
        return $user->isCustomer() && $order->customer_id === $user->id;
    }

    public function viewAsPerformer(User $user, Order $order): bool
    {
        return $user->isPerformer() && $order->performer_id === $user->id;
    }

    public function viewWorkspace(User $user, Order $order): bool
    {
        return $this->viewAsCustomer($user, $order) || $this->viewAsPerformer($user, $order);
    }

    public function sendMessage(User $user, Order $order): bool
    {
        return $this->viewWorkspace($user, $order);
    }

    public function uploadFile(User $user, Order $order): bool
    {
        return $this->viewWorkspace($user, $order);
    }

    public function downloadFile(User $user, Order $order): bool
    {
        return $this->viewWorkspace($user, $order);
    }

    public function markPaid(User $user, Order $order): bool
    {
        return $this->viewAsCustomer($user, $order)
            && $order->status === Order::STATUS_AWAITING_PAYMENT
            && $order->payment_status === Order::PAYMENT_UNPAID;
    }

    public function requestRevision(User $user, Order $order): bool
    {
        return $this->viewAsCustomer($user, $order)
            && $order->status === Order::STATUS_SUBMITTED_FOR_REVIEW;
    }

    public function complete(User $user, Order $order): bool
    {
        return $this->viewAsCustomer($user, $order)
            && $order->status === Order::STATUS_SUBMITTED_FOR_REVIEW;
    }

    public function cancelAsCustomer(User $user, Order $order): bool
    {
        return $this->viewAsCustomer($user, $order)
            && $order->status === Order::STATUS_AWAITING_PAYMENT
            && $order->payment_status === Order::PAYMENT_UNPAID;
    }

    public function submitWork(User $user, Order $order): bool
    {
        return $this->viewAsPerformer($user, $order)
            && in_array($order->status, [Order::STATUS_IN_PROGRESS, Order::STATUS_REVISION_REQUESTED], true);
    }

    public function cancelAsPerformer(User $user, Order $order): bool
    {
        return $this->viewAsPerformer($user, $order)
            && in_array($order->status, [Order::STATUS_AWAITING_PAYMENT, Order::STATUS_IN_PROGRESS], true);
    }
}
