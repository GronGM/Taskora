<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('performer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('delivery_days');
            $table->decimal('platform_fee_percent', 5, 2)->default(15.00);
            $table->unsignedInteger('platform_fee_amount')->default(0);
            $table->unsignedInteger('performer_amount')->default(0);
            $table->string('status')->default('awaiting_payment');
            $table->string('payment_status')->default('unpaid');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['performer_id', 'status']);
            $table->index(['source_type', 'status']);
            $table->index(['payment_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
