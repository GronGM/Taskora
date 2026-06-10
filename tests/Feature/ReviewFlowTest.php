<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\OrderSubmission;
use App\Models\Review;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class ReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sees_review_button_after_completed_order(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $response = $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk();

        $this->assertTrue($response->inertiaProps('order.can_review'));
        $this->assertNotNull($response->inertiaProps('order.review_create_url'));
    }

    public function test_customer_does_not_see_review_button_before_completion(): void
    {
        [$customer, , , $order] = $this->serviceOrder([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $response = $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk();

        $this->assertFalse($response->inertiaProps('order.can_review'));
    }

    public function test_customer_can_open_review_form_for_completed_order(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->get(route('customer.orders.review.create', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Customer/Reviews/Create')
                ->where('order.id', $order->id));
    }

    public function test_guest_cannot_open_review_form(): void
    {
        [, , , $order] = $this->completedServiceOrder();

        $this->get(route('customer.orders.review.create', $order))
            ->assertRedirect('/login');
    }

    public function test_performer_cannot_open_customer_review_form(): void
    {
        [, $performer, , $order] = $this->completedServiceOrder();

        $this->actingAs($performer)
            ->get(route('customer.orders.review.create', $order))
            ->assertForbidden();
    }

    public function test_customer_cannot_review_foreign_order(): void
    {
        [, , , $order] = $this->completedServiceOrder();
        $foreignCustomer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($foreignCustomer)
            ->get(route('customer.orders.review.create', $order))
            ->assertForbidden();
    }

    public function test_customer_can_create_review_with_rating_one(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), [
                'rating' => 1,
                'comment' => 'Результат принят, но ожидал больше внимания к деталям.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'rating' => 1,
            'status' => Review::STATUS_PUBLISHED,
        ]);
    }

    public function test_customer_can_create_review_with_rating_five(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), [
                'rating' => 5,
                'comment' => 'Отличный результат внутри согласованного срока.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'rating' => 5,
        ]);
    }

    public function test_rating_is_required(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), ['comment' => 'Текст отзыва.'])
            ->assertSessionHasErrors('rating');
    }

    public function test_rating_below_one_is_rejected(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), ['rating' => 0])
            ->assertSessionHasErrors('rating');
    }

    public function test_rating_above_five_is_rejected(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), ['rating' => 6])
            ->assertSessionHasErrors('rating');
    }

    public function test_comment_may_be_empty(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), ['rating' => 5])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'comment' => null,
        ]);
    }

    public function test_comment_length_is_limited(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), [
                'rating' => 5,
                'comment' => str_repeat('а', 2001),
            ])
            ->assertSessionHasErrors('comment');
    }

    public function test_contact_in_comment_blocks_review_and_creates_moderation_flag(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), [
                'rating' => 5,
                'comment' => 'Все хорошо, пишите test@example.com для деталей.',
            ])
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseHas('moderation_flags', [
            'user_id' => $customer->id,
            'entity_type' => Review::class,
            'reason' => 'contact_detected_in_review',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    public function test_review_is_not_saved_when_contact_detected(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), [
                'rating' => 5,
                'comment' => 'Свяжемся в telegram после заказа.',
            ])
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseMissing('reviews', ['order_id' => $order->id]);
    }

    public function test_cannot_review_awaiting_payment_order(): void
    {
        $this->assertStatusCannotBeReviewed(Order::STATUS_AWAITING_PAYMENT, Order::PAYMENT_UNPAID);
    }

    public function test_cannot_review_in_progress_order(): void
    {
        $this->assertStatusCannotBeReviewed(Order::STATUS_IN_PROGRESS, Order::PAYMENT_HELD);
    }

    public function test_cannot_review_submitted_for_review_order(): void
    {
        $this->assertStatusCannotBeReviewed(Order::STATUS_SUBMITTED_FOR_REVIEW, Order::PAYMENT_HELD);
    }

    public function test_cannot_review_revision_requested_order(): void
    {
        $this->assertStatusCannotBeReviewed(Order::STATUS_REVISION_REQUESTED, Order::PAYMENT_HELD);
    }

    public function test_cannot_review_disputed_order(): void
    {
        $this->assertStatusCannotBeReviewed(Order::STATUS_DISPUTED, Order::PAYMENT_HELD);
    }

    public function test_cannot_review_canceled_order(): void
    {
        $this->assertStatusCannotBeReviewed(Order::STATUS_CANCELED, Order::PAYMENT_CANCELED);
    }

    public function test_cannot_review_completed_order_without_released_payment(): void
    {
        $this->assertStatusCannotBeReviewed(Order::STATUS_COMPLETED, Order::PAYMENT_HELD);
    }

    public function test_customer_cannot_create_duplicate_review_for_order(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();

        $this->postReview($customer, $order);

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), [
                'rating' => 4,
                'comment' => 'Повторный отзыв.',
            ])
            ->assertForbidden();

        $this->assertSame(1, Review::where('order_id', $order->id)->count());
    }

    public function test_customer_cannot_override_performer_or_service_identity(): void
    {
        [$customer, $performer, $service, $order] = $this->completedServiceOrder();
        $otherPerformer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $otherService = Service::factory()->for($otherPerformer, 'user')->create();

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), [
                'rating' => 5,
                'comment' => 'Все прошло внутри платформы.',
                'performer_id' => $otherPerformer->id,
                'service_id' => $otherService->id,
            ])
            ->assertRedirect();

        $review = Review::firstOrFail();

        $this->assertSame($performer->id, $review->performer_id);
        $this->assertSame($service->id, $review->service_id);
    }

    public function test_service_aggregates_recalculate_after_review(): void
    {
        [$customer, , $service, $order] = $this->completedServiceOrder();

        $this->postReview($customer, $order, 4);

        $service->refresh();

        $this->assertSame(1, $service->reviews_count);
        $this->assertSame(1, $service->orders_count);
        $this->assertSame('4.00', $service->rating);
    }

    public function test_performer_aggregates_recalculate_after_review(): void
    {
        [$customer, $performer, , $order] = $this->completedServiceOrder();

        $this->postReview($customer, $order, 5);

        $performer->refresh();

        $this->assertSame(1, $performer->performer_reviews_count);
        $this->assertSame(1, $performer->performer_completed_orders_count);
        $this->assertSame('5.00', $performer->performer_rating);
    }

    public function test_completed_orders_count_recalculates_when_order_is_completed(): void
    {
        [$customer, $performer, $service, $order] = $this->serviceOrder([
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_started_at' => now()->subDay(),
            'review_hold_until' => now()->addDays(9),
            'auto_release_at' => now()->addDays(9),
        ]);
        OrderSubmission::factory()->for($order)->for($performer, 'user')->create([
            'status' => OrderSubmission::STATUS_SUBMITTED,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order))
            ->assertRedirect();

        $this->assertSame(1, $service->refresh()->orders_count);
        $this->assertSame(1, $performer->refresh()->performer_completed_orders_count);
    }

    public function test_service_page_contains_public_reviews(): void
    {
        [$customer, , $service, $order] = $this->completedServiceOrder();
        $this->postReview($customer, $order, 5, 'Публичный отзыв по услуге.');

        $response = $this->get($service->url)->assertOk();

        $this->assertSame('Services/Show', $response->inertiaPage()['component']);
        $this->assertSame('Публичный отзыв по услуге.', $response->inertiaProps('service.reviews.0.comment'));
    }

    public function test_catalog_service_without_reviews_has_no_fake_rating(): void
    {
        Service::factory()->create([
            'status' => Service::STATUS_PUBLISHED,
            'rating' => null,
            'reviews_count' => 0,
        ]);

        $service = collect($this->get('/catalog')->assertOk()->inertiaProps('services'))->first();

        $this->assertNull($service['rating']);
        $this->assertSame(0, $service['reviews_count']);
    }

    public function test_performers_page_uses_real_trust_signals(): void
    {
        [$customer, $performer, , $order] = $this->completedServiceOrder();
        $this->postReview($customer, $order, 5);

        $performerPayload = collect($this->get('/performers')->assertOk()->inertiaProps('performers'))
            ->firstWhere('id', $performer->id);

        $this->assertEquals(5.0, $performerPayload['rating']);
        $this->assertSame(1, $performerPayload['reviews_count']);
        $this->assertSame(1, $performerPayload['completed_orders_count']);
    }

    public function test_customer_reviews_page_lists_given_reviews(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();
        $this->postReview($customer, $order, 5, 'Отзыв в личном кабинете.');

        $response = $this->actingAs($customer)
            ->get(route('customer.reviews.index'))
            ->assertOk();

        $this->assertSame('Отзыв в личном кабинете.', $response->inertiaProps('reviews.0.comment'));
    }

    public function test_completion_notifies_customer_to_leave_review(): void
    {
        [$customer, $performer, , $order] = $this->serviceOrder([
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_until' => now()->addDay(),
            'auto_release_at' => now()->addDay(),
        ]);
        OrderSubmission::factory()->for($order)->for($performer, 'user')->create([
            'status' => OrderSubmission::STATUS_SUBMITTED,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order))
            ->assertRedirect();

        $notification = $this->assertNotification($customer, 'order.review_requested');

        $this->assertStringContainsString("/customer/orders/{$order->id}/review/create", $notification->data['url']);
    }

    public function test_review_publication_notifies_performer(): void
    {
        [$customer, $performer, , $order] = $this->completedServiceOrder();

        $this->postReview($customer, $order, 5);

        $notification = $this->assertNotification($performer, 'review.published');

        $this->assertStringContainsString('Вам оставили новый отзыв', $notification->data['title']);
    }

    public function test_demo_seeder_creates_reviews_only_for_completed_released_orders(): void
    {
        $this->seed();

        $reviews = Review::with('order')->get();

        $this->assertGreaterThanOrEqual(3, $reviews->count());
        $reviews->each(function (Review $review): void {
            $this->assertSame(Order::STATUS_COMPLETED, $review->order->status);
            $this->assertSame(Order::PAYMENT_RELEASED, $review->order->payment_status);
        });
    }

    public function test_demo_seeder_recalculates_performer_trust_fields(): void
    {
        $this->seed();

        $performer = User::where('email', 'performer@taskora.local')->firstOrFail();

        $this->assertGreaterThan(0, $performer->performer_reviews_count);
        $this->assertGreaterThan(0, $performer->performer_completed_orders_count);
        $this->assertNotNull($performer->performer_rating);
    }

    public function test_review_create_redirects_to_existing_review(): void
    {
        [$customer, , , $order] = $this->completedServiceOrder();
        $this->postReview($customer, $order);

        $this->actingAs($customer)
            ->get(route('customer.orders.review.create', $order))
            ->assertRedirect(route('customer.reviews.show', Review::firstOrFail()));
    }

    private function assertStatusCannotBeReviewed(string $status, string $paymentStatus): void
    {
        [$customer, , , $order] = $this->serviceOrder([
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), [
                'rating' => 5,
                'comment' => 'Попытка оставить отзыв.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('reviews', ['order_id' => $order->id]);
    }

    private function postReview(User $customer, Order $order, int $rating = 5, string $comment = 'Качественный результат по заказу.'): void
    {
        $this->actingAs($customer)
            ->post(route('customer.orders.review.store', $order), [
                'rating' => $rating,
                'comment' => $comment,
            ])
            ->assertRedirect();
    }

    /**
     * @return array{0: User, 1: User, 2: Service, 3: Order}
     */
    private function completedServiceOrder(array $state = []): array
    {
        return $this->serviceOrder(array_merge([
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_RELEASED,
            'completed_at' => now()->subHour(),
            'released_at' => now()->subHour(),
            'release_reason' => Order::RELEASE_CUSTOMER_EARLY_ACCEPT,
        ], $state));
    }

    /**
     * @return array{0: User, 1: User, 2: Service, 3: Order}
     */
    private function serviceOrder(array $state = []): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();
        $service = Service::factory()
            ->for($performer, 'user')
            ->for($category)
            ->create(['status' => Service::STATUS_PUBLISHED]);

        $order = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->for($category)
            ->for($service)
            ->create(array_merge([
                'source_type' => Order::SOURCE_SERVICE,
                'title' => 'Тестовый заказ для отзывов',
                'price' => 5000,
                'platform_fee_amount' => 750,
                'performer_amount' => 4250,
            ], $state));

        return [$customer, $performer, $service, $order];
    }

    private function assertNotification(User $user, string $type): DatabaseNotification
    {
        $notification = $user->notifications()
            ->get()
            ->first(fn (DatabaseNotification $notification): bool => ($notification->data['type'] ?? null) === $type);

        $this->assertInstanceOf(DatabaseNotification::class, $notification, "Notification {$type} was not created.");

        return $notification;
    }
}
