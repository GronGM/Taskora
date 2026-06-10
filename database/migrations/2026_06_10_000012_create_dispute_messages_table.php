<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispute_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['dispute_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_messages');
    }
};
