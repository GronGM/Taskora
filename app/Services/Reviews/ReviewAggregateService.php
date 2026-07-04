<?php

namespace App\Services\Reviews;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Support\PerformerLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReviewAggregateService
{
    public function recalculateForReview(Review $review): void
    {
        $review->loadMissing(['service', 'performer']);

        if ($review->service_id !== null) {
            $this->recalculateService($review->service);
        }

        $this->recalculatePerformer($review->performer);
    }

    public function recalculateForOrder(Order $order): void
    {
        $order->loadMissing(['service', 'performer']);

        if ($order->service_id !== null) {
            $this->recalculateService($order->service);
        }

        $this->recalculatePerformer($order->performer);
    }

    public function recalculateService(?Service $service): void
    {
        if (! $service instanceof Service) {
            return;
        }

        $reviewStats = Review::query()
            ->where('service_id', $service->id)
            ->where('status', Review::STATUS_PUBLISHED)
            ->where('is_public', true)
            ->selectRaw('count(*) as reviews_count, avg(rating) as rating')
            ->first();

        $completedOrdersCount = $service->orders()
            ->where('status', Order::STATUS_COMPLETED)
            ->where('payment_status', Order::PAYMENT_RELEASED)
            ->count();

        $service->forceFill([
            'rating' => ((int) $reviewStats->reviews_count) > 0 ? round((float) $reviewStats->rating, 2) : null,
            'reviews_count' => (int) $reviewStats->reviews_count,
            'orders_count' => $completedOrdersCount,
        ])->save();
    }

    public function recalculatePerformer(?User $performer): void
    {
        if (! $performer instanceof User) {
            return;
        }

        $reviewStats = Review::query()
            ->where('performer_id', $performer->id)
            ->where('status', Review::STATUS_PUBLISHED)
            ->where('is_public', true)
            ->selectRaw('count(*) as reviews_count, avg(rating) as rating')
            ->first();

        $completedOrdersCount = Order::query()
            ->where('performer_id', $performer->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->where('payment_status', Order::PAYMENT_RELEASED)
            ->count();

        $rating = ((int) $reviewStats->reviews_count) > 0 ? round((float) $reviewStats->rating, 2) : null;

        $lostDisputesCount = Dispute::query()
            ->where('resolution', Dispute::RESOLUTION_REFUND_TO_CUSTOMER)
            ->whereHas('order', fn (Builder $query) => $query->where('performer_id', $performer->id))
            ->count();

        $performer->forceFill([
            'performer_rating' => $rating,
            'performer_reviews_count' => (int) $reviewStats->reviews_count,
            'performer_completed_orders_count' => $completedOrdersCount,
            'performer_lost_disputes_count' => $lostDisputesCount,
            'performer_level' => PerformerLevel::determine($completedOrdersCount, $rating !== null ? (float) $rating : null, $lostDisputesCount),
        ])->save();
    }

    /**
     * @return array{rating: float|null, reviews_count: int}
     */
    public function publishedReviewStats(Builder $query): array
    {
        $stats = $query
            ->where('status', Review::STATUS_PUBLISHED)
            ->where('is_public', true)
            ->selectRaw('count(*) as reviews_count, avg(rating) as rating')
            ->first();

        $reviewsCount = (int) $stats->reviews_count;

        return [
            'rating' => $reviewsCount > 0 ? round((float) $stats->rating, 2) : null,
            'reviews_count' => $reviewsCount,
        ];
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
