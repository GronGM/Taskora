<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beta_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->nullable();
            $table->string('page_url')->nullable();
            $table->string('scenario')->nullable();
            $table->string('type');
            $table->string('severity');
            $table->string('title');
            $table->text('description');
            $table->string('browser')->nullable();
            $table->string('screen_size')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beta_feedback');
    }
};
