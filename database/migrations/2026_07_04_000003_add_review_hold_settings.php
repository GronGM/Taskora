<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->unsignedTinyInteger('review_hold_days')->nullable()->after('deadline_at');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->unsignedTinyInteger('max_review_hold_days')->nullable()->after('delivery_days');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('review_hold_days');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn('max_review_hold_days');
        });
    }
};
