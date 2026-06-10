<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('performer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status')->default('published')->index();
            $table->boolean('is_public')->default(true)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->index(['performer_id', 'status', 'is_public']);
            $table->index(['service_id', 'status', 'is_public']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('performer_rating', 3, 2)->nullable()->after('role');
            $table->unsignedInteger('performer_reviews_count')->default(0)->after('performer_rating');
            $table->unsignedInteger('performer_completed_orders_count')->default(0)->after('performer_reviews_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'performer_rating',
                'performer_reviews_count',
                'performer_completed_orders_count',
            ]);
        });

        Schema::dropIfExists('reviews');
    }
};
