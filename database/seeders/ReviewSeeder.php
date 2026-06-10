<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Services\Reviews\ReviewAggregateService;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@taskora.local')->firstOrFail();
        $performer = User::where('email', 'performer@taskora.local')->firstOrFail();
        $aggregates = app(ReviewAggregateService::class);

        $reviews = [
            [
                'service_slug' => 'oformlyu-dokument-word-po-trebovaniyam',
                'title' => 'Демо-заказ: оформление Word по требованиям',
                'rating' => 5,
                'comment' => 'Документ привели к единому виду, аккуратно оформили оглавление и таблицы. Все обсуждение осталось внутри Таскоры.',
                'completed_at' => now()->subDays(12),
            ],
            [
                'service_slug' => 'sdelayu-prezentaciyu-s-sovremennym-dizaynom',
                'title' => 'Демо-заказ: презентация для защиты',
                'rating' => 5,
                'comment' => 'Получилась чистая структура и ровный визуальный стиль. Исполнитель быстро уточнял детали в рабочей области заказа.',
                'completed_at' => now()->subDays(8),
            ],
            [
                'service_slug' => 'podgotovlyu-tablicu-excel-s-raschetami',
                'title' => 'Демо-заказ: Excel-таблица с расчетами',
                'rating' => 4,
                'comment' => 'Формулы и итоговые показатели оформлены понятно. Небольшие правки согласовали через чат заказа.',
                'completed_at' => now()->subDays(4),
            ],
        ];

        foreach ($reviews as $reviewData) {
            $service = Service::where('slug', $reviewData['service_slug'])->firstOrFail();
            $category = Category::findOrFail($service->category_id);
            $completedAt = $reviewData['completed_at'];
            $price = $service->price_from;
            $feeAmount = (int) round($price * 0.15);

            $order = Order::updateOrCreate(
                ['title' => $reviewData['title']],
                [
                    'customer_id' => $customer->id,
                    'performer_id' => $performer->id,
                    'category_id' => $category->id,
                    'service_id' => $service->id,
                    'task_id' => null,
                    'task_offer_id' => null,
                    'source_type' => Order::SOURCE_SERVICE,
                    'description' => 'Локальный демо-заказ для проверки отзывов и доверительных сигналов.',
                    'price' => $price,
                    'delivery_days' => $service->delivery_days,
                    'platform_fee_percent' => 15.00,
                    'platform_fee_amount' => $feeAmount,
                    'performer_amount' => $price - $feeAmount,
                    'status' => Order::STATUS_COMPLETED,
                    'payment_status' => Order::PAYMENT_RELEASED,
                    'review_hold_days' => Order::REVIEW_HOLD_DEFAULT_DAYS,
                    'review_hold_started_at' => $completedAt->copy()->subDays(2),
                    'review_hold_until' => $completedAt->copy()->addDays(Order::REVIEW_HOLD_DEFAULT_DAYS),
                    'auto_release_at' => $completedAt->copy()->addDays(Order::REVIEW_HOLD_DEFAULT_DAYS),
                    'started_at' => $completedAt->copy()->subDays(4),
                    'submitted_at' => $completedAt->copy()->subDay(),
                    'completed_at' => $completedAt,
                    'released_at' => $completedAt,
                    'release_reason' => Order::RELEASE_CUSTOMER_EARLY_ACCEPT,
                    'canceled_at' => null,
                ],
            );

            $review = Review::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'service_id' => $service->id,
                    'task_id' => null,
                    'customer_id' => $customer->id,
                    'performer_id' => $performer->id,
                    'rating' => $reviewData['rating'],
                    'comment' => $reviewData['comment'],
                    'status' => Review::STATUS_PUBLISHED,
                    'is_public' => true,
                    'published_at' => $completedAt->copy()->addHour(),
                    'hidden_at' => null,
                ],
            );

            $aggregates->recalculateForReview($review);
        }
    }
}
