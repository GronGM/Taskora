<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_active', 'sort_order']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('task_type_id')
                ->nullable()
                ->after('category_id')
                ->constrained('task_types')
                ->nullOnDelete();

            $table->index(['task_type_id', 'status']);
        });

        Schema::create('task_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('performer_favorite_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'category_id']);
        });

        Schema::create('performer_favorite_task_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'task_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performer_favorite_task_types');
        Schema::dropIfExists('performer_favorite_categories');
        Schema::dropIfExists('task_favorites');

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('task_type_id');
        });

        Schema::dropIfExists('task_types');
    }
};
