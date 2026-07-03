<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(['status', 'deadline_at']);
            $table->index(['status', 'offers_count']);
            $table->index(['status', 'budget_min']);
            $table->index(['status', 'budget_max']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['status', 'deadline_at']);
            $table->dropIndex(['status', 'offers_count']);
            $table->dropIndex(['status', 'budget_min']);
            $table->dropIndex(['status', 'budget_max']);
        });
    }
};
