<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->unsignedInteger('budget_min')->nullable();
            $table->unsignedInteger('budget_max')->nullable();
            $table->date('deadline_at')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('offers_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['category_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
