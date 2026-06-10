<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('performer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('currency')->default('RUB');
            $table->string('status')->default('draft');
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['performer_id', 'status']);
            $table->index(['status', 'requested_at']);
            $table->index(['reviewed_by', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
};
