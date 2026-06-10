<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name')->nullable();
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('status')->default('available');
            $table->string('moderation_status')->default('clean');
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['status', 'moderation_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_files');
    }
};
