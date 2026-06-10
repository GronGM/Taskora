<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('event_id')->nullable();
            $table->string('event_type');
            $table->string('status')->default('received');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['provider', 'event_id']);
            $table->index(['event_type', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_webhook_events');
    }
};
