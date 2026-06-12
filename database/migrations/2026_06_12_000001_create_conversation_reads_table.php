<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('conversation_type', 16);
            $table->unsignedBigInteger('conversation_id');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'conversation_type', 'conversation_id'],
                'conversation_reads_user_conversation_unique',
            );
            $table->index(['conversation_type', 'conversation_id']);
            $table->index(['user_id', 'last_read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_reads');
    }
};
