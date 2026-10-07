<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_reversal_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_reversal_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_allocation_id')->constrained()->restrictOnDelete();
            $table->decimal('selected_amount', 12, 2);
            $table->timestamps();

            $table->unique(['payment_reversal_id', 'payment_allocation_id'], 'pra_reversal_allocation_unique');
        });

        DB::table('payment_reversal_allocations')->insertUsing(
            ['payment_reversal_id', 'payment_allocation_id', 'selected_amount', 'created_at', 'updated_at'],
            DB::table('payment_reversals')
                ->join('payment_allocations', 'payment_allocations.payment_id', '=', 'payment_reversals.original_payment_id')
                ->select([
                    'payment_reversals.id',
                    'payment_allocations.id',
                    'payment_allocations.amount',
                    DB::raw('CURRENT_TIMESTAMP'),
                    DB::raw('CURRENT_TIMESTAMP'),
                ]),
        );

        Schema::table('payment_reversals', function (Blueprint $table) {
            $table->index('original_payment_id');
            $table->dropUnique('payment_reversals_original_payment_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException('Partial payment reversals cannot be safely rolled back after they have been recorded.');
    }
};
