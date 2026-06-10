<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->unsignedInteger('price');
            $table->unsignedInteger('delivery_days');
            $table->string('status')->default('submitted');
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_offers');
    }
};
