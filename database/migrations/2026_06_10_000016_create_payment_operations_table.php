<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('stub');
            $table->string('provider_operation_id')->nullable();
            $table->string('type');
            $table->string('status');
            $table->unsignedInteger('amount');
            $table->string('currency')->default('RUB');
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'type']);
            $table->index(['user_id', 'type']);
            $table->index(['provider', 'provider_operation_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_operations');
    }
};
