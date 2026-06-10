<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedInteger('review_hold_days')
                ->default(Order::REVIEW_HOLD_DEFAULT_DAYS)
                ->after('payment_status');
            $table->timestamp('review_hold_started_at')->nullable()->after('review_hold_days');
            $table->timestamp('review_hold_until')->nullable()->after('review_hold_started_at');
            $table->timestamp('auto_release_at')->nullable()->after('review_hold_until');
            $table->timestamp('released_at')->nullable()->after('auto_release_at');
            $table->string('release_reason')->nullable()->after('released_at');

            $table->index(['status', 'payment_status', 'review_hold_until'], 'orders_release_due_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_release_due_index');
            $table->dropColumn([
                'review_hold_days',
                'review_hold_started_at',
                'review_hold_until',
                'auto_release_at',
                'released_at',
                'release_reason',
            ]);
        });
    }
};
