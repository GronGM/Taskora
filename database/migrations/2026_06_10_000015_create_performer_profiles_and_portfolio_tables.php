<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('response_time_label')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->text('portfolio_summary')->nullable();
            $table->string('verification_status')->default('not_submitted')->index();
            $table->text('verification_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_for_verification_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_public')->default(true)->index();
            $table->timestamps();

            $table->index(['verification_status', 'submitted_for_verification_at']);
            $table->index(['is_public', 'published_at']);
        });

        Schema::create('category_performer_profile', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('performer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['performer_profile_id', 'category_id'], 'profile_category_unique');
            $table->index(['category_id', 'performer_profile_id'], 'category_profile_index');
        });

        Schema::create('performer_portfolio_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('performer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_public')->default(true)->index();
            $table->string('status')->default('published')->index();
            $table->timestamps();

            $table->index(['performer_profile_id', 'status', 'is_public'], 'portfolio_profile_public_index');
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performer_portfolio_items');
        Schema::dropIfExists('category_performer_profile');
        Schema::dropIfExists('performer_profiles');
    }
};
