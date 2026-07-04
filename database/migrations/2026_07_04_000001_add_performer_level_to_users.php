<?php

use App\Support\PerformerLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('performer_level')->default(PerformerLevel::NOVICE)->after('performer_completed_orders_count');
            $table->unsignedInteger('performer_lost_disputes_count')->default(0)->after('performer_level');
        });

        DB::table('users')
            ->where('role', 'performer')
            ->orderBy('id')
            ->chunkById(200, function ($performers): void {
                foreach ($performers as $performer) {
                    $lostDisputes = (int) DB::table('disputes')
                        ->join('orders', 'orders.id', '=', 'disputes.order_id')
                        ->where('orders.performer_id', $performer->id)
                        ->where('disputes.resolution', 'refund_to_customer')
                        ->count();

                    DB::table('users')->where('id', $performer->id)->update([
                        'performer_lost_disputes_count' => $lostDisputes,
                        'performer_level' => PerformerLevel::determine(
                            (int) $performer->performer_completed_orders_count,
                            $performer->performer_rating !== null ? (float) $performer->performer_rating : null,
                            $lostDisputes,
                        ),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['performer_level', 'performer_lost_disputes_count']);
        });
    }
};
