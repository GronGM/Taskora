<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_operation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('account');
            $table->string('direction');
            $table->unsignedInteger('amount');
            $table->string('currency')->default('RUB');
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'account']);
            $table->index(['user_id', 'account']);
            $table->index(['payment_operation_id', 'account'], 'ledger_operation_account_index');
            $table->index(['reference_type', 'reference_id'], 'ledger_reference_index');
            $table->index(['account', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
