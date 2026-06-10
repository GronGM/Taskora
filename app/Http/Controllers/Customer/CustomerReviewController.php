<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreReviewRequest;
use App\Models\Order;
use App\Models\Review;
use App\Services\Notifications\NotificationService;
use App\Services\Reviews\ReviewAggregateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerReviewController extends Controller
{
    public function create(Order $order): Response|RedirectResponse
    {
        $order->load(['performer', 'service', 'task', 'review']);

        if ($order->review) {
            return redirect()->route('customer.reviews.show', $order->review);
        }

        Gate::authorize('create', [Review::class, $order]);

        return Inertia::render('Customer/Reviews/Create', [
            'order' => $this->orderPayload($order),
            'ratingOptions' => [1, 2, 3, 4, 5],
        ]);
    }

    public function store(
        StoreReviewRequest $request,
        Order $order,
        ReviewAggregateService $aggregates,
        NotificationService $notifications,
    ): RedirectResponse {
        $order->load(['performer', 'service', 'task', 'review']);

        $review = $aggregates->transaction(function () use ($request, $order, $aggregates): Review {
            Gate::authorize('create', [Review::class, $order]);

            $review = $order->review()->create([
                'service_id' => $order->service_id,
                'task_id' => $order->task_id,
                'customer_id' => $order->customer_id,
                'performer_id' => $order->performer_id,
                'rating' => (int) $request->integer('rating'),
                'comment' => $request->string('comment')->trim()->toString() ?: null,
                'status' => Review::STATUS_PUBLISHED,
                'is_public' => true,
                'published_at' => now(),
            ]);

            $aggregates->recalculateForReview($review);

            return $review;
        });

        $review->load(['performer', 'service', 'customer']);

        $notifications->notifyUser(
            $review->performer,
            'review.published',
            'Вам оставили новый отзыв',
            "Заказчик {$review->customer->name} оставил отзыв по заказу «{$order->title}».",
            $review->service?->url ?? route('performers.reviews', $review->performer),
            [
                'actor_id' => $review->customer_id,
                'icon' => 'review',
                'severity' => 'success',
                'related_type' => Review::class,
                'related_id' => $review->id,
                'order_id' => $order->id,
            ],
        );

        return redirect()
            ->route('customer.reviews.show', $review)
            ->with('success', 'Отзыв опубликован. Спасибо, что помогаете формировать доверие в Таскоре.');
    }

    public function index(): Response
    {
        $reviews = request()->user()
            ->givenReviews()
            ->with(['order', 'performer', 'service', 'task'])
            ->latest('published_at')
            ->latest()
            ->get()
            ->map(fn (Review $review): array => $this->reviewPayload($review));

        return Inertia::render('Customer/Reviews/Index', [
            'reviews' => $reviews,
        ]);
    }

    public function show(Review $review): Response
    {
        Gate::authorize('view', $review);

        $review->load(['order', 'performer', 'service', 'task']);

        return Inertia::render('Customer/Reviews/Show', [
            'review' => $this->reviewPayload($review),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'title' => $order->title,
            'description' => $order->description,
            'performer' => [
                'id' => $order->performer?->id,
                'name' => $order->performer?->name,
                'rating' => $order->performer?->performer_rating,
                'reviews_count' => $order->performer?->performer_reviews_count ?? 0,
            ],
            'source' => [
                'type' => $order->source_type,
                'label' => $order->source_type === Order::SOURCE_SERVICE ? 'Услуга' : 'Задание',
                'title' => $order->service?->title ?? $order->task?->title,
            ],
            'store_url' => route('customer.orders.review.store', $order),
            'show_url' => route('customer.orders.show', $order),
            'workspace_url' => route('customer.orders.workspace', $order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewPayload(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'status' => $review->status,
            'is_public' => $review->is_public,
            'published_at' => $review->published_at?->format('d.m.Y H:i'),
            'order' => [
                'id' => $review->order?->id,
                'title' => $review->order?->title,
                'show_url' => $review->order ? route('customer.orders.show', $review->order) : null,
                'workspace_url' => $review->order ? route('customer.orders.workspace', $review->order) : null,
            ],
            'performer' => [
                'id' => $review->performer?->id,
                'name' => $review->performer?->name,
                'reviews_url' => $review->performer ? route('performers.reviews', $review->performer) : null,
            ],
            'source' => [
                'type' => $review->service_id ? 'service' : 'task',
                'title' => $review->service?->title ?? $review->task?->title,
                'url' => $review->service?->url ?? null,
            ],
            'show_url' => route('customer.reviews.show', $review),
        ];
    }
}
