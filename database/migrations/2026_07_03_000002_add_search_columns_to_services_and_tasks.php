<?php

use App\Services\Search\RelevanceSearch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->text('search_title')->nullable();
            $table->text('search_text')->nullable();
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->text('search_title')->nullable();
            $table->text('search_text')->nullable();
        });

        DB::table('services')->orderBy('id')->chunkById(200, function ($services): void {
            foreach ($services as $service) {
                DB::table('services')->where('id', $service->id)->update([
                    'search_title' => RelevanceSearch::normalize($service->title),
                    'search_text' => RelevanceSearch::normalize($service->short_description, $service->description),
                ]);
            }
        });

        DB::table('tasks')->orderBy('id')->chunkById(200, function ($tasks): void {
            foreach ($tasks as $task) {
                DB::table('tasks')->where('id', $task->id)->update([
                    'search_title' => RelevanceSearch::normalize($task->title),
                    'search_text' => RelevanceSearch::normalize($task->description),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn(['search_title', 'search_text']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn(['search_title', 'search_text']);
        });
    }
};
