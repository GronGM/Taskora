<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');
            $table->string('reason');
            $table->text('description');
            $table->string('previous_order_status')->nullable();
            $table->string('previous_payment_status')->nullable();
            $table->string('resolution')->nullable();
            $table->text('moderator_comment')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['order_id', 'status']);
            $table->index(['opened_by', 'status']);
            $table->index(['resolved_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
